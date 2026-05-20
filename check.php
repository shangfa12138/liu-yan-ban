<?php
if(empty($_SESSION['name'])){
    header("Location:login.php");
    exit();
}
?>