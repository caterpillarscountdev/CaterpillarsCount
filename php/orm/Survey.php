<?php

require_once('resources/Keychain.php');
require_once('User.php');
require_once('Plant.php');
require_once('ArthropodSighting.php');

class Survey
{
//PRIVATE VARS
	private $id;							//INT
	private $submissionTimestamp;					//INT: seconds since the Unix Epoch (January 1 1970 00:00:00 GMT) marking time that survey was submitted to our database. NOT the time entered by the user into the survey.
	private $observer;
	private $plant;
	private $localDate;						//the time entered by the user into the survey
	private $localTime;
	private $observationMethod;
	private $notes;
	private $wetLeaves;
	private $plantSpecies;
	private $numberOfLeaves;
	private $averageLeafLength;
	private $herbivoryScore;
	private $averageNeedleLength;
	private $linearBranchLength;
	private $submittedThroughApp;
	private $reviewedAndApproved;
	
	private $deleted;

//FACTORY
	public static function create($observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		if(!$dbconn){
			return "Cannot connect to server.";
		}
		
		$submissionTimestamp = time();
		$observer = self::validObserver($dbconn, $observer, $plant);
		$plant = self::validPlant($dbconn, $plant);
		$localDate = self::validLocalDate($dbconn, $localDate);
		$localTime = self::validLocalTime($dbconn, $localTime);
		$observationMethod = self::validObservationMethod($dbconn, $observationMethod);
		$notes = self::validNotes($dbconn, $notes);
		$wetLeaves = filter_var($wetLeaves, FILTER_VALIDATE_BOOLEAN);
		$plantSpecies = self::validPlantSpecies($dbconn, $plantSpecies, $plant);
		$isConifer = (intval($averageNeedleLength) !== -1);
		$numberOfLeaves = $isConifer ? -1 : self::validNumberOfLeaves($dbconn, $numberOfLeaves);
		$averageLeafLength = $isConifer ? -1 : self::validAverageLeafLength($dbconn, $averageLeafLength);
		$herbivoryScore = $isConifer ? -1 : self::validHerbivoryScore($dbconn, $herbivoryScore);
		$averageNeedleLength = $isConifer ? self::validAverageNeedleLength($dbconn, $averageNeedleLength) : -1;
		$linearBranchLength = $isConifer ? self::validLinearBranchLength($dbconn, $linearBranchLength) : -1;
		$submittedThroughApp = filter_var($submittedThroughApp, FILTER_VALIDATE_BOOLEAN);
		$reviewedAndApproved = false;
		
		
		$failures = "";
		
		if($plant === false){
			$failures .= "Invalid plant. ";
		}
		else if($observer === false){
			$failures .= "You have not been authenticated for this site. ";
		}
		if($localDate === false){
			$failures .= "Invalid date of survey. ";
		}
		if($localTime === false){
			$failures .= "Invalid time of survey. ";
		}
		if($observationMethod === false){
			$failures .= "Select an observation method. ";
		}
		if($notes === false){
			$failures .= "Invalid notes. ";
		}
		if($plantSpecies === false){
			$failures .= "Invalid plant species. ";
		}
		if(!$isConifer && $numberOfLeaves === false){
			$failures .= "Number of leaves must be between 1 and 500. ";
		}
		if(!$isConifer && $averageLeafLength === false){
			$failures .= "Average leaf length must be between 1cm and 60cm. ";
		}
		if(!$isConifer && $herbivoryScore === false){
			$failures .= "Select an herbivory score. ";
		}
		if($isConifer && $averageNeedleLength === false){
			$failures .= "Average needle length must be between 1cm and 60cm. ";
		}
		if($isConifer && $linearBranchLength === false){
			$failures .= "Linear branch length must be between 1cm and 500cm. ";
		}
		
		if($failures != ""){
			return $failures;
		}
		
		$needToSendToSciStarter = 1;
		if($plant->getSite()->getName() == "Example Site"){
			$needToSendToSciStarter = 0;
		}
		mysqli_query($dbconn, "INSERT INTO Survey (`SubmissionTimestamp`, `UserFKOfObserver`, `PlantFK`, `LocalDate`, `LocalTime`, `ObservationMethod`, `Notes`, `WetLeaves`, `PlantSpecies`, `NumberOfLeaves`, `AverageLeafLength`, `HerbivoryScore`, `AverageNeedleLength`, `LinearBranchLength`, `SubmittedThroughApp`, `NeedToSendToSciStarter`, `ReviewedAndApproved`) VALUES ('" . $submissionTimestamp . "', '" . $observer->getID() . "', '" . $plant->getID() . "', '$localDate', '$localTime', '$observationMethod', '$notes', '$wetLeaves', '$plantSpecies', '$numberOfLeaves', '$averageLeafLength', '$herbivoryScore', '$averageNeedleLength', '$linearBranchLength', '$submittedThroughApp', '$needToSendToSciStarter', '$reviewedAndApproved')");
		$id = intval(mysqli_insert_id($dbconn));
		mysqli_close($dbconn);
		
		if($plant->getSite()->getDateEstablished() == "0000-00-00"){
			$plant->getSite()->setDateEstablished($localDate);
		}
		
		return new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved);
	}
	private function __construct($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved) {
		$this->id = intval($id);
		$this->submissionTimestamp = intval($submissionTimestamp);
		$this->observer = $observer;
		$this->plant = $plant;
		$this->localDate = $localDate;
		$this->localTime = $localTime;
		$this->observationMethod = $observationMethod;
		$this->notes = $notes;
		$this->wetLeaves = filter_var($wetLeaves, FILTER_VALIDATE_BOOLEAN);
		$this->plantSpecies = $plantSpecies;
		$this->numberOfLeaves = intval($numberOfLeaves);
		$this->averageLeafLength = intval($averageLeafLength);
		$this->herbivoryScore = max(0, intval($herbivoryScore));
		$this->averageNeedleLength = intval($averageNeedleLength);
		$this->linearBranchLength = intval($linearBranchLength);
		$this->submittedThroughApp = $submittedThroughApp;
		$this->arthropodSightings = null;
		$this->reviewedAndApproved = filter_var($reviewedAndApproved, FILTER_VALIDATE_BOOLEAN);
		
		$this->deleted = false;
	}

