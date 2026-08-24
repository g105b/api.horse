<?php
namespace App\Http;

use RuntimeException;

class RateLimiter {
	const int LIMIT_SECONDS = 1;
	const int RESET_SECONDS = 20;

	public function __construct(
		private readonly string $dataDir,
	) {}

	public function limit(
		string $host,
		string $ipAddress,
	):void {
		$now = $this->getTime();
		$ipHandle = $this->openDataFile("ip", $this->normaliseIpAddress($ipAddress));
		$hostHandle = $this->openDataFile("host", strtolower($host));

		// Always acquire the IP lock first so concurrent requests cannot deadlock.
		flock($ipHandle, LOCK_EX);
		flock($hostHandle, LOCK_EX);

		$ipBucket = $this->getBucket($this->readState($ipHandle), $now);
		$hostBucket = $this->getBucket($this->readState($hostHandle), $now);

		$hostLimited = $this->isLimited($hostBucket, $now);
		$ipLimited = $this->isLimited($ipBucket, $now);

		if($hostLimited) {
			$hostBucket["cooldown"]++;
			$hostBucket["lastLimitedAt"] = $now;
		}
		if($ipLimited) {
			$ipBucket["cooldown"]++;
			$ipBucket["lastLimitedAt"] = $now;
		}

		$hostAvailableAt = $this->getAvailableTime($hostBucket, $now);
		$ipAvailableAt = $this->getAvailableTime($ipBucket, $now);
		$availableAt = max($hostAvailableAt, $ipAvailableAt);

		$hostBucket["lastRequestAt"] = $availableAt;
		$ipBucket["lastRequestAt"] = $availableAt;

		$this->writeState($ipHandle, $ipBucket);
		$this->writeState($hostHandle, $hostBucket);
		flock($hostHandle, LOCK_UN);
		flock($ipHandle, LOCK_UN);
		fclose($hostHandle);
		fclose($ipHandle);

		if($availableAt > $now) {
			$this->sleep($availableAt - $now);
		}
	}

	private function normaliseIpAddress(string $ipAddress):string {
		$packedAddress = inet_pton($ipAddress);
		if($packedAddress === false) {
			throw new RuntimeException("Invalid IP address.");
		}

		return inet_ntop($packedAddress);
	}

	/** @return resource */
	private function openDataFile(string $type, string $name) {
		$dir = "$this->dataDir/$type";
		if(!is_dir($dir)) {
			mkdir($dir, recursive: true);
		}

		$handle = fopen("$dir/$name.dat", "c+");
		if(!$handle) {
			throw new RuntimeException("Unable to open rate limiter data file.");
		}

		return $handle;
	}

	/**
	 * @param resource $handle
	 * @return array<string, float|int>
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
	 * @return array<string, float|int>
	 */
	private function getBucket(
		array $bucket,
		float $now,
	):array {
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
	 * @param array<string, float|int> $state
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
