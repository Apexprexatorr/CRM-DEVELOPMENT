<?php
/**
 * Automatic group assignment trigger
 * When a vendor or beneficiary record is created/updated, automatically assign user to group
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceGroupAssignment extends DolibarrTriggers
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "foodbankcrm";
        $this->description = "Auto-assign users to Vendors/Subscribers groups";
        $this->version = '1.0';
        $this->picto = 'foodbankcrm@foodbankcrm';
    }

    /**
     * Function called when a Dolibarr business event is triggered
     */
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        // We'll trigger on custom actions or check table names
        // Since we're using custom tables, we need to monitor them differently
        
        // For now, this is a placeholder - the real automation happens in the vendor/beneficiary creation pages
        return 0;
    }
    
    /**
     * Helper function to assign user to group
     */
    public static function assignUserToGroup($db, $user_id, $group_name)
    {
        // Get group ID
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."usergroup WHERE nom = '".$db->escape($group_name)."'";
        $resql = $db->query($sql);
        
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            $group_id = $obj->rowid;
            
            // Check if already in group
            $sql_check = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."usergroup_user 
                          WHERE fk_user = ".(int)$user_id." AND fk_usergroup = ".(int)$group_id;
            $res_check = $db->query($sql_check);
            $check = $db->fetch_object($res_check);
            
            if ($check->count == 0) {
                // Add to group
                $sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."usergroup_user (fk_user, fk_usergroup, entity) 
                               VALUES (".(int)$user_id.", ".(int)$group_id.", 1)";
                return $db->query($sql_insert);
            }
            return true; // Already in group
        }
        return false; // Group not found
    }
}