<?php
/**
 * ONE-TIME SCRIPT: Assign all existing vendors and beneficiaries to their groups
 * Run this ONCE to fix existing users, then delete or comment out
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__ . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
    exit(1);
}

require_once $path . "../../../main.inc.php";
require_once DOL_DOCUMENT_ROOT . "/user/class/usergroup.class.php";

// Get group IDs
$sql = "SELECT rowid, nom FROM " . MAIN_DB_PREFIX . "usergroup WHERE nom IN ('Vendors', 'Subscribers', 'Admin')";
$resql = $db->query($sql);

$groups = array();
while ($obj = $db->fetch_object($resql)) {
    $groups[$obj->nom] = $obj->rowid;
}

if (!isset($groups['Vendors']) || !isset($groups['Subscribers'])) {
    echo "ERROR: Groups 'Vendors' and/or 'Subscribers' not found!\n";
    echo "Please create these groups first.\n";
    exit(1);
}

echo "Found groups:\n";
echo "- Vendors: ID " . $groups['Vendors'] . "\n";
echo "- Subscribers: ID " . $groups['Subscribers'] . "\n";
if (isset($groups['Admin'])) {
    echo "- Admin: ID " . $groups['Admin'] . "\n";
}
echo "\n";

// Assign vendors to Vendors group
echo "Assigning vendors to 'Vendors' group...\n";
$sql = "SELECT DISTINCT fk_user FROM " . MAIN_DB_PREFIX . "foodbank_vendors WHERE fk_user > 0";
$resql = $db->query($sql);

$vendor_count = 0;
while ($obj = $db->fetch_object($resql)) {
    $user_id = $obj->fk_user;
    
    // Check if already in group
    $sql_check = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "usergroup_user 
                  WHERE fk_user = " . (int)$user_id . " AND fk_usergroup = " . (int)$groups['Vendors'];
    $res_check = $db->query($sql_check);
    $check = $db->fetch_object($res_check);
    
    if ($check->count == 0) {
        // Add to group
        $sql_insert = "INSERT INTO " . MAIN_DB_PREFIX . "usergroup_user (fk_user, fk_usergroup, entity) 
                       VALUES (" . (int)$user_id . ", " . (int)$groups['Vendors'] . ", 1)";
        $db->query($sql_insert);
        echo "  ✓ User ID $user_id added to Vendors group\n";
        $vendor_count++;
    } else {
        echo "  - User ID $user_id already in Vendors group\n";
    }
}
echo "Vendors assigned: $vendor_count\n\n";

// Assign beneficiaries to Subscribers group
echo "Assigning beneficiaries to 'Subscribers' group...\n";
$sql = "SELECT DISTINCT fk_user FROM " . MAIN_DB_PREFIX . "foodbank_beneficiaries WHERE fk_user > 0";
$resql = $db->query($sql);

$beneficiary_count = 0;
while ($obj = $db->fetch_object($resql)) {
    $user_id = $obj->fk_user;
    
    // Check if already in group
    $sql_check = "SELECT COUNT(*) as count FROM " . MAIN_DB_PREFIX . "usergroup_user 
                  WHERE fk_user = " . (int)$user_id . " AND fk_usergroup = " . (int)$groups['Subscribers'];
    $res_check = $db->query($sql_check);
    $check = $db->fetch_object($res_check);
    
    if ($check->count == 0) {
        // Add to group
        $sql_insert = "INSERT INTO " . MAIN_DB_PREFIX . "usergroup_user (fk_user, fk_usergroup, entity) 
                       VALUES (" . (int)$user_id . ", " . (int)$groups['Subscribers'] . ", 1)";
        $db->query($sql_insert);
        echo "  ✓ User ID $user_id added to Subscribers group\n";
        $beneficiary_count++;
    } else {
        echo "  - User ID $user_id already in Subscribers group\n";
    }
}
echo "Subscribers assigned: $beneficiary_count\n\n";

echo "✅ DONE! Assignment complete.\n";
echo "Total: $vendor_count vendors + $beneficiary_count subscribers assigned to groups.\n";
