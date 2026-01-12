<?php
/**
 * PUBLIC BENEFICIARY REGISTRATION
 * Action: Create User -> Create Profile -> Auto-Login -> Redirect to Dashboard
 */
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);
define('NOLOGIN', 1); // Allow public access

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

$error = '';

if (GETPOST('action') == 'register') {
    $db->begin();

    $email = GETPOST('email', 'alpha');
    $pass  = GETPOST('password', 'alpha');
    $fname = GETPOST('firstname', 'alpha');
    $lname = GETPOST('lastname', 'alpha');
    $phone = GETPOST('phone', 'alpha');

    // 1. Create Base User Account
    $newuser = new User($db);
    $newuser->login = $email; // Email is the username
    $newuser->email = $email;
    $newuser->firstname = $fname;
    $newuser->lastname  = $lname;
    $newuser->pass = $pass;
    $newuser->statut = 1; // Active immediately

    $uid = $newuser->create($user);

    if ($uid > 0) {
        // 2. Create Beneficiary Profile in Custom Table
        $ref = 'BEN-' . date('ym') . '-' . str_pad($uid, 4, '0', STR_PAD_LEFT);
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "foodbank_beneficiaries 
                (fk_user, ref, firstname, lastname, email, phone, subscription_status, date_creation)
                VALUES 
                (" . (int)$uid . ", '$ref', '" . $db->escape($fname) . "', '" . $db->escape($lname) . "', '" . $db->escape($email) . "', '" . $db->escape($phone) . "', 'Guest', NOW())";
        
        if ($db->query($sql)) {
            $db->commit();
            
            // 3. AUTO-LOGIN (Session Magic)
            $_SESSION["dol_login"] = $newuser->login;
            $_SESSION["dol_entity"] = 1; 
            
            // Redirect to the Smart Gateway (which will send them to Dashboard)
            header("Location: ../../index.php");
            exit;
        } else {
            $db->rollback();
            $error = "System Error: Could not create profile. " . $db->lasterror();
        }
    } else {
        $db->rollback();
        $error = "Registration Failed. This email might already be registered.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Join Foodbank CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .auth-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 100%; max-width: 400px; box-sizing: border-box; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 14px; }
        .btn-register { width: 100%; background: #667eea; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .btn-register:hover { background: #5a6fd6; }
        .error-msg { background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; text-align: center; }
        h2 { text-align: center; color: #2d3748; margin-top: 0; }
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #718096; font-size: 14px; }
    </style>
</head>
<body>

<div class="auth-card">
    <h2>Create Account</h2>
    <p style="text-align: center; color: #718096; margin-bottom: 30px;">Sign up to access food packages.</p>

    <?php if ($error) print '<div class="error-msg">'.$error.'</div>'; ?>

    <form method="POST">
        <input type="hidden" name="action" value="register">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div class="form-group"><input type="text" name="firstname" class="form-control" placeholder="First Name" required></div>
            <div class="form-group"><input type="text" name="lastname" class="form-control" placeholder="Last Name" required></div>
        </div>

        <div class="form-group"><input type="email" name="email" class="form-control" placeholder="Email Address" required></div>
        <div class="form-group"><input type="tel" name="phone" class="form-control" placeholder="Phone Number" required></div>
        <div class="form-group"><input type="password" name="password" class="form-control" placeholder="Create Password" required></div>

        <button type="submit" class="btn-register">Sign Up</button>
    </form>
    
    <a href="../../index.php" class="back-link">← Back to Home</a>
</div>

</body>
</html>