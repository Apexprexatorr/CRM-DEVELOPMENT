<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CHECKOUT DEBUG ===<br><br>";

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

global $user, $db;

echo "User ID: " . $user->id . "<br>";
echo "User Login: " . $user->login . "<br><br>";

// Check if beneficiary
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);

if (!$res) {
    die("ERROR: Query failed: " . $db->lasterror());
}

if ($db->num_rows($res) == 0) {
    die("ERROR: No beneficiary found for this user!");
}

$subscriber = $db->fetch_object($res);

echo "Subscriber ID: " . $subscriber->rowid . "<br>";
echo "Subscriber Ref: " . $subscriber->ref . "<br>";
echo "Name: " . $subscriber->firstname . " " . $subscriber->lastname . "<br>";
echo "Subscription Status: " . $subscriber->subscription_status . "<br><br>";

// Check cart
$sql_cart = "SELECT c.*, p.name as package_name 
             FROM ".MAIN_DB_PREFIX."foodbank_cart c
             INNER JOIN ".MAIN_DB_PREFIX."foodbank_packages p ON c.fk_package = p.rowid
             WHERE c.fk_subscriber = ".(int)$subscriber->rowid;

echo "Running cart query for subscriber ID: " . $subscriber->rowid . "<br><br>";

$res_cart = $db->query($sql_cart);

if (!$res_cart) {
    die("ERROR: Cart query failed: " . $db->lasterror());
}

$cart_count = $db->num_rows($res_cart);

echo "<strong>Cart Items Found: " . $cart_count . "</strong><br><br>";

if ($cart_count > 0) {
    echo "<h3>Cart Contents:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Package</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr>";
    
    while ($item = $db->fetch_object($res_cart)) {
        echo "<tr>";
        echo "<td>" . $item->package_name . "</td>";
        echo "<td>" . $item->quantity . "</td>";
        echo "<td>₦" . number_format($item->unit_price, 2) . "</td>";
        echo "<td>₦" . number_format($item->quantity * $item->unit_price, 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><br><a href='checkout.php' style='padding: 10px 20px; background: green; color: white; text-decoration: none;'>GO TO REAL CHECKOUT</a>";
} else {
    echo "<strong style='color: red;'>CART IS EMPTY!</strong><br>";
    echo "<a href='product_catalog.php'>Browse Packages</a>";
}
?>
