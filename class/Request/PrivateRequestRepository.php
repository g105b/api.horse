<?php
namespace App\Request;

use App\Fun\MemorableId;
use App\Request\RequestRepository;

class PrivateRequestRepository extends RequestRepository {
	public function create():RequestEntity {
		$id = new MemorableId();
		$requestEntity = new RequestEntity($id);
		$this->update($requestEntity);
		return $requestEntity;
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
