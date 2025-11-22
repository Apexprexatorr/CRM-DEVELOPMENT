<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class PackageItem extends CommonObject
{
    public $element = 'packageitem';
    public $table_element = 'foodbank_package_items';
    
    public $id;
    public $fk_package;
    public $product_name;
    public $quantity;
    public $unit;
    public $fk_vendor_preferred;
    public $note;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user = null)
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_package_items (";
        $sql .= "fk_package, product_name, quantity, unit, fk_vendor_preferred, note";
        $sql .= ") VALUES (";
        $sql .= (int)$this->fk_package.",";
        $sql .= "'".$this->db->escape($this->product_name)."',";
        $sql .= (float)$this->quantity.",";
        $sql .= "'".$this->db->escape($this->unit)."',";
        $sql .= ($this->fk_vendor_preferred ? (int)$this->fk_vendor_preferred : "NULL").",";
        $sql .= "'".$this->db->escape($this->note)."'";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."foodbank_package_items");
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE rowid = ".(int)$id;
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->fk_package = $obj->fk_package;
                $this->product_name = $obj->product_name;
                $this->quantity = $obj->quantity;
                $this->unit = $obj->unit;
                $this->fk_vendor_preferred = $obj->fk_vendor_preferred;
                $this->note = $obj->note;
                return 1;
            }
        }
        return -1;
    }

    public function update($user = null)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_package_items SET ";
        $sql .= "product_name = '".$this->db->escape($this->product_name)."',";
        $sql .= "quantity = ".(float)$this->quantity.",";
        $sql .= "unit = '".$this->db->escape($this->unit)."',";
        $sql .= "fk_vendor_preferred = ".($this->fk_vendor_preferred ? (int)$this->fk_vendor_preferred : "NULL").",";
        $sql .= "note = '".$this->db->escape($this->note)."'";
        $sql .= " WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    public function delete($user = null)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE rowid = ".(int)$this->id;
        if ($this->db->query($sql)) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Get all items for a specific package
     */
    public static function getAllByPackage($db, $fk_package)
    {
        $items = array();
        
        $sql = "SELECT pi.*, v.name as vendor_name 
                FROM ".MAIN_DB_PREFIX."foodbank_package_items pi
                LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON pi.fk_vendor_preferred = v.rowid
                WHERE pi.fk_package = ".(int)$fk_package." 
                ORDER BY pi.product_name ASC";
        
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $item = new PackageItem($db);
                $item->id = $obj->rowid;
                $item->fk_package = $obj->fk_package;
                $item->product_name = $obj->product_name;
                $item->quantity = $obj->quantity;
                $item->unit = $obj->unit;
                $item->fk_vendor_preferred = $obj->fk_vendor_preferred;
                $item->vendor_name = $obj->vendor_name;
                $item->note = $obj->note;
                $items[] = $item;
            }
        }
        
        return $items;
    }
}