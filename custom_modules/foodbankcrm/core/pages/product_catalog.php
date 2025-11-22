<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

$langs->load("admin");

// Check if user is a subscriber (beneficiary)
$user_is_subscriber = FoodbankPermissions::isBeneficiary($user, $db);
$subscriber_id = null;

if ($user_is_subscriber) {
    // Get subscriber record
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
    $res = $db->query($sql);
    if ($res && $db->num_rows($res) > 0) {
        $subscriber = $db->fetch_object($res);
        $subscriber_id = $subscriber->rowid;
        
        // Check subscription status
        if ($subscriber->subscription_status != 'Active') {
            llxHeader('', 'Product Catalog');
            print '<div class="error" style="padding: 40px; text-align: center;">';
            print '<h2>🔒 Subscription Required</h2>';
            print '<p>Your subscription status is: <strong>'.dol_escape_htmltag($subscriber->subscription_status).'</strong></p>';
            
            if ($subscriber->subscription_status == 'Pending') {
                print '<p>Please complete your payment to activate your subscription.</p>';
            } elseif ($subscriber->subscription_status == 'Expired') {
                print '<p>Your subscription has expired. Please renew to continue shopping.</p>';
            }
            
            print '<br><a class="button" href="/custom/foodbankcrm/core/pages/dashboard_beneficiary.php">Go to Dashboard</a>';
            print '</div>';
            llxFooter();
            exit;
        }
    }
}

llxHeader('', 'Product Catalog');

// Search and filter
$search_query = GETPOST('search', 'alpha');
$filter_category = GETPOST('category', 'alpha');
$sort = GETPOST('sort', 'alpha') ?: 'name';

print '<div style="margin-bottom: 20px;">';
print '<h1>🛒 Product Catalog</h1>';
print '</div>';

// Search form
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" style="margin-bottom: 20px;">';
print '<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">';

print '<input type="text" name="search" class="flat" placeholder="Search products..." value="'.dol_escape_htmltag($search_query).'" style="flex: 1; min-width: 200px;">';

print '<select name="category" class="flat" style="width: 150px;">';
print '<option value="">All Categories</option>';
print '<option value="Grains" '.($filter_category == 'Grains' ? 'selected' : '').'>Grains</option>';
print '<option value="Vegetables" '.($filter_category == 'Vegetables' ? 'selected' : '').'>Vegetables</option>';
print '<option value="Proteins" '.($filter_category == 'Proteins' ? 'selected' : '').'>Proteins</option>';
print '<option value="Dairy" '.($filter_category == 'Dairy' ? 'selected' : '').'>Dairy</option>';
print '<option value="Other" '.($filter_category == 'Other' ? 'selected' : '').'>Other</option>';
print '</select>';

print '<select name="sort" class="flat" style="width: 150px;">';
print '<option value="name" '.($sort == 'name' ? 'selected' : '').'>Sort by Name</option>';
print '<option value="price_low" '.($sort == 'price_low' ? 'selected' : '').'>Price: Low to High</option>';
print '<option value="price_high" '.($sort == 'price_high' ? 'selected' : '').'>Price: High to Low</option>';
print '<option value="stock" '.($sort == 'stock' ? 'selected' : '').'>Stock Available</option>';
print '</select>';

print '<button type="submit" class="button">Search</button>';
print '<a href="'.$_SERVER['PHP_SELF'].'" class="button">Clear</a>';

print '</div>';
print '</form>';

// Cart summary (if subscriber)
if ($subscriber_id) {
    $sql = "SELECT COUNT(*) as count, SUM(quantity * unit_price) as total 
            FROM ".MAIN_DB_PREFIX."foodbank_cart 
            WHERE fk_subscriber = ".(int)$subscriber_id;
    $res = $db->query($sql);
    $cart = $db->fetch_object($res);
    
    if ($cart && $cart->count > 0) {
        print '<div style="background: #e8f5e9; padding: 15px; border-radius: 5px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">';
        print '<div>';
        print '<strong>🛒 Your Cart:</strong> '.$cart->count.' item(s) | ';
        print '<strong>Total: ₦'.number_format($cart->total, 2).'</strong>';
        print '</div>';
        print '<a class="button" href="view_cart.php">View Cart & Checkout</a>';
        print '</div>';
    }
}

// Build SQL query
$sql = "SELECT d.*, 
        (d.quantity - d.quantity_allocated) as available_stock,
        v.name as vendor_name
        FROM ".MAIN_DB_PREFIX."foodbank_donations d
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_vendors v ON d.fk_vendor = v.rowid
        WHERE d.status = 'Received' 
        AND d.is_available_for_purchase = 1
        AND (d.quantity - d.quantity_allocated) > 0";

// Search filter
if ($search_query) {
    $sql .= " AND (d.product_name LIKE '%".$db->escape($search_query)."%' 
              OR d.description LIKE '%".$db->escape($search_query)."%')";
}

