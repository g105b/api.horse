<?php
namespace App\Collection;

use App\Repository;
use Gt\Ulid\Ulid;

class CollectionRepository extends Repository {
	const DEFAULT_COLLECTION_NAME = "Unnamed Collection";

	public function __construct(
		string $shareIdDataDir,
		private readonly CollectionMode $mode,
	){
		parent::__construct("$shareIdDataDir/" . $mode->name);
	}

	public function create(?string $name = null):CollectionEntity {
		return new CollectionEntity(new Ulid(), $name ?? self::DEFAULT_COLLECTION_NAME, $this->mode);
	}

	public function retrieve(string $id, bool $requireDirectory = true):?CollectionEntity {
		$collectionDir = "$this->dataDir/$id";
		if(is_dir($collectionDir) || !$requireDirectory) {
			$collectionNameFile = "$collectionDir/collection.txt";
			$name = self::DEFAULT_COLLECTION_NAME;
			if(is_file($collectionNameFile)) {
				$name = trim(file_get_contents($collectionNameFile));
			}
			return new CollectionEntity($id, $name, $this->mode);
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

			array_push(
				$collectionList,
				$this->retrieve($id),
			);
		}

		return $collectionList;
	}
}
