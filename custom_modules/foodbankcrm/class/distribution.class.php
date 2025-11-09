<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Distribution extends CommonObject
{
    public $element = 'distribution';
    public $table_element = 'foodbank_distributions';
    
    public $id;
    public $ref;
    public $fk_beneficiary;
    public $fk_warehouse;
    public $fk_user;
    public $date_distribution;
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

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "foodbank_distributions (";
        $sql .= "ref, fk_beneficiary, fk_warehouse, fk_user, note, entity, date_distribution";
        $sql .= ") VALUES (";
        $sql .= "'" . $this->db->escape($this->ref) . "',";
        $sql .= (int)$this->fk_beneficiary . ",";
        $sql .= (int)$this->fk_warehouse . ",";
        $sql .= ($this->fk_user ? (int)$this->fk_user : "NULL") . ",";
        $sql .= "'" . $this->db->escape($this->note) . "',";
        $sql .= "1,"; // entity
        $sql .= "NOW()";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "foodbank_distributions");
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

   public function fetch($id)
{
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_distributions WHERE rowid = " . (int) $id;
    $resql = $this->db->query($sql);
    
    if ($resql) {
        $obj = $this->db->fetch_object($resql);
        if ($obj) {
            $this->id               = $obj->rowid;
            $this->ref              = $obj->ref;
            $this->fk_beneficiary   = $obj->fk_beneficiary;
            $this->fk_warehouse     = $obj->fk_warehouse;
            $this->fk_user          = $obj->fk_user;
            $this->note             = $obj->note;
            $this->date_distribution = $obj->date_distribution;  // ← ADD THIS LINE
            return 1;
        }
    }
    return -1;
}

    public function update($user)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "foodbank_distributions SET ";
        $sql .= "ref = '" . $this->db->escape($this->ref) . "',";
        $sql .= "fk_beneficiary = " . (int)$this->fk_beneficiary . ",";
        $sql .= "fk_warehouse = " . (int)$this->fk_warehouse . ",";
        $sql .= "fk_user = " . ($this->fk_user ? (int)$this->fk_user : "NULL") . ",";
        $sql .= "note = '" . $this->db->escape($this->note) . "'";
        $sql .= " WHERE rowid = " . (int) $this->id;

        if ($this->db->query($sql)) {
            return 1;
        }
        return -1;
    }

public function delete($user = null) {
    if (empty($this->id)) return -1;
    
    $this->db->begin();
    
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE rowid = ".(int)$this->id;
    
    $resql = $this->db->query($sql);
    
    if ($resql && $this->db->affected_rows($this->db->db) > 0) {
        $this->db->commit();
        return 1;
    }
    
    $this->error = $this->db->lasterror();
    $this->db->rollback();
    
    if (strpos($this->error, 'foreign key constraint') !== false || 
        strpos($this->error, 'Cannot delete or update a parent row') !== false) {
        $this->error = "PROTECTED: Cannot delete distribution linked to beneficiary or warehouse";
    }
    
    return -1;
}
    /**
     * Auto-generate next distribution reference
     * Format: DIS2025-0001
     */
    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM ".MAIN_DB_PREFIX."foodbank_distributions";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $next = sprintf("DIS%s-%04d", date("Y"), ($obj->maxid ?? 0) + 1);
            return $next;
        }
        return "DIS".date("Y")."-".sprintf("%04d", rand(1, 9999));
    }
}