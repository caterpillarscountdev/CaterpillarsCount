<?php
  require_once('orm/User.php');
  require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
  $email = custgetparam("email");
  if (empty($email)) {
	die("false|No email specified"); 
  }
  if(User::sendEmailVerificationCodeToEmail($email)){
    die("true|");
  }
  die("false|We could not resend the verification email for " . filter_var(rawurldecode($email), FILTER_SANITIZE_EMAIL) . ". Please try again. If this problem continues, try creating a new account, either with the same or a new email address.");
?>
