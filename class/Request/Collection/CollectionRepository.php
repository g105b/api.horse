<?php
namespace App\Request\Collection;

use App\Repository;
use App\Slug;
use Gt\Ulid\Ulid;

class CollectionRepository extends Repository {
	const DEFAULT_COLLECTION_NAME = "Collection 1";
	const CURRENT_COLLECTION_FILE = "current-collection.txt";

	public function __construct(
		string $shareIdDataDir,
		private readonly CollectionMode $mode,
	){
		parent::__construct("$shareIdDataDir/" . $mode->name);
	}

	public function create(?string $name = null):CollectionEntity {
		return new CollectionEntity($name ?? self::DEFAULT_COLLECTION_NAME, $this->mode);
	}

	public function retrieve(string $id, bool $requireDirectory = true):?CollectionEntity {
		$collectionDir = "$this->dataDir/$id";
		if(is_dir($collectionDir) || !$requireDirectory) {
			$collectionNameFile = "$collectionDir/collection.txt";
			$name = self::DEFAULT_COLLECTION_NAME;
			if(is_file($collectionNameFile)) {
				$name = trim(file_get_contents($collectionNameFile));
			}
			return new CollectionEntity($name, $this->mode);
		}
		else {
			return null;
		}
	}

	/** @return array<CollectionEntity> */
	public function retrieveAll():array {
		$collectionList = [];

		foreach(glob("$this->dataDir/*") as $dir) {
			$id = pathinfo($dir, PATHINFO_FILENAME);
			if(str_starts_with($id, "_")) {
				continue;
			}
			if(!is_dir($dir)) {
				continue;
			}

			array_push(
				$collectionList,
				$this->retrieve($id),
			);
		}

		if(!$collectionList) {
			array_push($collectionList, new CollectionEntity(self::DEFAULT_COLLECTION_NAME, CollectionMode::request));
		}

		return $collectionList;
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

	public function setCurrent(string|CollectionEntity $collection):void {
		if(is_string($collection)) {
			$collection = $this->retrieve($collection, false);
		}

		$filePath = "$this->dataDir/" . self::CURRENT_COLLECTION_FILE;
		file_put_contents($filePath, $collection->id);
	}

	public function getCurrent():?CollectionEntity {
		$filePath = "$this->dataDir/" . self::CURRENT_COLLECTION_FILE;
		if(!is_file($filePath)) {
			return null;
		}

		return $this->retrieve(file_get_contents($filePath), false);
	}

}
