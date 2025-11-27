<?php
/**
 * Hook to inject redirect JavaScript on every Dolibarr page
 */

class ActionsFoodbankcrm
{
    public $db;
    public $conf;
    public $langs;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Overload the printCommonFooter function to inject our redirect script
     */
    public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $db;
        
        require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
        
        // Only redirect from main dashboard pages
        $current_page = $_SERVER['SCRIPT_NAME'] ?? '';
        
        if (strpos($current_page, '/index.php') !== false || 
            strpos($current_page, '/comm/index.php') !== false ||
            $current_page === '/index.php') {
            
            // Check if user should be redirected
            $redirect_url = '';
            
            if (FoodbankPermissions::isVendor($user, $db)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_vendor.php';
            } elseif (FoodbankPermissions::isBeneficiary($user, $db)) {
                $redirect_url = '/custom/foodbankcrm/core/pages/dashboard_beneficiary.php';
            }
            
            if ($redirect_url) {
                echo '<script>
                console.log("Foodbank CRM: Redirecting to custom dashboard...");
                window.location.replace("'.$redirect_url.'");
                </script>';
            }
        }
        
        return 0;
    }
}