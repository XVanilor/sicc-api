<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Crate.php";
include_once "Utils/Api.php";

use Vanilor\SiccApi\Utils\Api;
use Ramsey\Uuid\Uuid;

if(
    !isset($_GET["uuid"]) || !is_string($_GET["uuid"]) || !Uuid::isValid($_GET['uuid'])
)
{
    return Api::response(404, ["success" => false]);
}

$uuid = $_GET["uuid"];
$crate = Crate::get($uuid);
if(!$crate)
    return Api::response(404, ["success" => false]);
return Api::response(200, ["success" => true, "data" => $crate->toJson()]);