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

        private static $conn;

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
                          if (getenv("LOCAL_DEV")) {
                            $this->domainName =  "cc.devel";

                          } else {
                            $this->domainName =  "dev-caterpillarscount2-dept-caterpillars-count.apps.cloudapps.unc.edu";
                          }
                          
			} else {
			  $this->hostPointer = getenv("CATERPILLARSV2_SERVICE_HOST");
                          $this->domainName =  "caterpillarscount.unc.edu";
			}	
			$this->hostUsername = getenv("HOST_USERNAME");
			$this->hostPassword = getenv("HOST_PASSWORD");
			$this->databaseName = getenv("DATABASE_NAME");
                        $this->protocol = "https://";
                        $this->extraPaths = "";
                }
	}


//GETTERS
	public function getDatabaseConnection(){
                //mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
          if (!isset(self::$conn)) {
            //error_log("Creating new connection " . print_r(self::$conn, true));
            self::$conn = mysqli_connect($this->hostPointer, $this->hostUsername, $this->hostPassword, $this->databaseName);
          }
          return self::$conn;
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
