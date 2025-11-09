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

$b = new Beneficiary($db);
$b->fetch((int) $_GET['id']);

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $b->ref = $_POST['ref'];
        $b->firstname = $_POST['firstname'];
        $b->lastname = $_POST['lastname'];
        $b->phone = $_POST['phone'];
        $b->email = $_POST['email'];
        $b->address = $_POST['address'];
        $b->note = $_POST['note'];

        // simple update via direct SQL for now (or add an update() in your class)
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries 
                SET ref='".$db->escape($b->ref)."',
                    firstname='".$db->escape($b->firstname)."',
                    lastname='".$db->escape($b->lastname)."',
                    phone='".$db->escape($b->phone)."',
                    email='".$db->escape($b->email)."',
                    address='".$db->escape($b->address)."',
                    note='".$db->escape($b->note)."'
                WHERE rowid=".(int)$b->id;
        $res = $db->query($sql);

        $notice = $res ? '<div class="ok">Beneficiary updated successfully!</div>'
                       : '<div class="error">Update failed: '.$db->lasterror().'</div>';
    }
}

print $notice;
print '<div><a href="beneficiaries.php">← Back to Beneficiaries</a></div><br>';
?>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF'].'?id='.(int)$b->id; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  Ref: <input type="text" name="ref" value="<?php echo dol_escape_htmltag($b->ref); ?>" required><br>
  First Name: <input type="text" name="firstname" value="<?php echo dol_escape_htmltag($b->firstname); ?>" required><br>
  Last Name: <input type="text" name="lastname" value="<?php echo dol_escape_htmltag($b->lastname); ?>" required><br>
  Phone: <input type="text" name="phone" value="<?php echo dol_escape_htmltag($b->phone); ?>"><br>
  Email: <input type="email" name="email" value="<?php echo dol_escape_htmltag($b->email); ?>"><br>
  Address: <textarea name="address"><?php echo dol_escape_htmltag($b->address); ?></textarea><br>
  Note: <textarea name="note"><?php echo dol_escape_htmltag($b->note); ?></textarea><br>
  <input class="button" type="submit" value="Update Beneficiary">
</form>
<?php
// ... llxFooter();

?>
