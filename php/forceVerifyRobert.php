<?php
  require_once("orm/User.php");
  
  $user = User::findByID("1830");
  $user->verifyEmail("1879");
?>
