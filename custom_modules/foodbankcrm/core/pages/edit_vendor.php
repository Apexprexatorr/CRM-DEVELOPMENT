<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php';

$langs->load("admin");
llxHeader();

// Ensure 'id' is passed in the URL
// ... includes + llxHeader();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    print '<div class="error">Vendor ID is missing in the URL.</div>';
    print '<div><a href="vendor.php">← Back to Vendors </a></div>';
    llxFooter(); exit;
}

$v = new Vendor ($db);
$v->fetch((int) $_GET['id']);

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $v->ref = $_POST['ref'];
        $v->name = $_POST['name'];
        $v->contact_person = $_POST['contact_person'];
        $v->phone = $_POST['phone'];
        $v->email = $_POST['email'];
        $v->address = $_POST['address'];
        $v->note = $_POST['note'];

        // simple update via direct SQL for now (or add an update() in your class)
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_vendors 
                SET ref='".$db->escape($v->ref)."',
                    name='".$db->escape($v->name)."',
                    contact_person='".$db->escape($v->contact_person)."',
                    phone='".$db->escape($v->phone)."',
                    email='".$db->escape($v->email)."',
                    address='".$db->escape($v->address)."',
                    note='".$db->escape($v->note)."'
                WHERE rowid=".(int)$v->id;
        $res = $db->query($sql);

        $notice = $res ? '<div class="ok">Vendor updated successfully!</div>'
                       : '<div class="error">Update failed: '.$db->lasterror().'</div>';
    }
}

print $notice;
print '<div><a href="vendors.php">← Back to Vendors</a></div><br>';
?>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF'].'?id='.(int)$v->id; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  Ref: <input type="text" name="ref" value="<?php echo dol_escape_htmltag($v->ref); ?>" required><br>
  Name: <input type="text" name="name" value="<?php echo dol_escape_htmltag($v->name); ?>" required><br>
  Contact Person: <input type="text" name="contact_person" value="<?php echo dol_escape_htmltag($v->contact_person); ?>" required><br>
  Phone: <input type="text" name="phone" value="<?php echo dol_escape_htmltag($v->phone); ?>"><br>
  Email: <input type="email" name="email" value="<?php echo dol_escape_htmltag($v->email); ?>"><br>
  Address: <textarea name="address"><?php echo dol_escape_htmltag($v->address); ?></textarea><br>
  Note: <textarea name="note"><?php echo dol_escape_htmltag($v->note); ?></textarea><br>
  <input class="button" type="submit" value="Update Vendor">
</form>
<?php
// ... llxFooter();

?>
