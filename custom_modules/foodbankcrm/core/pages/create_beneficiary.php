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
        $b->email = $_POST['email'];
        $b->phone = $_POST['phone'];
        $b->address = $_POST['address'];
        $b->household_size = $_POST['household_size'];
        $b->note = $_POST['note'];
        
        // NEW: Subscription fields
        $subscription_type = GETPOST('subscription_type', 'alpha');
        $subscription_status = 'Pending'; // Default to pending until payment
        
        $res = $b->create($user);
        if ($res > 0) {
            // Update subscription fields
            $tier_id = GETPOST('subscription_tier', 'int');
            
            if ($tier_id > 0) {
                // Get tier details
                $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE rowid = ".(int)$tier_id;
                $resql = $db->query($sql);
                $tier = $db->fetch_object($resql);
                
                if ($tier) {
                    // Calculate end date
                    $start_date = date('Y-m-d');
                    $end_date = date('Y-m-d', strtotime('+'.$tier->duration_months.' months'));
                    
                    // Update beneficiary with subscription info
                    $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_beneficiaries SET 
                            subscription_type = '".$db->escape($tier->tier_type)."',
                            subscription_status = 'Pending',
                            subscription_start_date = '".$start_date."',
                            subscription_end_date = '".$end_date."',
                            subscription_fee = ".(float)$tier->price."
                            WHERE rowid = ".(int)$res;
                    $db->query($sql);
                }
            }
            
            $notice = '<div class="ok">Subscriber created successfully! Ref: '.$b->ref.' (ID: '.$res.')';
            if ($tier_id > 0) {
                $notice .= '<br>📧 Payment instructions will be sent to their email.';
            }
            $notice .= '</div>';
        } else {
            $notice = '<div class="error">Error creating subscriber: '.dol_escape_htmltag($b->error).'</div>';
        }
    }
}

// Get available subscription tiers
$subscription_tiers = array();
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_subscription_tiers WHERE is_active = 1 ORDER BY price ASC";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $subscription_tiers[] = $obj;
    }
}

print $notice;
print '<div><a href="beneficiaries.php">← Back to Subscribers</a></div><br>';
?>

<h2>Create Subscriber</h2>
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
      <td>Email</td>
      <td><input class="flat" type="email" name="email"></td>
    </tr>
    <tr>
      <td>Phone</td>
      <td><input class="flat" type="text" name="phone"></td>
    </tr>
    <tr>
      <td>Address</td>
      <td><textarea class="flat" name="address" rows="3"></textarea></td>
    </tr>
    <tr>
      <td>Household Size</td>
      <td><input class="flat" type="number" name="household_size" min="1" value="1"></td>
    </tr>
    <tr>
      <td>Note</td>
      <td><textarea class="flat" name="note" rows="3"></textarea></td>
    </tr>
    
    <!-- NEW: Subscription Section -->
    <tr class="liste_titre">
      <td colspan="2"><h3 style="margin: 10px 0;">💳 Subscription Plan</h3></td>
    </tr>
    <tr>
      <td><span class="fieldrequired">Select Plan</span></td>
      <td>
        <?php if (count($subscription_tiers) > 0): ?>
        <table class="noborder" style="width: 100%;">
          <?php foreach ($subscription_tiers as $tier): ?>
          <tr style="border-bottom: 1px solid #eee;">
            <td style="padding: 10px;">
              <input type="radio" name="subscription_tier" value="<?php echo $tier->rowid; ?>" id="tier_<?php echo $tier->rowid; ?>" required>
              <label for="tier_<?php echo $tier->rowid; ?>" style="cursor: pointer;">
                <strong><?php echo dol_escape_htmltag($tier->tier_name); ?></strong> 
                (<?php echo dol_escape_htmltag($tier->tier_type); ?>)
                - <strong>₦<?php echo number_format($tier->price, 2); ?></strong>
                <br>
                <span style="font-size: 12px; color: #666;">
                  <?php echo $tier->duration_months; ?> months | <?php echo dol_escape_htmltag($tier->description); ?>
                </span>
              </label>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div class="warning">No subscription tiers available. <a href="create_subscription_tier.php">Create a tier first</a>.</div>
        <?php endif; ?>
      </td>
    </tr>
  </table>
  
  <br>
  <div class="center">
    <input class="button" type="submit" value="Create Subscriber" <?php echo count($subscription_tiers) == 0 ? 'disabled' : ''; ?>>
    <a class="button" href="beneficiaries.php">Cancel</a>
  </div>
</form>

<?php if (count($subscription_tiers) > 0): ?>
<div style="margin-top: 20px; background: #e8f5e9; padding: 15px; border-radius: 5px; border-left: 4px solid #4caf50;">
  <h4 style="margin-top: 0;">💡 Subscription Process</h4>
  <ol style="margin-bottom: 0;">
    <li>Create subscriber and select a subscription plan</li>
    <li>Subscriber receives payment instructions via email</li>
    <li>Admin confirms payment manually (until Paystack is integrated)</li>
    <li>Subscription status changes to "Active"</li>
    <li>Subscriber can then access product catalog and place orders</li>
  </ol>
</div>
<?php endif; ?>

<?php llxFooter(); ?>