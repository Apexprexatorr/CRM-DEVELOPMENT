<?php
define('NOTOKENRENEWAL', 1);
define('NOCSRFCHECK', 1);

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/foodbankcrm/class/permissions.class.php';

echo "<h1>DEBUG: Beneficiary Dashboard Access</h1>";
echo "<pre>";
echo "User ID: " . $user->id . "\n";
echo "User Login: " . $user->login . "\n";
echo "User Admin: " . $user->admin . "\n\n";

echo "Checking isBeneficiary()...\n";
$is_beneficiary = FoodbankPermissions::isBeneficiary($user, $db);
echo "Result: " . ($is_beneficiary ? "TRUE" : "FALSE") . "\n\n";

echo "Checking database directly...\n";
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries WHERE fk_user = ".(int)$user->id;
$res = $db->query($sql);
echo "Query: " . $sql . "\n";
echo "Rows found: " . $db->num_rows($res) . "\n\n";

if ($db->num_rows($res) > 0) {
    $sub = $db->fetch_object($res);
    echo "Beneficiary Record Found:\n";
    echo "  ID: " . $sub->rowid . "\n";
    echo "  Ref: " . $sub->ref . "\n";
    echo "  Name: " . $sub->firstname . " " . $sub->lastname . "\n";
    echo "  Subscription: " . $sub->subscription_type . " - " . $sub->subscription_status . "\n";
} else {
    echo "NO BENEFICIARY RECORD FOUND FOR USER ID " . $user->id . "\n";
    echo "\nAll beneficiaries:\n";
    $sql2 = "SELECT rowid, ref, firstname, lastname, fk_user FROM ".MAIN_DB_PREFIX."foodbank_beneficiaries";
    $res2 = $db->query($sql2);
    while ($obj = $db->fetch_object($res2)) {
        echo "  - {$obj->ref}: {$obj->firstname} {$obj->lastname} (fk_user: {$obj->fk_user})\n";
    }
}

echo "</pre>";