// Category filter
if ($filter_category) {
    $sql .= " AND d.category = '".$db->escape($filter_category)."'";
}

// Sorting
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY d.unit_price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY d.unit_price DESC";
        break;
    case 'stock':
        $sql .= " ORDER BY available_stock DESC";
        break;
    default:
        $sql .= " ORDER BY d.product_name ASC";
}

$res = $db->query($sql);

if (!$res) {
    print '<div class="error">SQL Error: '.$db->lasterror().'</div>';
    llxFooter();
    exit;
}

$num_products = $db->num_rows($res);

print '<div style="margin-bottom: 15px; color: #666;">';
print 'Showing <strong>'.$num_products.'</strong> product'.($num_products != 1 ? 's' : '');
print '</div>';

if ($num_products == 0) {
    print '<div style="text-align: center; padding: 60px; background: #f9f9f9; border-radius: 8px;">';
    print '<div style="font-size: 64px; margin-bottom: 20px;">📦</div>';
    print '<h2>No Products Available</h2>';
    print '<p style="color: #666;">Check back later for new products!</p>';
    print '</div>';
} else {
    // Product grid
    print '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">';
    
    while ($obj = $db->fetch_object($res)) {
        $in_stock = $obj->available_stock > 0;
        $low_stock = $obj->available_stock <= $obj->stock_threshold;
        
        print '<div style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-5px)\'" onmouseout="this.style.transform=\'translateY(0)\'">';
        
        // Product image placeholder
        print '<div style="height: 180px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 64px;">';
        
        // Category emoji
        $emoji = '📦';
        switch($obj->category) {
            case 'Grains': $emoji = '🌾'; break;
            case 'Vegetables': $emoji = '🥕'; break;
            case 'Proteins': $emoji = '🍗'; break;
            case 'Dairy': $emoji = '🥛'; break;
        }
        print $emoji;
        print '</div>';
        
        // Product info
        print '<div style="padding: 15px;">';
        
        // Category badge
        print '<div style="display: inline-block; background: #e3f2fd; color: #1976d2; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; margin-bottom: 10px;">';
        print dol_escape_htmltag($obj->category);
        print '</div>';
        
        // Product name
        print '<h3 style="margin: 10px 0; font-size: 18px; color: #333;">';
        print dol_escape_htmltag($obj->product_name);
        print '</h3>';
        
        // Description
        if ($obj->description) {
            print '<p style="font-size: 13px; color: #666; margin: 10px 0; line-height: 1.4;">';
            print dol_trunc(dol_escape_htmltag($obj->description), 80);
            print '</p>';
        }
        
        // Vendor
        if ($obj->vendor_name) {
            print '<p style="font-size: 12px; color: #999; margin: 5px 0;">';
            print '👤 '.dol_escape_htmltag($obj->vendor_name);
            print '</p>';
        }
        
        // Stock info
        print '<div style="margin: 10px 0;">';
        if ($low_stock) {
            print '<span style="background: #fff3e0; color: #f57c00; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">';
            print '⚠ Low Stock: '.$obj->available_stock.' '.$obj->unit;
        } else {
            print '<span style="background: #e8f5e9; color: #2e7d32; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">';
            print '✓ In Stock: '.$obj->available_stock.' '.$obj->unit;
        }
        print '</span>';
        print '</div>';
        
        // Price and action
        print '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">';
        
        print '<div>';
        print '<div style="font-size: 24px; font-weight: bold; color: #1976d2;">₦'.number_format($obj->unit_price, 2).'</div>';
        print '<div style="font-size: 11px; color: #999;">per '.$obj->unit.'</div>';
        print '</div>';
        
        if ($subscriber_id && $in_stock) {
            print '<a href="add_to_cart.php?product_id='.$obj->rowid.'" class="button" style="text-decoration: none;">Add to Cart</a>';
        } else {
            print '<button class="button" disabled style="opacity: 0.5; cursor: not-allowed;">Out of Stock</button>';
        }
        
        print '</div>';
        
        print '</div>'; // end product info
        print '</div>'; // end product card
    }
    
    print '</div>'; // end grid
}

print '<div style="margin-top: 30px; background: #e3f2fd; padding: 20px; border-radius: 5px; border-left: 4px solid #2196f3;">';
print '<h3 style="margin-top: 0;">💡 How to Order</h3>';
print '<ol style="margin-bottom: 0;">';
print '<li>Browse products and add items to your cart</li>';
print '<li>Review your cart and adjust quantities</li>';
print '<li>Proceed to checkout</li>';
print '<li>Choose payment method (Pay Now or Pay on Delivery)</li>';
print '<li>Track your order status in your dashboard</li>';
print '</ol>';
print '</div>';

llxFooter();
?>