<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/distribution.class.php';

$langs->load("admin");

// Check if user is a subscriber
$user_is_subscriber = FoodbankPermissions::isBeneficiary($user, $db);
$subscriber_id = null;
$subscriber = null;

if ($user_is_subscriber) {
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $db->num_rows($res) > 0) {
        $subscriber = $db->fetch_object($res);
        $subscriber_id = $subscriber->rowid;
        
        // Check subscription status
        if ($subscriber->subscription_status != 'Active') {
            header('Location: product_catalog.php');
            exit;
        }
    }
}

if (!$subscriber_id) {
    accessforbidden('You must be a subscriber to checkout.');
}

llxHeader('', 'Checkout');

// Get cart items
$sql = "SELECT c.*, d.product_name, d.unit, d.ref as product_ref, d.fk_warehouse,
        (d.quantity - d.quantity_allocated) as available_stock,
        (c.quantity * c.unit_price) as line_total
        FROM ".MAIN_DB_PREFIX."foodbank_cart c
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_donations d ON c.fk_donation = d.rowid
        WHERE c.fk_subscriber = ".(int)$subscriber_id;

$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    print '<div class="error">Your cart is empty.</div>';
    print '<div><a href="product_catalog.php">← Browse Products</a></div>';
    llxFooter();
    exit;
}

// Validate stock availability
$cart_items = array();
$stock_issues = array();
$grand_total = 0;

while ($obj = $db->fetch_object($res)) {
    $cart_items[] = $obj;
    $grand_total += $obj->line_total;
    
    if ($obj->quantity > $obj->available_stock) {
        $stock_issues[] = $obj->product_name.' (requested: '.$obj->quantity.', available: '.$obj->available_stock.')';
    }
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed.</div>';
    } elseif (count($stock_issues) > 0) {
        print '<div class="error">Cannot proceed: Some items are out of stock.</div>';
    } else {
        $delivery_address = GETPOST('delivery_address', 'restricthtml');
        $delivery_notes = GETPOST('delivery_notes', 'restricthtml');
        $payment_method = GETPOST('payment_method', 'alpha');
        
        if (empty($delivery_address)) {
            print '<div class="error">Delivery address is required.</div>';
        } elseif (empty($payment_method)) {
            print '<div class="error">Please select a payment method.</div>';
        } else {
            $db->begin();
            
            try {
                // Create distribution (order)
                $dist = new Distribution($db);
                $dist->ref = ''; // Auto-generate
                $dist->fk_beneficiary = $subscriber_id;
                $dist->scheduled_date = date('Y-m-d');
                $dist->delivery_address = $delivery_address;
                $dist->notes = $delivery_notes;
                $dist->status = 'Pending';
                
                $dist_id = $dist->create($user);
                
                if ($dist_id <= 0) {
                    throw new Exception('Failed to create order: '.$dist->error);
                }
                
                // Add items to distribution
                foreach ($cart_items as $item) {
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distribution_lines 
                            (fk_distribution, fk_donation, quantity_distributed, fk_warehouse, unit_price) 
                            VALUES (
                                ".(int)$dist_id.",
                                ".(int)$item->fk_donation.",
                                ".(float)$item->quantity.",
                                ".(int)$item->fk_warehouse.",
                                ".(float)$item->unit_price."
                            )";
                    
                    if (!$db->query($sql)) {
                        throw new Exception('Failed to add order item: '.$db->lasterror());
                    }
                    
                    // Update allocated quantity in donations
                    $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_donations 
                            SET quantity_allocated = quantity_allocated + ".(float)$item->quantity." 
                            WHERE rowid = ".(int)$item->fk_donation;
                    
                    if (!$db->query($sql)) {
                        throw new Exception('Failed to allocate stock: '.$db->lasterror());
                    }
                }
                
                // Record payment
                $payment_status = ($payment_method == 'pay_now') ? 'Pending' : 'Pay_On_Delivery';
                
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_payments 
                        (fk_subscriber, fk_order, payment_type, amount, payment_method, payment_status) 
                        VALUES (
                            ".(int)$subscriber_id.",
                            ".(int)$dist_id.",
                            'Order',
                            ".(float)$grand_total.",
                            '".$db->escape($payment_method)."',
                            '".$db->escape($payment_status)."'
                        )";
                
                if (!$db->query($sql)) {
                    throw new Exception('Failed to record payment: '.$db->lasterror());
                }
                
                // Update distribution with payment info
                $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_distributions 
                        SET payment_status = '".$db->escape($payment_status)."',
                            payment_method = '".$db->escape($payment_method)."',
                            total_amount = ".(float)$grand_total."
                        WHERE rowid = ".(int)$dist_id;
                $db->query($sql);
                
                // Clear cart
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart WHERE fk_subscriber = ".(int)$subscriber_id;
                $db->query($sql);
                
                $db->commit();
                
                // Redirect to success page
                header('Location: order_confirmation.php?order_id='.$dist_id);
                exit;
                
            } catch (Exception $e) {
                $db->rollback();
                print '<div class="error">Error placing order: '.dol_escape_htmltag($e->getMessage()).'</div>';
            }
        }
    }
}

print '<h1>🛒 Checkout</h1>';

// Display stock issues
if (count($stock_issues) > 0) {
    print '<div class="error">';
    print '<h3>⚠️ Stock Availability Issues</h3>';
    print '<p>The following items have insufficient stock:</p>';
    print '<ul>';
    foreach ($stock_issues as $issue) {
        print '<li>'.dol_escape_htmltag($issue).'</li>';
    }
    print '</ul>';
    print '<p><a href="view_cart.php">← Update your cart</a></p>';
    print '</div>';
}

