<?php
session_start();
unset($_SESSION['user']);
session_destroy();
if (!isset($_GET['dst'])){
header("Location: index.php");
} else {
header("Location: ".$_GET['dst']);
}
?>
