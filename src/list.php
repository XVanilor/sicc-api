<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Crate.php";
include_once "Api.php";

$crates = array_map(function (Crate $c){ return $c->toJson(); }, Crate::all());
return Api::response(200, $crates);