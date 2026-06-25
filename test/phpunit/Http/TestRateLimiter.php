<?php
namespace App\Test\Http;

use App\Http\RateLimiter;

class TestRateLimiter extends RateLimiter {
	/** @var array<float> */
	public array $sleepList = [];

	public function __construct(
		string $dataFile,
		public float $time,
	) {
		parent::__construct($dataFile);
	}

	protected function getTime():float {
		return $this->time;
	}

	protected function sleep(float $seconds):void {
		$this->sleepList[] = $seconds;
	}
}
