<?php
namespace App\Request;
use App\Request\BodyEntity;

class BodyEntityMultipart extends BodyEntity {
	const TYPE_STRING = "Multipart";
	const VALUE_STRING = "form-multipart";
}
