This is my how-to guide for running a small PHP project with nginx and PHP-FPM. It documents the setup used by API.horse, including the extra protection added before opening the stable door to the public.

The examples use these placeholders:

- `<domain>` - the public hostname, such as `api.horse`
- `<project>` - a short, filesystem-safe name, such as `api-horse`
- `<php-version>` - the installed PHP version, such as `8.5`
- `<project-root>` - the deployment directory, such as `/var/www/api.horse`

Do not copy the capacity numbers blindly. API.horse currently runs on a small server with one CPU and about 1 GiB of memory, alongside other sites. A larger machine can safely run more PHP workers, while a busier shared machine may need fewer.

# What runs where

The request path is:

Internet -> nginx :80/:443

Then this is handled by either:

- static file found -> served directly by nginx
- dynamic request -> dedicated PHP-FPM socket -> PHP project

nginx terminates TLS, serves static assets, applies request limits and passes dynamic requests to PHP-FPM. Each important project gets its own FPM pool so one site cannot occupy every PHP worker on the server.

Fail2ban watches a dedicated log containing only requests that actually reached PHP. An abusive address is blocked at the firewall, before it can keep making nginx perform TLS, HTTP and logging work.

# Give the project its own PHP-FPM pool

Create `/etc/php/<php-version>/fpm/pool.d/<project>.conf`:

```ini
[<project>]
user = www-data
group = www-data

listen = /run/php/php<php-version>-fpm-<project>.sock
listen.owner = www-data
listen.group = www-data

pm = ondemand
pm.max_children = 2
pm.process_idle_timeout = 10s
pm.max_requests = 250

request_terminate_timeout = 15s
request_slowlog_timeout = 5s
slowlog = /var/log/php<php-version>-fpm-<project>-slow.log
catch_workers_output = yes
```

The two-worker limit is intentional for API.horse's small server. It contains the damage if outbound requests are slow and leaves resources for the other hosted sites. Estimate a pool's worst-case memory before increasing it:

```text
maximum children x PHP memory_limit
```

That is deliberately pessimistic, but it prevents configuring more theoretical PHP memory than the machine can provide.

Validate the complete FPM configuration:

```bash
php-fpm<php-version> -t
```

Reload FPM and check that the new socket exists:

```bash
systemctl reload php<php-version>-fpm
systemctl is-active php<php-version>-fpm
ls -l /run/php/php<php-version>-fpm-<project>.sock
```

# Define nginx limit zones

Shared-memory limit zones must be declared in nginx's `http` context, not inside a virtual host. Put them in a file such as `/etc/nginx/conf.d/security-limits.conf`:

```nginx
# Give every project unique zone names. Traffic on one site must not consume
# another site's counters.
limit_req_zone  $binary_remote_addr zone=<project>_dynamic:10m rate=5r/s;
limit_conn_zone $binary_remote_addr zone=<project>_per_ip:10m;

# Only the PHP location writes entries using this format. Fail2ban consumes it.
log_format <project>_php '$remote_addr [$time_local] "$request" $status';

limit_req_log_level warn;
limit_req_status 429;
```

`$binary_remote_addr` stores IPv4 and IPv6 addresses efficiently. A 10 MiB zone is ample for a small site and retains counters for many distinct visitors.

The API.horse policy allows five dynamic requests per second from one address, with a short burst handled by the virtual host. This is an application policy, not a magic universal number. Browser autosave, polling and API endpoints may need different limits.

# Configure the nginx site

Create `/etc/nginx/sites-available/<domain>`. This is the reusable part of the API.horse virtual host before Certbot adds its managed TLS lines:

```nginx
server {
	server_name <domain>;
	listen 80;

	root <project-root>/www;
	index index.html;
	client_max_body_size 10m;
	client_header_timeout 10s;
	client_body_timeout 10s;
	send_timeout 15s;

	add_header X-Content-Type-Options nosniff always;
	add_header Referrer-Policy strict-origin-when-cross-origin always;
	add_header X-Frame-Options SAMEORIGIN always;

	gzip on;
	gzip_vary on;
	gzip_min_length 1024;
	gzip_types
		application/javascript
		application/json
		application/manifest+json
		application/xml
		image/svg+xml
		text/css
		text/plain
		text/xml;

	location / {
		try_files $uri @gt;
	}

	location ~* \.(?:css|js|mjs|map|json|webmanifest|xml|txt|avif|gif|ico|jpe?g|png|svg|webp|woff2?|ttf|otf)$ {
		expires 1h;
	}

	location ~ /\. {
		deny all;
	}

	location @gt {
		include fastcgi_params;

		# Static files never enter this location, so they do not consume the
		# PHP rate or connection allowances, or appear in the Fail2ban source log.
		limit_req zone=<project>_dynamic burst=30 nodelay;
		limit_conn <project>_per_ip 10;
		access_log /var/log/nginx/<domain>-php.log <project>_php;

		fastcgi_pass unix:/run/php/php<php-version>-fpm-<project>.sock;
		fastcgi_connect_timeout 2s;
		fastcgi_send_timeout 15s;
		fastcgi_read_timeout 15s;

		fastcgi_param DOCUMENT_ROOT $realpath_root;
		fastcgi_param SERVER_NAME $host;
		fastcgi_param REQUEST_URI $request_uri;
		fastcgi_param SCRIPT_FILENAME <project-root>/vendor/phpgt/webengine/go.php;
	}
}

server {
	server_name www.<domain>;
	listen 80;
	return 301 $scheme://<domain>$request_uri;
}
```

The `SCRIPT_FILENAME` shown above is specific to PHP.GT/WebEngine. A conventional PHP application would normally use something like `$document_root/index.php` or `$document_root$fastcgi_script_name` instead.

