<?php
namespace App\Maintenance;

use App\Request\Collection\CollectionRepository;

class DataGarbageCollector {
	private const array RESERVED_DIRECTORY_LIST = [
		"html-cache",
		"rate-limit",
		"uploads",
	];

	public function __construct(
		private readonly string $dataDir,
	) {}

	public function collect():int {
		if(!is_dir($this->dataDir)) {
			return 0;
		}

		$removed = 0;
		foreach(scandir($this->dataDir) ?: [] as $name) {
			if($name === "." || $name === "..") {
				continue;
			}
			if(in_array($name, self::RESERVED_DIRECTORY_LIST, true)) {
				continue;
			}

			$path = "$this->dataDir/$name";
			if(!is_dir($path) || is_link($path) || $this->containsStoredData($path)) {
				continue;
			}

			$this->deleteDirectory($path);
			$removed++;
		}

		return $removed;
	}

	private function containsStoredData(string $dir):bool {
		foreach(scandir($dir) ?: [] as $name) {
			if($name === "." || $name === "..") {
				continue;
			}

			$path = "$dir/$name";
			if(is_dir($path) && !is_link($path)) {
				if($this->containsStoredData($path)) {
					return true;
				}
				continue;
			}

			if($name === CollectionRepository::CURRENT_COLLECTION_FILE) {
				continue;
			}

			if(
				$name === "collection.txt"
				&& trim((string)file_get_contents($path)) === CollectionRepository::DEFAULT_COLLECTION_NAME
			) {
				continue;
			}

			return true;
		}

		return false;
	}

	private function deleteDirectory(string $dir):void {
		foreach(scandir($dir) ?: [] as $name) {
			if($name === "." || $name === "..") {
				continue;
			}

			$path = "$dir/$name";
			if(is_dir($path) && !is_link($path)) {
				$this->deleteDirectory($path);
			}
			else {
				unlink($path);
			}
		}

		rmdir($dir);
	}
}
