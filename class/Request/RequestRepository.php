<?php
namespace App\Request;

use App\Fun\MemorableId;
use App\Repository;
use App\ShareId;
use Gt\Json\JsonObject;
use Gt\Json\JsonObjectBuilder;
use Gt\Ulid\Ulid;
use ReflectionObject;
use ReflectionProperty;
use stdClass;

class RequestRepository extends Repository {
	public function create():RequestEntity {
		$id = new MemorableId();
		$requestEntity = new RequestEntity($id);
		$this->update($requestEntity);
		return $requestEntity;
	}

	public function retrieve(string $id):?RequestEntity {
		$filePath = "$this->dataDir/$id/request.dat";
		if(!is_file($filePath)) {
			return null;
		}

		/** @var RequestEntity $requestEntity */
		$requestEntity = unserialize(file_get_contents($filePath));
		return $requestEntity;
	}

	/** @return array<RequestEntity> */
	public function retrieveAll():array {
		$requestEntityList = [];

		foreach(glob("$this->dataDir/*") as $dir) {
			$id = pathinfo($dir, PATHINFO_BASENAME);
			if(str_starts_with($id, "_")) {
				continue;
			}

			array_push(
				$requestEntityList,
				$this->retrieve($id),
			);
		}

		return $requestEntityList;
	}

	public function update(RequestEntity $requestEntity):void {
		$filePath = "$this->dataDir/$requestEntity->id/request.dat";
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), recursive: true);
		}
		file_put_contents($filePath, serialize($requestEntity));
	}

	public function delete(RequestEntity $requestEntity):void {
		$dirPath = "$this->dataDir/$requestEntity->id";

		if(is_dir($dirPath)) {
			$deletedDir = "$this->dataDir/_deleted";
			if(!is_dir($deletedDir)) {
				mkdir($deletedDir, recursive: true);
			}

			rename($dirPath, "$deletedDir/$requestEntity->id");
		}
	}
}
