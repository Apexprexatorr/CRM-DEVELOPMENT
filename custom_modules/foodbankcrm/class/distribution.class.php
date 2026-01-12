<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Distribution extends CommonObject
{
    public $element = 'distribution';
    public $table_element = 'foodbank_distributions';
    public $picto = 'shipping';
    
    public $id;
    public $ref;
    public $fk_beneficiary;
    public $fk_package;
    public $fk_warehouse;
    public $fk_user;
    public $date_distribution;
    public $note;
    public $status;
    
    // Financials
    public $total_amount;
    public $payment_status;
    public $payment_method;
    public $payment_reference;
    public $payment_gateway;
    public $payment_date;

    public function __construct($db)
    {
        $this->db = $db;
        $this->status = 'Prepared';
        $this->payment_status = 'Pending';
    }

    public function create($user = null)
    {
        if (empty($this->ref)) { $this->ref = $this->getNextRef(); }

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distributions (";
        $sql .= "ref, fk_beneficiary, fk_package, fk_warehouse, fk_user, note, status, entity, date_distribution, ";
        $sql .= "total_amount, payment_status, payment_method, payment_reference, payment_gateway, payment_date, datec";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= (int)$this->fk_beneficiary.",";
        $sql .= ($this->fk_package ? (int)$this->fk_package : "NULL").",";
        $sql .= (int)$this->fk_warehouse.",";
        $sql .= ($this->fk_user ? (int)$this->fk_user : "NULL").",";
        $sql .= "'".$this->db->escape($this->note)."',";
        $sql .= "'".$this->db->escape($this->status)."',";
        $sql .= "1, NOW(),";
        $sql .= (float)$this->total_amount.",";
        $sql .= "'".$this->db->escape($this->payment_status)."',";
        $sql .= "'".$this->db->escape($this->payment_method)."',";
        $sql .= "'".$this->db->escape($this->payment_reference)."',";
        $sql .= "'".$this->db->escape($this->payment_gateway)."',";
        $sql .= ($this->payment_date ? "'".$this->db->escape($this->payment_date)."'" : "NULL").",";
        $sql .= "NOW())";

        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."foodbank_distributions");
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
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE rowid = ".(int)$id;
        $res = $this->db->query($sql);
        if ($res && ($obj = $this->db->fetch_object($res))) {
            $this->id = $obj->rowid;
            $this->ref = $obj->ref;
            $this->fk_beneficiary = $obj->fk_beneficiary;
            $this->fk_package = $obj->fk_package;
            $this->fk_warehouse = $obj->fk_warehouse;
            $this->fk_user = $obj->fk_user;
            $this->date_distribution = $obj->date_distribution;
            $this->note = $obj->note;
            $this->status = $obj->status;
            
            $this->total_amount = $obj->total_amount;
            $this->payment_status = $obj->payment_status;
            $this->payment_method = $obj->payment_method;
            $this->payment_reference = $obj->payment_reference;
            $this->payment_gateway = $obj->payment_gateway;
            $this->payment_date = $obj->payment_date;
            return 1;
        }
        return -1;
    }

    public function update($user = null)
    {
        $this->db->begin();
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions SET ";
        $sql .= "fk_beneficiary = ".(int)$this->fk_beneficiary.",";
        $sql .= "fk_package = ".($this->fk_package ? (int)$this->fk_package : "NULL").",";
        $sql .= "fk_warehouse = ".(int)$this->fk_warehouse.",";
        $sql .= "fk_user = ".($this->fk_user ? (int)$this->fk_user : "NULL").",";
        $sql .= "note = '".$this->db->escape($this->note)."',";
        $sql .= "status = '".$this->db->escape($this->status)."',";
        $sql .= "total_amount = ".(float)$this->total_amount.",";
        $sql .= "payment_status = '".$this->db->escape($this->payment_status)."',";
        $sql .= "payment_method = '".$this->db->escape($this->payment_method)."',";
        $sql .= "payment_reference = '".$this->db->escape($this->payment_reference)."',";
        $sql .= "payment_gateway = '".$this->db->escape($this->payment_gateway)."',";
        $sql .= "payment_date = ".($this->payment_date ? "'".$this->db->escape($this->payment_date)."'" : "NULL");
        $sql .= " WHERE rowid = ".(int)$this->id;

        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    public function delete($user = null)
    {
        $this->db->begin();
        require_once dirname(__FILE__).'/distributionline.class.php';
        $lines = DistributionLine::getAllByDistribution($this->db, $this->id);
        foreach ($lines as $line) { $line->delete($user); } // Restore stock
        
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE rowid = ".(int)$this->id;
        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM ".MAIN_DB_PREFIX."foodbank_distributions";
        $res = $this->db->query($sql);
        $obj = $this->db->fetch_object($res);
        $next = sprintf("DIS%s-%04d", date("Y"), ($obj->maxid ?? 0) + 1);
        return $next;
    }

    // --- FIX: UPDATED HELPER METHOD WITH PRICE ---
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
            $sql .= " AND (d.label LIKE '%".$db->escape($product_name)."%' 
                      OR d.product_name LIKE '%".$db->escape($product_name)."%')";
        }
        
        $sql .= " ORDER BY d.date_donation ASC"; 
        
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $donations[] = array(
                    'id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'label' => $obj->product_name ?: $obj->label,
                    'vendor_name' => $obj->vendor_name,
                    'available' => $obj->available,
                    'unit' => $obj->unit,
                    'unit_price' => $obj->unit_price ?: 0 // Pass price to frontend
                );
            }
        }
        return $donations;
    }
}
?>