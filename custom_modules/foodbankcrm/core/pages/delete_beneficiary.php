<?php
// delete_beneficiaries.php

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';
$langs->load("admin");
llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Beneficiary ID is missing in the URL.</div>';
    print '<div><a href="beneficiaries.php">Back to Beneficiaries</a></div>';
    llxFooter(); 
    exit;
}

$id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // === FETCH BENEFICIARY DETAILS FOR CONFIRMATION ===
    $b = new Beneficiary($db);
    if ($b->fetch($id) <= 0) {
        print '<div class="error">Beneficiary not found.</div>';
        print '<div><a href="beneficiaries.php">Back to Beneficiaries</a></div>';
        llxFooter(); 
        exit;
    }

    print '<div class="warning">';
    print '<p><strong>Are you sure you want to delete this beneficiary?</strong></p>';
    print '<p><strong>Ref:</strong> '.dol_escape_htmltag($b->ref).'</p>';
    print '<p><strong>Name:</strong> '.dol_escape_htmltag($b->firstname.' '.$b->lastname).'</p>';
    print '<p><strong>Phone:</strong> '.dol_escape_htmltag($b->phone ?: '—').'</p>';
    print '<p><strong>Email:</strong> '.dol_escape_htmltag($b->email ?: '—').'</p>';
    print '</div>';

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input class="button butActionDelete" type="submit" name="confirm" value="Yes, delete">';
    print ' <a class="button" href="beneficiaries.php">Cancel</a>';
    print '</form>';

} else {
    // === POST: CONFIRMED DELETE ===
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $b = new Beneficiary($db);
        if ($b->fetch($id) <= 0) {
            print '<div class="error">Beneficiary not found.</div>';
        } else {
                        $res = $b->delete($user);
            if ($res > 0) {
                print '<div class="ok">Beneficiary deleted successfully!</div>';
            } else {
                print '<div class="error" style="padding: 15px; background: #ffaaaa; border: 1px solid #cc0000;">';
                print '<strong>Cannot delete this beneficiary</strong><br><br>';
                print 'This person has received food distributions.<br>';
                print 'Please delete or reassign those distributions first.<br><br>';
                print 'Go to: <a href="distributions.php?beneficiary='.$b->id.'">View Distributions</a>';
                print '</div>';
            }
        }
        print '<div><a href="beneficiaries.php">Back to Beneficiaries</a></div>';
    }
}
    
llxFooter();
?>