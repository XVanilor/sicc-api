<?php

namespace Vanilor\SiccApi\Utils;

abstract class Config
{

    public static function isVerificationCodeValid(string $code): bool
    {

        $db = DB::instance();
        $stmt = $db->prepare("SELECT value FROM config WHERE name = :name");
        $stmt->bindValue(":name", "pinCode", SQLITE3_TEXT);
        $stmt->execute();
        $res = $stmt->fetchAll();

        if(empty($res))
        {
            throw new \RuntimeException("Unable to retrieve pin code. Please contact your administrator");
        }

        return $code === $res[0]["value"];
    }

}