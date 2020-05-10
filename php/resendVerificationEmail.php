<?php
  require_once('orm/User.php');
  
  $email = $_GET["email"];
  
  if(User::sendEmailVerificationCodeToEmail($email)){
    die("true|");
  }
  die("false|We could not resend the verification email for " . filter_var(rawurldecode($email), FILTER_SANITIZE_EMAIL) . ". Please try again. If this problem continues, try creating a new account, either with the same or a new email address.");
?>
