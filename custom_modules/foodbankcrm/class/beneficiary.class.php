<?php
// htdocs/custom/foodbankcrm/class/beneficiary.class.php

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Beneficiary extends CommonObject
{
    public $element       = 'foodbank_beneficiary';
    public $table_element = 'foodbank_beneficiaries';
    public $picto         = 'user';

    public $id;
    public $ref;
    public $firstname;
    public $lastname;
    public $phone;
    public $email;
    public $address;
    public $note;
    public $entity;

    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = isset($conf->entity) ? $conf->entity : 1;
    }

    // EXACT SAME AS DONATIONFB
    public function create($user = null, $notrigger = false)
    {
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql.= "ref, firstname, lastname, phone, email, address, note, entity, datec";
        $sql.= ") VALUES (";
        $sql.= "'".$this->db->escape($this->ref)."', ";
        $sql.= "'".$this->db->escape($this->firstname)."', ";
        $sql.= "'".$this->db->escape($this->lastname)."', ";
        $sql.= "'".$this->db->escape($this->phone)."', ";
        $sql.= "'".$this->db->escape($this->email)."', ";
        $sql.= "'".$this->db->escape($this->address)."', ";
        $sql.= "'".$this->db->escape($this->note)."', ";
        $sql.= (int)$this->entity.", ";
        $sql.= "NOW()";
        $sql.= ")";

        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            $this->db->commit();
            return $this->id;
        }

        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".(int)$id;
        $res = $this->db->query($sql);
        if ($res && ($o = $this->db->fetch_object($res))) {
            $this->id        = (int)$o->rowid;
            $this->ref       = $o->ref;
            $this->firstname = $o->firstname;
            $this->lastname  = $o->lastname;
            $this->phone     = $o->phone;
            $this->email     = $o->email;
            $this->address   = $o->address;
            $this->note      = $o->note;
            $this->entity    = (int)$o->entity;
            return 1;
        }
        return 0;
    }

    public function update($user = null, $notrigger = false)
    {
        if (empty($this->id)) return -1;

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ";
        $sql.= "ref = '".$this->db->escape($this->ref)."', ";
        $sql.= "firstname = '".$this->db->escape($this->firstname)."', ";
        $sql.= "lastname = '".$this->db->escape($this->lastname)."', ";
        $sql.= "phone = '".$this->db->escape($this->phone)."', ";
        $sql.= "email = '".$this->db->escape($this->email)."', ";
        $sql.= "address = '".$this->db->escape($this->address)."', ";
        $sql.= "note = '".$this->db->escape($this->note)."', ";
        $sql.= "tms = NOW() ";
        $sql.= "WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        }

        $this->error = $this->db->lasterror();
        $this->db->rollback();
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

    public function getNextRef()
    {
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, 9) AS UNSIGNED)) as lastnum";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE ref LIKE 'BEN".date('Y')."%' AND entity = ".(int)$this->entity;

        $resql = $this->db->query($sql);
        if ($resql && ($obj = $this->db->fetch_object($resql))) {
            $next = ($obj->lastnum ? $obj->lastnum + 1 : 1);
        } else {
            $next = 1;
        }
        return 'BEN'.date('Y').'-'.sprintf('%04d', $next);
    }
}