The controls do different jobs:

- `limit_req` limits the rate of dynamic requests and returns `429` beyond the allowance.
- `limit_conn` permits no more than ten simultaneous requests that have reached PHP from one address.
- `pm.max_children` places a hard ceiling on PHP concurrency for the whole project.
- FastCGI and client timeouts release stalled resources rather than leaving them occupied indefinitely.

The static-file location wins for matching assets, so CSS, JavaScript, images and fonts do not use the PHP connection limit. Static files are cheap, and punishing them would make a browser with several tabs look more aggressive than it really is.

# Ban clients that keep invoking PHP

The nginx rate limit sheds traffic cheaply, but a large rejected flood can still consume CPU through TLS, connection handling and logs. During API.horse testing, four generators held about 800 connections and produced roughly 2.7 MiB of logs per second. The single CPU was completely occupied even though almost every response was `429`.

Fail2ban closes that gap by banning clients after too many requests have genuinely reached PHP.

Create `/etc/fail2ban/filter.d/<project>-php-abuse.conf`:

```ini
[Definition]
failregex = ^<HOST>.*"(?:GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS) .*" [0-9]{3}$
ignoreregex =
```

Create `/etc/fail2ban/jail.d/<project>-php-abuse.local`:

```ini
[<project>-php-abuse]
enabled = true
filter = <project>-php-abuse
port = http,https
protocol = tcp
logpath = /var/log/nginx/<domain>-php.log
backend = auto

# The 26th PHP request within ten seconds earns a five-minute ban.
findtime = 10
maxretry = 26
bantime = 300

# API.horse has iptables installed. Use nftables only when `nft` genuinely
# exists and has been tested on the server.
banaction = iptables-multiport
```

This log is written in the named PHP location, so it does not count static assets or requests already rejected by nginx. `maxretry = 26` implements the policy of banning after more than 25 PHP invocations in ten seconds.

The rule applies to an IP address, not a person. Offices, universities, VPNs and carrier networks may place many legitimate people behind one public address. Start with a short ban, monitor what happens and raise the threshold if genuine visitors are caught.

The nginx package's default logrotate rule covers `/var/log/nginx/*.log`, so the dedicated PHP log is rotated with the other nginx logs on this server. Confirm that the installed distribution uses a wildcard before relying on this:

```bash
cat /etc/logrotate.d/nginx
```

# Validate Fail2ban properly

First, create one real PHP log entry:

```bash
curl -sSI https://<domain>/ >/dev/null
```

Check the filter against the actual log:

```bash
fail2ban-regex /var/log/nginx/<domain>-php.log /etc/fail2ban/filter.d/<project>-php-abuse.conf
```

Then validate and load the configuration:

```bash
fail2ban-client -t
fail2ban-client reload
fail2ban-client status <project>-php-abuse
```

Do not stop at a Fail2ban status that says an address is banned. Prove that the firewall action exists. For the API.horse iptables setup:

```bash
iptables -S f2b-<project>-php-abuse
iptables -L f2b-<project>-php-abuse -n -v --line-numbers
```

The first command must show a chain reached from `INPUT`, and banned addresses must have `REJECT` rules. The packet counters in the second command should increase while blocked traffic continues.

It is safe to test the mechanism with an address reserved for documentation, provided it is immediately removed afterwards:

```bash
fail2ban-client set <project>-php-abuse banip 192.0.2.1
iptables -S f2b-<project>-php-abuse
fail2ban-client set <project>-php-abuse unbanip 192.0.2.1
```

To release a real address manually:

```bash
fail2ban-client set <project>-php-abuse unbanip <ip-address>
```

Always inspect `/var/log/fail2ban.log` after the first real ban. A failed action can otherwise look successful in `fail2ban-client status`.

# Load test in stages

Apache Bench is deliberately blunt, which makes it useful for proving the protective layers. Start below the limits before testing rejection and bans:

```bash
ab -k -r -s 20 -t 300 -n 100000000 -c 5 https://<domain>/
ab -k -r -s 20 -t 300 -n 100000000 -c 10 https://<domain>/
```

An aggressive protection test might use:

```bash
ab -k -r -s 20 -t 900 -n 100000000 -c 250 https://<domain>/
```

Only run this against a server you own and intend to test. A single load-generator IP will mostly test nginx and Fail2ban rather than PHP capacity. To measure application capacity, use controlled generators and a policy that does not immediately ban them.

While testing, watch:

```bash
uptime
free -h
vmstat 1
ss -s
ps -eo pid,ppid,user,stat,rss,%cpu,cmd --sort=-%cpu | head
fail2ban-client status <project>-php-abuse
iptables -L f2b-<project>-php-abuse -n -v --line-numbers
```

Healthy API.horse behaviour after a ban looked like this:

- banned addresses had real iptables `REJECT` entries
- firewall packet counters continued increasing
- nginx and PHP received no further requests from the generators
- the CPU returned to roughly 95-100% idle
- the live site remained responsive

Connections in `FIN-WAIT-1` can remain for a while after a ban as old TCP connections drain. That is expected; the important measurements are new PHP log entries, firewall counters and server load.

# Things I would change for a larger launch

This setup makes a small origin fail much more gracefully, but it does not turn one CPU into a fleet of servers. For a project likely to attract a large audience, the next improvements are:

- allow direct web traffic to the origin only from the proxy network
- enable HTTP/2 or HTTP/3 at the edge
- move state out of local files before running multiple application servers
- add per-site nginx and FPM metrics
- alert on CPU, memory, swap, disk space, FPM queues, 429s and 5xx responses
- test recovery after the traffic stops, not only peak throughput
