<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Beneficiary extends CommonObject
{
    public $element = 'foodbank_beneficiary';
    public $table_element = 'foodbank_beneficiaries';
    public $picto = 'users';

    public $id;
    public $ref;
    public $firstname;
    public $lastname;
    public $email;
    public $phone;
    public $address;
    
    // NEW FIELDS
    public $household_size; 
    public $subscription_type;
    public $subscription_status;
    public $subscription_start_date;
    public $subscription_end_date;
    public $subscription_fee;
    
    public $note;
    public $entity;
    public $date_creation;

    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = isset($GLOBALS['conf']->entity) ? (int) $GLOBALS['conf']->entity : 1;
        $this->household_size = 1;
        $this->subscription_status = 'Pending';
    }

    public function create($user = null, $notrigger = false)
    {
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= "ref, firstname, lastname, email, phone, address, household_size, note, ";
        $sql .= "subscription_type, subscription_status, subscription_start_date, subscription_end_date, subscription_fee, ";
        $sql .= "entity, datec";
        $sql .= ") VALUES (";
        $sql .= "'" . $this->db->escape($this->ref) . "',";
        $sql .= "'" . $this->db->escape($this->firstname) . "',";
        $sql .= "'" . $this->db->escape($this->lastname) . "',";
        $sql .= "'" . $this->db->escape($this->email) . "',";
        $sql .= "'" . $this->db->escape($this->phone) . "',";
        $sql .= "'" . $this->db->escape($this->address) . "',";
        $sql .= (int)$this->household_size . ",";
        $sql .= "'" . $this->db->escape($this->note) . "',";
        
        // Subscription Values
        $sql .= ($this->subscription_type ? "'" . $this->db->escape($this->subscription_type) . "'" : "NULL") . ",";
        $sql .= "'" . $this->db->escape($this->subscription_status) . "',";
        $sql .= ($this->subscription_start_date ? "'" . $this->db->escape($this->subscription_start_date) . "'" : "NULL") . ",";
        $sql .= ($this->subscription_end_date ? "'" . $this->db->escape($this->subscription_end_date) . "'" : "NULL") . ",";
        $sql .= (float)$this->subscription_fee . ",";
        
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
            $this->firstname = $obj->firstname;
            $this->lastname = $obj->lastname;
            $this->email = $obj->email;
            $this->phone = $obj->phone;
            $this->address = $obj->address;
            $this->household_size = $obj->household_size;
            $this->note = $obj->note;
            
            // Fetch Subscription Fields
            $this->subscription_type = $obj->subscription_type;
            $this->subscription_status = $obj->subscription_status;
            $this->subscription_start_date = $obj->subscription_start_date;
            $this->subscription_end_date = $obj->subscription_end_date;
            $this->subscription_fee = $obj->subscription_fee;
            
            $this->date_creation = $obj->datec;
            return 1;
        }
        return 0;
    }

    public function update($user = null, $notrigger = false)
    {
        $this->db->begin();
        
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET ";
        $sql .= "firstname = '" . $this->db->escape($this->firstname) . "',";
        $sql .= "lastname = '" . $this->db->escape($this->lastname) . "',";
        $sql .= "email = '" . $this->db->escape($this->email) . "',";
        $sql .= "phone = '" . $this->db->escape($this->phone) . "',";
        $sql .= "address = '" . $this->db->escape($this->address) . "',";
        $sql .= "household_size = " . (int)$this->household_size . ",";
        $sql .= "note = '" . $this->db->escape($this->note) . "',";
        
        // Update Subscription Fields
        $sql .= "subscription_type = " . ($this->subscription_type ? "'" . $this->db->escape($this->subscription_type) . "'" : "NULL") . ",";
        $sql .= "subscription_status = '" . $this->db->escape($this->subscription_status) . "',";
        $sql .= "subscription_fee = " . (float)$this->subscription_fee;
        
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
        // Simple sequential numbering BEN2025-0001
        $sql = "SELECT MAX(rowid) as maxid FROM " . MAIN_DB_PREFIX . $this->table_element;
        $res = $this->db->query($sql);
        $obj = $this->db->fetch_object($res);
        $nextId = ($obj->maxid ?? 0) + 1;
        return 'BEN' . date('Y') . '-' . sprintf('%04d', $nextId);
    }
}
?>