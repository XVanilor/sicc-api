<?php

require __DIR__ . '/../vendor/autoload.php';
include_once "Utils/Api.php";
include_once "Utils/DB.php";

use Ramsey\Uuid\Uuid;
use Vanilor\SiccApi\Utils\Api;
use Vanilor\SiccApi\Utils\DB;

$dbPath = "siccapi.db";
if(is_file($dbPath))
{
    return Api::error("Database already exists");
}

$db = new DB($dbPath);
$db->query("CREATE TABLE IF NOT EXISTS crate(
    uuid VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP
);");
$db->query("CREATE TABLE IF NOT EXISTS crate_item (
    uuid VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255),
    quantity INTEGER,
    crate_id VARCHAR(255),
    created_at INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at INTEGER NOT NULL DEFAULT CURRENT_TIMESTAMP
);");
$db->query('CREATE TABLE user (
    uuid             TEXT    NOT NULL                            constraint user_pk PRIMARY KEY,
    name             INTEGER default NULL,
    enrollment_token TEXT    default NULL                        constraint "user-enrollment_token" unique,
    api_key          INTEGER DEFAULT NULL                        constraint "user-api_key" unique,
    created_at       INTEGER default CURRENT_TIMESTAMP NOT NULL,
    updated_at       INTEGER default CURRENT_TIMESTAMP not null
);');

// Insert admin API token
$admin_uuid = Uuid::uuid4()->toString();
$admin_private_key = Uuid::uuid4()->toString();

$db->beginTransaction();
$stmt = $db->prepare("INSERT INTO user (uuid, name, api_key) VALUES(:uuid,:name,:api_key)");
$stmt->bindValue(":uuid",$admin_uuid, SQLITE3_TEXT);
$stmt->bindValue(":name","Admin", SQLITE3_TEXT);
$stmt->bindValue(":api_key", $admin_private_key, SQLITE3_TEXT);
$stmt->execute();
$db->commit();

return Api::response(201, ["success" => true, "data" => ["your_api_token" => $admin_private_key]]);