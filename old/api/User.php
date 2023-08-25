<?php
// By Pintian Zhang

require_once('PasswordHash.php');
require_once('Database_connection.php');
require_once('PasswordGenerator.php');
require "/opt/app-root/src/vendor/pear/mail/Mail.php";
class User{

	private $id;
	private $email;
  private $name;
  private $timeStamp;
  private $saltHash;
  private $validUser;
  private $privilegeLevel;
  private $active;
  private $recoveryToken;
  private $recoveryDate;

	private function __construct($id, $email, $name, $timeStamp, $saltHash, $validUser, $privilegeLevel, $active, $recoveryToken, $recoveryDate){
		$this->id = $id;
    error_log("User ID ".$this->id);
    error_log("new User ".json_encode(get_object_vars($this)));
		$this->email = $email;
		$this->name = $name;
    $this->timeStamp = $timeStamp;
    $this->saltHash = $saltHash;
    $this->validUser = $validUser;
    $this->privilegeLevel = $privilegeLevel;
		$this->active = $active;
    $this->recoveryToken = $recoveryToken;
    $this->recoveryDate = $recoveryDate;

    error_log($email);
    error_log("new User ".json_encode($this));
	}

    //returns null if DB error
    //returns -1 if user already exists
    //returns User on successful creation
    public static function create($email, $password, $name){
        $mysqli = Database_connection::getMysqli();
        if ($mysqli->connect_errno) {
            return null;
        }
        //check if email is already registered
        if (is_object(User::find($email))){
            return -1;
        }
        $saltHash = create_hash($password);
        $valid = validate_password($password, $saltHash);
        $result = $mysqli->query("INSERT INTO tbl_users (userID, email, saltHash, name, active) VALUES (0, \"" . $email . "\", \"" . $saltHash . "\", \"" . $name . "\", 0)");
        $id = $mysqli->insert_id;
        //send Email confirmation
        $subject = "Caterpillars Count registration (ACTION REQUIRED)";
        $message = '<html><body>';
        $message .= '<h1>Welcome to Caterpillars Count!</h1>';
        $message .= '<p>Please <a href="http://'.$_ENV['APP_DNS'].'/api/users.php?userID=' . $id . '&activate=1">CLICK HERE</a> to complete registration for <b>' . $email . '</b></p>';
        $message .= '<p>If you did not authorize this registration, you may ignore this message. Catepillars Count will not send any further communication.</p>';
        $message .= '</body></html>';
        $server   = "relay.unc.edu";
        $port     = "25";

        $headers = array(
        "From"    => "caterpillarscount@office.unc.edu",
        "To"      => $email,
        "Subject" => $subject,
        "Content-type" => "text/html"
        );
        
        $smtp = Mail::factory("smtp",
           array(
            "host"     => $server,
            "port"     => $port,
            "auth"     => false
          )
        );

        $mail = $smtp->send($email, $headers, $message);

        if($result){
            return User::find($email);
        }
        else{
            return null;
        }

     }

    //find by email
    //returns null if DB error
    //returns User on success
    public static function find($email){
      $mysqli = Database_connection::getMysqli();
      if ($mysqli->connect_errno) {
        return null;
      }

      $result = $mysqli->query("SELECT * FROM tbl_users WHERE email='" . $email . "'");
      if($result){
        if(0 == $result->num_rows){
          error_log("more than one entry");
            return null;
        }
        $user_info = $result->fetch_array();
        return new User(intval($user_info['userID']),
                                         strval($user_info['email']),
                                         strval($user_info['name']),
                                         strval($user_info['timeStamp']),
                                         strval($user_info['saltHash']),
                                         intval($user_info['validUser']),
                                         intval($user_info['privilegeLevel']),
                                         intval($user_info['active']),
                                         strval($user_info['recoveryToken']),
                                         strval($user_info['recoveryDate']));
      }
      else{
        return null;
      }
    }

    //returns null if DB error
    //returns User on successful creation
    public static function findByID($userID){
      $mysqli = Database_connection::getMysqli();
      if ($mysqli->connect_errno) {
        return null;
      }

      $result = $mysqli->query("SELECT * FROM tbl_users WHERE userID=".$userID);
      if($result){
        if(0 == $result->num_rows){
            return null;
        }
        $user_info = $result->fetch_array();
        return new User(intval($user_info['userID']),
                                         strval($user_info['email']),
                                         strval($user_info['name']),
                                         strval($user_info['timeStamp']),
                                         strval($user_info['saltHash']),
                                         intval($user_info['validUser']),
                                         intval($user_info['privilegeLevel']),
                                         intval($user_info['active']),
                                         strval($user_info['recoveryToken']),
                                         strval($user_info['recoveryDate']));
       }
       else{
          return null;
       }
    }

    //returns null if DB error
    //returns active= true on success
    public static function activate($id){
        $mysqli = Database_connection::getMysqli();
        if ($mysqli->connect_errno) {
            return null;
        }

        $result = $mysqli->query("UPDATE tbl_users SET active = 1 WHERE userID=" . $id);
        if($result){
            $json_rep = array();
            $json_rep['active'] = true;
            return $json_rep;
        }
        else{
            return null;
        }

    }

