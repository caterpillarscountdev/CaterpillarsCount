<?php

require_once('resources/Keychain.php');


class Publication
{
//PRIVATE VARS
	private $id;
	private $citation;
	private $doi;
	private $link;
	private $image;
	private $order;

	private $deleted;

//FACTORY
	public static function create($id, $citation, $doi, $link, $image, $order) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
				
		mysqli_query($dbconn, "INSERT INTO Publication (`ID`, `Citation`, `DOI`, `Link`, `Image`, `Order`) VALUES ('$id', '$citation', '$doi', '$link', '$image') ON DUPLICATE KEY UPDATE DOI='$doi', Link='$link', Image='$image', Order=$order");
		
		return new Publication($id, $citation, $doi, $link, $image, $order);
	}
	private function __construct($id, $citation, $doi, $link, $image, $order) {
		$this->id = intval($id);
		$this->citation = $citation;
		$this->doi = $doi;
		$this->link = $link;
		$this->image = $image;
		$this->order = $order;
		
		$this->deleted = false;
	}

        private static function _constructFromRow($row) {
                $id = $row["ID"];
                $citation = $row["Citation"];
		$doi = $row["DOI"];
                $link = $row["Link"];
                $image = $row["Image"];
                $order = $row["Order"];
		
		return new Publication($id, $citation, $doi, $link, $image, $order);
        }

//FINDERS
	public static function findAll() {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT * FROM `Publication` ORDER BY `Order`, `ID`;");
		
                $pubs = array();
                while($row = mysqli_fetch_assoc($query)){
                  $pub = self::_constructFromRow($row);
      
                  array_push($pubs, $pub);
                }
                return $pubs;
	}

        public static function findByID($id) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = mysqli_real_escape_string($dbconn, htmlentities($id));
		$query = mysqli_query($dbconn, "SELECT * FROM `Publication` WHERE `ID`='$id' LIMIT 1");
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$row = mysqli_fetch_assoc($query);

                return self::_constructFromRow($row);
	}

	public static function findForUser($userFK) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$userFK = mysqli_real_escape_string($dbconn, htmlentities($userFK));
		$query = mysqli_query($dbconn, "SELECT DISTINCT P.* FROM `Publication` P JOIN PublicationUsers U ON P.ID = U.PublicationFK  WHERE U.UserFK='$userFK'");
		
                $pubs = array();
                while($row = mysqli_fetch_assoc($query)){
                  $pub = self::_constructFromRow($row);
      
                  array_push($pubs, $pub);
                }
                return $pubs;
	}
        
	public static function findForSite($siteFK) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$siteFK = mysqli_real_escape_string($dbconn, htmlentities($siteFK));
		$query = mysqli_query($dbconn, "SELECT DISTINCT P.* FROM `Publication` P JOIN PublicationSitess S ON P.ID = S.PublicationFK  WHERE S.SiteFK='$siteFK'");
		
                $pubs = array();
                while($row = mysqli_fetch_assoc($query)){
                  $pub = self::_constructFromRow($row);
      
                  array_push($pubs, $pub);
                }
                return $pubs;
	}
                

//GETTERS
	public function getID() {
		if($this->deleted){return null;}
		return intval($this->id);
	}
	
	public function getCitation() {
		if($this->deleted){return null;}
		return $this->citation;
	}
	
	public function getDOI() {
		if($this->deleted){return null;}
		return $this->doi;
	}
	
	public function getLink() {
		if($this->deleted){return null;}
		return $this->link;
	}
	
	public function getImage() {
		if($this->deleted){return null;}
		return $this->image;
	}
	public function getOrder() {
		if($this->deleted){return null;}
		return $this->order;
	}

//LINKING
        public function addSite($siteFK, $nSurveys) {
          $dbconn = (new Keychain)->getDatabaseConnection();
          $id = $this->id;
          mysqli_query($dbconn, "INSERT INTO PublicationSites (`PublicationFK`, `SiteFK`, `NumberOfSurveys`) VALUES ('$id', '$siteFK', '$nSurveys') ON DUPLICATE KEY UPDATE NumberOfSurveys='$nSurveys'");
          
        }
        public function addUser($userFK, $nSurveys) {
          $dbconn = (new Keychain)->getDatabaseConnection();
          $id = $this->id;
          mysqli_query($dbconn, "INSERT INTO PublicationUsers (`PublicationFK`, `UserFK`, `NumberOfSurveys`) VALUES ('$id', '$userFK', '$nSurveys') ON DUPLICATE KEY UPDATE NumberOfSurveys='$nSurveys'");
          
        }
	
}		
?>
