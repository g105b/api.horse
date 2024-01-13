<?php
namespace App;

abstract class Repository {
	public function __construct(
		protected readonly string $dataDir,
	) {
	}
}
