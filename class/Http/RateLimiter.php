<?php
namespace App\Http;

use RuntimeException;

class RateLimiter {
	const int LIMIT_SECONDS = 1;
	const int RESET_SECONDS = 20;

	public function __construct(
		private readonly string $dataFile,
	) {}

	public function limit(
		string $host,
		string $session,
	):void {
		$now = $this->getTime();
		$hostKey = $this->getKey("host", $host);
		$sessionKey = $this->getKey("session", $session);

		$handle = $this->openDataFile();
		flock($handle, LOCK_EX);

		/** @var array<string, array<string, float|int>> $state */
		$state = $this->readState($handle);
		$hostBucket = $this->getBucket($state, $hostKey, $now);
		$sessionBucket = $this->getBucket($state, $sessionKey, $now);

		$hostLimited = $this->isLimited($hostBucket, $now);
		$sessionLimited = $this->isLimited($sessionBucket, $now);

		if($hostLimited) {
			$hostBucket["cooldown"]++;
			$hostBucket["lastLimitedAt"] = $now;
		}
		if($sessionLimited) {
			$sessionBucket["cooldown"]++;
			$sessionBucket["lastLimitedAt"] = $now;
		}

		$hostAvailableAt = $this->getAvailableTime($hostBucket, $now);
		$sessionAvailableAt = $this->getAvailableTime($sessionBucket, $now);
		$availableAt = max($hostAvailableAt, $sessionAvailableAt);

		$hostBucket["lastRequestAt"] = $availableAt;
		$sessionBucket["lastRequestAt"] = $availableAt;
		$state[$hostKey] = $hostBucket;
		$state[$sessionKey] = $sessionBucket;

		$this->writeState($handle, $state);
		flock($handle, LOCK_UN);
		fclose($handle);

		if($availableAt > $now) {
			$this->sleep($availableAt - $now);
		}
	}

	private function getKey(
		string $type,
		string $value,
	):string {
		return "$type:" . hash("sha256", strtolower($value));
	}

	/** @return resource */
	private function openDataFile() {
		$dir = dirname($this->dataFile);
		if(!is_dir($dir)) {
			mkdir($dir, recursive: true);
		}

		$handle = fopen($this->dataFile, "c+");
		if(!$handle) {
			throw new RuntimeException("Unable to open rate limiter data file.");
		}

		return $handle;
	}

	/**
	 * @param resource $handle
	 * @return array<string, array<string, float|int>>
	 */
	private function readState($handle):array {
		rewind($handle);
		$contents = stream_get_contents($handle);
		if(!$contents) {
			return [];
		}

		$state = unserialize($contents);
		if(!is_array($state)) {
			return [];
		}

		return $state;
	}

	/**
	 * @param array<string, array<string, float|int>> $state
	 * @return array<string, float|int>
	 */
	private function getBucket(
		array $state,
		string $key,
		float $now,
	):array {
		$bucket = $state[$key] ?? [];
		$lastLimitedAt = $bucket["lastLimitedAt"] ?? 0;

		if($lastLimitedAt <= $now - self::RESET_SECONDS) {
			$bucket["cooldown"] = 0;
		}

		return [
			"lastRequestAt" => $bucket["lastRequestAt"] ?? 0,
			"cooldown" => $bucket["cooldown"] ?? 0,
			"lastLimitedAt" => $bucket["lastLimitedAt"] ?? 0,
		];
	}

	/** @param array<string, float|int> $bucket */
	private function isLimited(
		array $bucket,
		float $now,
	):bool {
		return $now < $bucket["lastRequestAt"] + self::LIMIT_SECONDS;
	}

	/** @param array<string, float|int> $bucket */
	private function getAvailableTime(
		array $bucket,
		float $now,
	):float {
		if($now >= $bucket["lastRequestAt"] + self::LIMIT_SECONDS) {
			return $now;
		}

		return $bucket["lastRequestAt"]
			+ self::LIMIT_SECONDS
			+ $bucket["cooldown"];
	}

	/**
	 * @param resource $handle
	 * @param array<string, array<string, float|int>> $state
	 */
	private function writeState(
		$handle,
		array $state,
	):void {
		rewind($handle);
		ftruncate($handle, 0);
		fwrite($handle, serialize($state));
		fflush($handle);
	}

	protected function getTime():float {
		return microtime(true);
	}

	protected function sleep(float $seconds):void {
		usleep((int)($seconds * 1_000_000));
	}
}
