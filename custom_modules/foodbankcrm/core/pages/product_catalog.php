<?php
/**
 * Product Catalog - LIST VIEW FOR ITEMS
 */

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db, $conf;

if (isset($_SESSION['foodbank_checked'])) {
    $_SESSION['foodbank_checked'] = false;
}

$langs->load("admin");

$user_is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);

if (!$user_is_beneficiary) {
    accessforbidden('You do not have access to the product catalog.');
}

$search_text = GETPOST('search_text', 'alpha');
$sort_by = GETPOST('sort_by', 'alpha') ?: 'name';

llxHeader('', 'Browse Packages');

// --- AGGRESSIVE CSS RESET & FLUID LAYOUT ---
print '<style>
    /* 1. HIDE ALL DOLIBARR CHROME */
    #id-top, .side-nav, .side-nav-vert, #id-left, .login_block, .tmenudiv, .nav-bar, header {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    /* 2. RESET PARENT CONTAINERS */
    html, body {
        background-color: #f8f9fa !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
        overflow-x: hidden !important;
    }

    #id-container {
        display: block !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    /* 3. FORCE CONTENT TO FULL VIEWPORT WIDTH */
    #id-right, .id-right {
        margin: 0 !important;
        padding: 0 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        flex: none !important;
        display: block !important;
    }

    .fiche { width: 100% !important; max-width: 100% !important; margin: 0 !important; }

    /* 4. CUSTOM FLUID CONTAINER */
    .ben-container { 
        width: 98%;        
        max-width: none;   
        margin: 0 auto; 
        padding: 30px 0; 
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    /* CATALOG STYLES */
    .search-box { 
        background: white; 
        padding: 25px; 
        border-radius: 8px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
        margin-bottom: 30px; 
        border: 1px solid #eee;
    }

    .pkg-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
        gap: 25px; 
    }

    .pkg-card { 
        background: white; 
        border-radius: 12px; 
        overflow: hidden; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        transition: transform 0.2s; 
        border: 1px solid #eee; 
        display: flex; 
        flex-direction: column; 
    }
    .pkg-card:hover { transform: translateY(-5px); border-color: #667eea; }
    
    .pkg-img { 
        height: 180px; 
        background: #f4f6f7; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 80px; 
        border-bottom: 1px solid #eee;
    }
    
    .pkg-body { padding: 25px; flex: 1; display: flex; flex-direction: column; }
    .pkg-title { font-size: 20px; font-weight: 800; color: #2c3e50; margin-bottom: 5px; }
    .pkg-desc { font-size: 14px; color: #7f8c8d; margin-bottom: 15px; line-height: 1.5; }
    
    /* NEW ITEM LIST STYLES */
    .pkg-items-box {
        background: #fdfdfd;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        flex: 1; /* Pushes price down */
    }
    .pkg-items-title {
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        color: #a0aec0;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }
    .pkg-items-list {
        margin: 0;
        padding-left: 20px;
        color: #555;
        font-size: 14px;
    }
    .pkg-items-list li {
        margin-bottom: 4px;
    }

    .pkg-price { font-size: 24px; font-weight: 800; color: #28a745; margin-bottom: 20px; text-align: right; }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        margin-bottom: 20px;
    }
    .quantity-btn {
        width: 40px; height: 40px;
        border: 2px solid #667eea;
        background: white; color: #667eea;
        font-size: 20px; font-weight: bold;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s;
        display: flex; align-items: center; justify-content: center;
    }
    .quantity-btn:hover { background: #667eea; color: white; }
    .quantity-display { font-size: 22px; font-weight: bold; min-width: 30px; text-align: center; }

    /* ACTION BUTTONS */
    .btn-add { 
        background: #28a745; 
        color: white; 
        border: none; 
        padding: 15px; 
        border-radius: 8px; 
        width: 100%; 
        font-weight: bold; 
        cursor: pointer; 
        transition: background 0.2s; 
        text-transform: uppercase; 
        font-size: 14px;
        letter-spacing: 1px;
    }
    .btn-add:hover { background: #218838; }

    /* SEARCH BUTTONS */
    .btn-search {
        background: #667eea;
        color: white;
        border: none;
        padding: 0 30px;
        height: 48px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-search:hover { background: #5a6fd6; }

    .btn-clear {
        background: #a0aec0; /* Neutral Gray */
        color: white;
        border: none;
        padding: 0 30px;
        height: 48px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: background 0.2s;
    }
    .btn-clear:hover { background: #718096; }

    .form-input-lg {
        width: 100%;
        padding: 0 15px;
        height: 48px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 15px;
        box-sizing: border-box;
    }
</style>';

print '<div class="ben-container">';

// Header
print '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 0 10px;">';
print '<div><h1 style="margin: 0; color: #2c3e50; font-size: 32px;">🎁 Available Packages</h1><p style="color: #7f8c8d; margin: 5px 0 0 0; font-size: 16px;">Select a package to request assistance</p></div>';
print '<a href="dashboard_beneficiary.php" class="button" style="background:#eee; color:#333; padding:12px 25px; border-radius:30px; font-weight:bold; text-decoration:none;">← Back to Dashboard</a>';
print '</div>';

// Search & Sort Bar
print '<form method="GET" action="'.$_SERVER['PHP_SELF'].'" class="search-box">';
print '<div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 20px; align-items: end;">';

print '<div><label style="display: block; margin-bottom: 8px; font-weight: bold; color:#555;">Search packages</label>';
print '<input type="text" name="search_text" value="'.dol_escape_htmltag($search_text).'" placeholder="E.g. Family Box..." class="form-input-lg"></div>';

print '<div><label style="display: block; margin-bottom: 8px; font-weight: bold; color:#555;">Sort by</label>';
print '<select name="sort_by" class="form-input-lg">';
print '<option value="name" '.($sort_by == 'name' ? 'selected' : '').'>Name (A-Z)</option>';
print '<option value="price" '.($sort_by == 'price' ? 'selected' : '').'>Price (Low-High)</option>';
print '</select></div>';

print '<div style="display: flex; gap: 10px;">';
print '<button type="submit" class="btn-search">SEARCH</button>';
print '<a href="'.$_SERVER['PHP_SELF'].'" class="btn-clear">CLEAR</a>';
print '</div>';

print '</div></form>';

// Logic: Handle Add to Cart
if (isset($_GET['add_id'])) {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $pkg_id = (int)$_GET['add_id'];
    $qty = (int)$_GET['qty'];
    
    // Add logic
    if (isset($_SESSION['cart'][$pkg_id])) $_SESSION['cart'][$pkg_id] += $qty;
    else $_SESSION['cart'][$pkg_id] = $qty;
    
    print '<div style="position:fixed; top:20px; right:20px; background:#28a745; color:white; padding:15px 25px; border-radius:8px; z-index:9999; box-shadow:0 5px 20px rgba(0,0,0,0.2);">✅ Added to Cart!</div>';
    print '<script>setTimeout(function(){ window.location.href="product_catalog.php"; }, 1000);</script>';
}

// Display Grid
// CHANGED SQL: Using '||' separator to easily split items later
$sql = "SELECT p.rowid, p.ref, p.name, p.description, p.status,
        GROUP_CONCAT(CONCAT(pi.product_name, ' (', pi.quantity, ' ', pi.unit, ')') SEPARATOR '||') as items_list,
        SUM(pi.quantity * pi.unit_price) as total_price
        FROM ".MAIN_DB_PREFIX."foodbank_packages p
        LEFT JOIN ".MAIN_DB_PREFIX."foodbank_package_items pi ON p.rowid = pi.fk_package
        WHERE p.status = 'Active'";

if ($search_text) $sql .= " AND p.name LIKE '%".$db->escape($search_text)."%'";
$sql .= " GROUP BY p.rowid";
if ($sort_by == 'price') $sql .= " ORDER BY total_price ASC";
else $sql .= " ORDER BY p.name ASC";

$resql = $db->query($sql);

if ($resql && $db->num_rows($resql) > 0) {
    print '<div class="pkg-grid">';
    
    while ($obj = $db->fetch_object($resql)) {
        // Price logic (mock or calculated)
        $package_price = ($obj->total_price > 0) ? $obj->total_price : 4100; // Fallback for demo
        
        print '<div class="pkg-card">';
        print '<div class="pkg-img">📦</div>';
        
        print '<div class="pkg-body">';
        print '<div class="pkg-title">'.dol_escape_htmltag($obj->name).'</div>';
        print '<div class="pkg-desc">'.dol_escape_htmltag(dol_trunc($obj->description, 100)).'</div>';
        
        // --- NEW: RENDER ITEMS AS A LIST ---
        if ($obj->items_list) {
            print '<div class="pkg-items-box">';
            print '<div class="pkg-items-title">📦 Package Includes:</div>';
            print '<ul class="pkg-items-list">';
            
            // Explode by the '||' separator we set in SQL
            $items = explode('||', $obj->items_list);
            foreach ($items as $item) {
                print '<li>'.dol_escape_htmltag($item).'</li>';
            }
            
            print '</ul>';
            print '</div>';
        }
        
        print '<div class="pkg-price">₦'.number_format($package_price).'</div>';
        
        // Quantity & Add
        print '<div class="quantity-selector">';
        print '<button type="button" class="quantity-btn" onclick="modQty('.$obj->rowid.', -1)">−</button>';
        print '<span class="quantity-display" id="qty-'.$obj->rowid.'">1</span>';
        print '<button type="button" class="quantity-btn" onclick="modQty('.$obj->rowid.', 1)">+</button>';
        print '</div>';
        
        print '<button class="btn-add" onclick="addToCart('.$obj->rowid.')">Add to Cart</button>';
        
        print '</div></div>'; // End Card
    }
    print '</div>';
} else {
    print '<div style="text-align:center; padding:80px; background:white; border-radius:12px; color:#999;">';
    print '<div style="font-size:60px; margin-bottom:20px;">🔍</div>';
    print '<h2>No Packages Found</h2><p>Try adjusting your search terms.</p>';
    print '</div>';
}

print '</div>';

// Sticky Cart Button (Purple)
print '<a href="view_cart.php" style="position: fixed; bottom: 30px; right: 30px; background: #667eea; color: white; padding: 15px 30px; border-radius: 50px; font-weight: bold; text-decoration: none; box-shadow: 0 5px 20px rgba(102,126,234,0.4); z-index: 1000; display: flex; align-items: center; gap: 10px; font-size: 16px;">
    <span>🛒</span> <span>View Cart</span>
</a>';

// JS Logic
print '<script>
function modQty(id, change) {
    let el = document.getElementById("qty-"+id);
    let val = parseInt(el.innerText) + change;
    if(val < 1) val = 1;
    el.innerText = val;
}
function addToCart(id) {
    let qty = document.getElementById("qty-"+id).innerText;
    window.location.href = "?add_id="+id+"&qty="+qty;
}
</script>';

llxFooter();
?>