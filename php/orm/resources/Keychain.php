<?php

class Keychain
{
//PRIVATE VARS
	private $protocol;
	private $domainName;
	private $extraPaths;
	private $hostPointer;
	private $hostUsername;
	private $hostPassword;
	private $databaseName;

//FACTORY
	public function __construct() {
		if(getenv("Openshift") === false){
			require_once("GODADDY_KEYS.php");
			$dbconnCreds = getDatabaseConnectionCredentials();
			$this->hostPointer = $dbconnCreds[0];
			$this->hostUsername = $dbconnCreds[1];
			$this->hostPassword = $dbconnCreds[2];
			$this->databaseName = $dbconnCreds[3];
			
			$pathComponents = getPathComponents();
			$this->protocol = $pathComponents[0];
			$this->domainName = $pathComponents[1];
			$this->extraPaths = $pathComponents[2];
		}
		else{
			if (getenv("DEVELOPMENT_INSTANCE") == 1) {  
			  $this->hostPointer = getenv("DEVCCDB_SERVICE_HOST");	
			} else {
			  $this->hostPointer = getenv("CATERPILLARSV2_SERVICE_HOST");
			}	
			$this->hostUsername = getenv("HOST_USERNAME");
			$this->hostPassword = getenv("HOST_PASSWORD");
			$this->databaseName = getenv("DATABASE_NAME");
			
			$this->protocol = "https://";
			$this->domainName =  "caterpillarscount.unc.edu";
			$this->extraPaths = "";
		}
	}


//GETTERS
	public function getDatabaseConnection(){
		return mysqli_connect($this->hostPointer, $this->hostUsername, $this->hostPassword, $this->databaseName);
	}
	
	public function getProtocol(){
		return $this->protocol;
	}
	
	public function getDomain(){
		return $this->domainName;
	}
	
	public function getExtraPaths(){
		return $this->extraPaths;
	}
	
	public function getRoot(){
		return $this->protocol . $this->domainName . $this->extraPaths;
	}
}		
?>
