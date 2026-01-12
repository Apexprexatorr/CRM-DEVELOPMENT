<?php
/**
 * PUBLIC VENDOR REGISTRATION
 * Action: Create User (Disabled) -> Create Vendor Profile -> Show Pending Message
 */
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);
define('NOLOGIN', 1); // Allow public access

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

$error = '';
$success = false;

if (GETPOST('action') == 'register') {
    $db->begin();

    $email = GETPOST('email', 'alpha');
    $pass  = GETPOST('password', 'alpha');
    $business_name = GETPOST('business_name', 'alpha');
    $category = GETPOST('category', 'alpha');
    $contact_person = GETPOST('contact_person', 'alpha');
    $phone = GETPOST('phone', 'alpha');
    $address = GETPOST('address', 'restricthtml');

    // 1. Create User (DISABLED BY DEFAULT)
    $newuser = new User($db);
    $newuser->login = $email;
    $newuser->email = $email;
    $newuser->firstname = $contact_person;
    $newuser->lastname  = "(Vendor)";
    $newuser->pass = $pass;
    $newuser->statut = 0; // 0 = Disabled (Requires Admin Approval)

    $uid = $newuser->create($user);

    if ($uid > 0) {
        // 2. Create Vendor Profile
        $ref = 'VEND-' . date('ym') . '-' . str_pad($uid, 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "foodbank_vendors 
                (fk_user, ref, name, category, contact_person, contact_email, contact_phone, address, date_creation)
                VALUES 
                (" . (int)$uid . ", '$ref', '" . $db->escape($business_name) . "', '" . $db->escape($category) . "', '" . $db->escape($contact_person) . "', '" . $db->escape($email) . "', '" . $db->escape($phone) . "', '" . $db->escape($address) . "', NOW())";
        
        if ($db->query($sql)) {
            $db->commit();
            $success = true;
        } else {
            $db->rollback();
            $error = "Error creating vendor profile: " . $db->lasterror();
        }
    } else {
        $db->rollback();
        $error = "Registration Failed. Email might already exist.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Become a Supplier | Foodbank CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .auth-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 500px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; }
        .btn-register { width: 100%; background: #2c3e50; color: white; border: none; padding: 15px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .btn-register:hover { background: #34495e; }
        .error-msg { background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; text-align: center; }
        .success-box { text-align: center; padding: 20px; }
        .success-icon { font-size: 50px; margin-bottom: 15px; display: block; }
        .back-link { display: block; text-align: center; margin-top: 25px; color: #667eea; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="auth-card">
    <?php if ($success): ?>
        <div class="success-box">
            <span class="success-icon">✅</span>
            <h2 style="color: #2d3748; margin-top: 0;">Application Submitted!</h2>
            <p style="color: #718096; line-height: 1.6;">
                Thank you for applying to be a partner. 
                <br><br>
                Your account is currently <strong>Pending Approval</strong>. 
                Our team will review your details and enable your account shortly.
            </p>
            <a href="../../index.php" class="back-link">Return to Home</a>
        </div>
    <?php else: ?>
        <h2 style="text-align: center; color: #2d3748; margin-top: 0;">Supplier Registration</h2>
        <p style="text-align: center; color: #718096; margin-bottom: 30px;">Partner with us to distribute food efficiently.</p>

        <?php if ($error) print '<div class="error-msg">'.$error.'</div>'; ?>

        <form method="POST">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label class="form-label">Business Name</label>
                <input type="text" name="business_name" class="form-control" placeholder="e.g. Binke Farms Ltd." required>
            </div>

            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    <option value="Grains">Grains (Rice, Wheat)</option>
                    <option value="Vegetables">Vegetables</option>
                    <option value="Proteins">Proteins (Meat, Fish)</option>
                    <option value="Dairy">Dairy Products</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Mobile Number" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Business Email (Login)</label>
                <input type="email" name="email" class="form-control" placeholder="official@company.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Office / Warehouse Address" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Create Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
            </div>

            <button type="submit" class="btn-register">Submit Application</button>
        </form>

        <a href="../../index.php" class="back-link">← Back to Home</a>
    <?php endif; ?>
</div>

</body>
</html>