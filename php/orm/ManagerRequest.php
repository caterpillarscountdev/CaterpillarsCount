<?php

require_once('resources/mailing.php');
require_once('resources/Keychain.php');
require_once('User.php');
require_once('Site.php');

class ManagerRequest
{
	//PRIVATE VARS
	private $id;							//INT
	private $manager;						//User object
	private $site;							//Site object
	private $hasCompleteAuthority;					//Boolean
	private $status;						//String: "Pending", "Denied", or "Approved"
	
	private $deleted;
	
	//FACTORY (singleton)
	public static function create($site, $manager){
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
		
		$manager = self::validManager($dbconn, $manager, $site);
		
		$failures = "";
		
		if($manager === false){
			$failures .= "You are the creator of this site. There is no need to also appoint yourself as a manager. ";
		}
		
		if($failures != ""){
			return $failures;
		}
		
		$existingManagerRequest = self::findByManagerAndSite($manager, $site);
		if($existingManagerRequest === null){
			mysqli_query($dbconn, "INSERT INTO ManagerRequest (`UserFKOfManager`, `SiteFK`, `Status`) VALUES ('" . $manager->getID() . "', '" . $site->getID() . "', 'Pending')");
			$id = intval(mysqli_insert_id($dbconn));
			$message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">" . $site->getCreator()->getFullName() . " would like you to be a manager of the \"" . $site->getName() . "\" site in " . $site->getRegion() . ". Please sign in to <a href='https://caterpillarscount.unc.edu/signIn'>caterpillarscount.unc.edu</a> using this email address (" . $manager->getEmail() . ") to approve or deny this request.</div><a href='https://caterpillarscount.unc.edu/signIn'><button style=\"border:0px none transparent;background:#fed136; border-radius:5px;padding:20px 40px;font-size:20px;color:#fff;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;font-weight:bold;cursor:pointer;\">SIGN IN NOW</button></a><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"></div></div>";
			email($manager->getEmail(), "New Caterpillars Count! site manager request!", $message);
			mysqli_close($dbconn);
			return new ManagerRequest($id, $manager, $site, false, "Pending");
		}
		else if($existingManagerRequest->getStatus() == "Denied"){
			mysqli_query($dbconn, "UPDATE ManagerRequest SET `Status`='Pending' WHERE `UserFKOfManager`='" . $manager->getID() . "' AND `SiteFK`='" . $site->getID() . "'");
			$message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">" . $site->getCreator()->getFullName() . " would like you to reconsider being a manager of the \"" . $site->getName() . "\" site in " . $site->getRegion() . ". Please sign in to <a href='https://caterpillarscount.unc.edu/signIn'>caterpillarscount.unc.edu</a> using this email address (" . $manager->getEmail() . ") to approve or deny this request.</div><a href='https://caterpillarscount.unc.edu/signIn'><button style=\"border:0px none transparent;background:#fed136; border-radius:5px;padding:20px 40px;font-size:20px;color:#fff;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;font-weight:bold;cursor:pointer;\">SIGN IN NOW</button></a><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"></div></div>";
			email($manager->getEmail(), "New Caterpillars Count! site manager request!", $message);
			mysqli_close($dbconn);
			return $existingManagerRequest;
		}
	}
	private function __construct($id, $manager, $site, $hasCompleteAuthority, $status) {
		$this->id = intval($id);
		$this->manager = $manager;
		$this->site = $site;
		$this->hasCompleteAuthority = filter_var($hasCompleteAuthority, FILTER_VALIDATE_BOOLEAN);
		$this->status = $status;
		
		$this->deleted = false;
	}
	
