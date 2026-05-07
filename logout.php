<?php
require 'config.php';
if (isLoggedIn()) {
    logAudit($_SESSION['user_id'], 'logout', 'success');
}
session_destroy();
header('Location: login.php');
exit;
?>