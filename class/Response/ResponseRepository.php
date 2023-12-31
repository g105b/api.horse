<?php
namespace App\Response;

use App\Repository;
use App\Request\RequestEntity;

class ResponseRepository extends Repository {
	public function storeResponse(
		RequestEntity $requestEntity,
		ResponseEntity $responseEntity,
	):void {
		$responseDir = $this->getResponseDir($requestEntity);

		file_put_contents(
			"$responseDir/$responseEntity->id.dat",
			serialize($responseEntity),
		);
	}

	/** @return array<ResponseEntity> */
	public function getAll(?RequestEntity $requestEntity):array {
		if(!$requestEntity) {
			return [];
		}

		$responseDir = $this->getResponseDir($requestEntity);
		$responseArray = [];

		foreach(glob("$responseDir/*.dat") as $responseFile) {
			array_push(
				$responseArray,
				unserialize(file_get_contents($responseFile)),
			);
		}

		return array_reverse($responseArray);
	}

	private function getResponseDir(RequestEntity $requestEntity):string {
		$dir = implode("/", [
			$this->dataDir,
			$requestEntity->id,
			"response",
		]);
		if(!is_dir($dir)) {
			mkdir($dir);
		}
		return $dir;
	}

}
