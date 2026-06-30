<?php
namespace App\Test\Request;

use App\Request\SecretRepository;
use PHPUnit\Framework\TestCase;

class SecretRepositoryTest extends TestCase {
	private string $tempDir;

	protected function setUp():void {
		$this->tempDir = sys_get_temp_dir() . "/api-horse-test-" . uniqid();
		mkdir($this->tempDir, recursive: true);
	}

	protected function tearDown():void {
		$this->deleteDir($this->tempDir);
	}

	public function testRemoveDeletesOnlyMatchingSecret():void {
		$sut = new SecretRepository($this->tempDir);
		$sut->create("FIRST_SECRET", "first-value");

		$sut = new SecretRepository($this->tempDir);
		$sut->create("SECOND_SECRET", "second-value");

		$sut = new SecretRepository($this->tempDir);
		$sut->remove("FIRST_SECRET");

		$sut = new SecretRepository($this->tempDir);
		$secretList = $sut->getAll();

		self::assertCount(1, $secretList);
		self::assertSame("SECOND_SECRET", $secretList[0]->key);
		self::assertSame("second-value", $secretList[0]->getSecretValue());
	}

	private function deleteDir(string $dir):void {
		if(!is_dir($dir)) {
			return;
		}

		foreach(scandir($dir) as $entry) {
			if($entry === "." || $entry === "..") {
				continue;
			}

			$path = "$dir/$entry";
			if(is_dir($path)) {
				$this->deleteDir($path);
				continue;
			}

			unlink($path);
		}

		rmdir($dir);
	}
}
