<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Package extends CommonObject
{
    public $element = 'package';
    public $table_element = 'foodbank_packages';
    
    public $id;
    public $ref;
    public $name;
    public $description;
    public $status;
    public $entity;

    public function __construct($db)
    {
        $this->db = $db;
        $this->status = 'Active';
        $this->entity = 1;
    }

    public function create($user = null)
    {
        // Auto-generate ref if not provided
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_packages (";
        $sql .= "ref, name, description, status, entity";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= "'".$this->db->escape($this->name)."',";
        $sql .= "'".$this->db->escape($this->description)."',";
        $sql .= "'".$this->db->escape($this->status)."',";
        $sql .= (int)$this->entity;
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."foodbank_packages");
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE rowid = ".(int)$id;
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->name = $obj->name;
                $this->description = $obj->description;
                $this->status = $obj->status;
                $this->entity = $obj->entity;
                return 1;
            }
        }
        return -1;
    }

    public function update($user = null)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_packages SET ";
        $sql .= "name = '".$this->db->escape($this->name)."',";
        $sql .= "description = '".$this->db->escape($this->description)."',";
        $sql .= "status = '".$this->db->escape($this->status)."'";
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
        // Check if package is used in distributions
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_distributions 
                WHERE fk_package = ".(int)$this->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->count > 0) {
                $this->error = "Cannot delete: This package is used in ".$obj->count." distribution(s)";
                return -2;
            }
        }

        // Delete package items first
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE fk_package = ".(int)$this->id;
        $this->db->query($sql);

        // Delete package
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_packages WHERE rowid = ".(int)$this->id;
        if ($this->db->query($sql)) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Auto-generate next package reference
     * Format: PKG2025-0001
     */
    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM ".MAIN_DB_PREFIX."foodbank_packages";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $next = sprintf("PKG%s-%04d", date("Y"), ($obj->maxid ?? 0) + 1);
            return $next;
        }
        return "PKG".date("Y")."-".sprintf("%04d", rand(1, 9999));
    }

    /**
     * Get item count for this package
     */
    public function getItemCount()
    {
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_package_items 
                WHERE fk_package = ".(int)$this->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return $obj->count;
        }
        return 0;
    }
}