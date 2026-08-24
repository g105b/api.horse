<?php
namespace App\Test\Http;

use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase {
	private string $tempDir;

	protected function setUp():void {
		$this->tempDir = sys_get_temp_dir() . "/api-horse-test-" . uniqid();
		mkdir($this->tempDir, recursive: true);
	}

	protected function tearDown():void {
		$this->deleteDir($this->tempDir);
	}

	public function testLimit_enforcesHostLimitAcrossIpAddresses():void {
		$sut = new TestRateLimiter("$this->tempDir/rate-limit", 100);

		$sut->limit("example.com", "192.0.2.1");
		$sut->limit("example.com", "192.0.2.2");

		self::assertSame([2.0], $sut->sleepList);
	}

	public function testLimit_enforcesIpLimitAcrossHosts():void {
		$sut = new TestRateLimiter("$this->tempDir/rate-limit", 100);

		$sut->limit("example.com", "192.0.2.1");
		$sut->limit("example.org", "192.0.2.1");

		self::assertSame([2.0], $sut->sleepList);
	}

	public function testLimit_increasesCooldownUntilReset():void {
		$sut = new TestRateLimiter("$this->tempDir/rate-limit", 100);

		$sut->limit("example.com", "192.0.2.1");
		$sut->limit("example.com", "192.0.2.2");
		$sut->limit("example.com", "192.0.2.3");

		$sut->time = 701;
		$sut->limit("example.com", "192.0.2.4");

		self::assertSame([2.0, 5.0], $sut->sleepList);
	}

	public function testLimit_storesReadableIpAndHostFiles():void {
		$sut = new TestRateLimiter("$this->tempDir/rate-limit", 100);

		$sut->limit("example.com", "2001:db8::1");

		self::assertFileExists("$this->tempDir/rate-limit/ip/2001:db8::1.dat");
		self::assertFileExists("$this->tempDir/rate-limit/host/example.com.dat");
	}

	private function deleteDir(string $dir):void {
		if(!is_dir($dir)) {
			return;
		}

		$fileList = array_diff(scandir($dir), [".", ".."]);
		foreach($fileList as $file) {
			$path = "$dir/$file";
			if(is_dir($path)) {
				$this->deleteDir($path);
			}
			else {
				unlink($path);
			}
		}

		rmdir($dir);
	}
}
