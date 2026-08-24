<?php
namespace App\Test\Maintenance;

use App\Maintenance\DataGarbageCollector;
use PHPUnit\Framework\TestCase;

class DataGarbageCollectorTest extends TestCase {
	private string $tempDir;

	protected function setUp():void {
		$this->tempDir = sys_get_temp_dir() . "/api-horse-test-" . uniqid();
		mkdir($this->tempDir, recursive: true);
	}

	protected function tearDown():void {
		$this->deleteDir($this->tempDir);
	}

	public function testCollectRemovesEmptyShareDirectory():void {
		mkdir("$this->tempDir/share-1/request/collection-1", recursive: true);

		$removed = (new DataGarbageCollector($this->tempDir))->collect();

		self::assertSame(1, $removed);
		self::assertDirectoryDoesNotExist("$this->tempDir/share-1");
	}

	public function testCollectRemovesDefaultCollectionMetadataOnly():void {
		mkdir("$this->tempDir/share-1/request/collection-1", recursive: true);
		file_put_contents("$this->tempDir/share-1/request/current-collection.txt", "collection-1");
		file_put_contents("$this->tempDir/share-1/request/collection-1/collection.txt", "Collection 1");

		(new DataGarbageCollector($this->tempDir))->collect();

		self::assertDirectoryDoesNotExist("$this->tempDir/share-1");
	}

	public function testCollectPreservesShareContainingRequestData():void {
		mkdir("$this->tempDir/share-1/request/collection-1/request-1", recursive: true);
		file_put_contents("$this->tempDir/share-1/request/collection-1/request-1/request.dat", "request");

		$removed = (new DataGarbageCollector($this->tempDir))->collect();

		self::assertSame(0, $removed);
		self::assertFileExists("$this->tempDir/share-1/request/collection-1/request-1/request.dat");
	}

	public function testCollectPreservesNamedEmptyCollection():void {
		mkdir("$this->tempDir/share-1/request/named", recursive: true);
		file_put_contents("$this->tempDir/share-1/request/named/collection.txt", "Named");

		(new DataGarbageCollector($this->tempDir))->collect();

		self::assertDirectoryExists("$this->tempDir/share-1");
	}

	public function testCollectIgnoresInfrastructureDirectories():void {
		foreach(["html-cache", "rate-limit", "uploads"] as $name) {
			mkdir("$this->tempDir/$name", recursive: true);
		}

		$removed = (new DataGarbageCollector($this->tempDir))->collect();

		self::assertSame(0, $removed);
		self::assertDirectoryExists("$this->tempDir/html-cache");
		self::assertDirectoryExists("$this->tempDir/rate-limit");
		self::assertDirectoryExists("$this->tempDir/uploads");
	}

	private function deleteDir(string $dir):void {
		if(!is_dir($dir)) {
			return;
		}

		foreach(scandir($dir) ?: [] as $name) {
			if($name === "." || $name === "..") {
				continue;
			}
			$path = "$dir/$name";
			if(is_dir($path) && !is_link($path)) {
				$this->deleteDir($path);
			}
			else {
				unlink($path);
			}
		}
		rmdir($dir);
	}
}
