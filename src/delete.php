<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Api.php";
include_once "Utils/Crate.php";

use Ramsey\Uuid\Uuid;
use Vanilor\SiccApi\Utils\Api;

if(!isset($_GET["id"]))
{
    return Api::error("Missing id parameter");
}

$uuid = $_GET["id"];

if(!Uuid::isValid($uuid) || !Crate::exists($uuid))
{
    return Api::response(404, "Requested resource was not found");
}

Crate::delete($uuid);
return Api::response(204, "");