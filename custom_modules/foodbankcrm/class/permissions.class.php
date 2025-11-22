<?php
/**
 * Foodbank CRM Permission Helper
 * Handles role-based access control
 */

class FoodbankPermissions
{
    /**
     * Check if user is admin (full access)
     */
    public static function isAdmin($user)
    {
        // Super admin or has write permission
        return ($user->admin == 1) || 
               (!empty($user->rights->foodbankcrm) && !empty($user->rights->foodbankcrm->write));
    }
    
    /**
     * Check if user has vendor role
     */
    public static function isVendor($user, $db)
    {
        // Admin can act as vendor
        if (self::isAdmin($user)) {
            return true;
        }
        
        // Check if user has vendor permission
        if (empty($user->rights->foodbankcrm) || empty($user->rights->foodbankcrm->vendor)) {
            return false;
        }
        
        // Check if user is linked to a vendor
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_user_vendor 
                WHERE fk_user = ".(int)$user->id;
        $resql = $db->query($sql);
        
        if ($resql) {
            $obj = $db->fetch_object($resql);
            return $obj->count > 0;
        }
        
        return false;
    }
    
    /**
     * Get vendor ID for current user
     */
    public static function getVendorId($user, $db)
    {
        // Admin has no specific vendor
        if (self::isAdmin($user)) {
            return null;
        }
        
        $sql = "SELECT fk_vendor FROM ".MAIN_DB_PREFIX."foodbank_user_vendor 
                WHERE fk_user = ".(int)$user->id." LIMIT 1";
        $resql = $db->query($sql);
        
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            return (int)$obj->fk_vendor;
        }
        
        return null;
    }
    
    /**
     * Check if user has beneficiary role
     */
    public static function isBeneficiary($user, $db)
    {
        // Admin can act as beneficiary
        if (self::isAdmin($user)) {
            return true;
        }
        
        // Check if user has beneficiary permission
        if (empty($user->rights->foodbankcrm) || empty($user->rights->foodbankcrm->beneficiary)) {
            return false;
        }
        
        // Check if user is linked to a beneficiary
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_user_beneficiary 
                WHERE fk_user = ".(int)$user->id;
        $resql = $db->query($sql);
        
        if ($resql) {
            $obj = $db->fetch_object($resql);
            return $obj->count > 0;
        }
        
        return false;
    }
    
    /**
     * Get beneficiary ID for current user
     */
    public static function getBeneficiaryId($user, $db)
    {
        // Admin has no specific beneficiary
        if (self::isAdmin($user)) {
            return null;
        }
        
        $sql = "SELECT fk_beneficiary FROM ".MAIN_DB_PREFIX."foodbank_user_beneficiary 
                WHERE fk_user = ".(int)$user->id." LIMIT 1";
        $resql = $db->query($sql);
        
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            return (int)$obj->fk_beneficiary;
        }
        
        return null;
    }
    
    /**
     * Check if user can view a specific donation
     */
    public static function canViewDonation($user, $db, $donation_id)
    {
        // Admin can view all
        if (self::isAdmin($user)) {
            return true;
        }
        
        // Vendor can only view their own donations
        $vendor_id = self::getVendorId($user, $db);
        if (!$vendor_id) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_donations 
                WHERE rowid = ".(int)$donation_id." AND fk_vendor = ".(int)$vendor_id;
        $resql = $db->query($sql);
        
        if ($resql) {
            $obj = $db->fetch_object($resql);
            return $obj->count > 0;
        }
        
        return false;
    }
    
    /**
     * Check if user can view a specific distribution
     */
    public static function canViewDistribution($user, $db, $distribution_id)
    {
        // Admin can view all
        if (self::isAdmin($user)) {
            return true;
        }
        
        // Beneficiary can only view their own distributions
        $beneficiary_id = self::getBeneficiaryId($user, $db);
        if (!$beneficiary_id) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_distributions 
                WHERE rowid = ".(int)$distribution_id." AND fk_beneficiary = ".(int)$beneficiary_id;
        $resql = $db->query($sql);
        
        if ($resql) {
            $obj = $db->fetch_object($resql);
            return $obj->count > 0;
        }
        
        return false;
    }
    
    /**
     * Get user role name (for display)
     */
    public static function getUserRole($user, $db)
    {
        if (self::isAdmin($user)) {
            return 'Administrator';
        } elseif (self::isVendor($user, $db)) {
            return 'Vendor';
        } elseif (self::isBeneficiary($user, $db)) {
            return 'Beneficiary';
        } else {
            return 'No Access';
        }
    }
}