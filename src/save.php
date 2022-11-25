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

if($body === null)
{
    return Api::response(400, ["success" => false, "data" => "Not a valid JSON body"]);
}

$crate = Crate::fromJson($body);
if(!($crate instanceof Crate))
{
    return Api::response(400, ["success" => false, "data" => "Not a valid JSON body"]);
}

$crate->save();
// Refresh entity from database
$crate = Crate::get($crate->uuid);

return Api::response(200, ["success" => true, "data" => $crate->toJson()]);