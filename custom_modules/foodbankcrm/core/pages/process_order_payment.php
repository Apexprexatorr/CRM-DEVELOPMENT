<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

$order_id = GETPOST('order_id', 'int');

if (!$order_id) {
    header('Location: my_orders.php');
    exit;
}

// Get order
$sql = "SELECT d.*, b.firstname, b.lastname, b.email, b.phone
        FROM ".MAIN_DB_PREFIX."foodbank_distributions d
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_beneficiaries b ON d.fk_beneficiary = b.rowid
        WHERE d.rowid = ".(int)$order_id;

$res = $db->query($sql);
$order = $db->fetch_object($res);

if (!$order) {
    header('Location: my_orders.php');
    exit;
}

// Get order items for display
$sql_items = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines 
              WHERE fk_distribution = ".(int)$order_id." LIMIT 5";
$res_items = $db->query($sql_items);

// Paystack key
$paystack_public_key = 'pk_test_27e3e802c6afc73a7b4cadb65254648a9cebd6dc';

llxHeader('', 'Complete Payment');

echo '<style>
#id-left { display: none !important; }
#id-right { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
.fiche { max-width: 100% !important; }
body { background: #f8f9fa !important; }
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.loading { animation: pulse 1.5s ease-in-out infinite; }
</style>';

print '<div style="max-width: 700px; margin: 50px auto; padding: 0 20px;">';

// Payment Card
print '<div style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1);">';

// Header
print '<div style="text-align: center; margin-bottom: 30px;">';
print '<div style="font-size: 64px; margin-bottom: 15px;">💳</div>';
print '<h1 style="margin: 0 0 10px 0; font-size: 28px;">Complete Payment</h1>';
print '<p style="color: #666; margin: 0;">Secure payment powered by Paystack</p>';
print '</div>';

// Order Summary
print '<div style="background: #f8f9fa; padding: 25px; border-radius: 12px; margin-bottom: 30px;">';
print '<h3 style="margin: 0 0 20px 0; font-size: 18px; color: #333;">Order Summary</h3>';

print '<div style="display: flex; justify-content: space-between; margin-bottom: 12px;">';
print '<span style="color: #666;">Order Reference:</span>';
print '<strong>'.dol_escape_htmltag($order->ref).'</strong>';
print '</div>';

print '<div style="display: flex; justify-content: space-between; margin-bottom: 12px;">';
print '<span style="color: #666;">Customer:</span>';
print '<strong>'.dol_escape_htmltag($order->firstname.' '.$order->lastname).'</strong>';
print '</div>';

print '<div style="display: flex; justify-content: space-between; margin-bottom: 12px;">';
print '<span style="color: #666;">Phone:</span>';
print '<strong>'.dol_escape_htmltag($order->phone).'</strong>';
print '</div>';

// Show order items
if ($res_items && $db->num_rows($res_items) > 0) {
    print '<div style="margin: 20px 0; padding-top: 15px; border-top: 1px solid #dee2e6;">';
    print '<div style="color: #666; font-size: 13px; margin-bottom: 10px;">ORDER ITEMS:</div>';
    while ($item = $db->fetch_object($res_items)) {
        print '<div style="font-size: 14px; padding: 5px 0;">• '.dol_escape_htmltag($item->product_name).' ('.number_format($item->quantity, 0).' '.$item->unit.')</div>';
    }
    print '</div>';
}

print '<hr style="margin: 20px 0; border: none; border-top: 2px solid #dee2e6;">';

print '<div style="display: flex; justify-content: space-between; align-items: center;">';
print '<span style="font-size: 20px; font-weight: bold;">Total Amount:</span>';
print '<span style="font-size: 32px; font-weight: bold; color: #28a745;">₦'.number_format($order->total_amount, 2).'</span>';
print '</div>';

print '</div>';

// Payment Button
print '<button id="paystack-btn" class="butAction" style="width: 100%; padding: 20px; font-size: 18px; font-weight: bold; margin-bottom: 20px; cursor: pointer; border: none; border-radius: 8px;">';
print '💳 Pay ₦'.number_format($order->total_amount, 0).' with Paystack';
print '</button>';

// Loading indicator
print '<div id="loading" style="display: none; text-align: center; padding: 20px;">';
print '<div class="loading" style="font-size: 48px; margin-bottom: 15px;">⏳</div>';
print '<p style="color: #666;">Loading payment gateway...</p>';
print '</div>';

// Security info
print '<div style="text-align: center; padding: 20px; background: #e8f5e9; border-radius: 8px; margin-bottom: 20px;">';
print '<div style="color: #2e7d32; font-size: 14px; margin-bottom: 8px;">🔒 Secure Payment</div>';
print '<p style="margin: 0; font-size: 13px; color: #666;">Your payment information is encrypted and secure</p>';
print '</div>';

print '<div style="text-align: center;">';
print '<a href="order_confirmation.php?order_id='.$order_id.'" style="color: #666; font-size: 14px; text-decoration: none;">← Back to Order</a>';
print '</div>';

print '</div>';
print '</div>';

?>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
console.log('Paystack script loaded');

document.getElementById('paystack-btn').addEventListener('click', function(e) {
    e.preventDefault();
    
    console.log('Payment button clicked');
    document.getElementById('loading').style.display = 'block';
    document.getElementById('paystack-btn').style.display = 'none';
    
    try {
        var handler = PaystackPop.setup({
            key: '<?php echo $paystack_public_key; ?>',
            email: '<?php echo dol_escape_js($order->email); ?>',
            amount: <?php echo $order->total_amount * 100; ?>,
            currency: 'NGN',
            ref: 'ORD-<?php echo $order->ref; ?>-'+Math.floor((Math.random() * 1000000000) + 1),
            metadata: {
                order_id: <?php echo $order_id; ?>,
                customer_name: '<?php echo dol_escape_js($order->firstname.' '.$order->lastname); ?>',
                order_ref: '<?php echo dol_escape_js($order->ref); ?>',
                payment_type: 'order'
            },
            callback: function(response){
                console.log('Payment successful:', response);
                alert('Payment successful! Reference: ' + response.reference);
                window.location.href = 'order_confirmation.php?order_id=<?php echo $order_id; ?>';
            },
            onClose: function(){
                console.log('Payment modal closed');
                document.getElementById('loading').style.display = 'none';
                document.getElementById('paystack-btn').style.display = 'block';
                alert('Payment cancelled');
            }
        });
        
        handler.openIframe();
        console.log('Paystack iframe opened');
        
    } catch(error) {
        console.error('Paystack error:', error);
        alert('Error loading payment gateway: ' + error.message);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('paystack-btn').style.display = 'block';
    }
});

// Auto-hide loading after 5 seconds if stuck
setTimeout(function() {
    if (document.getElementById('loading').style.display === 'block') {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('paystack-btn').style.display = 'block';
        alert('Payment gateway taking too long. Please try again.');
    }
}, 5000);
</script>

<?php
llxFooter();
?>
