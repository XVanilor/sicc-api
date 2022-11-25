<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Api.php";
include_once "Utils/Crate.php";
include_once "Utils/User.php";

use Ramsey\Uuid\Uuid;
use Vanilor\SiccApi\Utils\Api;
use Vanilor\SiccApi\Utils\Token;
use Vanilor\SiccApi\Utils\User;

if($_SERVER['REQUEST_METHOD'] !== "POST")
{
    return Api::response(405, ["success" => false, ["data" => "Method Not Allowed"]]);
}

$body = json_decode(file_get_contents('php://input'), true);
$apiKey = $_SERVER["HTTP_X_API_TOKEN"] ?? null;
if (!isset($apiKey) || !is_string($apiKey) || !Uuid::isValid($apiKey) ||
    !User::exists($apiKey, Token::API_KEY)
)
{
    return Api::response(401, ["success" => false, "data" => "Invalid API token"]);
}

$user = [
    "uuid" => Uuid::uuid4()->toString(),
    "name" => $body["username"] ?? null,
    "apiKey" => $apiKey,
    "enrollmentToken" => Uuid::uuid4()->toString()
];

$user = User::fromJson($user);
if (!$user)
    return Api::response(400, "Missing some JSON fields. Please refer to documentation for correct usage");

$user->save();
return Api::response(201, ["success" => true, "data" => $user->toJson()]);