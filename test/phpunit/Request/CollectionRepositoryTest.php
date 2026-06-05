<?php
namespace App\Test\Request;

use App\Request\Collection\CollectionMode;
use App\Request\Collection\PrivateCollectionRepository;
use App\Request\PrivateRequestRepository;
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

	public function testFork_copiesRequestsWithoutResponsesOrSecrets():void {
		$sourceRoot = "$this->tmpDir/source";
		$targetRoot = "$this->tmpDir/target";
		mkdir("$sourceRoot/request", recursive: true);
		mkdir("$targetRoot/request", recursive: true);

		$sourceCollectionRepository = new PrivateCollectionRepository($sourceRoot, CollectionMode::request);
		$targetCollectionRepository = new PrivateCollectionRepository($targetRoot, CollectionMode::request);

		$sourceCollection = $sourceCollectionRepository->create("Access Card");
		$sourceCollectionRepository->save($sourceCollection);

		$sourceRequestRepository = new PrivateRequestRepository(
			"$sourceRoot/request/$sourceCollection->id",
		);
		$firstRequest = $sourceRequestRepository->create("Validate a card");
		$firstRequest->endpoint = "https://example.com/validate";
		$sourceRequestRepository->update($firstRequest);

		$secondRequest = $sourceRequestRepository->create("Get a photo");
		$secondRequest->endpoint = "https://example.com/photo";
		$sourceRequestRepository->update($secondRequest);

		mkdir("$sourceRoot/request/$sourceCollection->id/$firstRequest->id/response", recursive: true);
		file_put_contents(
			"$sourceRoot/request/$sourceCollection->id/$firstRequest->id/response/test.dat",
			"response",
		);
		file_put_contents(
			"$sourceRoot/request/$sourceCollection->id/secrets.ini",
			"API_KEY=\"secret\"\n",
		);

		$forkedCollection = $targetCollectionRepository->fork(
			$sourceCollectionRepository,
			$sourceCollection,
			"Forked Access Card",
		);
		$forkedRequestRepository = new PrivateRequestRepository(
			"$targetRoot/request/$forkedCollection->id",
		);

		$forkedRequests = $forkedRequestRepository->retrieveAll();
		self::assertSame("Forked Access Card", $forkedCollection->name);
		self::assertCount(2, $forkedRequests);
		self::assertSame(
			[$firstRequest->id, $secondRequest->id],
			array_map(fn($request) => $request->id, $forkedRequests),
		);
		self::assertSame(
			[$firstRequest->endpoint, $secondRequest->endpoint],
			array_map(fn($request) => $request->endpoint, $forkedRequests),
		);
		self::assertFileDoesNotExist(
			"$targetRoot/request/$forkedCollection->id/secrets.ini",
		);
		self::assertDirectoryDoesNotExist(
			"$targetRoot/request/$forkedCollection->id/$firstRequest->id/response",
		);
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
