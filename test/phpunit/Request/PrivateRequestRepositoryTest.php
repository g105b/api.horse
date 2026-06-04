<?php
namespace App\Test\Request;

use App\Request\PrivateRequestRepository;
use App\Request\RequestEntity;
use OverflowException;
use PHPUnit\Framework\TestCase;

class PrivateRequestRepositoryTest extends TestCase {
	private string $tempDir;

	protected function setUp():void {
		$this->tempDir = sys_get_temp_dir() . "/api-horse-test-" . uniqid();
		mkdir($this->tempDir, recursive: true);
	}

	protected function tearDown():void {
		$this->deleteDir($this->tempDir);
	}

	public function testCreate_prefixesRequestIdWithCreationOrder():void {
		$sut = new PrivateRequestRepository($this->tempDir);

		$first = $sut->create("My request name");
		$second = $sut->create("Another request");

		self::assertSame("000-my-request-name", $first->id);
		self::assertSame("001-another-request", $second->id);
	}

	public function testUpdate_preservesExistingPrefixWhenNameChanges():void {
		$sut = new PrivateRequestRepository($this->tempDir);

		$request = $sut->create("Original request");
		$request->name = "Renamed request";
		$sut->update($request);

		self::assertSame("000-renamed-request", $request->id);
		self::assertDirectoryExists("$this->tempDir/000-renamed-request");
		self::assertDirectoryDoesNotExist("$this->tempDir/000-original-request");
	}

	public function testCreate_throwsWhenMoreThan999RequestsExist():void {
		$sut = new PrivateRequestRepository($this->tempDir);

		for($i = 0; $i <= 999; $i++) {
			$dir = sprintf("%s/%03d-request-%03d", $this->tempDir, $i, $i);
			mkdir($dir, recursive: true);
			file_put_contents(
				"$dir/request.dat",
				serialize(new RequestEntity(sprintf("%03d-request-%03d", $i, $i))),
			);
		}

		$this->expectException(OverflowException::class);
		$sut->create("One too many");
	}

	public function testReorder_reindexesDirectoriesAndRequestIds():void {
		$sut = new PrivateRequestRepository($this->tempDir);

		$first = $sut->create("First request");
		$second = $sut->create("Second request");
		$third = $sut->create("Third request");

		$idMap = $sut->reorder($third->id, 0);
		$reorderedList = $sut->retrieveAll();

		self::assertSame([
			"000-third-request",
			"001-first-request",
			"002-second-request",
		], array_map(fn(RequestEntity $request) => $request->id, $reorderedList));
		self::assertSame("001-first-request", $idMap[$first->id]);
		self::assertSame("002-second-request", $idMap[$second->id]);
		self::assertSame("000-third-request", $idMap[$third->id]);
		self::assertDirectoryExists("$this->tempDir/000-third-request");
		self::assertDirectoryExists("$this->tempDir/001-first-request");
		self::assertDirectoryExists("$this->tempDir/002-second-request");
	}

	public function testDelete_archivesToUniqueDirectoryWhenDeletedIdAlreadyExists():void {
		$sut = new PrivateRequestRepository($this->tempDir);

		$request = $sut->create("Test");
		mkdir("$this->tempDir/_deleted", recursive: true);
		mkdir("$this->tempDir/_deleted/000-test", recursive: true);

		$sut->delete($request);

		self::assertDirectoryDoesNotExist("$this->tempDir/000-test");
		self::assertDirectoryExists("$this->tempDir/_deleted/000-test");
		self::assertDirectoryExists("$this->tempDir/_deleted/000-test-2");
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
