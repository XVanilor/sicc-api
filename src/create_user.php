<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Api.php";
include_once "Utils/Crate.php";
include_once "Utils/User.php";

use Ramsey\Uuid\Uuid;
use Vanilor\SiccApi\Utils\Api;
use Vanilor\SiccApi\Utils\User;


if ($_SERVER['REQUEST_METHOD'] !== "POST")
    exit();

$body = json_decode(file_get_contents('php://input'), true);
$private_token = $_SERVER["HTTP_X_API_TOKEN"] ?? null;

if (!isset($private_token) || !is_string($private_token) || !Uuid::isValid($private_token) ||
    $body === null ||
    !User::exists($private_token)
) {
    return Api::response(404, ["success" => false]);
}

$user = [
    "uuid" => Uuid::uuid4()->toString(),
    "name" => $body["username"] ?? null,
    "apiKey" => $private_token,
    "enrollmentToken" => $body["enrollmentToken"] ?? null
];

$user = User::fromJson($user);
if (!$user)
    return Api::response(400, "Missing some JSON fields. Please refer to documentation for correct usage");

$user->save();
return Api::response(201, ["success" => true]);