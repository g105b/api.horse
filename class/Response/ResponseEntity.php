<?php
namespace App\Response;

use App\Http\HeaderEntity;
use App\Request\RequestEntity;
use App\Request\SecretEntity;
use Gt\DomTemplate\BindGetter;
use Gt\Ulid\Ulid;

class ResponseEntity {
	public readonly string $id;
	public int $status;
	public string $statusText;
	/** @var null|array<HeaderEntity> */
	public ?array $headers = null;
	private float $initTimestamp;
	public float $millisecondsWaiting;
	public float $millisecondsReceiving;
	public int $bytes;
	public ?string $body;

	public function __construct(
	) {
		$this->id = new Ulid("res");
		$this->initTimestamp = microtime(true);
	}

	#[BindGetter]
	public function getStatusMessage():string {
		return implode(" ", [
			$this->status,
			$this->statusText,
		]);
	}

	#[BindGetter]
	public function getDateTime():string {
		$ulid = new Ulid(init: $this->id);
		return $ulid->getDateTime()->format("Y-m-d H:i:s");
	}

	#[BindGetter]
	public function getMillisecondsTotal():float {
		return round($this->millisecondsWaiting + $this->millisecondsReceiving, 2);
	}

	#[BindGetter]
	public function getSize():string {
		$size = $this->bytes;
		$unit = "B";

		if($size >= 1024) {
			$size /= 1024;
			$unit = "KiB";
		}
		if($size >= 1024) {
			$size /= 1024;
			$unit = "MiB";
		}
		if($size >= 1024) {
			$size /= 1024;
			$unit = "GiB";
		}

		return "$size $unit";
	}

	#[BindGetter]
	public function getHeaderSummary():string {
		$summaryString = "";

		foreach($this->headers as $i => $header) {
			if($i > 0) {
				$summaryString .= "; ";
			}
			$summaryString .= "$header->key: $header->value";
		}

		return $summaryString;
	}

	#[BindGetter]
	public function getContentType():?string {
		foreach($this->headers as $header) {
			if(strtolower($header->key) !== "content-type") {
				continue;
			}

			return explode(";", $header->value)[0];
		}

		return null;
	}

	#[BindGetter]
	public function getDisplayBody():string {
		if($this->isImage()) {
			return "";
		}

		return $this->body ?? "";
	}

	public function isImage():bool {
		$contentType = $this->getContentType();
		if(!$contentType) {
			return false;
		}

		return str_starts_with(strtolower($contentType), "image/");
	}

	public function getBodyDataUri():?string {
		$contentType = $this->getContentType();
		if(!$contentType || is_null($this->body) || !$this->isImage()) {
			return null;
		}

		return "data:$contentType;base64," . base64_encode($this->body);
	}

	public function setStatus(int $status, ?string $statusText):void {
		$this->waitingComplete();
		$this->status = $status;
		$this->statusText = $statusText ?? "";
	}

	public function addHeader(string $key, string $value):void {
		if(!$this->headers) {
			$this->headers = [];
		}

		array_push(
			$this->headers,
			new HeaderEntity(
				new Ulid("resheader"),
				$key,
				$value,
			),
		);
	}

	public function setBody(string $bodyData):void {
		$this->receivingComplete(strlen($bodyData));
		$this->body = $bodyData;
	}

	public function getBody():string {
		return $this->body;
	}

	/** @param array<SecretEntity> $secretList */
	public function withRedactedSecrets(array $secretList):self {
		if(empty($secretList)) {
			return $this;
		}

		usort(
			$secretList,
			fn(SecretEntity $a, SecretEntity $b) => strlen($b->getSecretValue()) <=> strlen($a->getSecretValue()),
		);

		$clone = clone($this);
		if($clone->headers) {
			$clone->headers = array_map(function(HeaderEntity $header)use($secretList):HeaderEntity {
				$header = clone($header);
				$header->value = $this->redactString($header->value, $secretList);
				return $header;
			}, $clone->headers);
		}
		if(!is_null($clone->body)) {
			$clone->body = $this->redactString($clone->body, $secretList);
		}

		return $clone;
	}

	/** @param array<SecretEntity> $secretList */
	private function redactString(string $value, array $secretList):string {
		foreach($secretList as $secret) {
			$secretValue = $secret->getSecretValue();
			if($secretValue === "") {
				continue;
			}

			$value = str_replace($secretValue, $secret->censoredValue, $value);
		}

		return $value;
	}

	private function waitingComplete():void {
		$this->millisecondsWaiting = round(microtime(true) - $this->initTimestamp, 2);
	}

	private function receivingComplete(int $bytes):void {
		if(!isset($this->millisecondsWaiting)) {
			$this->millisecondsWaiting = 0;
		}

		$this->millisecondsReceiving = round(microtime(true) - $this->initTimestamp - $this->millisecondsWaiting, 2);
		$this->bytes = $bytes;
	}

}
