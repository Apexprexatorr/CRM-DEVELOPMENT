<?php
// /custom/foodbankcrm/class/donation.class.php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class DonationFB extends CommonObject
{
    public $element       = 'foodbank_donation';
    public $table_element = 'foodbank_donations';
    public $picto         = 'generic';

    public $id;
    public $ref;
    public $fk_vendor;        // llx_foodbank_vendors.rowid
    public $fk_beneficiary;   // llx_foodbank_beneficiaries.rowid
    public $label;
    public $quantity;
    public $unit;
    public $date_donation;    // timestamp/datetime
    public $note;
    public $entity;

    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = isset($GLOBALS['conf']->entity) ? (int) $GLOBALS['conf']->entity : 1;
    }

    public function create($user = null, $notrigger = false)
    {
        // Auto-generate ref if not provided
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $this->db->begin();
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(".
               "ref, fk_vendor, fk_beneficiary, label, quantity, unit, date_donation, entity, note, datec".
               ") VALUES (".
               "'".$this->db->escape($this->ref)."',".
               (int)$this->fk_vendor.",".
               (int)$this->fk_beneficiary.",".
               "'".$this->db->escape($this->label)."',".
               (int)$this->quantity.",".
               "'".$this->db->escape($this->unit)."',".
               ($this->date_donation ? "'".$this->db->escape($this->date_donation)."'" : "NOW()").",".
               (int)$this->entity.",".
               "'".$this->db->escape($this->note)."',".
               "NOW()".
               ")";
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
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid=".(int)$id;
        $res = $this->db->query($sql);
        if ($res && ($o=$this->db->fetch_object($res))) {
            $this->id            = (int)$o->rowid;
            $this->ref           = $o->ref;
            $this->fk_vendor     = (int)$o->fk_vendor;
            $this->fk_beneficiary= (int)$o->fk_beneficiary;
            $this->label         = $o->label;
            $this->quantity      = (int)$o->quantity;
            $this->unit          = $o->unit;
            $this->date_donation = $o->date_donation;
            $this->note          = $o->note;
            $this->entity        = (int)$o->entity;
            return 1;
        }
        return 0;
    }

    public function update($user = null, $notrigger = false)
    {
        if (empty($this->id)) { $this->error = 'Missing id'; return -1; }
        $this->db->begin();
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ".
               "ref='".$this->db->escape($this->ref)."',".
               "fk_vendor=".(int)$this->fk_vendor.",".
               "fk_beneficiary=".(int)$this->fk_beneficiary.",".
               "label='".$this->db->escape($this->label)."',".
               "quantity=".(int)$this->quantity.",".
               "unit='".$this->db->escape($this->unit)."',".
               "date_donation=".($this->date_donation ? "'".$this->db->escape($this->date_donation)."'" : "date_donation").",".
               "note='".$this->db->escape($this->note)."',".
               "tms=NOW() ".
               "WHERE rowid=".(int)$this->id;
        if ($this->db->query($sql)) { $this->db->commit(); return 1; }
        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    public function delete($user = null, $notrigger = false)
    {
        if (empty($this->id)) { $this->error = 'Missing id'; return -1; }
        $this->db->begin();
        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid=".(int)$this->id;
        if ($this->db->query($sql)) { $this->db->commit(); return 1; }
        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    /**
     * Auto-generate next donation reference
     * Format: DON2025-0001
     */
    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM ".MAIN_DB_PREFIX.$this->table_element;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $next = sprintf("DON%s-%04d", date("Y"), ($obj->maxid ?? 0) + 1);
            return $next;
        }
        // Fallback if query fails
        return "DON".date("Y")."-".sprintf("%04d", rand(1, 9999));
    }
}