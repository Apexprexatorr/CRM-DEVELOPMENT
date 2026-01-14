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
    
    // NEW EXTENDED FIELDS
    public $gender;
    public $dob;
    public $city;
    public $state;
    public $family_size;        // Matches 'family_size' in DB
    public $household_size;     // Matches 'household_size' in DB (Keeping for compatibility)
    public $employment_status;
    public $identification_number;

    // SUBSCRIPTION FIELDS
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
        $this->family_size = 1;
        $this->subscription_status = 'Pending';
    }

    // CREATE
    public function create($user = null, $notrigger = false)
    {
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        // Standard info
        $sql .= "ref, firstname, lastname, email, phone, address, city, state, note, ";
        // Extended info
        $sql .= "gender, dob, identification_number, family_size, employment_status, ";
        // Subscription info
        $sql .= "subscription_type, subscription_status, subscription_start_date, subscription_end_date, subscription_fee, ";
        // System info
        $sql .= "entity, datec";
        $sql .= ") VALUES (";
        
        $sql .= "'" . $this->db->escape($this->ref) . "',";
        $sql .= "'" . $this->db->escape($this->firstname) . "',";
        $sql .= "'" . $this->db->escape($this->lastname) . "',";
        $sql .= "'" . $this->db->escape($this->email) . "',";
        $sql .= "'" . $this->db->escape($this->phone) . "',";
        $sql .= "'" . $this->db->escape($this->address) . "',";
        $sql .= "'" . $this->db->escape($this->city) . "',";
        $sql .= "'" . $this->db->escape($this->state) . "',";
        $sql .= "'" . $this->db->escape($this->note) . "',";

        // Extended Values
        $sql .= "'" . $this->db->escape($this->gender) . "',";
        $sql .= ($this->dob ? "'" . $this->db->escape($this->dob) . "'" : "NULL") . ",";
        $sql .= "'" . $this->db->escape($this->identification_number) . "',";
        $sql .= (int)$this->family_size . ",";
        $sql .= "'" . $this->db->escape($this->employment_status) . "',";
        
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

    // FETCH
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
            $this->city = $obj->city;
            $this->state = $obj->state;
            $this->note = $obj->note;
            
            // Extended
            $this->gender = $obj->gender;
            $this->dob = $obj->dob;
            $this->identification_number = $obj->identification_number;
            $this->family_size = $obj->family_size;
            $this->employment_status = $obj->employment_status;
            
            // Subscription
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

    // UPDATE
    public function update($user = null, $notrigger = false)
    {
        $this->db->begin();
        
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET ";
        $sql .= "firstname = '" . $this->db->escape($this->firstname) . "',";
        $sql .= "lastname = '" . $this->db->escape($this->lastname) . "',";
        $sql .= "email = '" . $this->db->escape($this->email) . "',";
        $sql .= "phone = '" . $this->db->escape($this->phone) . "',";
        $sql .= "address = '" . $this->db->escape($this->address) . "',";
        $sql .= "city = '" . $this->db->escape($this->city) . "',";
        $sql .= "state = '" . $this->db->escape($this->state) . "',";
        $sql .= "note = '" . $this->db->escape($this->note) . "',";

        // Extended
        $sql .= "gender = '" . $this->db->escape($this->gender) . "',";
        $sql .= "dob = " . ($this->dob ? "'" . $this->db->escape($this->dob) . "'" : "NULL") . ",";
        $sql .= "identification_number = '" . $this->db->escape($this->identification_number) . "',";
        $sql .= "family_size = " . (int)$this->family_size . ",";
        $sql .= "employment_status = '" . $this->db->escape($this->employment_status) . "',";
        
        // Subscription
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
        $sql = "SELECT MAX(rowid) as maxid FROM " . MAIN_DB_PREFIX . $this->table_element;
        $res = $this->db->query($sql);
        $obj = $this->db->fetch_object($res);
        $nextId = ($obj->maxid ?? 0) + 1;
        return 'BEN' . date('Y') . '-' . sprintf('%04d', $nextId);
    }
}
?>