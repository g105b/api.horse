<?php
namespace App\Request;

use Gt\DomTemplate\BindGetter;
use Gt\Ulid\Ulid;
use JsonSerializable;

class RequestEntity {
	public ?string $name = null;
	public ?string $method = null;
	public ?string $endpoint = null;
	/** @var null|array<QueryStringEntity> */
	public ?array $queryStringParameters = null;
	/** @var null|array<HeaderEntity> */
	public ?array $headers = null;
	public ?BodyEntity $body = null;

	public function __construct(
		public readonly string $id,
	) {}

	#[BindGetter]
	public function getNameOrId():string {
		return empty($this->name) ? $this->id : $this->name;
	}

	#[BindGetter]
	public function getQueryStringParameterCount():int {
		return count($this->queryStringParameters ?? []);
	}

	#[BindGetter]
	public function getHeaderCount():int {
		return count($this->headers ?? []);
	}

	#[BindGetter]
	public function getShowBodyParameters():bool {
		if(!$this->body) {
			return false;
		}

		return $this->body instanceof BodyEntityForm;
	}

	#[BindGetter]
	public function getShowBodyRaw():bool {
		if(!$this->body) {
			return false;
		}

		return $this->body instanceof BodyEntityRaw;
	}

	#[BindGetter]
	public function getRawMessage():string {
		$queryString = "";

		if($this->queryStringParameters) {
			foreach($this->queryStringParameters as $queryStringParameter) {
				if($queryString) {
					$queryString .= "&";
				}
				else {
					$queryString .= "?";
				}

				$queryString .= $queryStringParameter->key;
				if($queryStringParameter->value) {
					$queryString .= "=$queryStringParameter->value";
				}
			}
		}

		$host = parse_url($this->endpoint, PHP_URL_HOST);
		$uri = parse_url($this->endpoint, PHP_URL_PATH) ?: "/";

		$message = strtoupper($this->method) . " " . $uri . $queryString . " HTTP/1.1\n";
		$message .= "Host: $host\n";

		foreach($this->headers ?? [] as $header) {
			$message .= "$header->key: $header->value\n";
		}

		if($this->body) {
			$message .= "\n";
			$message .= $this->body;
		}

		return $message;
	}

	#[BindGetter]
	public function getRawMessageLines():int {
		return substr_count($this->getRawMessage(), "\n") + 1;
	}

	public function addQueryParameter(
		string $key = "",
		string $value = null,
	):void {
		if(is_null($this->queryStringParameters)) {
			$this->queryStringParameters = [];
		}

		array_push(
			$this->queryStringParameters,
			new QueryStringEntity(
				new Ulid("query"),
				$key,
				$value,
			),
		);
	}

	public function getQueryStringParameterById(string $id):?QueryStringEntity {
		return $this->getEntityById($this->queryStringParameters, $id);
	}

	public function deleteQueryParameterEntity(QueryStringEntity $queryParameterEntity):void {
		$this->queryStringParameters = array_filter(
			$this->queryStringParameters,
			fn(QueryStringEntity $match) => $match !== $queryParameterEntity,
		);
	}

	public function addHeader(
		string $key = "",
		string $value = "",
	):void {
		if(is_null($this->headers)) {
			$this->headers = [];
		}

		array_push(
			$this->headers,
			new HeaderEntity(
				new Ulid("header"),
				$key,
				$value,
			),
		);
	}

	public function getHeaderById(string $id):?HeaderEntity {
		return $this->getEntityById($this->headers, $id);
	}

	public function deleteHeaderEntity(HeaderEntity $headerEntity):void {
		$this->headers = array_filter(
			$this->headers,
			fn(HeaderEntity $match) => $match !== $headerEntity,
		);
	}

	/** @param array<QueryStringEntity|HeaderEntity> $entityList */
	private function getEntityById(
		array $entityList,
		string $id,
	):null|QueryStringEntity|HeaderEntity {
		foreach($entityList as $entity) {
			if($entity->id === $id) {
				return $entity;
			}
		}

		return null;
	}

	public function setBody(?BodyEntity $bodyEntity):void {
		$this->body = $bodyEntity;
	}

}
