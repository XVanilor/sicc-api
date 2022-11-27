<?php

namespace Vanilor\SiccApi\Utils;

include_once 'DB.php';

use PDOException;
use Ramsey\Uuid\Uuid;

class Crate
{

    public string $uuid;
    public string $name;
    public ?string $parent_uuid;
    /** @var Item[] */
    public array $items;
    /** @var Crate[] */
    public array $crates;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
        $this->name = "";
        $this->parent_uuid = null;
        $this->items = [];
        $this->crates = [];
    }

    public static function fromJson(array $json): ?Crate
    {
        if (
            !isset($json["uuid"]) || !is_string($json["uuid"]) || !Uuid::isValid($json["uuid"]) ||
            !isset($json["name"]) || !is_string($json["name"]) ||
            !isset($json["items"]) || !is_array($json["items"]) ||
            !isset($json["crates"]) || !is_array($json["crates"])
        ) {
            return null;
        }

        $json["items"] = array_map(function ($item) {
            return Item::fromJson($item);
        }, $json["items"]);
        if (in_array(null, $json["items"], true)) {
            return null;
        }

        $json["crates"] = array_map(function ($c) {
            return Crate::fromJson($c);
        }, $json["crates"]);
        if (in_array(null, $json["items"], true)) {
            return null;
        }

        $crate = new self();
        $crate->uuid = $json["uuid"];
        $crate->name = $json["name"];
        $crate->parent_uuid = $json["parent_uuid"] ?? null;
        $crate->items = $json["items"];
        $crate->crates = $json["crates"];

        return $crate;
    }

    public function toJson(): array
    {
        return [
            "uuid" => $this->uuid,
            "name" => $this->name,
            "parent_uuid" => $this->parent_uuid,
            "items" => array_map(function (Item $item){ return $item->toJson(); }, $this->items),
            "crates" => array_map(function (Crate $crate){ return $crate->toJson(); }, $this->crates)
        ];
    }

    public static function get(string $uuid): ?Crate
    {
        $db = DB::instance();

        // Gather crate
        $stmt = $db->prepare("SELECT uuid, name, parent_uuid FROM crate WHERE uuid = :uuid OR parent_uuid = :uuid");
        $stmt->bindValue(":uuid", $uuid, SQLITE3_TEXT);
        $stmt->execute();
        $cratesMeta = $stmt->fetchAll();

        if(empty($cratesMeta))
            return null;

        $childrenCrates = [];
        $mainCrate = new self();
        foreach($cratesMeta as $meta)
        {
            if($uuid === $meta["uuid"])
            {
                $mainCrate->uuid = $meta["uuid"];
                $mainCrate->name = $meta["name"];
                $mainCrate->parent_uuid = $meta["parent_uuid"];
            }
            else
            {
              $childrenCrates[] = $meta['uuid'];
            }

        }

        // Get crate's items
        $stmt = $db->prepare("SELECT uuid, name, quantity FROM crate_item WHERE crate_id = :uuid");
        $stmt->bindValue(":uuid", $mainCrate->uuid, SQLITE3_TEXT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        foreach($items as $rawItem)
        {
            $item = new Item($rawItem["name"]);
            $item->uuid = $rawItem["uuid"];
            $item->setQuantity($rawItem["quantity"]);

            $mainCrate->items[] = $item;
        }

        // Get recursively children crates
        foreach($childrenCrates as $uuid)
        {
            $mainCrate->crates[] = Crate::get($uuid);
        }

        return $mainCrate;
    }

    /**
     * @return Crate[]
     */
    public static function all(): array
    {
        $db = DB::instance();

        // Gather crate
        $stmt = $db->prepare("SELECT uuid FROM crate WHERE parent_uuid IS NULL");
        $stmt->execute();
        $uuids = $stmt->fetchAll();

        $crates = [];
        foreach($uuids as $uuid)
        {
            $crates[] = Crate::get($uuid["uuid"]);
        }
        return $crates;
    }

    public function save(): bool
    {
        $db = DB::instance();

        try {

            $db->beginTransaction();
            $stmt = $db->prepare("INSERT OR REPLACE INTO crate (uuid, name, parent_uuid) 
                                        VALUES(:uuid,:name, :parent_uuid)");
            $stmt->bindValue(":uuid", substr($this->uuid, 0, 254), SQLITE3_TEXT);
            $stmt->bindValue(":name", substr($this->name, 0, 254), SQLITE3_TEXT);
            if($this->parent_uuid !== null)
                $stmt->bindValue(":parent_uuid", substr($this->parent_uuid, 0, 254), SQLITE3_TEXT);
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

    public static function exists(string $uuid): bool
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