<?php
namespace App\Request;

use App\Http\HeaderEntity;
use Stringable;

class QueryEncodedKVP implements Stringable {
	/** @param array<QueryStringEntity|HeaderEntity|BodyParameterEntity> $entityList */
	public function __construct(
		private readonly array $entityList,
	) {}

	public function __toString():string {
		$assoc = array_reduce(
			$this->entityList,
			function(array $carry, QueryStringEntity|HeaderEntity|BodyParameterEntity $param) {
				if($param->key) {
					$carry[$param->key] = $param->value;
				}
				return $carry;
			},
			[],
		);
		return http_build_query($assoc);
	}
}
