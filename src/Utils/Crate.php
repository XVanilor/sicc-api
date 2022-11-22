<?php

include_once 'DB.php';

use Ramsey\Uuid\Uuid;
use Vanilor\SiccApi\Utils\Api;
use Vanilor\SiccApi\Utils\DB;

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

class Crate
{

    public string $uuid;
    public string $name;
    /** @var Item[] */
    public array $items;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->items = [];
    }

    public static function fromJson(array $json): ?Crate
    {
        if (
            !isset($json["uuid"]) || !is_string($json["uuid"]) || !Uuid::isValid($json["uuid"]) ||
            !isset($json["name"]) || !is_string($json["name"]) ||
            !isset($json["items"]) || !is_array($json["items"])
        ) {
            return null;
        }

        $json["items"] = array_map(function ($item) {
            return Item::fromJson($item);
        }, $json["items"]);
        if (in_array(null, $json["items"], true)) {
            return null;
        }

        $crate = new self();
        $crate->uuid = $json["uuid"];
        $crate->name = $json["name"];
        $crate->items = $json["items"];

        return $crate;
    }

    public function toJson(): array
    {
        return [
            "uuid" => $this->uuid,
            "name" => $this->name,
            "items" => array_map(function (Item $item){ return $item->toJson(); }, $this->items)
        ];
    }

    public static function get(string $uuid): ?Crate
    {
        $db = DB::instance();

        // Gather crate
        $stmt = $db->prepare("SELECT uuid, name FROM crate WHERE uuid = :uuid");
        $stmt->bindValue(":uuid", $uuid, SQLITE3_TEXT);
        $stmt->execute();
        $crateMeta = $stmt->fetchAll();

        if(empty($crateMeta))
            return null;
        else
            $crateMeta = $crateMeta[0];

        $crate = new self();
        $crate->uuid = $crateMeta["uuid"];
        $crate->name = $crateMeta["name"];

        $stmt = $db->prepare("SELECT uuid, name, quantity FROM crate_item WHERE crate_id = :uuid");
        $stmt->bindValue(":uuid", $uuid, SQLITE3_TEXT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        foreach($items as $rawItem)
        {
            $item = new Item($rawItem["name"]);
            $item->uuid = $rawItem["uuid"];
            $item->setQuantity($rawItem["quantity"]);

            $crate->items[] = $item;
        }

        return $crate;
    }

    /**
     * @return Crate[]
     */
    public static function all(): array
    {
        $db = DB::instance();

        // Gather crate
        $stmt = $db->prepare("SELECT uuid, name FROM crate");
        $stmt->execute();
        $cratesMeta = $stmt->fetchAll();

        if(empty($cratesMeta))
            return [];

        $crates = [];
        foreach($cratesMeta as $meta)
        {
            $crate = new self();
            $crate->uuid = $meta["uuid"];
            $crate->name = $meta["name"];

            $crates[$crate->uuid] = $crate;
        }

        $itemsToDelete = [];

        $stmt = $db->prepare("SELECT uuid, name, crate_id, quantity FROM crate_item");
        $stmt->execute();
        $items = $stmt->fetchAll();

        foreach($items as $rawItem)
        {
            $item = new Item($rawItem["name"]);
            $item->uuid = $rawItem["uuid"];
            $item->setQuantity($rawItem["quantity"]);

            if(!isset($crates[$rawItem["crate_id"]]))
            {
                $itemsToDelete[] = $item;
                continue;
            }
            $crates[$rawItem['crate_id']]->items[] = $item;
        }

        // Database auto-maintenance
        foreach($itemsToDelete as $item)
            $item->delete();

        return array_values($crates);
    }

    public function save(): bool
    {
        $db = DB::instance();

        try {

            $db->beginTransaction();
            $stmt = $db->prepare("INSERT OR REPLACE INTO crate (uuid, name) 
                                        VALUES(:uuid,:name)");
            $stmt->bindValue(":uuid", substr($this->uuid, 0, 254), SQLITE3_TEXT);
            $stmt->bindValue(":name", substr($this->name, 0, 254), SQLITE3_TEXT);
            $stmt->execute();

            // Delete all old items in this crate from database
            // @TODO See a way to better handle item deletion in save endpoint
            $stmt = $db->prepare("DELETE FROM crate_item WHERE crate_id = :uuid");
            $stmt->bindValue(":uuid", substr($this->uuid, 0, 254), SQLITE3_TEXT);
            $stmt->execute();

            foreach ($this->items as $item) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO crate_item (uuid, name, crate_id, quantity, updated_at) 
                                            VALUES (:uuid, :name, :crate_id, :quantity, CURRENT_TIMESTAMP)");
                $stmt->bindValue(":uuid", $item->uuid, SQLITE3_TEXT);
                $stmt->bindValue(":name", $item->name, SQLITE3_TEXT);
                $stmt->bindValue(":crate_id", $this->uuid, SQLITE3_TEXT);
                $stmt->bindValue(":quantity", $item->getQuantity(), SQLITE3_INTEGER);

                $stmt->execute();
            }

            $db->commit();
            return true;

        } catch (PDOException $e) {
            $db->rollBack();
            return Api::error("Cannot update crate " . $this->name . ". " . $e->getMessage());
        }
    }

    public static function exists(string $uuid)
    {
        $db = DB::instance();
        $stmt = $db->prepare("SELECT uuid FROM crate WHERE uuid = :uuid");
        $stmt->bindValue(":uuid", $uuid, SQLITE3_TEXT);
        $stmt->execute();

        return !empty($stmt->fetchAll());
    }

    public static function delete(string $uuid): bool
    {
        $db = DB::instance();

        try {

            $db->beginTransaction();

            $stmt = $db->prepare("DELETE FROM crate WHERE uuid = :uuid");
            $stmt->bindValue(":uuid", $uuid, SQLITE3_TEXT);
            $stmt->execute();

            $stmt = $db->prepare("DELETE FROM crate_item WHERE crate_id = :uuid");
            $stmt->bindValue(":uuid", $uuid, SQLITE3_TEXT);
            $stmt->execute();

            $db->commit();

            return true;

        } catch (PDOException $e)
        {
            $db->rollBack();
            return Api::error($e);
        }

    }
}