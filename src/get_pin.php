<?php


require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Crate.php";
include_once "Utils/Api.php";
include_once "Utils/User.php";
include_once "Utils/Config.php";

use Ramsey\Uuid\Uuid;
use Vanilor\SiccApi\Utils\Api;
use Vanilor\SiccApi\Utils\Config;
use Vanilor\SiccApi\Utils\User;
use Vanilor\SiccApi\Utils\Token;

if ($_SERVER['REQUEST_METHOD'] !== "GET") {
    return Api::response(405, ["success" => false, ["data" => "Method Not Allowed"]]);
}

$body = json_decode(file_get_contents('php://input'), true);
$apiKey = $_SERVER["HTTP_X_API_TOKEN"] ?? null;
if (!isset($apiKey) || !is_string($apiKey) || !Uuid::isValid($apiKey) ||
    !User::exists($apiKey, Token::API_KEY)
) {
    return Api::response(401, ["success" => false, "data" => "Invalid API token"]);
}

try {

    $pinCode = Config::getPIN();
    return Api::response(200, ["success" => true, "data" => $pinCode]);
}
catch (RuntimeException $e)
{
    return Api::error($e->getMessage());
}