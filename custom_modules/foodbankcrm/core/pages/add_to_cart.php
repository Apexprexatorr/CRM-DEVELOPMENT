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
        
        // Check subscription status
        if ($subscriber->subscription_status != 'Active') {
            header('Location: product_catalog.php');
            exit;
        }
    }
}

if (!$subscriber_id) {
    accessforbidden('You must be a subscriber to add items to cart.');
}

$product_id = GETPOST('product_id', 'int');
$quantity = GETPOST('quantity', 'int') ?: 1;

if (!$product_id) {
    header('Location: product_catalog.php');
    exit;
}

llxHeader('', 'Add to Cart');

// Get product details
$sql = "SELECT d.*, (d.quantity - d.quantity_allocated) as available_stock 
        FROM ".MAIN_DB_PREFIX."foodbank_donations d 
        WHERE d.rowid = ".(int)$product_id." 
        AND d.status = 'Received' 
        AND d.is_available_for_purchase = 1";
$res = $db->query($sql);
$product = $db->fetch_object($res);

if (!$product) {
    print '<div class="error">Product not found or not available.</div>';
    print '<div><a href="product_catalog.php">← Back to Catalog</a></div>';
    llxFooter();
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] != $_SESSION['newtoken']) {
        print '<div class="error">Security check failed.</div>';
    } else {
        $quantity = (float)GETPOST('quantity', 'price');
        
        if ($quantity <= 0) {
            print '<div class="error">Quantity must be greater than 0.</div>';
        } elseif ($quantity > $product->available_stock) {
            print '<div class="error">Only '.$product->available_stock.' '.$product->unit.' available in stock.</div>';
        } else {
            // Check if product already in cart
            $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_cart 
                    WHERE fk_subscriber = ".(int)$subscriber_id." 
                    AND fk_donation = ".(int)$product_id;
            $res = $db->query($sql);
            
            if ($db->num_rows($res) > 0) {
                // Update quantity
                $cart_item = $db->fetch_object($res);
                $new_quantity = $cart_item->quantity + $quantity;
                
                if ($new_quantity > $product->available_stock) {
                    print '<div class="error">Cannot add '.$quantity.' more. Maximum available: '.($product->available_stock - $cart_item->quantity).' '.$product->unit.'</div>';
                } else {
                    $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_cart SET 
                            quantity = ".(float)$new_quantity." 
                            WHERE rowid = ".(int)$cart_item->rowid;
                    if ($db->query($sql)) {
                        header('Location: view_cart.php?msg=updated');
                        exit;
                    } else {
                        print '<div class="error">Error updating cart: '.$db->lasterror().'</div>';
                    }
                }
            } else {
                // Insert new item
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_cart 
                        (fk_subscriber, fk_donation, quantity, unit_price) 
                        VALUES (
                            ".(int)$subscriber_id.",
                            ".(int)$product_id.",
                            ".(float)$quantity.",
                            ".(float)$product->unit_price."
                        )";
                
                if ($db->query($sql)) {
                    header('Location: view_cart.php?msg=added');
                    exit;
                } else {
                    print '<div class="error">Error adding to cart: '.$db->lasterror().'</div>';
                }
            }
        }
    }
}

// Display product details and add form
print '<div><a href="product_catalog.php">← Back to Catalog</a></div><br>';

print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; max-width: 1000px;">';

// Left: Product image
print '<div>';
print '<div style="height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 120px; border-radius: 8px;">';
$emoji = '📦';
switch($product->category) {
    case 'Grains': $emoji = '🌾'; break;
    case 'Vegetables': $emoji = '🥕'; break;
    case 'Proteins': $emoji = '🍗'; break;
    case 'Dairy': $emoji = '🥛'; break;
}
print $emoji;
print '</div>';
print '</div>';

// Right: Product details
print '<div>';

print '<div style="background: #e3f2fd; color: #1976d2; padding: 5px 12px; border-radius: 3px; display: inline-block; font-size: 12px; font-weight: bold; margin-bottom: 15px;">';
print dol_escape_htmltag($product->category);
print '</div>';

print '<h1 style="margin: 0 0 10px 0;">'.dol_escape_htmltag($product->product_name).'</h1>';

print '<div style="font-size: 36px; font-weight: bold; color: #1976d2; margin: 20px 0;">';
print '₦'.number_format($product->unit_price, 2);
print '<span style="font-size: 16px; color: #666; font-weight: normal;"> / '.$product->unit.'</span>';
print '</div>';

if ($product->description) {
    print '<p style="color: #666; line-height: 1.6; margin: 20px 0;">';
    print nl2br(dol_escape_htmltag($product->description));
    print '</p>';
}

print '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">';
print '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px;">';
print '<div><strong>Ref:</strong> '.dol_escape_htmltag($product->ref).'</div>';
print '<div><strong>Unit:</strong> '.dol_escape_htmltag($product->unit).'</div>';
print '<div><strong>Available:</strong> <span style="color: #2e7d32; font-weight: bold;">'.$product->available_stock.' '.$product->unit.'</span></div>';
print '<div><strong>Expiry:</strong> '.($product->expiry_date ? dol_print_date($db->jdate($product->expiry_date), 'day') : 'N/A').'</div>';
print '</div>';
print '</div>';

// Add to cart form
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?product_id='.$product_id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div style="margin: 20px 0;">';
print '<label style="display: block; margin-bottom: 8px; font-weight: bold;">Quantity ('.$product->unit.'):</label>';
print '<input type="number" name="quantity" class="flat" value="1" min="0.1" max="'.$product->available_stock.'" step="0.1" required style="width: 150px; padding: 10px; font-size: 16px;">';
print '</div>';

print '<div style="display: flex; gap: 10px;">';
print '<button type="submit" class="button" style="flex: 1; padding: 15px; font-size: 16px; background: #1976d2; color: white; border: none; border-radius: 5px; cursor: pointer;">🛒 Add to Cart</button>';
print '<a href="product_catalog.php" class="button" style="padding: 15px 30px; text-decoration: none;">Cancel</a>';
print '</div>';

print '</form>';

print '</div>'; // end right column
print '</div>'; // end grid

llxFooter();
?>