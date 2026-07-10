<?php
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
echo "New Password Hash: " . $hashed_password;
?>
