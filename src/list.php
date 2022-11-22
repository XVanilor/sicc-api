<?php

use Vanilor\SiccApi\Utils\Api;

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Crate.php";
include_once "Utils/Api.php";

$crates = array_map(function (Crate $c){ return $c->toJson(); }, Crate::all());
return Api::response(200, $crates);