<?php
/**
 * Trigger to redirect users to custom dashboards after login
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceRedirect extends DolibarrTriggers
{
    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "foodbankcrm";
        $this->description = "Redirect users to custom dashboards";
        $this->version = '1.0';
        $this->picto = 'foodbankcrm@foodbankcrm';
    }

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        // Only trigger on successful login
        if ($action == 'USER_LOGIN') {
            require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
            
            $redirect_url = '';
            
            // 1. Check Admin
            if (FoodbankPermissions::isAdmin($user)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_admin.php';
            } 
            // 2. Check Vendor
            elseif (FoodbankPermissions::isVendor($user, $this->db)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_vendor.php';
            } 
            // 3. Check Beneficiary
            elseif (FoodbankPermissions::isBeneficiary($user, $this->db)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_beneficiary.php';
            }
            
            // 4. FORCE REDIRECT
            if ($redirect_url) {
                header("Location: " . DOL_URL_ROOT . $redirect_url);
                exit;
            }
        }
        
        return 0;
    }
}