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
      if ($user) {
        // send consent request email
      } else {
        // send invite email
        $message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">" . $site->getCreator()->getFullName() . " would like you to be a manager of the \"" . $site->getName() . "\" site in " . $site->getRegion() . ". Please sign in to <a href='https://caterpillarscount.unc.edu/signIn'>caterpillarscount.unc.edu</a> using this email address (" . $manager->getEmail() . ") to approve or deny this request. If you are not prompted to approve this requst when you log in, this request has expired.</div><a href='https://caterpillarscount.unc.edu/signIn'><button style=\"border:0px none transparent;background:#fed136; border-radius:5px;padding:20px 40px;font-size:20px;color:#fff;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;font-weight:bold;cursor:pointer;\">SIGN IN NOW</button></a><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"></div></div>";
        email($manager->getEmail(), "Invitation to join  Caterpillars Count! group", $message);
      }
      $updatedRequested[] = $em;
      $this->setRequestedEmails($updatedRequested);
    }
  }

  public function consentFromUser($user) {
    $dbconn = (new Keychain)->getDatabaseConnection();
    mysqli_query($dbconn, "INSERT INTO UserGroupConsent (`UserGroupFK`, `UserFK`) VALUES ('". $this->id . "', '" . $user->getID() . "') ON DUPLICATE KEY UPDATE");
    $id = intval(mysqli_insert_id($dbconn));
    return true;
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
    while($ow = mysqli_fetch_assoc($query)){
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
    $emails = implode(',', self::validEmails($emails));
    mysqli_query($dbconn, "UPDATE UserGroup SET Emails='$emails' WHERE ID='" . $this->id . "'");
    $this->emails = $emails;
    return true;

  }

  public function setRequestedEmails($requestedEmails) {
    $dbconn = (new Keychain)->getDatabaseConnection();
    $requestedEmails = implode(',', self::validEmails($requestedEmails));
    mysqli_query($dbconn, "UPDATE UserGroup SET RequestedEmails='$requestedEmails' WHERE ID='" . $this->id . "'");
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