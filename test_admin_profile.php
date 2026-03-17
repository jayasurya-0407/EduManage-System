<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;

ob_start();
include 'admin/admin_profile.php';
$out = ob_get_clean();
echo "OUTPUT LENGTH: " . strlen($out) . "\n";
if (empty($out)) {
    echo "NO OUTPUT. Error might have occurred.\n";
}
?>
