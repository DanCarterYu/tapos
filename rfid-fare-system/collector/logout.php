<?php
// collector/logout.php
require_once 'session_config.php';
session_start();
session_destroy();
header('Location: login.php');
exit();
?>