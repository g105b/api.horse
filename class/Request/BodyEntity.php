<?php
namespace App\Request;

use Gt\DomTemplate\BindGetter;

abstract class BodyEntity {
	public function __construct(
		public string $id,
		public ?string $derivedType = null,
		public ?string $content = null,
	) {}

	#[BindGetter]
	public function getTypeString():string {
		if($this instanceof BodyEntityMultipart) {
			return BodyEntityMultipart::TYPE_STRING;
		}
		elseif($this instanceof  BodyEntityUrlEncoded) {
			return BodyEntityUrlEncoded::TYPE_STRING;
		}

		return "Raw $this->derivedType";
	}

	#[BindGetter]
	public function getTypeValue():string {
		if($this instanceof BodyEntityMultipart) {
			return BodyEntityMultipart::VALUE_STRING;
		}
		elseif($this instanceof  BodyEntityUrlEncoded) {
			return BodyEntityUrlEncoded::VALUE_STRING;
		}

		return $this->derivedType;
	}
}
