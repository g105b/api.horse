<?php
namespace App\Response;

use App\Http\HeaderEntity;
use App\Request\RequestEntity;
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

	private function waitingComplete():void {
		$this->millisecondsWaiting = round(microtime(true) - $this->initTimestamp, 2);
	}

	private function receivingComplete(int $bytes):void {
		$this->millisecondsReceiving = round(microtime(true) - $this->initTimestamp - $this->millisecondsWaiting, 2);
		$this->bytes = $bytes;
	}

}
