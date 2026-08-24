<?php
namespace App\Test\Http;

use App\Http\NetworkTargetException;
use App\Http\NetworkTargetValidator;
use Gt\Http\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NetworkTargetValidatorTest extends TestCase {
	#[DataProvider("blockedIpAddressProvider")]
	public function testAssertIpAddressAllowedRejectsPrivateAndReservedAddresses(string $ipAddress):void {
		$this->expectException(NetworkTargetException::class);

		(new NetworkTargetValidator())->assertIpAddressAllowed($ipAddress);
	}

	/** @return array<string, array<string>> */
	public static function blockedIpAddressProvider():array {
		return [
			"IPv4 loopback" => ["127.0.0.1"],
			"IPv4 private" => ["10.0.0.1"],
			"IPv4 link-local" => ["169.254.169.254"],
			"IPv4 carrier-grade NAT" => ["100.64.0.1"],
			"IPv4 multicast" => ["224.0.0.1"],
			"IPv6 loopback" => ["::1"],
			"IPv6 private" => ["fd00::1"],
			"IPv6 link-local" => ["fe80::1"],
			"IPv6 documentation" => ["2001:db8::1"],
			"IPv6 multicast" => ["ff02::1"],
		];
	}

	public function testAssertIpAddressAllowedAcceptsPublicAddress():void {
		(new NetworkTargetValidator())->assertIpAddressAllowed("8.8.8.8");
		self::addToAssertionCount(1);
	}

	public function testAssertIpAddressAllowedRejectsConfiguredServerAddress():void {
		$this->expectException(NetworkTargetException::class);
		$this->expectExceptionMessage("this server");

		(new NetworkTargetValidator(["8.8.8.8"]))
			->assertIpAddressAllowed("8.8.8.8");
	}

	public function testAssertUriAllowedRejectsNonHttpProtocol():void {
		$this->expectException(NetworkTargetException::class);
		$this->expectExceptionMessage("HTTP and HTTPS");

		(new NetworkTargetValidator())->assertUriAllowed(new Uri("file:///etc/passwd"));
	}

	public function testAssertUriAllowedRejectsPrivateLiteralAddress():void {
		$this->expectException(NetworkTargetException::class);

		(new NetworkTargetValidator())->assertUriAllowed(new Uri("http://127.0.0.1/private"));
	}
}
