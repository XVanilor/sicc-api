<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Crate.php";
include_once "Utils/Api.php";
include_once "Utils/User.php";

use Vanilor\SiccApi\Utils\Api;
use Vanilor\SiccApi\Utils\User;
use Vanilor\SiccApi\Utils\Token;
use Ramsey\Uuid\Uuid;

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

if(
    !isset($_GET["uuid"]) || !is_string($_GET["uuid"]) || !Uuid::isValid($_GET['uuid'])
)
{
    return Api::response(404, ["success" => false, "data" => "Resource Not Found"]);
}

$uuid = $_GET["uuid"];
$crate = Crate::get($uuid);
if(!$crate)
    return Api::response(404, ["success" => false, "data" => "Resource Not Found"]);

return Api::response(200, ["success" => true, "data" => $crate->toJson()]);