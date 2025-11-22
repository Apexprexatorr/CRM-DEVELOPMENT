<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class DistributionLine extends CommonObject
{
    public $element = 'distributionline';
    public $table_element = 'foodbank_distribution_lines';
    
    public $id;
    public $fk_distribution;
    public $fk_donation;
    public $product_name;
    public $quantity;
    public $unit;
    public $note;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user = null)
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distribution_lines (";
        $sql .= "fk_distribution, fk_donation, product_name, quantity, unit, note";
        $sql .= ") VALUES (";
        $sql .= (int)$this->fk_distribution.",";
        $sql .= (int)$this->fk_donation.",";
        $sql .= "'".$this->db->escape($this->product_name)."',";
        $sql .= (float)$this->quantity.",";
        $sql .= "'".$this->db->escape($this->unit)."',";
        $sql .= "'".$this->db->escape($this->note)."'";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."foodbank_distribution_lines");
            
            // Update donation allocated quantity
            $this->updateDonationAllocation($this->fk_donation, $this->quantity, 'add');
            
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE rowid = ".(int)$id;
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->fk_distribution = $obj->fk_distribution;
                $this->fk_donation = $obj->fk_donation;
                $this->product_name = $obj->product_name;
                $this->quantity = $obj->quantity;
                $this->unit = $obj->unit;
                $this->note = $obj->note;
                return 1;
            }
        }
        return -1;
    }

    public function update($user = null)
    {
        // Get old quantity first
        $old_qty = 0;
        $sql = "SELECT quantity FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE rowid = ".(int)$this->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $old_qty = $obj->quantity;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_distribution_lines SET ";
        $sql .= "product_name = '".$this->db->escape($this->product_name)."',";
        $sql .= "quantity = ".(float)$this->quantity.",";
        $sql .= "unit = '".$this->db->escape($this->unit)."',";
        $sql .= "note = '".$this->db->escape($this->note)."'";
        $sql .= " WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            // Update donation allocation (remove old, add new)
            $diff = $this->quantity - $old_qty;
            if ($diff != 0) {
                $this->updateDonationAllocation($this->fk_donation, abs($diff), $diff > 0 ? 'add' : 'remove');
            }
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    public function delete($user = null)
    {
        // Update donation allocated quantity
        $this->updateDonationAllocation($this->fk_donation, $this->quantity, 'remove');

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE rowid = ".(int)$this->id;
        if ($this->db->query($sql)) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Update the allocated quantity in donations table
     */
    private function updateDonationAllocation($fk_donation, $quantity, $action)
    {
        if ($action == 'add') {
            $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_donations 
                    SET quantity_allocated = quantity_allocated + ".(float)$quantity." 
                    WHERE rowid = ".(int)$fk_donation;
        } else {
            $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_donations 
                    SET quantity_allocated = quantity_allocated - ".(float)$quantity." 
                    WHERE rowid = ".(int)$fk_donation;
        }
        $this->db->query($sql);
    }

    /**
     * Get all lines for a specific distribution
     */
    public static function getAllByDistribution($db, $fk_distribution)
    {
        $lines = array();
        
        $sql = "SELECT dl.*, d.ref as donation_ref, d.label as donation_label, v.name as vendor_name
                FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines dl
                LEFT JOIN ".MAIN_DB_PREFIX."foodbank_donations d ON dl.fk_donation = d.rowid
                LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
                WHERE dl.fk_distribution = ".(int)$fk_distribution." 
                ORDER BY dl.product_name ASC";
        
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $line = new DistributionLine($db);
                $line->id = $obj->rowid;
                $line->fk_distribution = $obj->fk_distribution;
                $line->fk_donation = $obj->fk_donation;
                $line->product_name = $obj->product_name;
                $line->quantity = $obj->quantity;
                $line->unit = $obj->unit;
                $line->note = $obj->note;
                $line->donation_ref = $obj->donation_ref;
                $line->donation_label = $obj->donation_label;
                $line->vendor_name = $obj->vendor_name;
                $lines[] = $line;
            }
        }
        
        return $lines;
    }
}