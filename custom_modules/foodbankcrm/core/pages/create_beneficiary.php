<?php
require_once dirname(__DIR__, 4) . '/main.inc.php'; 
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php'; 

$langs->load("admin");
llxHeader();

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $b = new Beneficiary($db);
        $b->ref = $_POST['ref']; // Will auto-generate if empty
        $b->firstname = $_POST['firstname'];
        $b->lastname = $_POST['lastname'];
        $b->phone = $_POST['phone'];
        $b->email = $_POST['email'];
        $b->address = $_POST['address'];
        $b->note = $_POST['note'];

        $res = $b->create($user);
        if ($res > 0) {
            $notice = '<div class="ok">Beneficiary created successfully! Ref: '.$b->ref.' (ID: '.$res.')</div>';
        } else {
            $notice = '<div class="error">Error creating beneficiary: '.dol_escape_htmltag($b->error).'</div>';
        }
    }
}

print $notice;
print '<div><a href="beneficiaries.php">← Back to Beneficiaries</a></div><br>';
?>

<h2>Create Beneficiary</h2>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  
  <table class="border centpercent">
    <tr>
      <td width="25%">Ref</td>
      <td><input class="flat" type="text" name="ref" placeholder="Leave empty for auto-generation (BEN2025-0001)"></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">First Name</span></td>
      <td><input class="flat" type="text" name="firstname" required></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Last Name</span></td>
      <td><input class="flat" type="text" name="lastname" required></td>
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
    <input class="button" type="submit" value="Create Beneficiary">
    <a class="button" href="beneficiaries.php">Cancel</a>
  </div>
</form>

<?php llxFooter(); ?>