print '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">';

// Left column: Order details
print '<div>';

// Order summary
print '<h2>📦 Order Summary</h2>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Product</th>';
print '<th class="center">Quantity</th>';
print '<th class="center">Price</th>';
print '<th class="center">Total</th>';
print '</tr>';

foreach ($cart_items as $item) {
    print '<tr class="oddeven">';
    print '<td><strong>'.dol_escape_htmltag($item->product_name).'</strong></td>';
    print '<td class="center">'.$item->quantity.' '.$item->unit.'</td>';
    print '<td class="center">₦'.number_format($item->unit_price, 2).'</td>';
    print '<td class="center"><strong>₦'.number_format($item->line_total, 2).'</strong></td>';
    print '</tr>';
}

print '<tr class="liste_total">';
print '<td colspan="3" class="right"><strong>Grand Total:</strong></td>';
print '<td class="center"><strong style="font-size: 18px; color: #1976d2;">₦'.number_format($grand_total, 2).'</strong></td>';
print '</tr>';

print '</table>';

// Delivery information
print '<h2 style="margin-top: 30px;">🚚 Delivery Information</h2>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="border centpercent">';

print '<tr>';
print '<td width="30%"><span class="fieldrequired">Delivery Address</span></td>';
print '<td>';
print '<textarea name="delivery_address" class="flat" rows="4" required style="width: 100%;">'.dol_escape_htmltag($subscriber->address).'</textarea>';
print '<div style="font-size: 12px; color: #666; margin-top: 5px;">Pre-filled from your profile. You can modify it.</div>';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>Delivery Notes</td>';
print '<td>';
print '<textarea name="delivery_notes" class="flat" rows="3" style="width: 100%;" placeholder="e.g., Gate code, preferred delivery time, special instructions..."></textarea>';
print '</td>';
print '</tr>';

print '<tr>';
print '<td>Contact Phone</td>';
print '<td><strong>'.dol_escape_htmltag($subscriber->phone).'</strong></td>';
print '</tr>';

print '</table>';

print '</div>'; // end left column

// Right column: Payment
print '<div>';

print '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; position: sticky; top: 20px;">';

print '<h2 style="margin-top: 0;">💳 Payment Method</h2>';

print '<div style="margin-bottom: 15px;">';
print '<label style="display: block; padding: 15px; background: white; border: 2px solid #e0e0e0; border-radius: 5px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'" onmouseout="this.style.borderColor=\'#e0e0e0\'">';
print '<input type="radio" name="payment_method" value="pay_now" required> ';
print '<strong>Pay Now</strong>';
print '<div style="font-size: 12px; color: #666; margin-top: 5px; margin-left: 20px;">Complete payment via Paystack (Card/Bank Transfer)</div>';
print '</label>';
print '</div>';

print '<div style="margin-bottom: 20px;">';
print '<label style="display: block; padding: 15px; background: white; border: 2px solid #e0e0e0; border-radius: 5px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#1976d2\'" onmouseout="this.style.borderColor=\'#e0e0e0\'">';
print '<input type="radio" name="payment_method" value="pay_on_delivery" required> ';
print '<strong>Pay on Delivery</strong>';
print '<div style="font-size: 12px; color: #666; margin-top: 5px; margin-left: 20px;">Pay cash when order is delivered</div>';
print '</label>';
print '</div>';

print '<div style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">';
print '<span>Subtotal:</span>';
print '<span>₦'.number_format($grand_total, 2).'</span>';
print '</div>';
print '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">';
print '<span>Delivery Fee:</span>';
print '<span style="color: #2e7d32;">FREE</span>';
print '</div>';
print '<hr style="margin: 15px 0;">';
print '<div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold;">';
print '<span>Total:</span>';
print '<span style="color: #1976d2;">₦'.number_format($grand_total, 2).'</span>';
print '</div>';
print '</div>';

print '<button type="submit" class="butAction" style="width: 100%; padding: 15px; font-size: 16px; border: none; border-radius: 5px; cursor: pointer;" '.((count($stock_issues) > 0) ? 'disabled' : '').'>Place Order</button>';

print '<div style="margin-top: 15px; text-align: center;">';
print '<a href="view_cart.php" style="color: #666; font-size: 13px;">← Back to Cart</a>';
print '</div>';

print '</form>';

print '</div>'; // end sticky box

print '</div>'; // end right column

print '</div>'; // end grid

// Info box
print '<div style="margin-top: 30px; background: #e3f2fd; padding: 20px; border-radius: 5px; border-left: 4px solid #2196f3;">';
print '<h3 style="margin-top: 0;">📋 Order Process</h3>';
print '<ol style="margin-bottom: 0;">';
print '<li><strong>Order Confirmation:</strong> You\'ll receive an order confirmation immediately</li>';
print '<li><strong>Preparation:</strong> Our team will prepare your order (Status: Bundled)</li>';
print '<li><strong>Pickup:</strong> Order picked up for delivery (Status: Picked Up)</li>';
print '<li><strong>In Transit:</strong> Order is on the way to you</li>';
print '<li><strong>Delivery:</strong> Order delivered to your address</li>';
print '</ol>';
print '<p style="margin-bottom: 0; margin-top: 15px;"><strong>Estimated Delivery:</strong> 2-4 business days</p>';
print '</div>';

llxFooter();
?>