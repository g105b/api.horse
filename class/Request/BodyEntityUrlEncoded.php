<?php
namespace App\Request;

use App\Request\BodyEntity;

class BodyEntityUrlEncoded extends BodyEntity {
	const TYPE_STRING = "URL Encoded";
	const VALUE_STRING = "form-url";
}
