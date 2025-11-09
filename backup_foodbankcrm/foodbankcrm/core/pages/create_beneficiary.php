<?php
// Include Dolibarr's main.inc.php file to load the environment
require_once dirname(__DIR__, 4) . '/main.inc.php'; 
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php'; 

// Load language files
$langs->load("admin");
llxHeader();

// Check for CSRF token on form submission
// ... includes + llxHeader();

$notice = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        $notice = '<div class="error">Security check failed: invalid CSRF token.</div>';
    } else {
        $b = new Beneficiary($db);
        $b->ref = $_POST['ref'];
        $b->firstname = $_POST['firstname'];
        $b->lastname = $_POST['lastname'];
        $b->phone = $_POST['phone'];
        $b->email = $_POST['email'];
        $b->address = $_POST['address'];
        $b->note = $_POST['note'];

        $res = $b->create($user);
        if ($res > 0) {
            $notice = '<div class="ok">Beneficiary created successfully! ID: '.$res.'</div>';
        } else {
            $notice = '<div class="error">Error creating beneficiary: '.dol_escape_htmltag($b->error).'</div>';
        }
    }
}

print $notice;
print '<div><a href="beneficiaries.php">← Back to Beneficiaries</a></div><br>';

?>
<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="token" value="<?php echo newToken(); ?>">
  Ref: <input type="text" name="ref" required><br>
  First Name: <input type="text" name="firstname" required><br>
  Last Name: <input type="text" name="lastname" required><br>
  Phone: <input type="text" name="phone"><br>
  Email: <input type="email" name="email"><br>
  Address: <textarea name="address"></textarea><br>
  Note: <textarea name="note"></textarea><br>
  <input class="button" type="submit" value="Create Beneficiary">
</form>
<?php
// ... llxFooter();

?>