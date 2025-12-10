<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class DonationFB extends CommonObject
{
    public $element = 'foodbank_donation';
    public $table_element = 'foodbank_donations';
    public $picto = 'gift';

    public $id;
    public $ref;
    public $product_name;
    public $category;
    public $label;
    public $quantity;
    public $quantity_allocated;
    public $unit;
    public $unit_price;
    public $total_value;
    public $fk_vendor;
    public $fk_warehouse;
    public $fk_beneficiary;
    public $delivery_method;
    public $note;
    public $status;
    public $date_donation;
    public $date_creation;
    public $entity;

    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = isset($GLOBALS['conf']->entity) ? (int) $GLOBALS['conf']->entity : 1;
        $this->status = 'Pending';
    }

    public function create($user = null, $notrigger = false)
    {
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }
        // Sync label with product name for compatibility
        $this->label = $this->product_name;

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= "ref, product_name, category, label, quantity, quantity_allocated, unit, unit_price, total_value, ";
        $sql .= "fk_vendor, fk_warehouse, fk_beneficiary, delivery_method, note, status, date_donation, entity, datec";
        $sql .= ") VALUES (";
        $sql .= "'" . $this->db->escape($this->ref) . "',";
        $sql .= "'" . $this->db->escape($this->product_name) . "',";
        $sql .= "'" . $this->db->escape($this->category) . "',";
        $sql .= "'" . $this->db->escape($this->label) . "',";
        $sql .= (int)$this->quantity . ",";
        $sql .= "0,"; // Initial allocated is 0
        $sql .= "'" . $this->db->escape($this->unit) . "',";
        $sql .= ($this->unit_price != '' ? "'".$this->db->escape($this->unit_price)."'" : "NULL").",";
        $sql .= ($this->total_value != '' ? "'".$this->db->escape($this->total_value)."'" : "NULL").",";
        $sql .= ($this->fk_vendor > 0 ? (int)$this->fk_vendor : "NULL") . ",";
        $sql .= ($this->fk_warehouse > 0 ? (int)$this->fk_warehouse : "NULL") . ",";
        $sql .= "NULL,";
        $sql .= "'" . $this->db->escape($this->delivery_method) . "',";
        $sql .= "'" . $this->db->escape($this->note) . "',";
        $sql .= "'" . $this->db->escape($this->status) . "',";
        $sql .= "'" . $this->db->escape($this->date_donation) . "',";
        $sql .= (int)$this->entity . ",";
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
            $this->ref = $obj->ref;
            $this->product_name = $obj->product_name;
            $this->category = $obj->category;
            $this->label = $obj->label;
            $this->quantity = $obj->quantity;
            $this->quantity_allocated = $obj->quantity_allocated;
            $this->unit = $obj->unit;
            $this->unit_price = $obj->unit_price;
            $this->total_value = $obj->total_value;
            $this->fk_vendor = $obj->fk_vendor;
            $this->fk_warehouse = $obj->fk_warehouse;
            $this->delivery_method = $obj->delivery_method;
            $this->note = $obj->note;
            $this->status = $obj->status;
            $this->date_donation = $obj->date_donation;
            return 1;
        }
        return 0;
    }

    public function update($user = null, $notrigger = false)
    {
        // FORCE SYNC: Make sure label matches product name so list views work
        $this->label = $this->product_name;

        $this->db->begin();
        
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET ";
        $sql .= "product_name = '" . $this->db->escape($this->product_name) . "',";
        $sql .= "category = '" . $this->db->escape($this->category) . "',";
        $sql .= "label = '" . $this->db->escape($this->label) . "',";
        $sql .= "quantity = " . (int)$this->quantity . ",";
        $sql .= "unit = '" . $this->db->escape($this->unit) . "',";
        $sql .= "fk_vendor = " . ($this->fk_vendor > 0 ? (int)$this->fk_vendor : "NULL") . ",";
        $sql .= "fk_warehouse = " . ($this->fk_warehouse > 0 ? (int)$this->fk_warehouse : "NULL") . ",";
        $sql .= "note = '" . $this->db->escape($this->note) . "',";
        $sql .= "status = '" . $this->db->escape($this->status) . "'";
        $sql .= " WHERE rowid = " . (int)$this->id;

        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    public function delete($user = null, $notrigger = false)
    {
        if ($this->quantity_allocated > 0) {
            $this->error = "Cannot delete: Stock has already been allocated.";
            return -1;
        }
        $this->db->begin();
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=" . (int)$this->id;
        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        }
        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM " . MAIN_DB_PREFIX . $this->table_element;
        $res = $this->db->query($sql);
        $obj = $this->db->fetch_object($res);
        $nextId = ($obj->maxid ?? 0) + 1;
        return 'DON' . date('Y') . '-' . sprintf('%04d', $nextId);
    }
}
?>