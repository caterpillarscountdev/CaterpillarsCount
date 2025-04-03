<?php

require_once('resources/mailing.php');
require_once('resources/Keychain.php');
require_once('User.php');

class UserGroup {

  private $id;
  private $name;
  private $manager;
  private $emails;
  private $requestedEmails;

  private $users;

  public static function create($manager, $name, $emails){
    $dbconn = (new Keychain)->getDatabaseConnection();
    if(!$dbconn){
      return "Cannot connect to server.";
    }

    $emails = self::validEmails($emails);
    
    mysqli_query($dbconn, "INSERT INTO UserGroup (`UserFKOfManager`, `Name`, `Emails`) VALUES ('" . $manager->getID() . "', '" . $name . "', '" . implode(",", $emails) . "')");
    $id = intval(mysqli_insert_id($dbconn));

    return new UserGroup($id, $manager, $name, $emails, []);


  }

  private function __construct($id, $manager, $name, $emails, $requestedEmails) {
    $this->id = intval($id);
    $this->manager = $manager;
    $this->name = $name;
    $this->emails = self::validEmails($emails);
    $this->requestedEmails = self::validEmails($requestedEmails);
  }
	
  //FINDERS
  public static function findByID($id){
    $dbconn = (new Keychain)->getDatabaseConnection();
    $id = mysqli_real_escape_string($dbconn, htmlentities($id));
    $query = mysqli_query($dbconn, "SELECT * FROM `UserGroup` WHERE `ID`='$id' LIMIT 1");
    
    if(mysqli_num_rows($query) == 0){
      return null;
    }
    
    $row = mysqli_fetch_assoc($query);
    
    $manager = User::findByID($row["UserFKOfManager"]);
    $name = $row["Name"];
    $emails = $row["Emails"];
    $requestedEmails = $row["RequestedEmails"];
    
    return new UserGroup($id, $manager, $name, $emails, $requestedEmails);
  }

  public static function findByManager($manager){
    $dbconn = (new Keychain)->getDatabaseConnection();
    $id = mysqli_real_escape_string($dbconn, htmlentities($manager->getID()));
    $query = mysqli_query($dbconn, "SELECT * FROM `UserGroup` WHERE `UserFKOfManager`='$id'");
    
    $groups = array();
    while($row = mysqli_fetch_assoc($query)){
      $id = $row["ID"];
      $manager = User::findByID($row["UserFKOfManager"]);
      $name = $row["Name"];
      $emails = $row["Emails"];
      $requestedEmails = $row["RequestedEmails"];
      $group = new UserGroup($id, $manager, $name, $emails, $requestedEmails);
      
      array_push($groups, $group);
    }
    return $groups;
  }

  //

  public function requestUserConsents() {
    $newEmails = array_diff($this->emails, $this->requestedEmails);
    $updatedRequested = $this->requestedEmails;
    foreach($newEmails as $em){
      $user = User::findByEmail($em);
      if(is_object($user) && get_class($user) == "User"){
        // send consent request email
        $message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">" . $this->getManager()->getFullName() . " would like to add you to the group \"" . $this->getName() . "\" to let them view your quiz and survey stats. Please sign in to <a href='https://caterpillarscount.unc.edu/signIn'>caterpillarscount.unc.edu</a> using this email address to approve or decline this request.</div><a href='https://caterpillarscount.unc.edu/signIn'><button style=\"border:0px none transparent;background:#fed136; border-radius:5px;padding:20px 40px;font-size:20px;color:#fff;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;font-weight:bold;cursor:pointer;\">Respond Now</button></a><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"></div></div>";
      } else {
        // send invite email
        $message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">" . $this->getManager()->getFullName() . " would like to add you to the group \"" . $this->getName() . "\" to let them view your quiz and survey stats. Please create an account at <a href='https://caterpillarscount.unc.edu/signUp'>caterpillarscount.unc.edu</a> using this email address to approve or decline this request, or let them know your existing Caterpillars Count account email to add to the group.</div> <a href='https://caterpillarscount.unc.edu/signUp'><button style=\"border:0px none transparent;background:#fed136; border-radius:5px;padding:20px 40px;font-size:20px;color:#fff;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;font-weight:bold;cursor:pointer;\">Sign Up Now</button></a><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"></div></div>";
      }
      email($em, "Invitation to join  Caterpillars Count! group", $message);
      $updatedRequested[] = $em;
      $this->setRequestedEmails($updatedRequested);
    }
  }

