<?php

namespace Vanilor\SiccApi\Utils;

use Exception;
use PDO;

class DB extends PDO
{

    public const DEFAULT_FILE = "siccapi.db";

    private static ?DB $_instance = null;

    public function __construct(string $name = self::DEFAULT_FILE)
    {

        try {
            parent::__construct("sqlite:" . $name);
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            self::$_instance = $this;

        } catch (Exception $e) {
            Api::error($e);
        }
    }

    public static function instance(): DB
    {
        if (self::$_instance === null) {
            self::$_instance = new self(self::DEFAULT_FILE);
        }

        return self::$_instance;
    }
}