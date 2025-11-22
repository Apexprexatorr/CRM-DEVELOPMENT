<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Distribution extends CommonObject
{
    public $element = 'distribution';
    public $table_element = 'foodbank_distributions';
    
    public $id;
    public $ref;
    public $fk_beneficiary;
    public $fk_package;
    public $fk_warehouse;
    public $fk_user;
    public $date_distribution;
    public $note;
    public $status;

    public function __construct($db)
    {
        $this->db = $db;
        $this->status = 'Prepared';
    }

    public function create($user = null)
    {
        // Auto-generate ref if not provided
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distributions (";
        $sql .= "ref, fk_beneficiary, fk_package, fk_warehouse, fk_user, note, status, entity, date_distribution";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= (int)$this->fk_beneficiary.",";
        $sql .= ($this->fk_package ? (int)$this->fk_package : "NULL").",";
        $sql .= (int)$this->fk_warehouse.",";
        $sql .= ($this->fk_user ? (int)$this->fk_user : "NULL").",";
        $sql .= "'".$this->db->escape($this->note)."',";
        $sql .= "'".$this->db->escape($this->status)."',";
        $sql .= "1,"; // entity
        $sql .= "NOW()";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."foodbank_distributions");
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE rowid = ".(int)$id;
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->fk_beneficiary = $obj->fk_beneficiary;
                $this->fk_package = $obj->fk_package;
                $this->fk_warehouse = $obj->fk_warehouse;
                $this->fk_user = $obj->fk_user;
                $this->date_distribution = $obj->date_distribution;
                $this->note = $obj->note;
                $this->status = $obj->status;
                return 1;
            }
        }
        return -1;
    }

    public function update($user = null)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions SET ";
        $sql .= "fk_beneficiary = ".(int)$this->fk_beneficiary.",";
        $sql .= "fk_package = ".($this->fk_package ? (int)$this->fk_package : "NULL").",";
        $sql .= "fk_warehouse = ".(int)$this->fk_warehouse.",";
        $sql .= "fk_user = ".($this->fk_user ? (int)$this->fk_user : "NULL").",";
        $sql .= "note = '".$this->db->escape($this->note)."',";
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
        // First, delete all distribution lines (which will update donation allocations)
        require_once dirname(__FILE__).'/distributionline.class.php';
        $lines = DistributionLine::getAllByDistribution($this->db, $this->id);
        foreach ($lines as $line) {
            $line->delete($user);
        }

        // Then delete the distribution
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE rowid = ".(int)$this->id;
        if ($this->db->query($sql)) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
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

    /**
     * Get available donations for a specific product
     */
   /**
 * Get available donations for a specific product
 */
public static function getAvailableDonations($db, $product_name = null)
{
    $donations = array();
    
    $sql = "SELECT d.*, v.name as vendor_name,
            (d.quantity - d.quantity_allocated) as available
            FROM ".MAIN_DB_PREFIX."foodbank_donations d
            LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
            WHERE d.status = 'Received'
            AND (d.quantity - d.quantity_allocated) > 0";
    
    if ($product_name) {
        // Use LIKE for flexible matching (e.g., "Rice" matches "White Rice", "Basmati Rice")
        $sql .= " AND (d.label LIKE '%".$db->escape($product_name)."%' 
                  OR d.label LIKE '%".$db->escape(strtolower($product_name))."%'
                  OR d.label LIKE '%".$db->escape(strtoupper($product_name))."%')";
    }
    
    $sql .= " ORDER BY d.date_donation ASC"; // FIFO - First In First Out
    
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $donations[] = array(
                'id' => $obj->rowid,
                'ref' => $obj->ref,
                'label' => $obj->label,
                'vendor_name' => $obj->vendor_name,
                'quantity' => $obj->quantity,
                'quantity_allocated' => $obj->quantity_allocated,
                'available' => $obj->available,
                'unit' => $obj->unit
            );
        }
    }
    
    return $donations;
}
    /**
     * Get line count for this distribution
     */
    public function getLineCount()
    {
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines 
                WHERE fk_distribution = ".(int)$this->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return $obj->count;
        }
        return 0;
    }
}
