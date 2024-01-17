<?php
namespace App\Request;

use App\Repository;

class SecretRepository extends Repository {
	private readonly string $secretIniFile;
	/** @var array<string, string> */
	private readonly array $secretAssoc;

	public function __construct(string $dataDir) {
		$this->secretIniFile = "$dataDir/secrets.ini";
		$this->secretAssoc = is_file($this->secretIniFile)
			? parse_ini_file($this->secretIniFile)
			: [];

		parent::__construct($dataDir);
	}

	public function create(string $key, string $value):void {
		$newSecretAssoc = $this->secretAssoc;
		$newSecretAssoc[$key] = $value;
		$this->write($newSecretAssoc);
	}

	public function remove(string $key):void {
		$newSecretAssoc = $this->secretAssoc;
		unset($newSecretAssoc[$key]);
		$this->write($newSecretAssoc);
	}

	/** @return array<SecretEntity> */
	public function getAll():array {
		$secretArray = [];

		foreach($this->secretAssoc as $key => $value) {
			array_push(
				$secretArray,
				new SecretEntity($key, $value)
			);
		}

		return $secretArray;
	}

	private function write(array $secretAssocData):void {
		$fh = fopen($this->secretIniFile, "w");
		foreach($secretAssocData as $key => $value) {
			$escapedValue = str_replace('"', '\"', $value);
			fwrite($fh, "$key=\"$escapedValue\"\n");
		}
		fclose($fh);
	}
}
