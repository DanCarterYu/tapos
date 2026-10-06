<?php
// passenger/logout.php
session_name('PASSENGER_SESSION');
session_start();
session_destroy();
header('Location: login.php');
exit();
?>