<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class VendorProduct extends CommonObject
{
    public $element = 'vendorproduct';
    public $table_element = 'foodbank_vendor_products';
    
    public $id;
    public $fk_vendor;
    public $product_name;
    public $unit;
    public $typical_quantity;
    public $note;
    public $status;

    public function __construct($db)
    {
        $this->db = $db;
        $this->status = 'Active';
    }

    /**
     * Create vendor product in database
     */
    public function create($user = null)
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_vendor_products (";
        $sql .= "fk_vendor, product_name, unit, typical_quantity, note, status";
        $sql .= ") VALUES (";
        $sql .= (int)$this->fk_vendor.",";
        $sql .= "'".$this->db->escape($this->product_name)."',";
        $sql .= "'".$this->db->escape($this->unit)."',";
        $sql .= ($this->typical_quantity ? (float)$this->typical_quantity : "NULL").",";
        $sql .= "'".$this->db->escape($this->note)."',";
        $sql .= "'".$this->db->escape($this->status)."'";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."foodbank_vendor_products");
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Fetch vendor product from database
     */
    public function fetch($id)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendor_products WHERE rowid = ".(int)$id;
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->fk_vendor = $obj->fk_vendor;
                $this->product_name = $obj->product_name;
                $this->unit = $obj->unit;
                $this->typical_quantity = $obj->typical_quantity;
                $this->note = $obj->note;
                $this->status = $obj->status;
                return 1;
            }
        }
        return -1;
    }

    /**
     * Update vendor product in database
     */
    public function update($user = null)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_vendor_products SET ";
        $sql .= "product_name = '".$this->db->escape($this->product_name)."',";
        $sql .= "unit = '".$this->db->escape($this->unit)."',";
        $sql .= "typical_quantity = ".($this->typical_quantity ? (float)$this->typical_quantity : "NULL").",";
        $sql .= "note = '".$this->db->escape($this->note)."',";
        $sql .= "status = '".$this->db->escape($this->status)."'";
        $sql .= " WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Delete vendor product from database
     */
    public function delete($user = null)
    {
        // Check if product is used in any donations
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_donations 
                WHERE fk_vendor_product = ".(int)$this->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->count > 0) {
                $this->error = "Cannot delete: This product is used in ".$obj->count." donation(s)";
                return -2; // Special code for "blocked by foreign key"
            }
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_vendor_products WHERE rowid = ".(int)$this->id;
        if ($this->db->query($sql)) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Get all products for a specific vendor
     */
    public static function getAllByVendor($db, $fk_vendor)
    {
        $products = array();
        
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendor_products 
                WHERE fk_vendor = ".(int)$fk_vendor." 
                ORDER BY product_name ASC";
        
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $product = new VendorProduct($db);
                $product->id = $obj->rowid;
                $product->fk_vendor = $obj->fk_vendor;
                $product->product_name = $obj->product_name;
                $product->unit = $obj->unit;
                $product->typical_quantity = $obj->typical_quantity;
                $product->note = $obj->note;
                $product->status = $obj->status;
                $products[] = $product;
            }
        }
        
        return $products;
    }

    /**
     * Get all active products for a vendor (for dropdowns)
     */
    public static function getActiveByVendor($db, $fk_vendor)
    {
        $products = array();
        
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_vendor_products 
                WHERE fk_vendor = ".(int)$fk_vendor." 
                AND status = 'Active'
                ORDER BY product_name ASC";
        
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $products[$obj->rowid] = $obj->product_name.' ('.$obj->typical_quantity.' '.$obj->unit.')';
            }
        }
        
        return $products;
    }
}