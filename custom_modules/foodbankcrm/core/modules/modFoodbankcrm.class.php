<?php
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modFoodbankcrm extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;
        $this->db = $db;

        $this->numero       = 110001;
        $this->rights_class = 'foodbankcrm';
        $this->family       = 'crm';
        $this->module_position = 500000;
        $this->picto = 'generic';
        $this->name         = 'foodbankcrm';
        $this->description  = 'Foodbank CRM custom module';
        $this->version      = '1.1';
        $this->editor_name  = 'YourName';
        $this->editor_url   = '';

        $this->const        = array();
        $this->dirs         = array('/foodbankcrm');
        $this->config_page_url = array('setup.php@foodbankcrm');
        $this->langfiles    = array('foodbankcrm@foodbankcrm');
        $this->phpmin       = array(7,4);

        $this->depends      = array();
        $this->requiredby   = array();
        $this->conflictwith = array();

        // ---- Permissions ----
        $this->rights = array();
        $r = 0;

        // Admin permissions
        $this->rights[$r][0] = 100001;
        $this->rights[$r][1] = 'Read Foodbank CRM';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = 100002;
        $this->rights[$r][1] = 'Write Foodbank CRM';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->rights[$r][0] = 100003;
        $this->rights[$r][1] = 'Delete Foodbank CRM';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';
        $r++;

        // Vendor permissions
        $this->rights[$r][0] = 100011;
        $this->rights[$r][1] = 'Vendor Dashboard Access';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'vendor';
        $this->rights[$r][5] = 'dashboard';
        $r++;

        $this->rights[$r][0] = 100012;
        $this->rights[$r][1] = 'Vendor Create Donations';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'vendor';
        $this->rights[$r][5] = 'create_donation';
        $r++;

        // Beneficiary permissions
        $this->rights[$r][0] = 100021;
        $this->rights[$r][1] = 'Beneficiary Dashboard Access';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'beneficiary';
        $this->rights[$r][5] = 'dashboard';
        $r++;

        // ---- Menus ----
        $this->menu = array();
        $r = 0;

        // Top menu
        $this->menu[$r] = array(
            'fk_menu'   => '',
            'type'      => 'top',
            'titre'     => 'Foodbank CRM',
            'mainmenu'  => 'foodbankcrm',
            'leftmenu'  => '',
            'url'       => '/custom/foodbankcrm/index.php',
            'langs'     => 'foodbankcrm@foodbankcrm',
            'position'  => 1000,
            'enabled'   => '1',
            'perms'     => '$user->rights->foodbankcrm->read',
            'target'    => '',
            'user'      => 2
        );
        $r++;

        // Left menu entries
        $lefts = array(
        array('Beneficiaries', '/custom/foodbankcrm/core/pages/beneficiaries.php'),
        array('Vendors', '/custom/foodbankcrm/core/pages/vendors.php'),
        array('Donations', '/custom/foodbankcrm/core/pages/donations.php'),
        array('Packages', '/custom/foodbankcrm/core/pages/packages.php'),
        array('Distributions', '/custom/foodbankcrm/core/pages/distributions.php'),
        array('Warehouses', '/custom/foodbankcrm/core/pages/warehouses.php'),
        array('Subscription Tiers', '/custom/foodbankcrm/core/pages/subscription_tiers.php'), 
        array('User Management', '/custom/foodbankcrm/core/pages/user_management.php'),
        array('Product Catalog', '/custom/foodbankcrm/core/pages/product_catalog.php'),
         );

        foreach ($lefts as $i => $m) {
            $this->menu[$r] = array(
                'fk_menu'   => 'fk_mainmenu=foodbankcrm',
                'type'      => 'left',
                'titre'     => $m[0],
                'mainmenu'  => 'foodbankcrm',
                'leftmenu'  => 'foodbankcrm_'.$i,
                'url'       => $m[1],
                'langs'     => 'foodbankcrm@foodbankcrm',
                'position'  => 1000 + $i + 1,
                'enabled'   => '1',
                'perms'     => '$user->rights->foodbankcrm->read',
                'target'    => '',
                'user'      => 2
            );
            $r++;
        }
    }

    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}