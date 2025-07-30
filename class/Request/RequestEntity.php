<?php
namespace App\Request;

use App\ImmutableClone;
use App\Http\HeaderEntity;
use Gt\DomTemplate\BindGetter;
use Gt\Http\Uri;
use Gt\Ulid\Ulid;
use Psr\Http\Message\UriInterface;

class RequestEntity {
	use ImmutableClone;

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

	public function getMethod():string {
		return $this->method;
	}

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

		if(!$this->method || !$this->endpoint) {
			return "INCOMPLETE";
		}

		if($this->queryStringParameters) {
			$queryString = "?" . new QueryEncodedKVP($this->queryStringParameters);
		}

		$host = parse_url($this->endpoint, PHP_URL_HOST);
		$uri = parse_url($this->endpoint, PHP_URL_PATH) ?: "/";

		$message = strtoupper($this->method) . " " . $uri . $queryString . " HTTP/1.1\n";
		$message .= "Host: $host\n";

		$headerList = $this->headers ?? [];
		if($this->body instanceof BodyEntityMultipart) {
			array_push(
				$headerList,
				new HeaderEntity(
					new Ulid("header"),
					"Content-type",
					"multipart/form-data",
					"boundary",
					"--" . $this->body->boundary,
				)
			);
		}

		foreach($headerList as $header) {
			if(!$header->key) {
				continue;
			}

			$message .= "$header\n";
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
				new Ulid("reqheader"),
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

	public function getFetchableUri():UriInterface {
		$uri = new Uri($this->endpoint);

		foreach($this->queryStringParameters ?? [] as $queryStringParameter) {
			$uri = $uri->withQueryValue(
				$queryStringParameter->key,
				$queryStringParameter->value,
			);
		}

		return $uri;
	}

	/** @return null|array<string, string> */
	public function getFetchableHeaders():?array {
		if(!$this->headers) {
			return null;
		}

		$headerArray = [];
		foreach($this->headers as $header) {
			$headerArray[$header->key] = $header->value;
		}

		return $headerArray;
	}

	public function getFetchableBody():?string {
		if(!$this->body) {
			return null;
		}

		return (string)$this->body;
	}

	/** @param array<SecretEntity> $secretList */
	public function withInjectedSecrets(array $secretList):self {
		if(empty($secretList)) {
			return $this;
		}

		$clone = clone($this);
		$clone->endpoint = $this->injectString($clone->endpoint, $secretList);
		$clone->queryStringParameters = $this->injectKeyValuePairs(
			$clone->queryStringParameters,
			$secretList,
		);
		$clone->headers = $this->injectKeyValuePairs(
			$clone->headers,
			$secretList,
		);
		if($clone->body instanceof BodyEntityForm) {
			$clone->body->parameters = $this->injectKeyValuePairs(
				$clone->body->parameters,
				$secretList,
			);
		}
		elseif($clone->body instanceof BodyEntityRaw) {
			$clone->body->content = $this->injectString(
				$clone->body->content,
				$secretList,
			);
		}
		return $clone;
	}

	/**
	 * @param array<QueryStringEntity|HeaderEntity|BodyParameterEntity> $kvpEntityArray
	 * @param array<SecretEntity> $secretList
	 * @return array<QueryStringEntity|HeaderEntity|BodyParameterEntity>
	 */
	private function injectKeyValuePairs(
		?array $kvpEntityArray,
		array $secretList,
	):?array {
		foreach($kvpEntityArray ?? [] as $kvpEntity) {
			$kvpEntity->key = $this->injectString($kvpEntity->key, $secretList);
			if($kvpEntity->value) {
				$kvpEntity->value = $this->injectString($kvpEntity->value, $secretList);
			}
		}

		return $kvpEntityArray;
	}

	/** @param array<SecretEntity> $secretList */
	private function injectString(string $string, array $secretList):string {
		if(!str_contains($string, "{{")) {
			return $string;
		}

		foreach($secretList as $secret) {
			$string = str_ireplace("{{" . $secret->key . "}}", $secret->getSecretValue(), $string);
		}

		return $string;
	}
}
