<?php

include_once "Api.php";
include_once "DB.php";

if(is_file(__DIR__.'/'.DB::DEFAULT_FILE))
{
    return Api::error("Database already exists");
}

$db = new DB();
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

return Api::response(201, ["success" => true]);