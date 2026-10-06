<?php
// management/logout.php
require_once 'session_config.php';
session_start();
session_destroy();
header('Location: login.php');  // Now points to management/login.php
exit();
?>