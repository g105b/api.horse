<?php
namespace App\Request;

use App\Fun\MemorableId;
use App\Request\RequestRepository;
use App\Slug;

class PrivateRequestRepository extends RequestRepository {
	private const int MAX_REQUEST_INDEX = 999;
	private const string REORDER_TEMP_PREFIX = "__reorder__";

	public function create(?string $name = null):RequestEntity {
		$index = $this->getNextRequestIndex();
		$slug = $name
			? new Slug($name)
			: new MemorableId();
		$id = $this->createPrefixedId($index, (string)$slug);

		$requestEntity = new RequestEntity($id);
		$this->update($requestEntity);
		return $requestEntity;
	}

	public function update(RequestEntity &$requestEntity):void {
		$filePath = "$this->dataDir/$requestEntity->id/request.dat";
		if(!is_dir(dirname($filePath))) {
			mkdir(dirname($filePath), recursive: true);
		}

		$slug = $requestEntity->name
			? (string)new Slug($requestEntity->name)
			: $this->stripNumericPrefix($requestEntity->id);
		$prefix = $this->extractNumericPrefix($requestEntity->id);
		$newId = $prefix !== null
			? $this->createPrefixedId($prefix, $slug)
			: $slug;
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

	/** @return array<string, string> */
	public function reorder(string $requestId, int $targetOrder):array {
		$requestList = $this->retrieveAll();
		if(count($requestList) > self::MAX_REQUEST_INDEX + 1) {
			throw new \OverflowException("Maximum request count per collection exceeded.");
		}

		$currentOrder = null;
		foreach($requestList as $index => $requestEntity) {
			if($requestEntity->id === $requestId) {
				$currentOrder = $index;
				break;
			}
		}

		if($currentOrder === null) {
			return [];
		}

		$targetOrder = max(0, min($targetOrder, count($requestList) - 1));
		$movedRequest = array_splice($requestList, $currentOrder, 1);
		array_splice($requestList, $targetOrder, 0, $movedRequest);

		$idMap = [];
		$renamePlan = [];

		foreach($requestList as $index => $requestEntity) {
			$newId = $this->createPrefixedId(
				$index,
				$requestEntity->name
					? (string)new Slug($requestEntity->name)
					: $this->stripNumericPrefix($requestEntity->id),
			);
			$idMap[$requestEntity->id] = $newId;

			if($newId === $requestEntity->id) {
				continue;
			}

			$renamePlan[] = [
				"oldId" => $requestEntity->id,
				"newId" => $newId,
				"tempId" => self::REORDER_TEMP_PREFIX . $index . "-" . $requestEntity->id,
			];
		}

		foreach($renamePlan as $renameItem) {
			rename(
				"$this->dataDir/{$renameItem["oldId"]}",
				"$this->dataDir/{$renameItem["tempId"]}",
			);
		}

		foreach($renamePlan as $renameItem) {
			$tempDir = "$this->dataDir/{$renameItem["tempId"]}";
			$newDir = "$this->dataDir/{$renameItem["newId"]}";
			rename($tempDir, $newDir);

			$requestEntity = $this->retrieve($renameItem["newId"]);
			$requestEntity = $requestEntity->with(["id" => $renameItem["newId"]]);
			file_put_contents("$newDir/request.dat", serialize($requestEntity));
		}

		return $idMap;
	}

	private function getNextRequestIndex():int {
		$nextIndex = -1;

		foreach(glob("$this->dataDir/*") as $dir) {
			if(!is_dir($dir)) {
				continue;
			}

			$id = pathinfo($dir, PATHINFO_BASENAME);
			if(str_starts_with($id, "_")) {
				continue;
			}

			$prefix = $this->extractNumericPrefix($id);
			if($prefix === null) {
				continue;
			}

			$nextIndex = max($nextIndex, $prefix);
		}

		$nextIndex++;
		if($nextIndex > self::MAX_REQUEST_INDEX) {
			throw new \OverflowException("Maximum request count per collection exceeded.");
		}

		return $nextIndex;
	}

	private function createPrefixedId(int $index, string $slug):string {
		return sprintf("%03d-%s", $index, $slug);
	}

	private function extractNumericPrefix(string $id):?int {
		if(!preg_match('/^(\d{3})-(.+)$/', $id, $matches)) {
			return null;
		}

		return (int)$matches[1];
	}

	private function stripNumericPrefix(string $id):string {
		if(!preg_match('/^\d{3}-(.+)$/', $id, $matches)) {
			return (string)new Slug($id);
		}

		return $matches[1];
	}
}
