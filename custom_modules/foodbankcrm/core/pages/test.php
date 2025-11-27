<?php
echo "TEST FILE LOADED SUCCESSFULLY!";
echo "<br>User ID: " . (isset($user->id) ? $user->id : 'Not set');
echo "<br>User Login: " . (isset($user->login) ? $user->login : 'Not set');
phpinfo();
?>