//FINDERS
	public static function findByID($id) {
		$dbconn = (new Keychain)->getDatabaseConnection();
		$id = intval($id);
		$query = mysqli_query($dbconn, "SELECT * FROM `Survey` WHERE `ID`='$id' LIMIT 1");
		mysqli_close($dbconn);
		
		if(mysqli_num_rows($query) == 0){
			return null;
		}
		
		$surveyRow = mysqli_fetch_assoc($query);
		
		$submissionTimestamp = intval($surveyRow["SubmissionTimestamp"]);
		$observer = User::findByID($surveyRow["UserFKOfObserver"]);
		$plant = Plant::findByID($surveyRow["PlantFK"]);
		$localDate = $surveyRow["LocalDate"];
		$localTime = $surveyRow["LocalTime"];
		$observationMethod = $surveyRow["ObservationMethod"];
		$notes = $surveyRow["Notes"];
		$wetLeaves = $surveyRow["WetLeaves"];
		$plantSpecies = $surveyRow["PlantSpecies"];
		$numberOfLeaves = $surveyRow["NumberOfLeaves"];
		$averageLeafLength = $surveyRow["AverageLeafLength"];
		$herbivoryScore = $surveyRow["HerbivoryScore"];
		$averageNeedleLength = $surveyRow["AverageNeedleLength"];
		$linearBranchLength = $surveyRow["LinearBranchLength"];
		$submittedThroughApp = $surveyRow["SubmittedThroughApp"];
		$reviewedAndApproved = $surveyRow["ReviewedAndApproved"];
		
		return new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved);
	}
	
	public static function findSurveysByIDs($surveyIDs, $orderBy="", $start=null, $limit=null) {
		if(count($surveyIDs) == 0){
			return array();
		}
		
		for($i = 0; $i < count($surveyIDs); $i++){
			$surveyIDs[$i] = intval($surveyIDs[$i]);
		}
		
		$acceptableOrderBys = array(
			"LocalDate DESC, LocalTime DESC"
		);
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$limitSQL = " LIMIT " . count($surveyIDs);
		if($limit !== null){
			if($start === null){
				$limitSQL = " LIMIT " . min($limit, count($surveyIDs));
			}
			else{
				$limitSQL = " LIMIT " . $start . ", " . min($limit, count($surveyIDs));
			}
		}
		
		$query = mysqli_query($dbconn, "SELECT * FROM `Survey` WHERE `ID` IN ('" . implode("', '", $surveyIDs) . "') " . (in_array($orderBy, $acceptableOrderBys) ? "ORDER BY " . $orderBy . " " : "") . $limitSQL);
		mysqli_close($dbconn);
		
		//get associated plants
		$associatedPlantFKs = array();
		while($surveyRow = mysqli_fetch_assoc($query)){
			$associatedPlantFKs[$surveyRow["PlantFK"]] = 1;
		}
		$associatedPlantFKs = array_keys($associatedPlantFKs);
		
		$associatedPlantsByPlantFK = array();
		$associatedPlants = Plant::findPlantsByIDs($associatedPlantFKs);
		for($i = 0; $i < count($associatedPlants); $i++){
			$associatedPlantsByPlantFK[$associatedPlants[$i]->getID()] = $associatedPlants[$i];
		}
		
		//get associated users
		$associatedUserFKs = array();
		mysqli_data_seek($query, 0);
		while($surveyRow = mysqli_fetch_assoc($query)){
			$associatedUserFKs[$surveyRow["UserFKOfObserver"]] = 1;
		}
		$associatedUserFKs = array_keys($associatedUserFKs);
		
		$associatedUsersByUserFK = array();
		$associatedUsers = User::findUsersByIDs($associatedUserFKs);
		for($i = 0; $i < count($associatedUsers); $i++){
			$associatedUsersByUserFK[$associatedUsers[$i]->getID()] = $associatedUsers[$i];
		}
		
		//make survey objects
		$surveysArray = array();
		mysqli_data_seek($query, 0);
		while($surveyRow = mysqli_fetch_assoc($query)){
			$id = intval($surveyRow["ID"]);
			$submissionTimestamp = intval($surveyRow["SubmissionTimestamp"]);
			$observer = array_key_exists($surveyRow["UserFKOfObserver"], $associatedUsersByUserFK) ? $associatedUsersByUserFK[$surveyRow["UserFKOfObserver"]] : null;
			$plant = array_key_exists($surveyRow["PlantFK"], $associatedPlantsByPlantFK) ? $associatedPlantsByPlantFK[$surveyRow["PlantFK"]] : null;
			$localDate = $surveyRow["LocalDate"];
			$localTime = $surveyRow["LocalTime"];
			$observationMethod = $surveyRow["ObservationMethod"];
			$notes = $surveyRow["Notes"];
			$wetLeaves = $surveyRow["WetLeaves"];
			$plantSpecies = $surveyRow["PlantSpecies"];
			$numberOfLeaves = $surveyRow["NumberOfLeaves"];
			$averageLeafLength = $surveyRow["AverageLeafLength"];
			$herbivoryScore = $surveyRow["HerbivoryScore"];
			$averageNeedleLength = $surveyRow["AverageNeedleLength"];
			$linearBranchLength = $surveyRow["LinearBranchLength"];
			$submittedThroughApp = $surveyRow["SubmittedThroughApp"];
			$reviewedAndApproved = $surveyRow["ReviewedAndApproved"];

			$surveysArray[] = new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved);
		}
		return $surveysArray;
	}
	
	public static function findSurveysByUser($user, $filters, $start, $limit) {
		//returns all surveys user has completed
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$start = mysqli_real_escape_string($dbconn, htmlentities(($start . "")));
		$limit = mysqli_real_escape_string($dbconn, htmlentities(($limit . "")));
		$filterKeys = array_keys($filters);
		foreach($filterKeys as $filterKey) {
			$filters[$filterKey] = mysqli_real_escape_string($dbconn, htmlentities(($filters[$filterKey] . "")));
		}
		
		$surveysArray = array();
		//as well as all surveys completed at sites the user created or manages
		$sites = $user->getSites();
		$siteIDs = array(-1);
		for($i = 0; $i < count($sites); $i++){
			$siteIDs[] = $sites[$i]->getID();
		}
		
		$baseTable = "`Survey`";
		$additionalSQL = "";
		$groupBy = "";
		
		$arthropodSearch = trim($filters["arthropod"]);
		$minArthropodLength = intval($filters["minArthropodLength"]);
		if(strlen($arthropodSearch) > 0 || $minArthropodLength > 0){
			$baseTable = "`ArthropodSighting` JOIN `Survey` ON ArthropodSighting.SurveyFK = Survey.ID";
			$groupBy = " GROUP BY ArthropodSighting.SurveyFK";
		}
		
		if($minArthropodLength > 0){
			$additionalSQL .= " AND ArthropodSighting.Length>='" . $minArthropodLength . "'";
		}
		
		if(strlen($arthropodSearch) > 0){
			$additionalSQL .= " AND (ArthropodSighting.OriginalGroup='" . $arthropodSearch . "' OR ArthropodSighting.UpdatedGroup='" . $arthropodSearch . "')";
		}
		
		$userSearch = trim($filters["user"]);
		if(strlen($userSearch) > 0){
			$additionalSQL .= " AND CONCAT(User.FirstName, ' ', User.LastName) LIKE '%" . $userSearch . "%'";
		}
		
		$siteSearch = trim(strval($filters["site"]));
		$circleSearch = trim(strval($filters["circle"]));
		$codeSearch = trim($filters["code"]);
		
		if(strlen($codeSearch) > 0){
			$additionalSQL .= " AND Plant.Code='" . $codeSearch . "'";
		}
		else if(strlen($siteSearch) > 0 && is_numeric($siteSearch)){
			$additionalSQL .= " AND Plant.SiteFK='" . $siteSearch . "'";
			if(strlen($circleSearch) > 0 && is_numeric($circleSearch)){
				$additionalSQL .= " AND Plant.Circle='" . $circleSearch . "'";
			}
		}
		
		$dateSearch = mysqli_real_escape_string($dbconn, trim(htmlentities(strval($filters["date"]))));
		
		$totalCount = intval(mysqli_fetch_assoc(mysqli_query($dbconn, "SELECT COUNT(*) AS `Count` FROM (SELECT DISTINCT Survey.ID FROM " . $baseTable . " JOIN `Plant` ON Survey.PlantFK = Plant.ID JOIN `User` ON Survey.UserFKOfObserver=User.ID WHERE (Plant.SiteFK IN (" . join(",", $siteIDs) . ") OR Survey.UserFKOfObserver='" . $user->getID() . "') AND Survey.LocalDate LIKE '" . $dateSearch . "'" . $additionalSQL . $groupBy . ") AS Results"))["Count"]);
		if($limit === "max"){
			$limit = $totalCount;
		}
		if($start === "last"){
			$start = $totalCount - ($totalCount % intval($limit));
			if($start == $totalCount && $totalCount > 0){
				$start = $totalCount - intval($limit);
			}
		}
		$query = mysqli_query($dbconn, "SELECT Survey.* FROM " . $baseTable . " JOIN `Plant` ON Survey.PlantFK = Plant.ID JOIN `User` ON Survey.UserFKOfObserver=User.ID WHERE (Plant.SiteFK IN (" . join(",", $siteIDs) . ") OR Survey.UserFKOfObserver='" . $user->getID() . "') AND Survey.LocalDate LIKE '" . $dateSearch . "'" . $additionalSQL . $groupBy . " ORDER BY Survey.LocalDate DESC, Survey.LocalTime DESC, Plant.Code DESC LIMIT " . $start . ", " . $limit);
		$observerFKs = array();
		$plantFKs = array();
		while($surveyRow = mysqli_fetch_assoc($query)){
			$observerFKs[] = $surveyRow["UserFKOfObserver"];
			$plantFKs[] = $surveyRow["PlantFK"];
		}
		
		$users = User::findUsersByIDs($observerFKs);
		$plants = Plant::findPlantsByIDs($plantFKs);
		
		$usersByID = array();
		for($i = 0; $i < count($users); $i++){
			$usersByID[$users[$i]->getID()] = $users[$i];
		}
		
		$plantsByID = array();
		for($i = 0; $i < count($plants); $i++){
			$plantsByID[$plants[$i]->getID()] = $plants[$i];
		}
		
		mysqli_data_seek($query, 0);
		while($surveyRow = mysqli_fetch_assoc($query)){
			$id = $surveyRow["ID"];
			$submissionTimestamp = intval($surveyRow["SubmissionTimestamp"]);
			$observer = $usersByID[$surveyRow["UserFKOfObserver"]];
			$plant = $plantsByID[$surveyRow["PlantFK"]];
			$localDate = $surveyRow["LocalDate"];
			$localTime = $surveyRow["LocalTime"];
			$observationMethod = $surveyRow["ObservationMethod"];
			$notes = $surveyRow["Notes"];
			$wetLeaves = $surveyRow["WetLeaves"];
			$plantSpecies = $surveyRow["PlantSpecies"];
			$numberOfLeaves = $surveyRow["NumberOfLeaves"];
			$averageLeafLength = $surveyRow["AverageLeafLength"];
			$herbivoryScore = $surveyRow["HerbivoryScore"];
			$averageNeedleLength = $surveyRow["AverageNeedleLength"];
			$linearBranchLength = $surveyRow["LinearBranchLength"];
			$submittedThroughApp = $surveyRow["SubmittedThroughApp"];
			$reviewedAndApproved = $surveyRow["ReviewedAndApproved"];
			
			$survey = new Survey($id, $submissionTimestamp, $observer, $plant, $localDate, $localTime, $observationMethod, $notes, $wetLeaves, $plantSpecies, $numberOfLeaves, $averageLeafLength, $herbivoryScore, $averageNeedleLength, $linearBranchLength, $submittedThroughApp, $reviewedAndApproved);
			
			array_push($surveysArray, $survey);
		}
		return array($totalCount, $surveysArray);
	}
	
	public static function findSurveysByFlagged($user, $start, $limit){
		if(!User::isSuperUser($user)){
			return array();
		}
		
		$start = $start == "last" ? $start : intval($start);
		$limit = intval($limit);
		
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$flaggingRules = self::getFlaggingRules();
		
		$arthropodGroupsExcludedFromTotalQuantityCount = array();
		$rareArthropodGroups = array();
		foreach($flaggingRules["arthropodGroupFlaggingRules"] as $arthropodGroup => $flaggingData){
			if($flaggingData["excludedFromTotalQuantityCount"]){
				$arthropodGroupsExcludedFromTotalQuantityCount[] = mysqli_real_escape_string($dbconn, $arthropodGroup);
			}
			
			if($flaggingData["isRare"]){
				$rareArthropodGroups[] = mysqli_real_escape_string($dbconn, $arthropodGroup);
			}
		}
		
		$flaggedSurveyIDs = array();
		
		//survey flags
		$sql = "SELECT `ID` FROM `Survey` WHERE `AverageNeedleLength`='-1' AND (`NumberOfLeaves`<'" . intval($flaggingRules["minSafeLeaves"]) . "' OR `NumberOfLeaves`>'" . intval($flaggingRules["maxSafeLeaves"]) . "' OR `AverageLeafLength`>'" . intval($flaggingRules["maxSafeLeafLength"]) . "')";
		$query = mysqli_query($dbconn, $sql);
		while($row = mysqli_fetch_assoc($query)){
			$flaggedSurveyIDs[$row["ID"]] = 1;
		}
		
		//arthropod flags
		$sql = "SELECT DISTINCT `SurveyFK` FROM `ArthropodSighting` WHERE (`UpdatedSawfly`='1' AND (`Length`>'" . intval($flaggingRules["sawflyFlaggingRules"]["maxSafeLength"]) . "' OR Quantity>'" . intval($flaggingRules["sawflyFlaggingRules"]["maxSafeQuantity"]) . "'))";
		foreach($flaggingRules["arthropodGroupFlaggingRules"] as $arthropodGroup => $flaggingData){
			$sql .= " OR (`UpdatedGroup`='" . mysqli_real_escape_string($dbconn, $arthropodGroup) . "' AND (`Length`>'" . intval($flaggingData["maxSafeLength"]) . "' OR `Quantity`>'" . intval($flaggingData["maxSafeQuantity"]) . "'))";
		}
		$query = mysqli_query($dbconn, $sql);
		while($row = mysqli_fetch_assoc($query)){
			$flaggedSurveyIDs[$row["SurveyFK"]] = 1;
		}
		
		//too many total arthropods (minus specified exclusions) flags
		// removed 6/1/2023
		// $sql = "SELECT `SurveyFK` FROM `ArthropodSighting`" . (count($arthropodGroupsExcludedFromTotalQuantityCount) > 0 ? (" WHERE `UpdatedGroup` NOT IN ('" . implode("', '", $arthropodGroupsExcludedFromTotalQuantityCount) . "')") : "") . " GROUP BY `SurveyFK` HAVING SUM(`Quantity`)>'" . intval($flaggingRules["maxSafeTotalQuantity"]) . "'";
		// $query = mysqli_query($dbconn, $sql);
		// while($row = mysqli_fetch_assoc($query)){
		// 	$flaggedSurveyIDs[$row["SurveyFK"]] = 1;
		// }
		
		//too many distinct groups flags
		// removed 6/1/2023
		// $sql = "SELECT `SurveyFK` FROM (SELECT DISTINCT `SurveyFK`, `UpdatedGroup` FROM `ArthropodSighting`) AS `DistinctSurveyGroupTable` GROUP BY `SurveyFK` HAVING COUNT(*)>'" . intval($flaggingRules["maxSafeArthropodGroups"]) . "'";
		// $query = mysqli_query($dbconn, $sql);
		// while($row = mysqli_fetch_assoc($query)){
		// 	$flaggedSurveyIDs[$row["SurveyFK"]] = 1;
		// }
		
		//too many rare groups flags
		// removed 6/1/2023
		// if(count($rareArthropodGroups) > 0){
		// 	$sql = "SELECT `SurveyFK` FROM `ArthropodSighting` WHERE `UpdatedGroup` IN ('" . implode("', '", $rareArthropodGroups) . "') GROUP BY SurveyFK HAVING COUNT(DISTINCT (CONCAT(`SurveyFK`, `UpdatedGroup`)))>'" . intval($flaggingRules["maxSafeRareArthropodGroups"]) . "'";
		// 	$query = mysqli_query($dbconn, $sql);
		// 	while($row = mysqli_fetch_assoc($query)){
		// 		$flaggedSurveyIDs[$row["SurveyFK"]] = 1;
		// 	}
		// }
		
		//remove example site data
		$sql = "SELECT `Survey`.`ID` FROM `Survey` JOIN `Plant` ON `Survey`.`PlantFK`=`Plant`.`ID` WHERE `Plant`.`SiteFK`='2'";
		$query = mysqli_query($dbconn, $sql);
		while($row = mysqli_fetch_assoc($query)){
			unset($flaggedSurveyIDs[$row["ID"]]);
		}
		
		//remove approved survey data
		$sql = "SELECT `ID` FROM `Survey` WHERE `ReviewedAndApproved`>0"; // changed this to allow 1,2,3 as values to remove, but why pull in data and then remove?  Why not just remove from first set?
		$query = mysqli_query($dbconn, $sql);
		while($row = mysqli_fetch_assoc($query)){
			unset($flaggedSurveyIDs[$row["ID"]]);
		}
		
		mysqli_close($dbconn);
		
		$flaggedSurveyIDs = array_keys($flaggedSurveyIDs);
		
		$totalCount = count($flaggedSurveyIDs);
		
		if($start === "last"){
			$start = $totalCount - ($totalCount % intval($limit));
			if($start == $totalCount && $totalCount > 0){
				$start = $totalCount - intval($limit);
			}
		}
		
		return array($totalCount, self::findSurveysByIDs($flaggedSurveyIDs, "LocalDate DESC, LocalTime DESC", $start, $limit));
	}

