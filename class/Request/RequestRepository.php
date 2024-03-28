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
			if(!is_dir($dir)) {
				continue;
			}

			array_push(
				$requestEntityList,
				$this->retrieve($id),
			);
		}

		return $requestEntityList;
	}
}
