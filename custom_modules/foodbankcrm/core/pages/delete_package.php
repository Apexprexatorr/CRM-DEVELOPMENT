<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/package.class.php';

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Package ID is missing.</div>';
    print '<div><a href="packages.php">← Back to Packages</a></div>';
    llxFooter(); exit;
}

$package_id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Fetch package details for confirmation
    $package = new Package($db);
    $package->fetch($package_id);
    
    // Check usage count
    $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."foodbank_distributions WHERE fk_package = ".$package_id;
    $resql = $db->query($sql);
    $usage_count = 0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $usage_count = $obj->count;
    }
    
    // Get item count
    $item_count = $package->getItemCount();
    
    print '<div class="warning" style="padding: 20px; border: 2px solid #f57c00; background: #fff3e0;">';
    print '<h3 style="margin-top: 0;">⚠ Confirm Package Deletion</h3>';
    print '<p><strong>Package:</strong> '.dol_escape_htmltag($package->ref).' - '.dol_escape_htmltag($package->name).'</p>';
    print '<p><strong>Items in package:</strong> '.$item_count.'</p>';
    print '<p><strong>Used in distributions:</strong> '.$usage_count.'</p>';
    
    if ($usage_count > 0) {
        print '<div class="error" style="margin-top: 15px; padding: 15px; background: #ffebee; border: 2px solid #d32f2f;">';
        print '<h4 style="margin-top: 0; color: #d32f2f;">❌ Cannot Delete This Package</h4>';
        print '<p>This package is currently used in <strong>'.$usage_count.' distribution(s)</strong>.</p>';
        print '<p>To delete this package, you must first:</p>';
        print '<ul>';
        print '<li>Delete all distributions using this package, OR</li>';
        print '<li>Change those distributions to use a different package</li>';
        print '</ul>';
        print '<p><strong>This protection cannot be bypassed.</strong></p>';
        print '</div>';
        print '<br><a class="button" href="packages.php">← Back to Packages</a>';
        print '</div>';
    } else {
        print '<p style="color: #d32f2f; font-weight: bold;">This will permanently delete the package and all its items. This action cannot be undone.</p>';
        print '</div>';
        
        print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$package_id.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input class="button butActionDelete" type="submit" name="confirm" value="Yes, Delete Package">';
        print ' <a class="button" href="packages.php">Cancel</a>';
        print '</form>';
    }
} else {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $package = new Package($db);
        $package->fetch($package_id);
        
        $result = $package->delete($user);
        
        if ($result > 0) {
            print '<div class="ok">Package deleted successfully!</div>';
        } elseif ($result == -2) {
            print '<div class="error">'.$package->error.'</div>';
        } else {
            print '<div class="error">Error deleting package: '.$package->error.'</div>';
        }
        print '<div><a href="packages.php">← Back to Packages</a></div>';
    }
}

llxFooter();
?>