//GETTERS
	public function getID() {
		if($this->deleted){return null;}
		return intval($this->id);
	}
	
	public function getSubmissionTimestamp() {
		if($this->deleted){return null;}
		return intval($this->submissionTimestamp);
	}
	
	public function getObserver() {
		if($this->deleted){return null;}
		return $this->observer;
	}
	
	public function getPlant() {
		if($this->deleted){return null;}
		return $this->plant;
	}
	
	public function getLocalDate() {
		if($this->deleted){return null;}
		return $this->localDate;
	}
	
	public function getLocalTime() {
		if($this->deleted){return null;}
		return $this->localTime;
	}
	
	public function getObservationMethod() {
		if($this->deleted){return null;}
		return $this->observationMethod;
	}
	
	public function getNotes() {
		if($this->deleted){return null;}
		return $this->notes;
	}
	
	public function getWetLeaves() {
		if($this->deleted){return null;}
		return filter_var($this->wetLeaves, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function getArthropodSightings() {
		if($this->deleted){return null;}
		return ArthropodSighting::findArthropodSightingsBySurvey($this);
	}
	
	public function getPlantSpecies() {
		if($this->deleted){return null;}
		return $this->plantSpecies;
	}
	
	public function getNumberOfLeaves() {
		if($this->deleted){return null;}
		return intval($this->numberOfLeaves);
	}
	
	public function getAverageLeafLength() {
		if($this->deleted){return null;}
		return intval($this->averageLeafLength);
	}
	
	public function getHerbivoryScore() {
		if($this->deleted){return null;}
		return $this->herbivoryScore;
	}
	
	public function getAverageNeedleLength() {
		if($this->deleted){return null;}
		return intval($this->averageNeedleLength);
	}
	
	public function getLinearBranchLength() {
		if($this->deleted){return null;}
		return intval($this->linearBranchLength);
	}
	
	public function getSubmittedThroughApp(){
		if($this->deleted){return null;}
		return filter_var($this->submittedThroughApp, FILTER_VALIDATE_BOOLEAN);
	}
	
	public function isConifer(){
		if($this->deleted){return null;}
		return intval($this->averageNeedleLength) > -1;
	}
	
	public function getReviewedAndApproved(){
		if($this->deleted){return null;}
		return filter_var($this->reviewedAndApproved, FILTER_VALIDATE_BOOLEAN);
	}
	
	private static function getFlaggingRules(){
                        // the following removed from the returned array 6/1/2023
		        // "maxSafeTotalQuantity" => 10,
			// "maxSafeArthropodGroups" => 4,
			// "maxSafeRareArthropodGroups" => 3,
		return array(
			"minSafeLeaves" => 5,
			"maxSafeLeaves" => 400,
			"maxSafeLeafLength" => 30,
			"sawflyFlaggingRules" => array(
				"maxSafeLength" => 50,
				"maxSafeQuantity" => 20
			),
			"arthropodGroupFlaggingRules" => array(
				"ant" => array(
					"maxSafeLength" => 17,
					"maxSafeQuantity" => 50,
					"excludedFromTotalQuantityCount" => true,
					"isRare" => false
				),
				"aphid" => array(
					"maxSafeLength" => 10,
					"maxSafeQuantity" => 50,
					"excludedFromTotalQuantityCount" => true,
					"isRare" => false
				),
				"bee" =>  array(
					"maxSafeLength" => 25,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => true
				),
				"beetle" =>  array(
					"maxSafeLength" => 20,
					"maxSafeQuantity" => 10,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => false
				),
				"caterpillar" =>  array(
					"maxSafeLength" => 50,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => false
				),
				"daddylonglegs" =>  array(
					"maxSafeLength" => 15,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => true
				),
				"fly" =>  array(
					"maxSafeLength" => 20,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => false
				),
				"grasshopper" =>  array(
					"maxSafeLength" => 30,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => true
				),
				"leafhopper" =>  array(
					"maxSafeLength" => 20,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => false
				),
				"moths" =>  array(
					"maxSafeLength" => 30,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => true
				),
				"spider" =>  array(
					"maxSafeLength" => 20,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => false
				),
				"truebugs" =>  array(
					"maxSafeLength" => 25,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => true
				),
				"other" =>  array(
					"maxSafeLength" => 25,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => false
				),
				"unidentified" =>  array(
					"maxSafeLength" => 25,
					"maxSafeQuantity" => 6,
					"excludedFromTotalQuantityCount" => false,
					"isRare" => false
				)
			)
		);
	}
	
	public function getFlags(){
		$flaggingRules = self::getFlaggingRules();
		
		//grab flags based on info provided above...
		$flags = array();
		
		$arthropodGroupsExcludedFromTotalQuantityCount = array();
		$rareArthropodGroups = array();
		foreach($flaggingRules["arthropodGroupFlaggingRules"] as $arthropodGroup => $flaggingData){
			if($flaggingData["excludedFromTotalQuantityCount"]){
				$arthropodGroupsExcludedFromTotalQuantityCount[] = $arthropodGroup;
			}
			
			if($flaggingData["isRare"]){
				$rareArthropodGroups[] = $arthropodGroup;
			}
		}
		
		$updatedArthropodGroups = array();
		$totalQuantityExcludingSpecifiedArthropodGroups = 0;
		$arthropodSightings = $this->getArthropodSightings();
		for($i = 0; $i < count($arthropodSightings); $i++){
			$updatedArthropodGroup = $arthropodSightings[$i]->getUpdatedGroup();
			$isSawfly = $arthropodSightings[$i]->getUpdatedSawfly();
			
			//flag lengths
			$arthropodLength = $arthropodSightings[$i]->getLength();
			if($isSawfly && $arthropodLength > $flaggingRules["sawflyFlaggingRules"]["maxSafeLength"]){
				$flags[] = "LONG ARTHROPOD: " . $arthropodLength . "mm exceeds safe \"sawfly\" limit of " . $flaggingRules["sawflyFlaggingRules"]["maxSafeLength"] . "mm.";
			}
			
			if(array_key_exists($updatedArthropodGroup, $flaggingRules["arthropodGroupFlaggingRules"]) && $arthropodLength > $flaggingRules["arthropodGroupFlaggingRules"][$updatedArthropodGroup]["maxSafeLength"]){
				$flags[] = "LONG ARTHROPOD: " . $arthropodLength . "mm exceeds safe \"" . $updatedArthropodGroup . "\" limit of " . $flaggingRules["arthropodGroupFlaggingRules"][$updatedArthropodGroup]["maxSafeLength"] . "mm.";
			}
			
			//flag quantities
			$arthropodQuantity = $arthropodSightings[$i]->getQuantity();
			if($isSawfly && $arthropodQuantity > $flaggingRules["sawflyFlaggingRules"]["maxSafeQuantity"]){
				$flags[] = "LARGE ARTHROPOD QUANTITY: " . $arthropodQuantity . " exceeds safe \"sawfly\" quantity limit of " . $MAX_SAFE_SAWFLY_QUANTITY . ".";
			}
			
			if(array_key_exists($updatedArthropodGroup, $flaggingRules["arthropodGroupFlaggingRules"]) && $arthropodQuantity > $flaggingRules["arthropodGroupFlaggingRules"][$updatedArthropodGroup]["maxSafeQuantity"]){
				$flags[] = "LARGE ARTHROPOD QUANTITY: " . $arthropodQuantity . " exceeds safe \"" . $updatedArthropodGroup . "\" quantity limit of " . $flaggingRules["arthropodGroupFlaggingRules"][$updatedArthropodGroup]["maxSafeQuantity"] . ".";
			}
			
			//collect unique groups in $updatedArthropodGroups array
			if(!in_array($updatedArthropodGroup, $updatedArthropodGroups)){
				$updatedArthropodGroups[] = $updatedArthropodGroup;
			}
			
			//collect total quantity, excluding specified arthropod groups, in $totalQuantityExcludingSpecifiedArthropodGroups
			if(!in_array($updatedArthropodGroup, $arthropodGroupsExcludedFromTotalQuantityCount)){
				$totalQuantityExcludingSpecifiedArthropodGroups += $arthropodQuantity;
			}
		}
		
		//flag too many total arthropods (minus groups we've specifically excluded)
		// removed 6/1/2023
		// if($totalQuantityExcludingSpecifiedArthropodGroups > $flaggingRules["maxSafeTotalQuantity"]){
		// 	$excludingClause = "";
		// 	if(count($arthropodGroupsExcludedFromTotalQuantityCount) > 0){
		// 		$excludedGroupsCopy = $arthropodGroupsExcludedFromTotalQuantityCount;
		// 		$lastGroup = count($excludedGroupsCopy) > 1 ?  "\" and \"" . array_pop($excludedGroupsCopy) : "";
		// 		$excludingClause = " (excluding \"" . implode("\", \"", $excludedGroupsCopy) . $lastGroup . "\")";
		// 	}
                //  
		// 	$flags[] = "TOO MANY ARTHROPODS: " . $totalQuantityExcludingSpecifiedArthropodGroups . " total arthropods" . $excludingClause . " exceeds safe limit of " .  $flaggingRules["maxSafeTotalQuantity"] . ".";
		// }
		
		//flag too many arthropod groups
		// removed 6/1/2023
		// $numberOfArthropodGroups = count($updatedArthropodGroups);
		// if($numberOfArthropodGroups > $flaggingRules["maxSafeArthropodGroups"]){
		// 	$flags[] = "TOO MANY ARTHROPOD GROUPS: " . $numberOfArthropodGroups . " arthropod groups exceeds safe limit of " . $flaggingRules["maxSafeArthropodGroups"];
		// }
		// removed 6/1/2023
		// $rareArthropodGroupsInSurvey = array();
		// for($i = 0; $i < count($updatedArthropodGroups); $i++){
		// 	if(in_array($updatedArthropodGroups[$i], $rareArthropodGroups)){
		// 		$rareArthropodGroupsInSurvey[] = $updatedArthropodGroups[$i];
		// 	}
		// }
		
		//flag too many rare arthropod groups
		// removed 6/1/2023
		// $numberOfRareArthropodGroupsInSurvey = count($rareArthropodGroupsInSurvey);
		// if($numberOfRareArthropodGroupsInSurvey > $flaggingRules["maxSafeRareArthropodGroups"]){
		// 	$groupsClause = "";
		// 	if($numberOfRareArthropodGroupsInSurvey > 0){
		// 		$rareGroupsInSurveyCopy = $rareArthropodGroupsInSurvey;
		// 		$lastGroup = count($rareGroupsInSurveyCopy) > 1 ?  "\" and \"" . array_pop($rareGroupsInSurveyCopy) : "";
		// 		$groupsClause = " (\"" . implode("\", \"", $rareGroupsInSurveyCopy) . $lastGroup . "\")";
		// 	}
		// 	$flags[] = "TOO MANY RARE ARTHROPOD GROUPS: " . $numberOfRareArthropodGroups . " rare arthropod groups" . $groupsClause . " exceeds the safe limit of " . $flaggingRules["maxSafeRareArthropodGroups"];
		// } 
		
		//flag too few leaves
		$isConifer = $this->isConifer();
		$numberOfLeaves = $this->getNumberOfLeaves();
		if(!$isConifer && $numberOfLeaves < $flaggingRules["minSafeLeaves"]){
			$flags[] = "TOO FEW LEAVES: " . $numberOfLeaves . " leaves does not meet safe limit of " . $flaggingRules["minSafeLeaves"] . " leaves.";
		}
		
		//flag too many leaves
		if(!$isConifer && $numberOfLeaves > $flaggingRules["maxSafeLeaves"]){
			$flags[] = "TOO MANY LEAVES: " . $numberOfLeaves . " leaves exceeds safe limit of " . $flaggingRules["maxSafeLeaves"] . " leaves.";
		}
		
		//flag long leaves
		$averageLeafLength = $this->getAverageLeafLength();
		if(!$isConifer && $averageLeafLength > $flaggingRules["maxSafeLeafLength"]){
			$flags[] = "LONG LEAVES: " . $averageLeafLength . "cm exceeds safe limit of " . $flaggingRules["maxSafeLeafLength"] . "cm.";
		}
		
		return $flags;
	}
	
//SETTERS
	public function setPlant($plant){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$plant = self::validPlant($dbconn, $plant);
			if($plant !== false){
				mysqli_query($dbconn, "UPDATE Survey SET PlantFK='" . $plant->getID() . "' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->plant = $plant;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setLocalDate($localDate){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$localDate = self::validLocalDate($dbconn, $localDate);
			if($localDate !== false){
				mysqli_query($dbconn, "UPDATE Survey SET LocalDate='$localDate' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->localDate = $localDate;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setLocalTime($localTime){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$localTime = self::validLocalTime($dbconn, $localTime);
			if($localTime !== false){
				mysqli_query($dbconn, "UPDATE Survey SET LocalTime='$localTime' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->localTime = $localTime;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setObservationMethod($observationMethod){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$observationMethod = self::validObservationMethod($dbconn, $observationMethod);
			if($observationMethod !== false){
				mysqli_query($dbconn, "UPDATE Survey SET ObservationMethod='$observationMethod' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->observationMethod = $observationMethod;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setNotes($notes){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$notes = self::validNotes($dbconn, $notes);
			if($notes !== false){
				mysqli_query($dbconn, "UPDATE Survey SET Notes='$notes' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->notes = $notes;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setWetLeaves($wetLeaves){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$wetLeaves = filter_var($wetLeaves, FILTER_VALIDATE_BOOLEAN);
			mysqli_query($dbconn, "UPDATE Survey SET WetLeaves='$wetLeaves' WHERE ID='" . $this->id . "'");
			mysqli_close($dbconn);
			$this->wetLeaves = $wetLeaves;
			return true;
		}
		return false;
	}
	
	public function setPlantSpecies($plantSpecies){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$plantSpecies = self::validPlantSpecies($dbconn, $plantSpecies);
			if($plantSpecies !== false){
				mysqli_query($dbconn, "UPDATE Survey SET PlantSpecies='$plantSpecies' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->plantSpecies = $plantSpecies;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setNumberOfLeaves($numberOfLeaves){
		if(!$this->deleted && !$this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$numberOfLeaves = self::validNumberOfLeaves($dbconn, $numberOfLeaves);
			if($numberOfLeaves !== false){
				mysqli_query($dbconn, "UPDATE Survey SET NumberOfLeaves='$numberOfLeaves' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->numberOfLeaves = $numberOfLeaves;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setAverageLeafLength($averageLeafLength){
		if(!$this->deleted && !$this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$averageLeafLength = self::validAverageLeafLength($dbconn, $averageLeafLength);
			if($averageLeafLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET AverageLeafLength='$averageLeafLength' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->averageLeafLength = $averageLeafLength;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setHerbivoryScore($herbivoryScore){
		if(!$this->deleted && !$this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$herbivoryScore = self::validHerbivoryScore($dbconn, $herbivoryScore);
			if($herbivoryScore !== false){
				mysqli_query($dbconn, "UPDATE Survey SET HerbivoryScore='$herbivoryScore' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->herbivoryScore = $herbivoryScore;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setAverageNeedleLength($averageNeedleLength){
		if(!$this->deleted && $this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$averageNeedleLength = self::validAverageNeedleLength($dbconn, $averageNeedleLength);
			if($averageNeedleLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET AverageNeedleLength='$averageNeedleLength' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->averageNeedleLength = $averageNeedleLength;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setLinearBranchLength($linearBranchLength){
		if(!$this->deleted && $this->isConifer()){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$linearBranchLength = self::validLinearBranchLength($dbconn, $linearBranchLength);
			if($linearBranchLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET LinearBranchLength='$linearBranchLength' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->linearBranchLength = $linearBranchLength;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setConifer($averageNeedleLength, $linearBranchLength){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$averageNeedleLength = self::validAverageNeedleLength($dbconn, $averageNeedleLength);
			$linearBranchLength = self::validLinearBranchLength($dbconn, $linearBranchLength);
			if($averageNeedleLength !== false && $linearBranchLength !== false){
				mysqli_query($dbconn, "UPDATE Survey SET NumberOfLeaves='-1', AverageLeafLength='-1', HerbivoryScore='-1', AverageNeedleLength='$averageNeedleLength', LinearBranchLength='$linearBranchLength' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->numberOfLeaves = -1;
				$this->averageLeafLength = -1;
				$this->herbivoryScore = -1;
				$this->averageNeedleLength = $averageNeedleLength;
				$this->linearBranchLength = $linearBranchLength;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setNonConifer($numberOfLeaves, $averageLeafLength, $herbivoryScore){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$numberOfLeaves = self::validNumberOfLeaves($dbconn, $numberOfLeaves);
			$averageLeafLength = self::validAverageLeafLength($dbconn, $averageLeafLength);
			$herbivoryScore = self::validHerbivoryScore($dbconn, $herbivoryScore);
			if($numberOfLeaves !== false && $averageLeafLength !== false && $herbivoryScore !== false){
				mysqli_query($dbconn, "UPDATE Survey SET NumberOfLeaves='$numberOfLeaves', AverageLeafLength='$averageLeafLength', HerbivoryScore='$herbivoryScore', AverageNeedleLength='-1', LinearBranchLength='-1' WHERE ID='" . $this->id . "'");
				mysqli_close($dbconn);
				$this->numberOfLeaves = $numberOfLeaves;
				$this->averageLeafLength = $averageLeafLength;
				$this->herbivoryScore = $herbivoryScore;
				$this->averageNeedleLength = -1;
				$this->linearBranchLength = -1;
				return true;
			}
			mysqli_close($dbconn);
		}
		return false;
	}
	
	public function setReviewedAndApproved($reviewedAndApproved, $qccomment = ""){
		if(!$this->deleted){
			$dbconn = (new Keychain)->getDatabaseConnection();
			$reviewedAndApproved = filter_var($reviewedAndApproved, FILTER_VALIDATE_INT);
			if ($reviewedAndApproved ===0 or $reviewedAndApproved>0) { //either update to 0 or update to something >0 , null and "" filtered out
				if (empty($qccomment)) {
				  //only update ReviewedAndApproved	 
				  mysqli_query($dbconn, "UPDATE Survey SET `ReviewedAndApproved`='$reviewedAndApproved' WHERE ID='" . $this->id . "'");	
				} else {
				  mysqli_query($dbconn, "UPDATE Survey SET `ReviewedAndApproved`='$reviewedAndApproved', `QCComment`='" . 
				  mysqli_real_escape_string($dbconn, $qccomment)
					. "' WHERE ID='" . $this->id . "'");
				}
				mysqli_close($dbconn);
				$this->reviewedAndApproved = $reviewedAndApproved;
				return true;
			}
		}
		return false;
	}
	
	
//REMOVER
	public function permanentDelete()
	{
		if(!$this->deleted)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			$arthropodSightings = ArthropodSighting::findArthropodSightingsBySurvey($this);
			for($i = 0; $i < count($arthropodSightings); $i++){
				$arthropodSightings[$i]->permanentDelete();
			}
			mysqli_query($dbconn, "DELETE FROM `Survey` WHERE `ID`='" . $this->id . "'");
			$this->deleted = true;
			mysqli_close($dbconn);
			return true;
		}
	}
	
	public static function permanentDeleteByIDs($ids)
	{
		if(is_array($ids) && count($ids) > 0)
		{
			$dbconn = (new Keychain)->getDatabaseConnection();
			mysqli_query($dbconn, "DELETE FROM `Survey` WHERE `ID` IN ('" . implode("', '", $ids) . "')");
			mysqli_query($dbconn, "DELETE FROM `ArthropodSighting` WHERE `SurveyFK` IN ('" . implode("', '", $ids) . "')");
			mysqli_close($dbconn);
			return true;
		}
	}
	
	public static function permanentDeleteAllLooseEnds(){
		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT `Survey`.`ID` FROM `Survey` LEFT JOIN `Plant` ON `Survey`.`PlantFK`=`Plant`.`ID` WHERE `Plant`.`ID` IS NULL");
		mysqli_close($dbconn);
		$idsToDelete = array();
		while($row = mysqli_fetch_assoc($query)){
			$idsToDelete[] = $row["ID"];
		}
		self::permanentDeleteByIDs($idsToDelete);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//validity ensurance
	public static function validObserver($dbconn, $observer, $plant){
		if(!is_object($plant) || get_class($plant) != "Plant" || !$plant->getSite()->validateUser($observer, "")){
			return false;
		}
		return $observer;
	}
	
	public static function validPlant($dbconn, $plant){
		if(!is_object($plant) || get_class($plant) != "Plant"){
			return false;
		}
		return $plant;
	}
	
	public static function validLocalDate($dbconn, $localDate){
		$localDate = rawurldecode($localDate);
		if(strlen($localDate) == 10){
			$year = intval(substr($localDate, 0, 4));
			$month = intval(substr($localDate, 5, 2));
			$day = intval(substr($localDate, 8, 2));
			if($year >= 1980 && $year <= 2200 && checkdate($month, $day, $year)){
				if($month < 10){$month = "0" . $month;}
				if($day < 10){$day = "0" . $day;}
				return $year . "-" . $month . "-" . $day;
			}
		}
		return false;
	}
	
	public static function validLocalTime($dbconn, $localTime){
		$localTime = rawurldecode($localTime);
		if(strlen($localTime) == 8){
			$hours = intval(substr($localTime, 0, 2));
			$minutes = intval(substr($localTime, 3, 2));
			$seconds = intval(substr($localTime, 6, 2));
			if($hours >= 0 && $hours <=23 && $minutes >=0 && $minutes <= 59 && $seconds == 0){
				if($hours < 10){$hours = "0" . $hours;}
				if($minutes < 10){$minutes = "0" . $minutes;}
				return ((string)$hours) . ":" . ((string)$minutes) . ":00";
			}
		}
		return false;
	}
	
	public static function validObservationMethod($dbconn, $observationMethod){
		if($observationMethod != "Visual" && $observationMethod != "Beat sheet"){
			return false;
		}
		return $observationMethod;
	}
	
	public static function validNotes($dbconn, $notes){
		$notes = mysqli_real_escape_string($dbconn, htmlentities(rawurldecode($notes)));
		return $notes;
	}
	
	public static function validPlantSpecies($dbconn, $plantSpecies, $plant){
		if(self::validPlant($dbconn, $plant) !== false){
			$officialPlantSpecies = $plant->getSpecies();
			if($officialPlantSpecies != "N/A"){
				return $officialPlantSpecies;
			}
		}
		
		$plantSpecies = rawurldecode($plantSpecies);
		if(preg_replace('/\s+/', '', $plantSpecies) == ""){
			return "N/A";
		}
		
		$plantSpecies = trim($plantSpecies);
		$plantSpeciesList = array(array("acacia spp.", "Acacia spp."), array("maple spp.", "Acer spp."), array("buckeye spp.", "Aesculus spp."), array("ailanthus spp.", "Ailanthus spp."), array("alder spp.", "Alnus spp."), array("madrone spp.", "Arbutus spp."), array("birch spp.", "Betula spp."), array("boxwood spp.", "Buxus spp."), array("sweetshrub spp.", "Calycanthus spp."), array("camellia spp.", "Camellia spp."), array("hornbeam spp.", "Carpinus spp."), array("hickory spp.", "Carya spp."), array("chestnut spp.", "Castanea spp."), array("sheoak spp.", "Casuarina spp."), array("catalpa spp.", "Catalpa spp."), array("hackberry spp.", "Celtis spp."), array("fringetree spp.", "Chionanthus spp."), array("citrus spp.", "Citrus spp."), array("cleyera spp.", "Cleyera spp."), array("dogwood spp.", "Cornus spp."), array("hazelnut spp.", "Corylus spp."), array("hawthorn spp.", "Crataegus spp."), array("bush honeysuckle spp.", "Diervilla spp."), array("persimmon spp.", "Diospyros spp."), array("eucalyptus spp.", "Eucalyptus spp."), array("beech spp.", "Fagus spp."), array("fig spp.", "Ficus spp."), array("forsythia spp.", "Forsythia spp."), array("ash spp.", "Fraxinus spp."), array("gardenia spp.", "Gardenia spp."), array("huckleberry spp.", "Gaylussacia spp."), array("locust spp.", "Gleditsia spp."), array("silverbell spp.", "Halesia spp."), array("hydrangea spp.", "Hydrangea spp."), array("walnut spp.", "Juglans spp."), array("privet spp.", "Ligustrum spp."), array("spicebush spp.", "Lindera spp."), array("honeysuckle spp.", "Lonicera spp."), array("magnolia spp.", "Magnolia spp."), array("apple spp.", "Malus spp."), array("mulberry spp.", "Morus spp."), array("tupelo spp.", "Nyssa spp."), array("tupelo spp.", "Nyssa spp."), array("hophornbeam spp.", "Ostrya spp."), array("bay spp.", "Persea spp."), array("sycamore spp.", "Platanus spp."), array("sycamore spp.", "Platanus spp."), array("NA spp.", "Podocarpus spp."), array("cottonwood and poplar spp.", "Populus spp."), array("mesquite spp.", "Prosopis spp."), array("cherry spp.", "Prunus spp."), array("cherry and plum spp.", "Prunus spp."), array("pear spp.", "Pyrus spp."), array("oak spp.", "Quercus spp."), array("buckthorn spp.", "Rhamnus spp."), array("azalea, rhododendron spp.", "Rhododendron spp."), array("sumac spp.", "Rhus spp."), array("rose spp.", "Rosa spp."), array("royal palm spp.", "Roystonea spp."), array("brambles spp.", "Rubus spp."), array("willow spp.", "Salix spp."), array("elderberry spp.", "Sambucus spp."), array("sassafras spp.", "Sassafras spp."), array("mountain-ash spp.", "Sorbus spp."), array("stewartia spp.", "Stewartia spp."), array("lilac spp.", "Syringa spp."), array("saltcedar spp.", "Tamarix spp."), array("basswood spp.", "Tilia spp."), array("torreya spp.", "Torreya spp."), array("elm spp.", "Ulmus spp."), array("blueberry spp.", "Vaccinium spp."), array("viburnum spp.", "Viburnum spp."), array("wisteria spp.", "Wisteria spp."), array("sweet acacia", "Acacia farnesiana"), array("catclaw acacia", "Acacia greggii"), array("trident maple", "Acer buergerianum"), array("hedge maple", "Acer campestre"), array("horned maple", "Acer diabolicum"), array("florida maple", "Acer floridanum"), array("amur maple", "Acer ginnala"), array("rocky mountain maple", "Acer glabrum"), array("bigtooth maple", "Acer grandidentatum"), array("chalk maple", "Acer leucoderme"), array("bigleaf maple", "Acer macrophyllum"), array("boxelder", "Acer negundo"), array("black maple", "Acer nigrum"), array("japanese maple", "Acer palmatum"), array("striped maple", "Acer pensylvanicum"), array("norway maple", "Acer platanoides"), array("red maple", "Acer rubrum"), array("silver maple", "Acer saccharinum"), array("sugar maple", "Acer saccharum"), array("mountain maple", "Acer spicatum"), array("freeman maple", "Acer X freemanii"), array("everglades palm", "Acoelorraphe wrightii"), array("california buckeye", "Aesculus californica"), array("yellow buckeye", "Aesculus flava"), array("ohio buckeye", "Aesculus glabra"), array("horse chestnut", "Aesculus hippocastanum"), array("bottlebrush buckeye", "Aesculus parviflora"), array("red buckeye", "Aesculus pavia"), array("painted buckeye", "Aesculus sylvatica"), array("yellow buckeye", "Aesculus flava"), array("tree of heaven", "Ailanthus altissima"), array("mimosa", "Albizia julibrissin"), array("european alder", "Alnus glutinosa"), array("arizona alder", "Alnus oblongifolia"), array("white alder", "Alnus rhombifolia"), array("red alder", "Alnus rubra"), array("hazel alder", "Alnus serrulata"), array("grey alder", "Alnus incana"), array("serviceberry", "Amelanchier"), array("common serviceberry", "Amelanchier arborea"), array("allegheny serviceberry", "Amelanchier laevis"), array("roundleaf serviceberry", "Amelanchier sanguinea"), array("sea torchwood", "Amyris elemifera"), array("pond-apple  ", "Annona glabra"), array("arizona madrone", "Arbutus arizonica"), array("pacific madrone", "Arbutus menziesii"), array("texas madrone", "Arbutus xalapensis"), array("dwarf pawpaw", "Asimina pygmea"), array("pawpaw", "Asimina triloba"), array("black-mangrove", "Avicennia germinans"), array("eastern baccharis", "Baccharis halimifolia"), array("yellow birch", "Betula alleghaniensis"), array("sweet birch", "Betula lenta"), array("white birch", "Betula minor"), array("river birch", "Betula nigra"), array("water birch", "Betula occidentalis"), array("paper birch", "Betula papyrifera"), array("gray birch", "Betula populifolia"), array("virginia roundleaf birch", "Betula uber"), array("northwestern paper birch", "Betula x utahensis"), array("gumbo limbo  ", "Bursera simaruba"), array("american beautyberry", "Callicarpa americana"), array("incense-cedar", "Calocedrus decurrens"), array("eastern sweetshrub", "Calycanthus floridus"), array("american hornbeam", "Carpinus caroliniana"), array("mockernut hickory", "Carya alba"), array("water hickory", "Carya aquatica"), array("southern shagbark hickory", "Carya carolinae-septentrionalis"), array("bitternut hickory", "Carya cordiformis"), array("scrub hickory", "Carya floridana"), array("pignut hickory", "Carya glabra"), array("pecan", "Carya illinoinensis"), array("shellbark hickory", "Carya laciniosa"), array("nutmeg hickory", "Carya myristiciformis"), array("red hickory", "Carya ovalis"), array("shagbark hickory", "Carya ovata"), array("sand hickory", "Carya pallida"), array("black hickory", "Carya texana"), array("mockernut hickory", "Carya tomentosa"), array("american chestnut", "Castanea dentata"), array("chinese chestnut", "Castanea mollissima"), array("chinquapin", "Castanea pumila"), array("gray sheoak", "Casuarina glauca"), array("belah", "Casuarina lepidophloia"), array("southern catalpa", "Catalpa bignonioides"), array("northern catalpa", "Catalpa speciosa"), array("oriental bittersweet", "Celastrus orbiculatus"), array("sugarberry", "Celtis laevigata"), array("western hackberry", "Celtis occidentalis"), array("common buttonbush", "Cephalanthus occidentalis"), array("eastern redbud", "Cercis canadensis"), array("curlleaf mountain-mahogany", "Cercocarpus ledifolius"), array("chinese quince", "Chaenomeles sinensis"), array("fragrant wintersweet", "Chimonanthus praecox"), array("giant chinkapin", "Chrysolepis chrysophylla"), array("camphortree", "Cinnamomum camphora"), array("florida fiddlewood", "Citharexylum fruticosum"), array("kentucky yellowwood", "Cladrastis kentukea"), array("tietongue", "Coccoloba diversifolia"), array("florida silver palm", "Coccothrinax argentata"), array("coconut palm  ", "Cocos nucifera"), array("soldierwood", "Colubrina elliptica"), array("bluewood", "Condalia hookeri"), array("buttonwood-mangrove", "Conocarpus erectus"), array("anacahuita", "Cordia boissieri"), array("largeleaf geigertree", "Cordia sebestena"), array("alternate-leaf dogwood", "Cornus alternifolia"), array("silky dogwood", "Cornus amomum"), array("roughleaf dogwood", "Cornus drummondii"), array("flowering dogwood", "Cornus florida"), array("stiff dogwood", "Cornus foemina"), array("kousa dogwood", "Cornus kousa"), array("big-leaf dogwood", "Cornus macrophylla"), array("cornelian cherry", "Cornus mas"), array("pacific dogwood", "Cornus nuttallii"), array("redosier dogwood", "Cornus sericea"), array("grey dogwood", "Cornus racemosa"), array("american hazelnut", "Corylus americana"), array("beaked hazel", "Corylus cornuta"), array("smoketree", "Cotinus obovatus"), array("brainerd's hawthorn", "Crataegus brainerdii"), array("pear hawthorn", "Crataegus calpodendron"), array("fireberry hawthorn", "Crataegus chrysocarpa"), array("cockspur hawthorn", "Crataegus crus-galli"), array("broadleaf hawthorn", "Crataegus dilatata"), array("fanleaf hawthorn", "Crataegus flabellata"), array("downy hawthorn", "Crataegus mollis"), array("oneseed hawthorn", "Crataegus monogyna"), array("scarlet hawthorn", "Crataegus pedicellata"), array("washington hawthorn", "Crataegus phaenopyrum"), array("fleshy hawthorn", "Crataegus succulenta"), array("dwarf hawthorn", "Crataegus uniflora"), array("broadleaf hawthorn", "Crataegus coccinioides"), array("carrotwood", "Cupaniopsis anacardioides"), array("swamp titi", "Cyrilla racemiflora"), array("texas persimmon", "Diospyros texana"), array("common persimmon", "Diospyros virginiana"), array("blackbead ebony", "Ebenopsis ebano"), array("oriental paperbush", "Edgeworthia chrysantha"), array("anacua knockaway", "Ehretia anacua"), array("russian olive", "Elaeagnus angustifolia"), array("autumn olive", "Elaeagnus umbellata"), array("river redgum", "Eucalyptus camaldulensis"), array("tasmanian bluegum", "Eucalyptus globulus"), array("grand eucalyptus", "Eucalyptus grandis"), array("swampmahogany", "Eucalyptus robusta"), array("red stopper", "Eugenia rhombea"), array("burningbush", "Euonymus alatus"), array("european spindletree", "Euonymus europaeus"), array("yeddo euonymous", "Euonymus hamiltonianus"), array("butterbough", "Exothea paniculata"), array("american beech", "Fagus grandifolia"), array("european beech", "Fagus sylvatica"), array("japanese knotweed", "Fallopia japonica"), array("speckled japanese aralla", "Fatsia japonica"), array("florida strangler fig", "Ficus aurea"), array("wild banyantree", "Ficus citrifolia"), array("florida swampprivet", "Forestiera segregata"), array("white ash", "Fraxinus americana"), array("berlandier ash", "Fraxinus berlandieriana"), array("carolina ash", "Fraxinus caroliniana"), array("oregon ash", "Fraxinus latifolia"), array("black ash", "Fraxinus nigra"), array("green ash", "Fraxinus pennsylvanica"), array("pumpkin ash", "Fraxinus profunda"), array("blue ash", "Fraxinus quadrangulata"), array("texas ash", "Fraxinus texensis"), array("velvet ash", "Fraxinus velutina"), array("black huckleberry", "Gaylussacia baccata"), array("ginkgo", "Ginkgo biloba"), array("waterlocust", "Gleditsia aquatica"), array("honeylocust", "Gleditsia triacanthos"), array("loblolly-bay", "Gordonia lasianthus"), array("beeftree", "Guapira discolor"), array("kentucky coffeetree", "Gymnocladus dioicus"), array("carolina silverbell", "Halesia carolina"), array("two-wing silverbell", "Halesia diptera"), array("little silverbell", "Halesia parviflora"), array("witch-hazel", "Hamamelis virginiana"), array("ozark witch hazel", "Hamamelis vernalis"), array("rose of sharon", "Hibiscus syriacus"), array("manchineel", "Hippomane mancinella"), array("raisin", "Hovenia dulcis"), array("oakleaf hydrangea", "Hydrangea quercifolia"), array("possumhaw", "Ilex decidua"), array("inkberry", "Ilex glabra"), array("mountain holly", "Ilex montana"), array("catberry", "Ilex mucronata"), array("american holly", "Ilex opaca"), array("winterberry", "Ilex verticillata"), array("yaupon", "Ilex vomitoria"), array("virginia sweetspire", "Itea virginica"), array("southern california black walnut", "Juglans californica"), array("butternut", "Juglans cinerea"), array("northern california black walnut", "Juglans hindsii"), array("arizona walnut", "Juglans major"), array("texas walnut", "Juglans microcarpa"), array("black walnut", "Juglans nigra"), array("mountain laurel", "Kalmia latifolia"), array("castor aralia", "Kalopanax septemlobus"), array("flamegold", "Koelreuteria elegans"), array("golden rain tree", "Koelreuteria paniculata"), array("crapemyrtle", "Lagerstroemia indica"), array("white-mangrove", "Laguncularia racemosa"), array("great leucaene", "Leucaena pulverulenta"), array("coastal doghobble", "Leucothoe axillaris"), array("japanese privet", "Ligustrum japonicum"), array("waxyleaf privet", "Ligustrum quihoui"), array("northern spicebush", "Lindera benzoin"), array("sweetgum", "Liquidambar styraciflua"), array("tuliptree", "Liriodendron tulipifera"), array("tanoak", "Lithocarpus densiflorus"), array("pink honeysuckle", "Lonicera hispidula"), array("japanese honeysuckle", "Lonicera japonica"), array("amur honeysuckle", "Lonicera maackii"), array("tatarian honeysuckle", "Lonicera tatarica"), array("false tamarind", "Lysiloma latisiliquum"), array("amur maackia", "Maackia amurensis"), array("osage-orange", "Maclura pomifera"), array("cucumbertree", "Magnolia acuminata"), array("fraser's magnolia", "Magnolia fraseri"), array("southern magnolia", "Magnolia grandiflora"), array("bigleaf magnolia", "Magnolia macrophylla"), array("pyramid magnolia", "Magnolia pyramidata"), array("umbrella magnolia", "Magnolia tripetala"), array("sweetbay", "Magnolia virginiana"), array("loebner magnolia", "Magnolia X loebneri"), array("southern crab apple", "Malus angustifolia"), array("siberian crab apple", "Malus baccata"), array("sweet crab apple", "Malus coronaria"), array("oregon crab apple", "Malus fusca"), array("prairie crab apple", "Malus ioensis"), array("toringa crab apple", "Malus sieboldii"), array("mango", "Mangifera indica"), array("melaleuca", "Melaleuca quinquenervia"), array("chinaberry", "Melia azedarach"), array("florida poisontree", "Metopium toxiferum"), array("southern bayberry", "Morella caroliniensis"), array("wax myrtle", "Morella cerifera"), array("red bay", "Morella rubra"), array("white mulberry", "Morus alba"), array("texas mulberry", "Morus microphylla"), array("black mulberry", "Morus nigra"), array("red mulberry", "Morus rubra"), array("heavenly bamboo", "Nandina domestica"), array("water tupelo", "Nyssa aquatica"), array("swamp tupelo", "Nyssa biflora"), array("ogeechee tupelo", "Nyssa ogeche"), array("blackgum", "Nyssa sylvatica"), array("desert ironwood", "Olneya tesota"), array("eastern hophornbeam", "Ostrya virginiana"), array("sourwood", "Oxydendrum arboreum"), array("persian ironwood", "Parrotia persica"), array("paulownia  empress-tree", "Paulownia tomentosa"), array("avocado", "Persea americana"), array("redbay", "Persea borbonia"), array("common ninebark", "Physocarpus opulifolius"), array("fishpoison tree", "Piscidia piscipula"), array("water-elm  planertree", "Planera aquatica"), array("american sycamore", "Platanus occidentalis"), array("california sycamore", "Platanus racemosa"), array("arizona sycamore", "Platanus wrightii"), array("silver poplar", "Populus alba"), array("narrowleaf cottonwood", "Populus angustifolia"), array("balsam poplar", "Populus balsamifera"), array("eastern cottonwood", "Populus deltoides"), array("fremont cottonwood", "Populus fremontii"), array("bigtooth aspen", "Populus grandidentata"), array("swamp cottonwood", "Populus heterophylla"), array("lombardy poplar", "Populus nigra"), array("quaking aspen", "Populus tremuloides"), array("honey mesquite ", "Prosopis glandulosa"), array("screwbean mesquite", "Prosopis pubescens"), array("velvet mesquite", "Prosopis velutina"), array("allegheny plum", "Prunus alleghaniensis"), array("american plum", "Prunus americana"), array("chickasaw plum", "Prunus angustifolia"), array("sweet cherry", "Prunus avium"), array("sour cherry", "Prunus cerasus"), array("european plum", "Prunus domestica"), array("bitter cherry", "Prunus emarginata"), array("cherry laurel", "Prunus laurocerasus"), array("mahaleb cherry", "Prunus mahaleb"), array("beach plum", "Prunus maritima"), array("japanese apricot", "Prunus mume"), array("canada plum", "Prunus nigra"), array("pin cherry", "Prunus pensylvanica"), array("peach", "Prunus persica"), array("black cherry", "Prunus serotina"), array("chokecherry", "Prunus virginiana"), array("kwanzan cherry", "Prunus serrulata"), array("weeping cherry", "Prunus subhirtella"), array("wafer ash", "Ptelea trifoliata"), array("buffalo nut", "Pyrularia pubera"), array("callery pear", "Pyrus calleryana"), array("california live oak", "Quercus agrifolia"), array("white oak", "Quercus alba"), array("arizona white oak", "Quercus arizonica"), array("swamp white oak", "Quercus bicolor"), array("buckley oak", "Quercus buckleyi"), array("canyon live oak", "Quercus chrysolepis"), array("scarlet oak", "Quercus coccinea"), array("blue oak", "Quercus douglasii"), array("northern pin oak", "Quercus ellipsoidalis"), array("emory oak", "Quercus emoryi"), array("engelmann oak", "Quercus engelmannii"), array("southern red oak", "Quercus falcata"), array("gambel oak", "Quercus gambelii"), array("oregon white oak", "Quercus garryana"), array("ring-cup oak", "Quercus glauca"), array("chisos oak", "Quercus graciliformis"), array("graves oak", "Quercus gravesii"), array("gray oak", "Quercus grisea"), array("silverleaf oak", "Quercus hypoleucoides"), array("bear oak", "Quercus ilicifolia"), array("shingle oak", "Quercus imbricaria"), array("bluejack oak", "Quercus incana"), array("california black oak", "Quercus kelloggii"), array("lacey oak", "Quercus laceyi"), array("turkey oak", "Quercus laevis"), array("laurel oak", "Quercus laurifolia"), array("california white oak", "Quercus lobata"), array("overcup oak", "Quercus lyrata"), array("bur oak", "Quercus macrocarpa"), array("dwarf post oak", "Quercus margarettiae"), array("blackjack oak", "Quercus marilandica"), array("swamp chestnut oak", "Quercus michauxii"), array("dwarf live oak", "Quercus minima"), array("chestnut oak", "Quercus montana"), array("chinkapin oak", "Quercus muehlenbergii"), array("water oak", "Quercus nigra"), array("mexican blue oak", "Quercus oblongifolia"), array("oglethorpe oak", "Quercus oglethorpensis"), array("cherrybark oak", "Quercus pagoda"), array("pin oak", "Quercus palustris"), array("willow oak", "Quercus phellos"), array("mexican white oak", "Quercus polymorpha"), array("dwarf chinkapin oak", "Quercus prinoides"), array("english oak", "Quercus robur"), array("northern red oak", "Quercus rubra"), array("netleaf oak", "Quercus rugosa"), array("shumard oak", "Quercus shumardii"), array("delta post oak", "Quercus similis"), array("durand oak", "Quercus sinuata"), array("post oak", "Quercus stellata"), array("texas red oak", "Quercus texana"), array("black oak", "Quercus velutina"), array("live oak", "Quercus virginiana"), array("interior live oak", "Quercus wislizeni"), array("common buckthorn", "Rhamnus cathartica"), array("frangula alnus", "Rhamnus frangula"), array("american mangrove", "Rhizophora mangle"), array("dwarf azalea", "Rhododendron atlanticum"), array("florida azalea", "Rhododendron austrinum"), array("mountain azalea", "Rhododendron canescens"), array("catawba rhododendron", "Rhododendron catawbiense"), array("piedmont azalea", "Rhododendron flammeum"), array("great rhododendron", "Rhododendron maximum"), array("plumleaf azalea", "Rhododendron prunifolium"), array("jetbead", "Rhodotypos scandens"), array("winged sumac", "Rhus copallinum"), array("smooth sumac", "Rhus glabra"), array("staghorn sumac", "Rhus typhina"), array("new mexico locust", "Robinia neomexicana"), array("black locust", "Robinia pseudoacacia"), array("multiflora rose", "Rosa multiflora"), array("wineberry", "Rubus phoenicolasius"), array("mexican palmetto", "Sabal mexicana"), array("cabbage palmetto", "Sabal palmetto"), array("white willow", "Salix alba"), array("peachleaf willow", "Salix amygdaloides"), array("weeping willow", "Salix babylonica"), array("bebb willow", "Salix bebbiana"), array("bonpland willow", "Salix bonplandiana"), array("coastal plain willow", "Salix caroliniana"), array("black willow", "Salix nigra"), array("balsam willow", "Salix pyrifolia"), array("scoulers willow", "Salix scouleriana"), array("red elderberry", "Sambucus racemosa"), array("western soapberry", "Sapindus saponaria"), array("sassafras", "Sassafras albidum"), array("octopus tree", "Schefflera actinophylla"), array("false mastic", "Sideroxylon foetidissimum"), array("chittamwood", "Sideroxylon lanuginosum"), array("white bully", "Sideroxylon salicifolium"), array("paradisetree", "Simarouba glauca"), array("texas sophora", "Sophora affinis"), array("american mountain-ash", "Sorbus americana"), array("european mountain-ash", "Sorbus aucuparia"), array("northern mountain-ash", "Sorbus decora"), array("american bladdernut", "Staphylea trifolia"), array("japanese snowbell", "Styrax japonicus"), array("west indian mahogany", "Swietenia mahagoni"), array("sweetleaf", "Symplocos tinctoria"), array("japanese tree lilac", "Syringa reticulata"), array("java plum", "Syzygium cumini"), array("tamarind", "Tamarindus indica"), array("key thatch palm", "Thrinax morrisii"), array("florida thatch palm", "Thrinax radiata"), array("american basswood", "Tilia americana"), array("littleleaf linden", "Tilia cordata"), array("common lime", "Tilia X europaea"), array("california torreya", "Torreya californica"), array("florida torreya", "Torreya taxifolia"), array("chinese tallowtree", "Triadica sebifera"), array("winged elm", "Ulmus alata"), array("american elm", "Ulmus americana"), array("cedar elm", "Ulmus crassifolia"), array("siberian elm", "Ulmus pumila"), array("slippery elm", "Ulmus rubra"), array("september elm", "Ulmus serotina"), array("rock elm", "Ulmus thomasii"), array("california laurel", "Umbellularia californica"), array("highbush blueberry", "Vaccinium corymbosum"), array("deerberry", "Vaccinium stamineum"), array("tungoil tree", "Vernicia fordii"), array("viburnum", "Viburnum"), array("mapleleaf viburnum", "Viburnum acerifolium"), array("southern arrowwood", "Viburnum dentatum"), array("linden arrowwood", "Viburnum dilatatum"), array("nannyberry", "Viburnum lentago"), array("possumhaw viburnum", "Viburnum nudum"), array("european cranberrybush", "Viburnum opulus"), array("japanese snowball", "Viburnum plicatum"), array("blackhaw", "Viburnum prunifolium"), array("rusty blackhaw", "Viburnum rufidulum"), array("canyon grape", "Vitis arizonica"), array("joshua tree", "Yucca brevifolia"), array("japanese zelkova", "Zelkova serrata"), array("fir spp.", "Abies spp."), array("white-cedar spp.", "Chamaecyparis spp."), array("cypress spp.", "Cupressus spp."), array("redcedar/juniper spp.", "Juniperus spp."), array("larch spp.", "Larix spp."), array("spruce spp.", "Picea spp."), array("pine spp.", "Pinus spp."), array("douglas-fir spp.", "Pseudotsuga spp."), array("baldcypress spp.", "Taxodium spp."), array("yew spp.", "Taxus spp."), array("thuja spp.", "Thuja spp."), array("hemlock spp.", "Tsuga spp."), array("pacific silver fir", "Abies amabilis"), array("balsam fir", "Abies balsamea"), array("bristlecone fir", "Abies bracteata"), array("white fir", "Abies concolor"), array("fraser fir", "Abies fraseri"), array("grand fir", "Abies grandis"), array("subalpine fir", "Abies lasiocarpa"), array("california red fir", "Abies magnifica"), array("noble fir", "Abies procera"), array("shasta red fir", "Abies shastensis"), array("port-orford-cedar", "Chamaecyparis lawsoniana"), array("alaska yellow-cedar", "Chamaecyparis nootkatensis"), array("atlantic white-cedar", "Chamaecyparis thyoides"), array("arizona cypress", "Cupressus arizonica"), array("modoc cypress", "Cupressus bakeri"), array("tecate cypress", "Cupressus forbesii"), array("macnabs cypress", "Cupressus macnabiana"), array("monterey cypress", "Cupressus macrocarpa"), array("sargents cypress", "Cupressus sargentii"), array("ashe juniper", "Juniperus ashei"), array("california juniper", "Juniperus californica"), array("redberry juniper", "Juniperus coahuilensis"), array("alligator juniper", "Juniperus deppeana"), array("drooping juniper", "Juniperus flaccida"), array("oneseed juniper", "Juniperus monosperma"), array("western juniper", "Juniperus occidentalis"), array("utah juniper", "Juniperus osteosperma"), array("pinchot juniper", "Juniperus pinchotii"), array("rocky mountain juniper", "Juniperus scopulorum"), array("eastern redcedar", "Juniperus virginiana"), array("tamarack", "Larix laricina"), array("subalpine larch", "Larix lyallii"), array("western larch", "Larix occidentalis"), array("norway spruce", "Picea abies"), array("brewer spruce", "Picea breweriana"), array("engelmann spruce", "Picea engelmannii"), array("white spruce", "Picea glauca"), array("black spruce", "Picea mariana"), array("blue spruce", "Picea pungens"), array("red spruce", "Picea rubens"), array("sitka spruce", "Picea sitchensis"), array("whitebark pine", "Pinus albicaulis"), array("bristlecone pine", "Pinus aristata"), array("arizona pine", "Pinus arizonica"), array("knobcone pine", "Pinus attenuata"), array("foxtail pine", "Pinus balfouriana"), array("jack pine", "Pinus banksiana"), array("mexican pinyon pine", "Pinus cembroides"), array("sand pine", "Pinus clausa"), array("lodgepole pine", "Pinus contorta"), array("coulter pine", "Pinus coulteri"), array("border pinyon", "Pinus discolor"), array("shortleaf pine", "Pinus echinata"), array("common pinyon", "Pinus edulis"), array("slash pine", "Pinus elliottii"), array("apache pine", "Pinus engelmannii"), array("limber pine", "Pinus flexilis"), array("spruce pine", "Pinus glabra"), array("jeffrey pine", "Pinus jeffreyi"), array("sugar pine", "Pinus lambertiana"), array("chihuahua pine", "Pinus leiophylla"), array("great basin bristlecone pine", "Pinus longaeva"), array("singleleaf pinyon", "Pinus monophylla"), array("western white pine", "Pinus monticola"), array("bishop pine", "Pinus muricata"), array("austrian pine", "Pinus nigra"), array("longleaf pine", "Pinus palustris"), array("ponderosa pine", "Pinus ponderosa"), array("table mountain pine", "Pinus pungens"), array("four-leaf or parry pinyon pine", "Pinus quadrifolia"), array("monterey pine", "Pinus radiata"), array("papershell pinyon pine", "Pinus remota"), array("red pine", "Pinus resinosa"), array("pitch pine", "Pinus rigida"), array("gray or california foothill pine", "Pinus sabiniana"), array("pond pine", "Pinus serotina"), array("southwestern white pine ", "Pinus strobiformis"), array("eastern white pine", "Pinus strobus"), array("scotch pine", "Pinus sylvestris"), array("loblolly pine", "Pinus taeda"), array("torrey pine", "Pinus torreyana"), array("virginia pine", "Pinus virginiana"), array("washoe pine", "Pinus washoensis"), array("bigcone douglas-fir", "Pseudotsuga macrocarpa"), array("douglas-fir", "Pseudotsuga menziesii"), array("redwood", "Sequoia sempervirens"), array("giant sequoia", "Sequoiadendron giganteum"), array("pondcypress", "Taxodium ascendens"), array("bald cypress", "Taxodium distichum"), array("montezuma baldcypress", "Taxodium mucronatum"), array("pacific yew", "Taxus brevifolia"), array("florida yew", "Taxus floridana"), array("eastern white cedar", "Thuja occidentalis"), array("western redcedar", "Thuja plicata"), array("eastern hemlock", "Tsuga canadensis"), array("carolina hemlock", "Tsuga caroliniana"), array("western hemlock", "Tsuga heterophylla"), array("mountain hemlock", "Tsuga mertensiana"));
		for($i = 0; $i < count($plantSpeciesList); $i++){
			$plantSpeciesList[$i][0] = trim(preg_replace('!\s+!', ' ', $plantSpeciesList[$i][0]));
			$plantSpeciesList[$i][1] = trim(preg_replace('!\s+!', ' ', $plantSpeciesList[$i][1]));
			if(strtolower($plantSpecies) == strtolower($plantSpeciesList[$i][1]) || strtolower($plantSpecies) == strtolower($plantSpeciesList[$i][0])){
				return ucfirst(strtolower($plantSpeciesList[$i][0]));
			}
		}
		return ucfirst(strtolower(trim(preg_replace('!\s+!', ' ', $plantSpecies))));
	}
	
	public static function validNumberOfLeaves($dbconn, $numberOfLeaves){
		$numberOfLeaves = intval($numberOfLeaves);
		if($numberOfLeaves >= 1 && $numberOfLeaves <= 500){
			return $numberOfLeaves;
		}
		return false;
	}
	
	public static function validAverageLeafLength($dbconn, $averageLeafLength){
		$averageLeafLength = intval($averageLeafLength);
		if($averageLeafLength >= 1 && $averageLeafLength <= 60){
			return $averageLeafLength;
		}
		return false;
	}
	
	public static function validHerbivoryScore($dbconn, $herbivoryScore){
		$herbivoryScore = intval(preg_replace("/[^0-9]/", "", $herbivoryScore));
		if($herbivoryScore >= 0 && $herbivoryScore <= 4){
			return $herbivoryScore;
		}
		return false;
	}
	
	public static function validAverageNeedleLength($dbconn, $averageNeedleLength){
		$averageNeedleLength = intval($averageNeedleLength);
		if($averageNeedleLength >= 1 && $averageNeedleLength <= 60){
			return $averageNeedleLength;
		}
		return false;
	}
	
	public static function validLinearBranchLength($dbconn, $linearBranchLength){
		$linearBranchLength = intval($linearBranchLength);
		if($linearBranchLength >= 1 && $linearBranchLength <= 500){
			return $linearBranchLength;
		}
		return false;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

//FUNCTIONS
	public function addArthropodSighting($originalGroup, $length, $quantity, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $originalBeetleLarva){
		return ArthropodSighting::create($this, $originalGroup, $length, $quantity, $notes, $pupa, $hairy, $rolled, $tented, $originalSawfly, $originalBeetleLarva);
	}
}		
?>
