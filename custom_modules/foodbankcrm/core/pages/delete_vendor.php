<?php
// delete_vendors.php

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/vendor.class.php';

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Vendor ID is missing in the URL.</div>';
    print '<div><a href="vendors.php">Back to Vendors</a></div>';
    llxFooter(); 
    exit;
}

$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // === FETCH VENDOR DETAILS FOR CONFIRMATION ===
    $vendor = new Vendor($db);
    if ($vendor->fetch($id) <= 0) {
        print '<div class="error">Vendor not found.</div>';
        print '<div><a href="vendors.php">Back to Vendors</a></div>';
        llxFooter(); 
        exit;
    }

    print '<div class="warning">';
    print '<p><strong>Are you sure you want to delete this vendor?</strong></p>';
    print '<p><strong>Ref:</strong> '.dol_escape_htmltag($vendor->ref).'</p>';
    print '<p><strong>Name:</strong> '.dol_escape_htmltag($vendor->name).'</p>';
    print '<p><strong>Contact:</strong> '.dol_escape_htmltag($vendor->contact_person ?: '—').'</p>';
    print '<p><strong>Phone:</strong> '.dol_escape_htmltag($vendor->phone ?: '—').'</p>';
    print '<p><strong>Email:</strong> '.dol_escape_htmltag($vendor->email ?: '—').'</p>';
    print '</div>';

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input class="button butActionDelete" type="submit" value="Yes, delete">';
    print ' <a class="button" href="vendors.php">Cancel</a>';
    print '</form>';

} else {
    // === POST: CONFIRMED DELETE ===
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $vendor = new Vendor($db);
        if ($vendor->fetch($id) <= 0) {
            print '<div class="error">Vendor not found.</div>';
        } else {
            $res = $vendor->delete($user);
            if ($res > 0) {
                print '<div class="ok">Vendor deleted successfully!</div>';
            } else {
                // === FRIENDLY ERROR FOR FOREIGN KEY ===
                if (strpos($vendor->error, 'foreign key constraint') !== false) {
                    print '<div class="error" style="padding: 15px; background: #ffaaaa; border: 1px solid #cc0000;">';
                    print '<strong>Cannot delete this vendor</strong><br><br>';
                    print 'This vendor has made <strong>donations</strong> to the food bank.<br>';
                    print 'Please delete or reassign those donations first.<br><br>';
                    print 'Go to: <a href="donations.php?vendor='.$vendor->id.'"><strong>View Donations from this Vendor</strong></a>';
                    print '</div>';
                } else {
                    print '<div class="error">Delete failed: '.dol_escape_htmltag($vendor->error).'</div>';
                }
            }
        }
        print '<div><a href="vendors.php">Back to Vendors</a></div>';
    }
}

llxFooter();
?>