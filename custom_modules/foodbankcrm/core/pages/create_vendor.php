<?php
require_once dirname(__DIR__, 4) . '/main.inc.php'; 
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php'; 

$langs->load("admin");
llxHeader();

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $v = new Vendor($db);
        $v->ref = $_POST['ref']; // Will auto-generate if empty
        $v->name = $_POST['name'];
        $v->contact_person = $_POST['contact_person'];
        $v->phone = $_POST['phone'];
        $v->email = $_POST['email'];
        $v->address = $_POST['address'];
        $v->note = $_POST['note'];

        $res = $v->create($user);
        if ($res > 0) {
            $notice = '<div class="ok">Vendor created successfully! Ref: '.$v->ref.' (ID: '.$res.')</div>';
        } else {
            $notice = '<div class="error">Error creating vendor: '.dol_escape_htmltag($v->error).'</div>';
        }
    }
}

print $notice;
print '<div><a href="vendors.php">← Back to Vendors</a></div><br>';
?>

<h2>Create Vendor</h2>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  
  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><input class="flat" type="text" name="ref" placeholder="Leave empty for auto-generation (VEN2025-0001)"></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Vendor Name</span></td>
      <td><input class="flat" type="text" name="name" required></td>
    </tr>
    <tr>
      <td>Contact Person</td>
      <td><input class="flat" type="text" name="contact_person"></td>
    </tr>
    <tr>
      <td>Phone</td>
      <td><input class="flat" type="text" name="phone"></td>
    </tr>
    <tr>
      <td>Email</td>
      <td><input class="flat" type="email" name="email"></td>
    </tr>
    <tr>
      <td>Address</td>
      <td><textarea class="flat" name="address" rows="3"></textarea></td>
    </tr>
    <tr>
      <td>Note</td>
      <td><textarea class="flat" name="note" rows="3"></textarea></td>
    </tr>
  </table>
  
  <br>
  <div class="center">
    <input class="button" type="submit" value="Create Vendor">
    <a class="button" href="vendors.php">Cancel</a>
  </div>
</form>

<?php llxFooter(); ?>