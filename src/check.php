<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Api.php";
include_once "Utils/Crate.php";
include_once "Utils/User.php";

use Ramsey\Uuid\Uuid;
use Vanilor\SiccApi\Utils\Api;
use Vanilor\SiccApi\Utils\User;

$private_token = $_SERVER["HTTP_X_API_TOKEN"] ?? null;

if(!isset($token) || !is_string($token) || !Uuid::isValid($token))
{
    return Api::response(404, ["success" => false]);
}

$is_valid = User::checkPrivateToken($token);

return Api::response($is_valid ? 200 : 404, ["success" => $is_valid]);