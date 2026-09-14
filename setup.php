<?php
use Gt\Config\ConfigFactory;
use function Sentry\init as SentryInit;

require "vendor/autoload.php";

$config = ConfigFactory::createForProject(__DIR__, "vendor/phpgt/webengine/config.default.ini");

if($glitchTipDsn = $config->getString("glitchtip.dsn")) {
	SentryInit([
		"dsn" => $glitchTipDsn,
	]);
}