	//FINDERS
	public static function findByID($id){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = mysqli_real_escape_string($dbconn, $id);
		$query = mysqli_query($dbconn, "SELECT * FROM `ManagerRequest` WHERE `ID`='$id' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$managerRequestRow = mysqli_fetch_assoc($query);
		
		$manager = User::findByID($managerRequestRow["UserFKOfManager"]);
		$site = Site::findByID($managerRequestRow["SiteFK"]);
		$hasCompleteAuthority = $managerRequestRow["HasCompleteAuthority"];
		$status = $managerRequestRow["Status"];
		
		return new ManagerRequest($id, $manager, $site, $hasCompleteAuthority, $status);
	}
	
	public static function findByManagerAndSite($manager, $site){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = mysqli_real_escape_string($dbconn, $id);
		$query = mysqli_query($dbconn, "SELECT * FROM `ManagerRequest` WHERE `UserFKOfManager`='" . $manager->getID() . "' AND `SiteFK`='" . $site->getID() . "' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$managerRequestRow = mysqli_fetch_assoc($query);
		
		$id = intval($managerRequestRow["ID"]);
		$hasCompleteAuthority = $managerRequestRow["HasCompleteAuthority"];
		$status = $managerRequestRow["Status"];
		
		return new ManagerRequest($id, $manager, $site, $hasCompleteAuthority, $status);
	}
  
  	public static function findManagerRequestsBySite($site){
    		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `ManagerRequest` WHERE `SiteFK`='" . $site->getID() . "'");
		mysqli_close($dbconn);
		
		$managerRequestsArray = array();
		while($managerRequestRow = mysqli_fetch_assoc($query)){
			$id = $managerRequestRow["ID"];
			$manager = User::findByID($managerRequestRow["UserFKOfManager"]);
			$site = Site::findByID($managerRequestRow["SiteFK"]);
			$hasCompleteAuthority = $managerRequestRow["HasCompleteAuthority"];
			$status = $managerRequestRow["Status"];
			$managerRequest = new ManagerRequest($id, $manager, $site, $hasCompleteAuthority, $status);
			
			array_push($managerRequestsArray, $managerRequest);
		}
		return $managerRequestsArray;
	}
  
  	public static function findPendingManagerRequestsByManager($manager){
    		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `ManagerRequest` WHERE `UserFKOfManager`='" . $manager->getID() . "' AND `Status`='Pending'");
		mysqli_close($dbconn);
		
		$managerRequestsArray = array();
		while($managerRequestRow = mysqli_fetch_assoc($query)){
			$id = $managerRequestRow["ID"];
			$manager = User::findByID($managerRequestRow["UserFKOfManager"]);
			$site = Site::findByID($managerRequestRow["SiteFK"]);
			$hasCompleteAuthority = $managerRequestRow["HasCompleteAuthority"];
			$status = $managerRequestRow["Status"];
			$managerRequest = new ManagerRequest($id, $manager, $site, $hasCompleteAuthority, $status);
			
			array_push($managerRequestsArray, $managerRequest);
		}
		return $managerRequestsArray;
	}
  
	//GETTERS
  	public function getID(){
		if($this->deleted){return null;}
		return intval($this->id);
	}
  
  	public function getRequester(){
		if($this->deleted){return null;}
		return $this->site->getCreator();
	}
	
	public function getManager(){
		if($this->deleted){return null;}
		return $this->manager;
	}
	
	public function getSite(){
		if($this->deleted){return null;}
		return $this->site;
	}
	
	public function getStatus(){
		if($this->deleted){return null;}
		return $this->status;
	}
	
	public function getHasCompleteAuthority(){
		if($this->deleted){return null;}
		return filter_var($this->hasCompleteAuthority, FILTER_VALIDATE_BOOLEAN);
	}
  
	//SETTERS
	public function setHasCompleteAuthority($hasCompleteAuthority){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$hasCompleteAuthority = filter_var($hasCompleteAuthority, FILTER_VALIDATE_BOOLEAN);
			if($status !== false){
				mysqli_query($dbconn, "UPDATE ManagerRequest SET HasCompleteAuthority='$hasCompleteAuthority' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->hasCompleteAuthority = $hasCompleteAuthority;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
  	public function setStatus($status){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$status = self::validStatus($dbconn, $status);
			if($status !== false){
				mysqli_query($dbconn, "UPDATE ManagerRequest SET Status='$status' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->status = $status;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
  
	//REMOVER
  	public function permanentDelete(){
		if(!$this->deleted)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			
			if($this->status == "Pending"){
				$message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">" . $this->getSite()->getCreator()->getFullName() . " no longer requires your services as a manager of the \"" . $this->getSite()->getName() . "\" Caterpillars Count! site in " . $this->getSite()->getRegion() . "." . $approvedMessageAddOn . "</div><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"></div></div>";
				email($this->getManager()->getEmail(), "Your Caterpillars Count! \"" . $this->getSite()->getName() . "\" managment services are no longer required.", $message);
			}
			else if($this->status == "Approved"){
				$message = "<div style=\"text-align:center;border-radius:5px;padding:20px;font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\"><div style=\"text-align:left;color:#777;margin-bottom:40px;font-size:20px;\">" . $this->getSite()->getCreator()->getFullName() . " no longer requires your services as a manager of the \"" . $this->getSite()->getName() . "\" Caterpillars Count! site in " . $this->getSite()->getRegion() . ". Thank you for the time you've dedicated to managing this site in the past.</div><div style=\"padding-top:40px;margin-top:40px;margin-left:-40px;margin-right:-40px;border-top:1px solid #eee;color:#bbb;font-size:14px;\"></div></div>";
				email($this->getManager()->getEmail(), "Your Caterpillars Count! \"" . $this->getSite()->getName() . "\" managment services are no longer required.", $message);
			}
			
			mysqli_query($dbconn, "DELETE FROM `ManagerRequest` WHERE `ID`='" . $this->id . "'");
			$this->deleted = true;
			mysqli_close($dbconn);
			return true;
		}
  	}
	
	//validity insurance
	public static function validManager($dbconn, $manager, $site){
		if($site->getCreator() == $manager){
			return false;
		}
		return $manager;
	}
	
	public static function validStatus($dbconn, $status){
		if($status != "Pending" && $status != "Denied" && $status != "Approved"){
			return false;
		}
		return $status;
	}
}
?>
