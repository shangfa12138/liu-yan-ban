<?php
function superClean(string $str): string
{
    $str = stripcslashes($str);
    $str = trim($str);
    $str = htmlspecialchars($str);
    return $str;
}
?>