<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendorproduct.class.php';

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Vendor ID is missing.</div>';
    print '<div><a href="vendors.php">← Back to Vendors</a></div>';
    llxFooter(); exit;
}

$vendor_id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // CONFIRMATION PAGE - Show before deleting
    $vendor = new Vendor($db);
    $vendor->fetch($vendor_id);
    
    // Check for products
    $products = VendorProduct::getAllByVendor($db, $vendor_id);
    $product_count = count($products);
    
    // Check for donations using this vendor
    $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_donations WHERE fk_vendor = ".$vendor_id;
    $resql = $db->query($sql);
    $donation_count = 0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $donation_count = $obj->count;
    }
    
    print '<div class="warning" style="padding: 20px; border: 2px solid #f57c00; background: #fff3e0;">';
    print '<h3 style="margin-top: 0;">⚠ Confirm Vendor Deletion</h3>';
    print '<p><strong>Vendor:</strong> '.dol_escape_htmltag($vendor->ref).' - '.dol_escape_htmltag($vendor->name).'</p>';
    print '<p><strong>Products in catalog:</strong> '.$product_count.'</p>';
    print '<p><strong>Donations from this vendor:</strong> '.$donation_count.'</p>';
    
    if ($donation_count > 0) {
        print '<div class="error" style="margin-top: 15px; padding: 15px; background: #ffebee; border: 2px solid #d32f2f;">';
        print '<h4 style="margin-top: 0; color: #d32f2f;">❌ Cannot Delete This Vendor</h4>';
        print '<p>This vendor has <strong>'.$donation_count.' donation(s)</strong> in the system.</p>';
        print '<p>To delete this vendor, you must first:</p>';
        print '<ul>';
        print '<li>Delete all donations from this vendor, OR</li>';
        print '<li>Change those donations to a different vendor</li>';
        print '</ul>';
        print '<p><strong>This protection cannot be bypassed.</strong></p>';
        print '</div>';
        print '<br><a class="button" href="vendors.php">← Back to Vendors</a>';
        print '</div>';
    } else {
        // Safe to delete
        if ($product_count > 0) {
            print '<div style="margin-top: 15px; padding: 15px; background: #fff8e1; border: 2px solid #ff9800;">';
            print '<h4 style="margin-top: 0; color: #f57c00;">⚠ Warning: This Vendor Has Products</h4>';
            print '<p>This vendor has <strong>'.$product_count.' product(s)</strong> in the catalog:</p>';
            print '<ul>';
            foreach ($products as $product) {
                print '<li>'.dol_escape_htmltag($product->product_name).' ('.$product->typical_quantity.' '.$product->unit.')</li>';
            }
            print '</ul>';
            print '<p><strong>All these products will also be deleted permanently.</strong></p>';
            print '</div>';
        }
        
        print '<p style="color: #d32f2f; font-weight: bold; margin-top: 20px;">This action cannot be undone.</p>';
        print '</div>';
        
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$vendor_id.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input class="button butActionDelete" type="submit" name="confirm" value="Yes, Delete Vendor'.($product_count > 0 ? ' and '.$product_count.' Product(s)' : '').'">';
        print ' <a class="button" href="vendors.php">Cancel</a>';
        print '</form>';
    }
} else {
    // PROCESS DELETION
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $vendor = new Vendor($db);
        $vendor->fetch($vendor_id);
        
        $result = $vendor->delete($user);
        
        if ($result > 0) {
            print '<div class="ok">Vendor and all associated products deleted successfully!</div>';
        } elseif ($result == -2) {
            print '<div class="error">'.$vendor->error.'</div>';
        } else {
            print '<div class="error">Error deleting vendor: '.$vendor->error.'</div>';
        }
        print '<div><a href="vendors.php">← Back to Vendors</a></div>';
    }
}

llxFooter();
?>