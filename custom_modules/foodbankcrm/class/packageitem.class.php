<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class PackageItem extends CommonObject
{
    public $element = 'foodbank_packageitem';
    public $table_element = 'foodbank_package_items';

    public $id;
    public $fk_package;
    public $product_name;
    public $quantity;
    public $unit;
    public $unit_price; // New
    public $fk_vendor_preferred;
    public $note;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user = null, $notrigger = false)
    {
        $this->db->begin();
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= "fk_package, product_name, quantity, unit, unit_price, fk_vendor_preferred, note, datec";
        $sql .= ") VALUES (";
        $sql .= (int)$this->fk_package . ",";
        $sql .= "'" . $this->db->escape($this->product_name) . "',";
        $sql .= (float)$this->quantity . ",";
        $sql .= "'" . $this->db->escape($this->unit) . "',";
        $sql .= (float)$this->unit_price . ",";
        $sql .= ($this->fk_vendor_preferred > 0 ? (int)$this->fk_vendor_preferred : "NULL") . ",";
        $sql .= "'" . $this->db->escape($this->note) . "',";
        $sql .= "NOW()";
        $sql .= ")";

        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
            $this->db->commit();
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=" . (int)$id;
        $res = $this->db->query($sql);
        if ($res && ($obj = $this->db->fetch_object($res))) {
            $this->id = $obj->rowid;
            $this->fk_package = $obj->fk_package;
            $this->product_name = $obj->product_name;
            $this->quantity = $obj->quantity;
            $this->unit = $obj->unit;
            $this->unit_price = $obj->unit_price;
            $this->fk_vendor_preferred = $obj->fk_vendor_preferred;
            $this->note = $obj->note;
            return 1;
        }
        return 0;
    }

    public function update($user = null, $notrigger = false)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET ";
        $sql .= "product_name='" . $this->db->escape($this->product_name) . "',";
        $sql .= "quantity=" . (float)$this->quantity . ",";
        $sql .= "unit='" . $this->db->escape($this->unit) . "',";
        $sql .= "unit_price=" . (float)$this->unit_price . ",";
        $sql .= "fk_vendor_preferred=" . ($this->fk_vendor_preferred > 0 ? (int)$this->fk_vendor_preferred : "NULL") . ",";
        $sql .= "note='" . $this->db->escape($this->note) . "'";
        $sql .= " WHERE rowid=" . (int)$this->id;

        return $this->db->query($sql) ? 1 : -1;
    }

    public function delete($user = null, $notrigger = false)
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=" . (int)$this->id;
        return $this->db->query($sql) ? 1 : -1;
    }

    // Static helper to get all items for a package
    public static function getAllByPackage($db, $package_id) {
        $items = [];
        $sql = "SELECT i.*, v.name as vendor_name 
                FROM ".MAIN_DB_PREFIX."foodbank_package_items i
                LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON i.fk_vendor_preferred = v.rowid
                WHERE i.fk_package = ".(int)$package_id;
        $res = $db->query($sql);
        if ($res) {
            while ($obj = $db->fetch_object($res)) {
                $items[] = $obj;
            }
        }
        return $items;
    }
}
?>