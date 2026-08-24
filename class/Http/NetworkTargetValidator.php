<?php
namespace App\Http;

use Psr\Http\Message\UriInterface;
class NetworkTargetValidator {
	private const array NON_PUBLIC_NETWORK_LIST = [
		"0.0.0.0/8",
		"10.0.0.0/8",
		"100.64.0.0/10",
		"127.0.0.0/8",
		"169.254.0.0/16",
		"172.16.0.0/12",
		"192.0.0.0/24",
		"192.0.2.0/24",
		"192.88.99.0/24",
		"192.168.0.0/16",
		"198.18.0.0/15",
		"198.51.100.0/24",
		"203.0.113.0/24",
		"224.0.0.0/4",
		"240.0.0.0/4",
		"::/96",
		"::ffff:0:0/96",
		"64:ff9b::/96",
		"64:ff9b:1::/48",
		"100::/64",
		"2001::/23",
		"2001:db8::/32",
		"2002::/16",
		"fc00::/7",
		"fe80::/10",
		"ff00::/8",
	];

	/** @var array<string, true> */
	private array $blockedIpAddressMap = [];

	/** @param array<string> $blockedIpAddressList */
	public function __construct(array $blockedIpAddressList = []) {
		$configuredAddressList = array_filter(array_map(
			trim(...),
			explode(",", getenv("SSRF_BLOCKED_IPS") ?: ""),
		));
		array_push($blockedIpAddressList, ...$configuredAddressList);

		foreach($blockedIpAddressList as $ipAddress) {
			$this->blockedIpAddressMap[$this->normaliseIpAddress($ipAddress)] = true;
		}
	}

	public function assertUriAllowed(UriInterface $uri):void {
		$scheme = strtolower($uri->getScheme());
		if(!in_array($scheme, ["http", "https"], true)) {
			throw new NetworkTargetException("Only HTTP and HTTPS destinations are allowed.");
		}

		$host = trim($uri->getHost(), "[]");
		if($host === "") {
			throw new NetworkTargetException("The destination must have a host.");
		}

		if(filter_var($host, FILTER_VALIDATE_IP)) {
			$this->assertIpAddressAllowed($host);
		}
	}

	public function assertIpAddressAllowed(string $ipAddress):void {
		$ipAddress = $this->normaliseIpAddress($ipAddress);
		if(isset($this->blockedIpAddressMap[$ipAddress])) {
			throw new NetworkTargetException("Requests to this server are not allowed.");
		}

		foreach(self::NON_PUBLIC_NETWORK_LIST as $network) {
			if($this->isInNetwork($ipAddress, $network)) {
				throw new NetworkTargetException(
					"Requests to private or reserved network addresses are not allowed.",
				);
			}
		}
	}

	private function isInNetwork(string $ipAddress, string $network):bool {
		[$networkAddress, $prefixLength] = explode("/", $network);
		$ip = inet_pton($ipAddress);
		$networkIp = inet_pton($networkAddress);
		if($ip === false || $networkIp === false || strlen($ip) !== strlen($networkIp)) {
			return false;
		}

		$prefixLength = (int)$prefixLength;
		$wholeBytes = intdiv($prefixLength, 8);
		$remainingBits = $prefixLength % 8;
		if(substr($ip, 0, $wholeBytes) !== substr($networkIp, 0, $wholeBytes)) {
			return false;
		}
		if($remainingBits === 0) {
			return true;
		}

		$mask = (0xff << (8 - $remainingBits)) & 0xff;
		return (ord($ip[$wholeBytes]) & $mask) === (ord($networkIp[$wholeBytes]) & $mask);
	}

	private function normaliseIpAddress(string $ipAddress):string {
		$packedAddress = inet_pton(trim($ipAddress, "[]"));
		if($packedAddress === false) {
			throw new NetworkTargetException("Invalid destination IP address.");
		}

		return inet_ntop($packedAddress);
	}
}
