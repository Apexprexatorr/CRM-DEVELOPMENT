<?php
/**
 * Permissions management class for Foodbank CRM
 */

class FoodbankPermissions
{
    /**
     * Check if user is admin
     */
    public static function isAdmin($user)
    {
        if (empty($user->id)) {
            return false;
        }
        
        // SuperAdmin or has admin flag
        return ($user->admin == 1);
    }

    /**
     * Check if user is a vendor
     */
    public static function isVendor($user, $db)
    {
        if (empty($user->id)) {
            return false;
        }
        
        // Admin can act as vendor for testing
        if (self::isAdmin($user)) {
            return false; // Set to false so admin uses admin dashboard
        }
        
        // Check if user is linked to a vendor record
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
        $resql = $db->query($sql);
        
        if ($resql) {
            $obj = $db->fetch_object($resql);
            return ($obj->count > 0);
        }
        
        return false;
    }

    /**
     * Check if user is a beneficiary/subscriber
     */
    public static function isBeneficiary($user, $db)
    {
        if (empty($user->id)) {
            return false;
        }
        
        // Admin can act as beneficiary for testing
        if (self::isAdmin($user)) {
            return false; // Set to false so admin uses admin dashboard
        }
        
        // Check if user is linked to a beneficiary record
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
        $resql = $db->query($sql);
        
        if ($resql) {
            $obj = $db->fetch_object($resql);
            return ($obj->count > 0);
        }
        
        return false;
    }

    /**
     * Get vendor ID for user
     */
    public static function getVendorId($user, $db)
    {
        if (empty($user->id)) {
            return 0;
        }
        
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_vendors WHERE fk_user = ".(int)$user->id;
        $resql = $db->query($sql);
        
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            return $obj->rowid;
        }
        
        return 0;
    }

    /**
     * Get beneficiary ID for user
     */
    public static function getBeneficiaryId($user, $db)
    {
        if (empty($user->id)) {
            return 0;
        }
        
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
        $resql = $db->query($sql);
        
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            return $obj->rowid;
        }
        
        return 0;
    }
}
