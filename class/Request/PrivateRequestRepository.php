<?php
namespace App\Request;

use App\Fun\MemorableId;
use App\Request\RequestRepository;
use App\Slug;

class PrivateRequestRepository extends RequestRepository {
	public function create(?string $name = null):RequestEntity {
		if($name) {
			$id = new Slug($name);
		}
		else {
			$id = new MemorableId();
		}

		$requestEntity = new RequestEntity($id);
		$this->update($requestEntity);
		return $requestEntity;
	}

	public function update(RequestEntity &$requestEntity):void {
		$filePath = "$this->dataDir/$requestEntity->id/request.dat";
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), recursive: true);
		}

		$newId = $requestEntity->name
			? new Slug($requestEntity->name)
			: new Slug($requestEntity->id);
		if((string)$newId !== $requestEntity->id) {
			$oldFilePath = $filePath;
			$filePath = "$this->dataDir/$newId/request.dat";

			if(is_file($oldFilePath)) {
				$oldDir = dirname($oldFilePath);
				$newDir = dirname($filePath);
				rename($oldDir, $newDir);
			}

			$requestEntity = $requestEntity->with(["id" => $newId]);
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
