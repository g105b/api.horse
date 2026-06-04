<?php
namespace App\Test\Request;

use App\Request\Collection\CollectionMode;
use App\Request\Collection\PrivateCollectionRepository;
use PHPUnit\Framework\TestCase;

class CollectionRepositoryTest extends TestCase {
	private string $tmpDir;

	protected function setUp():void {
		$this->tmpDir = sys_get_temp_dir() . "/api-horse-test-" . uniqid();
		mkdir("$this->tmpDir/request", recursive: true);
	}

	protected function tearDown():void {
		$this->deleteDir($this->tmpDir);
	}

	public function testRetrieveAll_returnsOnlyPersistedCollections():void {
		$sut = new PrivateCollectionRepository($this->tmpDir, CollectionMode::request);

		self::assertSame([], $sut->retrieveAll());

		$collection = $sut->create("Numbers");
		$sut->save($collection);

		$all = $sut->retrieveAll();
		self::assertCount(1, $all);
		self::assertSame("numbers", (string)$all[0]->id);
	}

	public function testGetCurrent_returnsNullWhenStoredCollectionDoesNotExist():void {
		$sut = new PrivateCollectionRepository($this->tmpDir, CollectionMode::request);
		file_put_contents(
			"$this->tmpDir/request/current-collection.txt",
			"collection-1",
		);

		self::assertNull($sut->getCurrent());
	}

	private function deleteDir(string $dir):void {
		if(!is_dir($dir)) {
			return;
		}

		foreach(scandir($dir) as $item) {
			if($item === "." || $item === "..") {
				continue;
			}

			$path = "$dir/$item";
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
