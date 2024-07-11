<?php
namespace App;

use ReflectionClass;
use ReflectionObject;

trait ImmutableClone {
	/**
	 * @param array<string, mixed> $kvp Key value pair of properties and
	 * their values.
	 */
	public function with(array $kvp):self {
		if(empty($kvp)) {
			return $this;
		}

		$refClass = new ReflectionClass($this);
		$refInstance = $refClass->newInstanceWithoutConstructor();

		$propertyArray = array_merge(get_object_vars($this), $kvp);

		foreach($propertyArray as $propertyName => $value) {
			$refProp = $refClass->getProperty($propertyName);
			$refProp->setValue($refInstance, $value);
		}

		return $refInstance;
	}
}
