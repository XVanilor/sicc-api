<?php

use Vanilor\SiccApi\Utils\Api;

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Api.php";
include_once "Utils/Crate.php";

if($_SERVER['REQUEST_METHOD'] !== "POST")
   exit();

$body = json_decode(file_get_contents('php://input'), true);

if($body === null)
{
    http_response_code(400);
    $error = [
        "error" => "Not a valid JSON body"
    ];
    echo json_encode($error);
    return;
}

$crate = Crate::fromJson($body);
if(!($crate instanceof Crate))
{
    http_response_code(400);
    $error = [
        "error" => "Invalid JSON body"
    ];
    echo json_encode($error);
    return;
}

$crate->save();

// Refresh entity from database
$crate = Crate::get($crate->uuid);

return Api::response(200, ["success" => true, "data" => $crate->toJson()]);