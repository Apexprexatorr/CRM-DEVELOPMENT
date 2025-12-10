<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once dirname(__DIR__, 3) . '/foodbankcrm/class/vendor.class.php'; 

$langs->load("admin");
llxHeader('', 'Vendor Management');

// --- MODERN UI STYLES ---
print '<style>
    /* Hide Dolibarr Top Bar & Menu Clutter */
    #id-top { display: none !important; }
    .side-nav { top: 0 !important; height: 100vh !important; }
    #id-right { padding-top: 30px !important; }
    
    /* Clean Sidebar */
    #mainmenutd_commercial, #mainmenutd_billing, #mainmenutd_compta, 
    #mainmenutd_projet, #mainmenutd_mrp, #mainmenutd_hrm, 
    #mainmenutd_ticket, #mainmenutd_agenda, #mainmenutd_documents, #mainmenutd_bank {
        display: none !important;
    }

    /* Page Layout */
    .fb-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
    
    /* Modern Card & Table */
    .fb-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 0; overflow: hidden; border: 1px solid #eee; }
    .clean-table { width: 100%; border-collapse: collapse; }
    .clean-table th { text-align: left; padding: 15px 20px; background: #f8f9fa; color: #666; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .clean-table td { padding: 15px 20px; border-bottom: 1px solid #f5f5f5; font-size: 14px; color: #444; }
    .clean-table tr:hover { background: #fafafa; }
    
    /* Badges */
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; color: #fff !important; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge.green { background-color: #28a745 !important; }
    .badge.red { background-color: #dc3545 !important; }
    .badge.gray { background-color: #6c757d !important; }
    
    /* Actions */
    .action-btn { text-decoration: none; color: #555; padding: 5px 10px; border-radius: 4px; font-size: 13px; border: 1px solid #ddd; margin-right: 5px; background: #fff; }
    .action-btn:hover { background: #f0f0f0; color: #333; }
    .action-btn.delete { color: #d32f2f; border-color: #f5c6cb; }
    .action-btn.delete:hover { background: #ffebee; }
</style>';

$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_vendors ORDER BY rowid DESC";
$res = $db->query($sql);

print '<div class="fb-container">';

// Header
print '<div class="page-header">';
print '<div><h1 style="margin: 0;">🏢 Vendors</h1><p style="color:#888; margin: 5px 0 0 0;">Manage food suppliers and donors</p></div>';
print '<div>';
print '<a class="butAction" href="create_vendor.php" style="padding: 10px 20px;">+ Register Vendor</a>';
print '<a class="button" href="dashboard_admin.php" style="margin-left: 10px; background:#eee; color:#333;">Back to Dashboard</a>';
print '</div>';
print '</div>';

// Table
print '<div class="fb-card">';

if ($res && $db->num_rows($res) > 0) {
    print '<table class="clean-table">';
    print '<thead><tr><th>Ref</th><th>Business Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th>Category</th><th>Status</th><th style="text-align:right;">Actions</th></tr></thead>';
    print '<tbody>';

    while ($obj = $db->fetch_object($res)) {
        // Status Logic
        $status_label = !empty($obj->status) ? $obj->status : 'Active';
        $status_class = ($status_label == 'Active') ? 'green' : 'red';

        print '<tr>';
        print '<td><strong>'.dol_escape_htmltag($obj->ref).'</strong></td>';
        print '<td>'.dol_escape_htmltag($obj->name).'</td>';
        print '<td>'.($obj->contact_person ? dol_escape_htmltag($obj->contact_person) : '<span style="color:#ccc">--</span>').'</td>';
        print '<td>'.($obj->contact_phone ? dol_escape_htmltag($obj->contact_phone) : '<span style="color:#ccc">--</span>').'</td>';
        print '<td>'.($obj->contact_email ? dol_escape_htmltag($obj->contact_email) : '<span style="color:#ccc">--</span>').'</td>';
        print '<td>'.($obj->category ? '<span style="color:#667eea; font-weight:bold;">'.$obj->category.'</span>' : '<span style="color:#ccc">--</span>').'</td>';
        print '<td><span class="badge '.$status_class.'">'.$status_label.'</span></td>';
        
        print '<td style="text-align: right;">';
        print '<a href="edit_vendor.php?id='.$obj->rowid.'" class="action-btn">✏️ Edit</a>';
        print '<a href="delete_vendor.php?id='.$obj->rowid.'" class="action-btn delete">🗑️ Delete</a>';
        print '</td>';
        print '</tr>';
    }
    
    print '</tbody></table>';
} else {
    print '<div style="text-align: center; padding: 60px; color: #999;">';
    print '<div style="font-size: 40px; margin-bottom: 10px;">🏢</div>';
    print 'No vendors found. <a href="create_vendor.php" style="font-weight:bold">Register the first one</a>.';
    print '</div>';
}

print '</div>'; 
print '</div>'; 

llxFooter();
?>