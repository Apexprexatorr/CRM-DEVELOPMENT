<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Warehouse extends CommonObject
{
    public $element = 'warehouse';
    public $table_element = 'foodbank_warehouses';
    
    public $id;
    public $ref;
    public $label;
    public $address;
    public $capacity;
    public $note;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user)
    {
        // Auto-generate ref if not provided
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "foodbank_warehouses (";
        $sql .= "ref, label, address, capacity, entity, datec";
        $sql .= ") VALUES (";
        $sql .= "'" . $this->db->escape($this->ref) . "',";
        $sql .= "'" . $this->db->escape($this->label) . "',";
        $sql .= "'" . $this->db->escape($this->address) . "',";
        $sql .= (int)$this->capacity . ",";
        $sql .= "1,"; // entity
        $sql .= "NOW()";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "foodbank_warehouses");
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_warehouses WHERE rowid = " . (int) $id;
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->label = $obj->label;
                $this->address = $obj->address;
                $this->capacity = $obj->capacity;
                $this->note = $obj->note;
                return 1;
            }
        }
        return -1;
    }

    public function update($user)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "foodbank_warehouses SET ";
        $sql .= "ref = '" . $this->db->escape($this->ref) . "',";
        $sql .= "label = '" . $this->db->escape($this->label) . "',";
        $sql .= "address = '" . $this->db->escape($this->address) . "',";
        $sql .= "capacity = " . (int)$this->capacity;
        $sql .= " WHERE rowid = " . (int) $this->id;

        if ($this->db->query($sql)) {
            return 1;
        }
        return -1;
    }

    public function delete($user)
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "foodbank_warehouses WHERE rowid = " . (int) $this->id;
        if ($this->db->query($sql)) {
            return 1;
        }
        return -1;
    }

    /**
     * Auto-generate next warehouse reference
     * Format: WAR2025-0001
     */
    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM ".MAIN_DB_PREFIX."foodbank_warehouses";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $next = sprintf("WAR%s-%04d", date("Y"), ($obj->maxid ?? 0) + 1);
            return $next;
        }
        return "WAR".date("Y")."-".sprintf("%04d", rand(1, 9999));
    }
}