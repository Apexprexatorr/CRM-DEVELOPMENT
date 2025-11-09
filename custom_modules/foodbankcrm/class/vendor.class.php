<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Vendor extends CommonObject
{
    public $element = 'vendor';
    public $table_element = 'foodbank_vendors';
    
    public $id;
    public $ref;
    public $name;
    public $contact_person;
    public $phone;
    public $email;
    public $address;
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

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "foodbank_vendors (";
        $sql .= "ref, name, contact_person, phone, email, address, note";
        $sql .= ") VALUES (";
        $sql .= "'" . $this->db->escape($this->ref) . "',";
        $sql .= "'" . $this->db->escape($this->name) . "',";
        $sql .= "'" . $this->db->escape($this->contact_person) . "',";
        $sql .= "'" . $this->db->escape($this->phone) . "',";
        $sql .= "'" . $this->db->escape($this->email) . "',";
        $sql .= "'" . $this->db->escape($this->address) . "',";
        $sql .= "'" . $this->db->escape($this->note) . "'";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "foodbank_vendors");
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_vendors WHERE rowid = " . (int) $id;
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->name = $obj->name;
                $this->contact_person = $obj->contact_person;
                $this->phone = $obj->phone;
                $this->email = $obj->email;
                $this->address = $obj->address;
                $this->note = $obj->note;
                return 1;
            }
        }
        return -1;
    }

    public function update($user)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "foodbank_vendors SET ";
        $sql .= "ref = '" . $this->db->escape($this->ref) . "',";
        $sql .= "name = '" . $this->db->escape($this->name) . "',";
        $sql .= "contact_person = '" . $this->db->escape($this->contact_person) . "',";
        $sql .= "phone = '" . $this->db->escape($this->phone) . "',";
        $sql .= "email = '" . $this->db->escape($this->email) . "',";
        $sql .= "address = '" . $this->db->escape($this->address) . "',";
        $sql .= "note = '" . $this->db->escape($this->note) . "'";
        $sql .= " WHERE rowid = " . (int) $this->id;

        if ($this->db->query($sql)) {
            return 1;
        }
        return -1;
    }

public function delete($user = null, $notrigger = false)
{
    if (empty($this->id)) return -1;

    $this->db->begin();
    $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".(int)$this->id;

    if ($this->db->query($sql)) {
        $this->db->commit();
        return 1;
    }

    $this->error = $this->db->lasterror();
    $this->db->rollback();
    return -1;
}

    /**
     * Auto-generate next vendor reference
     * Format: VEN2025-0001
     */
    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM ".MAIN_DB_PREFIX."foodbank_vendors";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $next = sprintf("VEN%s-%04d", date("Y"), ($obj->maxid ?? 0) + 1);
            return $next;
        }
        return "VEN".date("Y")."-".sprintf("%04d", rand(1, 9999));
    }
}