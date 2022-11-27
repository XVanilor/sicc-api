<?php

namespace Vanilor\SiccApi\Utils;

use PDOException;
use Ramsey\Uuid\Uuid;

class Item
{

    public string $uuid;
    public string $name;
    private int $quantity;

    public function __construct(string $name)
    {
        $this->uuid = Uuid::uuid4();
        $this->name = $name;
        $this->setQuantity(0);
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        if ($quantity < 0)
            $quantity = 0;
        $this->quantity = $quantity;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function delete(): bool
    {
        try {
            $db = DB::instance();
            $stmt = $db->prepare("DELETE FROM crate_item WHERE uuid = :uuid");
            $stmt->bindValue(":uuid", $this->uuid, SQLITE3_TEXT);
            $stmt->execute();

            return true;
        } catch (PDOException $e)
        {
            return false;
        }
    }

    public function toJson(): array
    {
        return [
            "uuid" => $this->uuid,
            "name" => $this->name,
            "quantity" => $this->getQuantity()
        ];
    }

    public static function fromJson(array $json): ?Item
    {
        if (
            !isset($json["uuid"]) || !is_string($json["uuid"]) || !Uuid::isValid($json['uuid']) ||
            !isset($json["name"]) || !is_string($json["name"]) ||
            !isset($json['quantity']) || !is_numeric($json['quantity'])
        ) {
            return null;
        }

        $item = new self($json["name"]);
        $item->uuid = $json["uuid"];
        $item->setQuantity((int)$json["quantity"]);

        return $item;
    }
}