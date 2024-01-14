<?php
namespace App\Collection;

class CollectionEntity {
	public function __construct(
		public readonly string $id,
		public readonly string $name,
		public readonly CollectionMode $mode,
	) {}
}
