<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Api.php";
include_once "Utils/Crate.php";
include_once "Utils/User.php";
include_once "Utils/Config.php";

use Ramsey\Uuid\Uuid;
use Vanilor\SiccApi\Utils\Api;
use Vanilor\SiccApi\Utils\Token;
use Vanilor\SiccApi\Utils\User;
use Vanilor\SiccApi\Utils\Config;

if ($_SERVER['REQUEST_METHOD'] !== "POST")
    exit();

$body = json_decode(file_get_contents('php://input'), true);
$enrollmentToken = $_SERVER["HTTP_X_ENROLLMENT_TOKEN"] ?? null;

if (!isset($enrollmentToken) || !is_string($enrollmentToken) || !Uuid::isValid($enrollmentToken) ||
    $body === null ||
    !User::exists($enrollmentToken, Token::ENROLLMENT_TOKEN)
)
{
    return Api::response(401, ["success" => false, "data" => "Invalid enrollment token. Please contact you app administrator in case of an error"]);
}

try {
    if(!isset($body["pinCode"]) || !Config::isVerificationCodeValid($body["pinCode"]))
    {
        return Api::response(401, ["success" => false, "data" => "Invalid PIN code"]);
    }
}
catch (RuntimeException $e)
{
    echo $e->getMessage();
    return Api::error($e->getMessage());
}

$user = [
    "uuid" => Uuid::uuid4()->toString(),
    "name" => $body["username"] ?? null,
    "apiKey" => Uuid::uuid4()->toString(),
    "enrollmentToken" => Uuid::uuid4()->toString()
];

$user = User::fromJson($user);
if (!$user)
    return Api::response(400, "Missing some JSON fields. Please refer to documentation for correct usage");

$user->save();
return Api::response(201, ["success" => true, "data" => $user->toJson()]);