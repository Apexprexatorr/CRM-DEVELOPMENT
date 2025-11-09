<?php
// Minimal, safe descriptor for Dolibarr 15–19
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modFoodbankcrm extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;
        $this->db = $db;

        $this->numero       = 110001;                 // unique > 100000
        $this->rights_class = 'foodbankcrm';
        $this->family       = 'crm';                  // or 'other'
        $this->module_position = 500000;
        $this->picto = 'generic';
        $this->name         = 'foodbankcrm';
        $this->description  = 'Foodbank CRM custom module';
        $this->version      = '1.0';
        $this->editor_name  = 'YourName';
        $this->editor_url   = '';

        $this->const        = array();
        $this->dirs         = array('/foodbankcrm');  // creates documents/foodbankcrm
        $this->config_page_url = array('setup.php@foodbankcrm');
        $this->langfiles    = array('foodbankcrm@foodbankcrm');
        $this->phpmin       = array(7,4);             // Dolibarr 19 runs on PHP 8.x, 7.4 min okay

        $this->depends      = array();                // no hard deps
        $this->requiredby   = array();
        $this->conflictwith = array();

        // ---- Permissions (Updated) ----
        $this->rights = array();
        $r=0;
        $this->rights[$r][0] = 100001;  // Changed from 110001 to match your new code
        $this->rights[$r][1] = 'Read Foodbank';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;  // default enabled
        $this->rights[$r][4] = 'read';
        $r++;
        
        $this->rights[$r][0] = 100002;
        $this->rights[$r][1] = 'Write Foodbank';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 1;  // default enabled
        $this->rights[$r][4] = 'write';
        $r++;

        // ---- Menus ----
        $this->menu = array();
        $r=0;
        
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
            array('Distributions', '/custom/foodbankcrm/core/pages/distributions.php'),
            array('Warehouses', '/custom/foodbankcrm/core/pages/warehouses.php'),
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