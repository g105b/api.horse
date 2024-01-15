<?php
namespace App\Request\Collection;

use App\Slug;
use Gt\DomTemplate\BindGetter;

class CollectionEntity {
	public readonly string $id;

	public function __construct(
		public readonly string $name,
		public readonly CollectionMode $mode,
	) {
		$this->id = new Slug($name);
	}
}
