<?php
namespace App\Request\Collection;

use App\Request\PrivateRequestRepository;
use App\Request\RequestRepository;
use App\Request\Collection\CollectionRepository;
use App\Slug;

class PrivateCollectionRepository extends CollectionRepository {
	public function create(?string $name = null):CollectionEntity {
		return new CollectionEntity($name ?? self::DEFAULT_COLLECTION_NAME, $this->mode);
	}

	public function fork(
		CollectionRepository $sourceCollectionRepository,
		CollectionEntity $collection,
		?string $name = null,
	):CollectionEntity {
		$forkName = $this->getAvailableForkName($name ?? $collection->name);
		$forkCollection = $this->create($forkName);
		$this->save($forkCollection);

		$sourceRequestRepository = new RequestRepository(
			"$sourceCollectionRepository->dataDir/$collection->id",
		);
		$targetRequestRepository = new PrivateRequestRepository(
			"$this->dataDir/$forkCollection->id",
		);

		foreach($sourceRequestRepository->retrieveAll() as $requestEntity) {
			/** @var \App\Request\RequestEntity $forkedRequest */
			$forkedRequest = unserialize(serialize($requestEntity));
			$targetRequestRepository->update($forkedRequest);
		}

		return $forkCollection;
	}

	public function rename(CollectionEntity $collection, string $newName):void {
		$newSlug = new Slug($newName);
		$matchingCollectionDir = "$this->dataDir/$collection->id";
		$newCollectionDir = "$this->dataDir/$newSlug";

		if(!is_dir($matchingCollectionDir)) {
			if($collection->name === self::DEFAULT_COLLECTION_NAME) {
				$this->save($collection);
			}
			else {
				throw new CollectionNotFoundException($collection->id);
			}
		}

		rename($matchingCollectionDir, $newCollectionDir);
		file_put_contents(
			"$newCollectionDir/collection.txt",
			$newName,
		);
	}

	public function save(CollectionEntity $collection):void {
		$filePath = "$this->dataDir/$collection->id/collection.txt";
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), recursive: true);
		}

		file_put_contents($filePath, $collection->name);
	}

	public function delete(CollectionEntity $collection):void {
		$dirPath = "$this->dataDir/$collection->id";
		if(!is_dir($dirPath)) {
			return;
		}

		foreach(glob("$dirPath/*") as $file) {
			unlink($file);
		}
		rmdir($dirPath);
	}

	private function getAvailableForkName(string $baseName):string {
		$forkName = $baseName;
		if(!$this->retrieve((string)new Slug($forkName))) {
			return $forkName;
		}

		$forkName = "$baseName (fork)";
		$forkNumber = 2;
		while($this->retrieve((string)new Slug($forkName))) {
			$forkName = "$baseName (fork $forkNumber)";
			$forkNumber++;
		}

		return $forkName;
	}
}
