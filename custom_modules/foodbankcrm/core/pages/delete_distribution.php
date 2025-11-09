<?php
// delete_distribution.php

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/distribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/beneficiary.class.php';

$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Distribution ID is missing.</div>';
    print '<div><a href="distributions.php">Back to Distributions</a></div>';
    llxFooter(); exit;
}

$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // === SHOW CONFIRMATION ===
    $dist = new Distribution($db);
    if ($dist->fetch($id) <= 0) {
        print '<div class="error">Distribution not found.</div>';
        print '<div><a href="distributions.php">Back to Distributions</a></div>';
        llxFooter(); exit;
    }

    $b = new Beneficiary($db);
    $beneficiary = ($dist->fk_beneficiary && $b->fetch($dist->fk_beneficiary) > 0)
        ? dol_escape_htmltag($b->firstname.' '.$b->lastname)
        : '—';

    $warehouse = '—';
    $sqlw = "SELECT label FROM ".MAIN_DB_PREFIX."foodbank_warehouses WHERE rowid = ".(int)$dist->fk_warehouse;
    $resw = $db->query($sqlw);
    if ($resw && ($objw = $db->fetch_object($resw))) {
        $warehouse = dol_escape_htmltag($objw->label);
    }

    print '<div class="warning">';
    print '<p><strong>Are you sure you want to delete this distribution?</strong></p>';
    print '<p><strong>Ref:</strong> '.dol_escape_htmltag($dist->ref).'</p>';
    print '<p><strong>Beneficiary:</strong> '.$beneficiary.'</p>';
    print '<p><strong>Warehouse:</strong> '.$warehouse.'</p>';
    print '<p><strong>Date:</strong> '.dol_print_date($db->jdate($dist->date_distribution), 'day').'</p>';
    print '<p><strong>Note:</strong> '.dol_escape_htmltag($dist->note ?: '—').'</p>';
    print '</div>';

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input class="button butActionDelete" type="submit" value="Yes, delete">';
    print ' <a class="button" href="distributions.php">Cancel</a>';
    print '</form>';

} else {
    // === TRY TO DELETE ===
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Invalid CSRF token.</div>';
    } else {
        $dist = new Distribution($db);
        if ($dist->fetch($id) <= 0) {
            print '<div class="error">Distribution not found.</div>';
        } else {
            $res = $dist->delete($user);

            if ($res > 0) {
                print '<div class="ok">Distribution deleted successfully!</div>';
            } else {
                // === FRIENDLY BLOCKED MESSAGE ===
               print '<div class="error" style="padding:20px; background:#ffebee; border:2px solid #c62828; border-radius:10px; font-size:15px;">';
                print '<strong>CANNOT DELETE THIS DISTRIBUTION</strong><br><br>';
                print 'This distribution is <strong>permanently linked</strong> to:<br>';
                print '• A real beneficiary who received food<br>';
                print '• A warehouse with updated stock levels<br><br>';
                print 'Deleting it would corrupt your inventory and audit trail.<br><br>';
                print 'To remove food safely:<br>';
                print '→ Create a <strong>Return</strong> entry<br>';
                print '→ Or use <strong>Stock Adjustment</strong><br><br>';
                print '<em>This protection cannot be bypassed.</em>';
                print '</div>';
            }
        }
        print '<div style="margin-top:20px;"><a href="distributions.php">Back to Distributions</a></div>';
    }
}
llxFooter();
?>