  public static function requestsForUser($user) {
    $dbconn = (new Keychain)->getDatabaseConnection();
    $id = mysqli_real_escape_string($dbconn, htmlentities($user->getID()));
    $email = mysqli_real_escape_string($dbconn, htmlentities($user->getEmail()));
    $query = mysqli_query($dbconn, "SELECT G.ID, C.UserFK FROM `UserGroup` G LEFT JOIN (SELECT * FROM `UserGroupConsent` WHERE UserFK=$id) C ON G.ID = C.UserGroupFK WHERE C.UserFK IS NULL AND G.Emails LIKE '%$email%'");
    
    $groups = array();
    while($row = mysqli_fetch_assoc($query)){
      if (!$row["UserFK"]) {
        $groups[] = self::findByID($row["ID"]);
      }
    }

    return $groups;

  
  }

  public static function groupsForUser($user) {
    $dbconn = (new Keychain)->getDatabaseConnection();
    $id = mysqli_real_escape_string($dbconn, htmlentities($user->getID()));
    $query = mysqli_query($dbconn, "SELECT UserGroupFK FROM `UserGroupConsent` WHERE `UserFK`='$id'");
    
    $groups = array();
    while($row = mysqli_fetch_assoc($query)){
      $groups[] = self::findByID($row["UserGroupFK"]);
    }

    return $groups;
  
  }

  public function consentFromUser($user) {
    $dbconn = (new Keychain)->getDatabaseConnection();
    mysqli_query($dbconn, "INSERT INTO UserGroupConsent (`UserGroupFK`, `UserFK`) VALUES ('". $this->id . "', '" . $user->getID() . "') ON DUPLICATE KEY UPDATE`UserGroupFK` = `UserGroupFK`, `UserFK` = `UserFK`;");
    $id = intval(mysqli_insert_id($dbconn));
    return true; 
 }

  public function declineFromUser($user) {
    $this->removeUser($user);
    $updatedEmails = array_diff($this->emails, array($user->getEmail()));
    $this->setEmails($updatedEmails);
    return true;
  }

  public function removeUser($user) {
    $dbconn = (new Keychain)->getDatabaseConnection();
    mysqli_query($dbconn, "DELETE FROM UserGroupConsent WHERE `UserGroupFK` = '". $this->id . "' AND UserFK = '" . $user->getID() . "'");
    $updatedRequested = array_diff($this->requestedEmails, array($user->getEmail()));
    $this->setRequestedEmails($updatedRequested);
  }
  

  //GETTERS
  public function getID(){
    return intval($this->id);
  }
  
  public function getManager(){
    return $this->manager;
  }
    
  public function getName(){
    return $this->name;
  }

  public function getEmails(){
    return $this->emails;
  }

  public function getRequestedEmails(){
    return $this->requestedEmails;
  }

  
  public function getUsers(){
    if ($this->users) {
      return $this->users;
    }
    $dbconn = (new Keychain)->getDatabaseConnection();
    $id = mysqli_real_escape_string($dbconn, htmlentities($this->id));
    $query = mysqli_query($dbconn, "SELECT UserFK FROM `UserGroupConsent` WHERE `UserGroupFK`='$id'");
    
    $users = array();
    while($row = mysqli_fetch_assoc($query)){
      $users[] = User::findByID($row["UserFK"]);
    }

    $this->users = $users;
    return $this->users;
    
  }
  
  //SETTERS
  public function setName($name){
    $dbconn = (new Keychain)->getDatabaseConnection();
    mysqli_query($dbconn, "UPDATE UserGroup SET Name='$name' WHERE ID='" . $this->id . "'");
    $this->name = $name;
    return true;

  }

  public function setEmails($emails){
    $dbconn = (new Keychain)->getDatabaseConnection();
    $emails = self::validEmails($emails);
    $removed = array_diff($this->emails, $emails);
    foreach($removed as $rem) {
      $u = User::findByEmail($rem);
      if(is_object($u) && get_class($u) == "User") {
        $this->removeUser($u);
      }
    }
    $emailsR = implode(',', $emails);
    mysqli_query($dbconn, "UPDATE UserGroup SET Emails='$emailsR' WHERE ID='" . $this->id . "'");
    $this->emails = $emails;
    return true;

  }

  public function setRequestedEmails($requestedEmails) {
    $dbconn = (new Keychain)->getDatabaseConnection();
    $requestedEmails = self::validEmails($requestedEmails);
    $requestedEmailsR = implode(',', $requestedEmails);
    mysqli_query($dbconn, "UPDATE UserGroup SET RequestedEmails='$requestedEmailsR' WHERE ID='" . $this->id . "'");
    $this->requestedEmails = $requestedEmails;
    return true;
  }

  public static function validEmails($emails){
    $dbconn = (new Keychain)->getDatabaseConnection();
    if (!is_array($emails)) {
      $emails = str_replace("\n", ",", str_replace("\r", "", $emails));
      $emails = explode(',', $emails);
    }
    $validEmails = array();
    foreach($emails as $em) {
      $em = User::validEmailFormat($dbconn, $em);
      if ($em) {
        $validEmails[] = $em;
      }
    }
    return $validEmails;
  }
  
}


?>