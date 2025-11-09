<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php';

$langs->load("admin");
llxHeader();

// Ensure 'id' is passed in the URL
// ... includes + llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Beneficiary ID is missing in the URL.</div>';
    print '<div><a href="beneficiaries.php">← Back to Beneficiaries</a></div>';
    llxFooter(); exit;
}

$id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Confirm screen
    print '<div class="warning">Are you sure you want to delete beneficiary #'.$id.'?</div>';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input class="button" type="submit" name="confirm" value="Yes, delete">';
    print ' <a class="butActionRefused" href="beneficiaries.php">Cancel</a>';
    print '</form>';
} else {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $b = new Beneficiary($db);
        $ok = $b->delete($id);
        print $ok ? '<div class="ok">Beneficiary deleted successfully!</div>'
                  : '<div class="error">Delete failed.</div>';
        print '<div><a href="beneficiaries.php">← Back to Beneficiaries</a></div>';
    }
}

// ... llxFooter();

?>