    //returns null if DB error
    //returns validate status on success
    public static function validatePassword($email, $password){
        $mysqli = Database_connection::getMysqli();
         if ($mysqli->connect_errno) {
            return null;
        }
        $result = $mysqli->query("SELECT * FROM tbl_users WHERE email='" . $email . "'");
        if($result){
            if(0 == $result->num_rows){
                return null;
            }
            $user_info = $result->fetch_array();
            $saltHash = $user_info['saltHash'];
            $active = $user_info['active'];
            $validUser = $user_info['validUser'];
            $validPw =  validate_password($password, $saltHash);
            $privilegeLevel = $user_info['privilegeLevel'];
            $userID = $user_info['userID'];
            $json_rep = array();
            $json_rep['validUser'] = $validUser;
            if($validPw) {
              $json_rep['validPw'] = "1";
            } else {
              $json_rep['validPw'] = "0";
            }
            $json_rep['active'] = $active;
            $json_rep['privilegeLevel'] = $privilegeLevel;
            $json_rep['userID'] = $userID;
            return $json_rep;
         }
         else{
            return null;
         }

    }

    //Mark a user as invalid in the database
    //return true if success, return null or false if failure
    public static function markInvalid($userID){
        $mysqli = Database_connection::getMysqli();
         if ($mysqli->connect_errno) {
            //echo $mysqli->connect_error;
            return null;
        }

        $result = $mysqli->query("UPDATE tbl_users SET validUser = 0 WHERE userID =" . $userID);
        return $result && $mysqli->affected_rows != 0;
    }


    //returns null if user not found
    //returns -1 if user found but recovery email has been sent this day
    //returns the user object if succesff
    public static function requestPasswordRecovery($email){

        $user = User::find($email);

        if(is_null($user)){
          return null;
        }

        $date = $user->getRecoveryDate();
        $token = $user->getRecoveryToken();

        $currentDate = date("Y-m-d");

        if($currentDate == $date){

          return -1;

        }
        else{


          $id = $user->getID();

          $mysqli = Database_connection::getMysqli();
         if ($mysqli->connect_errno) {
            //echo $mysqli->connect_error;
            return null;
          }

          //Change recovery date to current date
          $result0 = $mysqli->query("UPDATE tbl_users SET recoveryDate ='" . $currentDate . "' WHERE email='" . $email . "'");

          //Generate new recovery token
          $newToken = PasswordGenerator::randomPassword(5);
          $result1 = $mysqli->query("UPDATE tbl_users SET recoveryToken ='" . $newToken . "' WHERE email='" . $email . "'");

          //Send mail with token in url
          
          $subject = "Caterpillars Count! Password Recovery";

          $message = '<html><body>';
          $message .= '<h1>Caterpillars Count! Password Recovery</h1>';
          $message .= '<p>Please <a href="http://'.$_ENV['APP_DNS'].'/user/recover.php?userID=' . $id . '&recoveryToken='. $newToken .'">CLICK HERE</a> to reset the password for <b>' . $email . '</b></p>';
          $message .= '<p>The link above will be valid on ' . strval($currentDate) . ' only.</p>';
          $message .= '<p>If you did not request to change your password, you may ignore this message.</p>';
          $message .= '</body></html>';
          $server   = "relay.unc.edu";
          $port     = "25";
          $headers = array(
          "From"    => "caterpillarscount@office.unc.edu",
          "To"      => $email,
          "Subject" => $subject,
          "Content-type" => "text/html"
          );
        
          $smtp = Mail::factory("smtp",
             array(
              "host"     => $server,
              "port"     => $port,
              "auth"     => false
             )
          );          

        $mail = $smtp->send($email, $headers, $message);

          return $user;

        }



    }

    public static function changePassword($id, $recoveryToken, $newPassword){

        $user = User::findByID($id);

        if(is_null($user)){
          return null;
        }

        $serverRecoveryToken = $user->getRecoveryToken();
        $serverRecoveryDate = $user->getRecoveryDate();
        $currentDate = date("Y-m-d");

        if($currentDate != $serverRecoveryDate){
          return -1;
        }

        if($recoveryToken != $serverRecoveryToken){
          return -1;
        }


        $saltHash = create_hash($newPassword);

        $mysqli = Database_connection::getMysqli();
        if ($mysqli->connect_errno) {
            return null;
        }

        $result = $mysqli->query("UPDATE tbl_users SET saltHash ='" . $saltHash . "' WHERE userID =" . $id);

        if($result){
            return User::findByID($id);
        }
        else{
            return null;
        }



    }

    //returns whether this userObj is valid
    public function checkValid(){
        return $this->validUser != 0;
    }

    public function checkSaltHash($password){
        return validate_password($password, $this->saltHash);
    }

    public function getID(){
    	return $this->id;
    }

    public function getEmail(){
     	return $this->email;
    }

    public function getNAME(){
        return $this->name;
    }

    public function getTimeStamp(){
        return $this->timeStamp;
    }

    public function getActive(){
        return $this->active;
    }

    public function getValidUser(){
        return $this->validUser;
    }

    public function getPrivilegeLevel() {
        return $this->privilegeLevel;
    }

    public function getRecoveryToken(){
        return $this->recoveryToken;

    }

    public function getRecoveryDate(){
      return $this->recoveryDate;
    }

    public function getJSON(){
            $json_rep = array();
            $json_rep['userID'] = $this->id;
            $json_rep['email'] = $this->email;
            $json_rep['name'] = $this->name;
            $json_rep['validUser'] = $this->validUser;
            $json_rep['privilegeLevel'] = $this->privilegeLevel;
            $json_rep['active'] = $this->active;
            $json_rep['timeStamp'] = $this->timeStamp;
            $json_rep['recoveryToken'] = $this->recoveryToken;
            $json_rep['recoveryDate'] = $this->recoveryDate;
            return json_encode($json_rep);
    }
}

?>
