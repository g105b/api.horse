<?php
namespace App\Request;

use Gt\DomTemplate\BindGetter;

abstract class BodyEntity {
	/** @var null|array<BodyParameterEntity> */
	public ?array $parameters = null;
	public ?string $content = null;

	/** @param null|string|array<BodyParameterEntity> $content */
	public function __construct(
		public string $id,
		public ?string $derivedType = null,
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

	abstract public function addBodyParameter(string $key = "", string $value = ""):void;

	public function getParameterById(string $id):?BodyParameterEntity {
		foreach($this->parameters ?? [] as $parameter) {
			if($parameter->id === $id) {
				return $parameter;
			}
		}

		return null;
	}

	public function deleteParameter(BodyParameterEntity $bodyParameterEntity):void {
		$this->parameters = array_filter(
			$this->parameters,
			fn(BodyParameterEntity $match) => $match !== $bodyParameterEntity,
		);
	}
}
