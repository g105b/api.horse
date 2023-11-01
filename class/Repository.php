<?php
namespace App;

abstract class Repository {
	protected string $dataDir;

	public function __construct(
		string $baseDataDir,
		protected readonly ShareId $shareId,
	) {
		$this->dataDir = "$baseDataDir/$shareId";
	}
}
