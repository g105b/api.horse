<?php
namespace App\Collection;

use App\Repository;
use Gt\Ulid\Ulid;

class CollectionRepository extends Repository {
	public function __construct(
		string $shareIdDataDir,
		private readonly CollectionMode $mode,
	){
		parent::__construct("$shareIdDataDir/" . $mode->name);
	}

	public function create():CollectionEntity {
		return new CollectionEntity(new Ulid(), $this->mode);
	}

	public function retrieve(string $id, bool $requireDirectory = true):?CollectionEntity {
		$collectionDir = "$this->dataDir/$id";
		if(is_dir($collectionDir) || !$requireDirectory) {
			return new CollectionEntity($id, $this->mode);
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
