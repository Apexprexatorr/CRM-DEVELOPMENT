<?php
/**
 * Trigger to redirect users to custom dashboards after login
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceRedirect extends DolibarrTriggers
{
    /**
     * Constructor
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "foodbankcrm";
        $this->description = "Redirect users to custom dashboards";
        $this->version = '1.0';
        $this->picto = 'foodbankcrm@foodbankcrm';
    }

    /**
     * Function called when a Dolibarr business event is triggered
     */
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        // Only trigger on successful login
        if ($action == 'USER_LOGIN') {
            require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
            
            // Check user role and set redirect URL
            $redirect_url = '';
            
            if (FoodbankPermissions::isAdmin($user)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_admin.php';
            } elseif (FoodbankPermissions::isVendor($user, $this->db)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_vendor.php';
            } elseif (FoodbankPermissions::isBeneficiary($user, $this->db)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_beneficiary.php';
            }
            
            // If a custom dashboard exists, redirect
            if ($redirect_url) {
                // Store redirect URL in session
                $_SESSION['foodbankcrm_redirect'] = $redirect_url;
            }
        }
        
        return 0;
    }
}