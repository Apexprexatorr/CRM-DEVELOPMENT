<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a subscriber
$user_is_subscriber = FoodbankPermissions::isBeneficiary($user, $db);
$subscriber_id = null;

if ($user_is_subscriber) {
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $db->num_rows($res) > 0) {
        $subscriber = $db->fetch_object($res);
        $subscriber_id = $subscriber->rowid;
    }
}

if (!$subscriber_id) {
    accessforbidden('You must be a subscriber to view cart.');
}

llxHeader('', 'Shopping Cart');

// Handle messages
$msg = GETPOST('msg', 'alpha');
if ($msg == 'added') {
    print '<div class="ok">✓ Product added to cart!</div>';
} elseif ($msg == 'updated') {
    print '<div class="ok">✓ Cart updated!</div>';
} elseif ($msg == 'removed') {
    print '<div class="ok">✓ Item removed from cart!</div>';
}

// Handle remove item
$action = GETPOST('action', 'alpha');
if ($action == 'remove' && GETPOST('id', 'int')) {
    $cart_id = GETPOST('id', 'int');
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_cart 
            WHERE rowid = ".(int)$cart_id." 
            AND fk_subscriber = ".(int)$subscriber_id;
    $db->query($sql);
    header('Location: view_cart.php?msg=removed');
    exit;
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    $quantities = $_POST['quantity'] ?? array();
    
    foreach ($quantities as $cart_id => $qty) {
        $qty = (float)$qty;
        if ($qty > 0) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_cart 
                    SET quantity = ".(float)$qty." 
                    WHERE rowid = ".(int)$cart_id." 
                    AND fk_subscriber = ".(int)$subscriber_id;
            $db->query($sql);
        }
    }
    
    header('Location: view_cart.php?msg=updated');
    exit;
}

print '<h1>🛒 Your Shopping Cart</h1>';

// Get cart items
$sql = "SELECT c.*, d.product_name, d.unit, d.category, 
        (d.quantity - d.quantity_allocated) as available_stock,
        (c.quantity * c.unit_price) as line_total
        FROM ".MAIN_DB_PREFIX."foodbank_cart c
        INNER JOIN ".MAIN_DB_PREFIX."foodbank_donations d ON c.fk_donation = d.rowid
        WHERE c.fk_subscriber = ".(int)$subscriber_id."
        ORDER BY c.date_added DESC";

$res = $db->query($sql);

if (!$res || $db->num_rows($res) == 0) {
    print '<div style="text-align: center; padding: 60px; background: #f9f9f9; border-radius: 8px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">🛒</div>';
    print '<h2>Your Cart is Empty</h2>';
    print '<p style="color: #666;">Start shopping to add products to your cart!</p>';
    print '<br><a class="button" href="product_catalog.php">Browse Products</a>';
    print '</div>';
    llxFooter();
    exit;
}

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>Product</th>';
print '<th class="center">Unit Price</th>';
print '<th class="center">Quantity</th>';
print '<th class="center">Total</th>';
print '<th class="center">Actions</th>';
print '</tr>';

$grand_total = 0;

while ($obj = $db->fetch_object($res)) {
    $grand_total += $obj->line_total;
    
    print '<tr class="oddeven">';
    
    // Product
    print '<td>';
    print '<strong>'.dol_escape_htmltag($obj->product_name).'</strong><br>';
    print '<span style="font-size: 12px; color: #666;">Category: '.dol_escape_htmltag($obj->category).'</span><br>';
    if ($obj->quantity > $obj->available_stock) {
        print '<span style="color: #d32f2f; font-size: 12px; font-weight: bold;">⚠ Only '.$obj->available_stock.' '.$obj->unit.' available!</span>';
    }
    print '</td>';
    
    // Unit price
    print '<td class="center">₦'.number_format($obj->unit_price, 2).'<br><span style="font-size: 11px; color: #666;">per '.$obj->unit.'</span></td>';
    
    // Quantity
    print '<td class="center">';
    print '<input type="number" name="quantity['.$obj->rowid.']" value="'.$obj->quantity.'" min="0.1" max="'.$obj->available_stock.'" step="0.1" class="flat" style="width: 80px; text-align: center;">';
    print '<div style="font-size: 11px; color: #666; margin-top: 3px;">'.$obj->unit.'</div>';
    print '</td>';
    
    // Total
    print '<td class="center"><strong>₦'.number_format($obj->line_total, 2).'</strong></td>';
    
    // Actions
    print '<td class="center">';
    print '<a href="'.$_SERVER['PHP_SELF'].'?action=remove&id='.$obj->rowid.'" onclick="return confirm(\'Remove this item?\');" style="color: #d32f2f;">Remove</a>';
    print '</td>';
    
    print '</tr>';
}

print '</table>';

print '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding: 20px; background: #f5f5f5; border-radius: 5px;">';
print '<button type="submit" name="update_cart" class="button">Update Cart</button>';
print '<div style="text-align: right;">';
print '<div style="font-size: 14px; color: #666; margin-bottom: 5px;">Grand Total:</div>';
print '<div style="font-size: 32px; font-weight: bold; color: #1976d2;">₦'.number_format($grand_total, 2).'</div>';
print '</div>';
print '</div>';

print '</form>';

print '<div style="display: flex; gap: 15px; margin-top: 20px;">';
print '<a class="button" href="product_catalog.php">← Continue Shopping</a>';
print '<a class="button butAction" href="checkout.php" style="flex: 1; text-align: center;">Proceed to Checkout →</a>';
print '</div>';

llxFooter();
?>