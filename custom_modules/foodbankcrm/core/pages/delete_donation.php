<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Donation ID is missing in the URL.</div>';
    print '<div><a href="donations.php">← Back to Donations</a></div>';
    llxFooter(); exit;
}

$id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Fetch donation details for confirmation
    $sql = "SELECT d.*, v.name as vendor_name, b.firstname, b.lastname 
            FROM ".MAIN_DB_PREFIX."foodbank_donations d
            LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
            LEFT JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
            WHERE d.rowid = ".$id;
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    
    print '<div class="warning">';
    print '<p><strong>Are you sure you want to delete this donation?</strong></p>';
    print '<p>Ref: '.dol_escape_htmltag($obj->ref).'</p>';
    print '<p>Label: '.dol_escape_htmltag($obj->label).'</p>';
    print '<p>Vendor: '.dol_escape_htmltag($obj->vendor_name).'</p>';
    print '</div>';
    
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input class="button butActionDelete" type="submit" name="confirm" value="Yes, delete">';
    print ' <a class="button" href="donations.php">Cancel</a>';
    print '</form>';
} else {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_donations WHERE rowid = ".$id;
        $ok = $db->query($sql);
        print $ok ? '<div class="ok">Donation deleted successfully!</div>'
                  : '<div class="error">Delete failed: '.$db->lasterror().'</div>';
        print '<div><a href="donations.php">← Back to Donations</a></div>';
    }
}

llxFooter();
?>