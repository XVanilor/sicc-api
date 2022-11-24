<?php

namespace Vanilor\SiccApi\Utils;

use Ramsey\Uuid\Uuid;

class Token {
    const API_KEY = "apiKey";
    const ENROLLMENT_TOKEN = "enrollmentToken";
}

class User
{

    public string $uuid;
    public string $name;
    public string $apiKey;
    public string $enrollmentToken;

    public static function exists(string $token, string $token_type): bool
    {
        if(!Uuid::isValid($token))
            return false;

        $db = DB::instance();

        switch($token_type)
        {
            case Token::ENROLLMENT_TOKEN:
                $sql = "SELECT uuid FROM user WHERE enrollment_token = :token";
                break;
            case Token::API_KEY:
            default:
                $sql = "SELECT uuid FROM user WHERE api_key = :token";
                break;
        }
        $stmt = $db->prepare($sql);
        $stmt->bindValue(":token", $token, SQLITE3_TEXT);
        $stmt->execute();

        return !empty($stmt->fetchAll());
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

    public function toJson(): array
    {
        return [
            'uuid'              => $this->uuid,
            'name'              => $this->name,
            'apiKey'            => $this->apiKey,
            'enrollmentToken'   => $this->enrollmentToken
        ];
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