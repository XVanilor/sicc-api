<?php

namespace Vanilor\SiccApi\Utils;

use Ramsey\Uuid\Uuid;

class User
{

    public string $uuid;
    public string $name;
    public string $apiKey;
    public string $enrollmentToken;

    public static function exists(string $token): bool
    {
        if(!Uuid::isValid($token))
            return false;

        $db = DB::instance();
        $stmt = $db->prepare("SELECT uuid FROM user WHERE api_key = :key");
        $stmt->bindValue(":key", $token, SQLITE3_TEXT);
        $stmt->execute();

        return !empty($stmt->fetchAll());
    }

    public static function isRegistered(string $token): bool
    {
        if(!Uuid::isValid($token))
            return false;

        $db = DB::instance();
        $stmt = $db->prepare("SELECT uuid, enrollment_token FROM user WHERE api_key = :key");
        $stmt->bindValue(":key", $token, SQLITE3_TEXT);
        $stmt->execute();

        $userMeta = $stmt->fetchAll();
        if(!empty($userMeta) && Uuid::isValid($userMeta[0]["public_api_key"]))
            return true;
        return false;
    }

    public static function fromJson(array $json): ?User
    {
        if(
            !isset($json["uuid"]) || !is_string($json['uuid']) || !Uuid::isValid($json['uuid']) ||
            !isset($json["name"]) || !is_string($json['name']) || strlen($json["name"]) < 1 ||
            !isset($json["apiKey"]) || !is_string($json['apiKey']) || !Uuid::isValid($json['apiKey']) ||
            !isset($json["enrollmentToken"]) || !is_string($json['enrollmentToken']) || !Uuid::isValid($json['enrollmentToken'])
        )
        {
            return null;
        }

        $user = new self();
        $user->uuid = $json["uuid"];
        $user->name = $json["name"];
        $user->enrollmentToken = $json["enrollmentToken"];
        $user->apiKey = $json["apiKey"];

        return $user;
    }

    public function save(): bool
    {

        $db = DB::instance();
        $stmt = $db->prepare("INSERT OR REPLACE INTO user (uuid, name, api_key, enrollment_token) VALUES(:uuid, :name, :priv_key, :pub_key)");
        $stmt->bindValue(":uuid", $this->uuid, SQLITE3_TEXT);
        $stmt->bindValue(":name", $this->name, SQLITE3_TEXT);
        $stmt->bindValue(":priv_key", $this->apiKey, SQLITE3_TEXT);
        $stmt->bindValue(":pub_key", $this->enrollmentToken, SQLITE3_TEXT);
        $stmt->execute();

        return true;
    }

}