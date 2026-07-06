<?php
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash pour 'password123' : " . $hash;
?>