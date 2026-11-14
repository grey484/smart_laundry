<?php
session_start();
unset($_SESSION['member_id']);
unset($_SESSION['member_nama']);
header("Location: index.php");
exit;
?>