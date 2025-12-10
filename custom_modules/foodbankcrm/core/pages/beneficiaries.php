<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/beneficiary.class.php'; 

$langs->load("admin");
llxHeader('', 'Subscriber Management');

// --- MODERN UI STYLES ---
print '<style>
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank {
        display: none !important;
    }

    .fb-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 0; overflow: hidden; border: 1px solid #eee; }
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { text-align: left; padding: 15px 20px; background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .clean-table td { padding: 15px 20px; border-bottom: 1px solid #f5f5f5; font-size: 14px; color: #444; }
    .clean-table tr:last-child td { border-bottom: none; }
    .clean-table tr:hover { background: #fafafa; }
    
    /* UPDATED BADGES: Force White Text */
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; color: #fff !important; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge.green { background-color: #28a745 !important; }
    .badge.orange { background-color: #fd7e14 !important; }
    .badge.red { background-color: #dc3545 !important; }
    .badge.gray { background-color: #6c757d !important; }
    
    .action-btn { text-decoration: none; color: #555; padding: 5px 10px; border-radius: 4px; font-size: 13px; border: 1px solid #ddd; margin-right: 5px; background: #fff; }
    .action-btn:hover { background: #f0f0f0; color: #333; }
    .action-btn.delete { color: #d32f2f; border-color: #f5c6cb; }
    .action-btn.delete:hover { background: #ffebee; }
</style>';

$beneficiary = new Beneficiary($db);
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_beneficiaries ORDER BY rowid DESC";
$res = $db->query($sql);

print '<div class="fb-container">';

print '<div class="page-header">';
print '<div><h1 style="margin: 0;">👥 Subscribers</h1><p style="color:#888; margin: 5px 0 0 0;">Manage beneficiary accounts</p></div>';
print '<div>';
print '<a class="butAction" href="create_beneficiary.php" style="padding: 10px 20px;">+ Add Subscriber</a>';
print '<a class="button" href="dashboard_admin.php" style="margin-left: 10px; background:#eee; color:#333;">Back to Dashboard</a>';
print '</div>';
print '</div>';

print '<div class="fb-card">';

if ($res && $db->num_rows($res) > 0) {
    print '<table class="clean-table">';
    print '<thead><tr><th>Ref</th><th>Name</th><th>Contact</th><th>Household</th><th>Plan</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>';
    print '<tbody>';

    while ($obj = $db->fetch_object($res)) {
        $status_class = 'gray';
        $status_label = !empty($obj->subscription_status) ? $obj->subscription_status : 'Pending';
        
        if ($status_label == 'Active') $status_class = 'green';
        elseif ($status_label == 'Pending') $status_class = 'orange';
        elseif ($status_label == 'Inactive' || $status_label == 'Expired') $status_class = 'red';
        
        $phone = $obj->phone ? dol_escape_htmltag($obj->phone) : '<span style="color:#ccc">--</span>';
        $email = $obj->email ? dol_escape_htmltag($obj->email) : '';
        $contact = $phone . ($email ? '<br><small style="color:#888">'.$email.'</small>' : '');
        
        $household = ((int)$obj->household_size > 0) ? $obj->household_size . ' members' : '<span style="color:#ccc">--</span>';
        $sub_type = !empty($obj->subscription_type) ? '<strong>'.dol_escape_htmltag($obj->subscription_type).'</strong>' : '<span style="color:#ccc">Standard</span>';

        print '<tr>';
        print '<td><strong>'.dol_escape_htmltag($obj->ref).'</strong></td>';
        print '<td>'.dol_escape_htmltag($obj->firstname.' '.$obj->lastname).'</td>';
        print '<td>'.$contact.'</td>';
        print '<td>'.$household.'</td>';
        print '<td>'.$sub_type.'</td>';
        print '<td><span class="badge '.$status_class.'">'.dol_escape_htmltag($status_label).'</span></td>';
        
        print '<td style="text-align: right;">';
        print '<a href="edit_beneficiary.php?id='.$obj->rowid.'" class="action-btn">✏️ Edit</a>';
        print '<a href="delete_beneficiary.php?id='.$obj->rowid.'" class="action-btn delete">🗑️ Delete</a>';
        print '</td>';
        print '</tr>';
    }
    print '</tbody></table>';
} else {
    print '<div style="text-align: center; padding: 60px; color: #999;">';
    print '<div style="font-size: 40px; margin-bottom: 10px;">👥</div>';
    print 'No subscribers found. <a href="create_beneficiary.php" style="font-weight:bold">Add the first one</a>.';
    print '</div>';
}

print '</div>'; 
print '</div>'; 

llxFooter();
?>