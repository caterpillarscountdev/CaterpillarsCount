<?php
	require_once('php/orm/resources/Keychain.php');
	
	$colHeaders = array("SiteName", 
		"SiteDescription", 
		"Latitude", 
		"Longitude", 
		"Region", 
		"LocalDate", 
		"LocalTime", 
		"SurveyLocationCode", 
		"Circle", 
		"Orientation", 
		"PlantSpeciesMarkedByObserver", 
		"OfficialPlantSpecies", 
		"PlantIsConiferous",
		"ObservationMethod", 
		"SurveyNotes", 
		"WetLeavesOrNeedles", 
		"NumberOfLeaves", 
		"AverageLeafLength", 
		"HerbivoryScore", 
		"AverageNeedleLength", 
		"LinearBranchLength", 
		"OriginalArthropodGroup", 
		"UpdatedArthropodGroup", 
		"ArthropodLength", 
		"ArthropodQuantity", 
		"ArthropodPhotoURL", 
		"ArthropodNotes", 
		"IsCaterpillarAndIsHairy", 
		"IsCaterpillarAndIsInLeafRoll", 
		"IsCaterpillarAndIsInSilkTent", 
		"IsButterflyOrMothAndIsPupa", 
		"OriginalIsBeetleLarva", 
		"UpdatedIsBeetleLarva", 
		"OriginalIsSawflyLarva", 
		"UpdatedIsSawflyLarva");
	
	function redirect($url){
		$string = '<script type="text/javascript">';
		$string .= 'window.location = "' . $url . '"';
		$string .= '</script>';
		die($string);
	}

	function getArrayFromTable($siteID, $yearStart, $yearEnd, $arthropod){
		global $colHeaders;
		ini_set('memory_limit', '-1');
		$tableArray = array();

		$dbconn = (new Keychain)->getDatabaseConnection();
		$query = mysqli_query($dbconn, "SELECT Survey.ID, Survey.LocalDate, SUBSTR(Survey.LocalTime, 1, 5) AS `LocalTime`, Plant.Code AS SurveyLocationCode, Plant.Circle, Plant.Orientation, Survey.PlantSpecies AS PlantSpeciesMarkedByObserver, Plant.Species AS OfficialPlantSpecies, Plant.IsConifer AS PlantIsConiferous, Site.Name AS SiteName, Site.Description AS SiteDescription, Site.Latitude, Site.Longitude, Site.Region, ArthropodSighting.OriginalGroup AS OriginalArthropodGroup, ArthropodSighting.UpdatedGroup AS UpdatedArthropodGroup, ArthropodSighting.Length AS ArthropodLength, ArthropodSighting.Quantity AS ArthropodQuantity, IF(ArthropodSighting.PhotoURL='','',CONCAT('https://caterpillarscount.unc.edu/images/arthropods/', ArthropodSighting.PhotoURL)) AS ArthropodPhotoURL, ArthropodSighting.Notes AS ArthropodNotes, ArthropodSighting.Hairy AS IsCaterpillarAndIsHairy, ArthropodSighting.Rolled AS IsCaterpillarAndIsInLeafRoll, ArthropodSighting.Tented AS IsCaterpillarAndIsInSilkTent, ArthropodSighting.Pupa AS IsButterflyOrMothAndIsPupa, ArthropodSighting.OriginalBeetleLarva AS OriginalIsBeetleLarva, ArthropodSighting.UpdatedBeetleLarva AS UpdatedIsBeetleLarva, ArthropodSighting.OriginalSawfly AS OriginalIsSawflyLarva, ArthropodSighting.UpdatedSawfly AS UpdatedIsSawflyLarva, Survey.ObservationMethod, Survey.Notes AS SurveyNotes, Survey.WetLeaves AS WetLeavesOrNeedles, Survey.NumberOfLeaves, Survey.AverageLeafLength, Survey.HerbivoryScore, Survey.AverageNeedleLength, Survey.LinearBranchLength FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK=Site.ID WHERE Site.ID<>'2' AND Plant.SiteFK LIKE '$siteID' AND YEAR(Survey.LocalDate)>='$yearStart' AND YEAR(Survey.LocalDate)<='$yearEnd' AND ArthropodSighting.UpdatedGroup LIKE '$arthropod' ORDER BY Survey.LocalDate DESC, Survey.LocalTime DESC");
		
		//ROWS
		$surveyIDsWithSightings = array();
		while ($row = mysqli_fetch_assoc($query)){
			$rowArray = array();
			for($i = 0; $i < count($colHeaders); $i++){
				$rowArray[] = ($row[$colHeaders[$i]] == "bee" && ($colHeaders[$i] == "OriginalArthropodGroup" || $colHeaders[$i] == "UpdatedArthropodGroup")) ? "bee/wasp/sawfly" : $row[$colHeaders[$i]];
			}
			$tableArray[] = $rowArray;
			$surveyIDsWithSightings[] = $row["ID"];
		}

		$query = mysqli_query($dbconn, "SELECT Survey.LocalDate, SUBSTR(Survey.LocalTime, 1, 5) AS `LocalTime`, Plant.Code AS SurveyLocationCode, Plant.Circle, Plant.Orientation, Survey.PlantSpecies AS PlantSpeciesMarkedByObserver, Plant.Species AS OfficialPlantSpecies, Plant.IsConifer AS PlantIsConiferous, Site.Name AS SiteName, Site.Description AS SiteDescription, Site.Latitude, Site.Longitude, Site.Region, Survey.ObservationMethod, Survey.Notes AS SurveyNotes, Survey.WetLeaves AS WetLeavesOrNeedles, Survey.NumberOfLeaves, Survey.AverageLeafLength, Survey.HerbivoryScore, Survey.AverageNeedleLength, Survey.LinearBranchLength FROM Survey JOIN Plant ON Survey.PlantFK=Plant.ID JOIN Site ON Plant.SiteFK=Site.ID WHERE Survey.ID NOT IN (" . join(", ", $surveyIDsWithSightings) . ") AND Site.ID<>'2' AND Plant.SiteFK LIKE '$siteID' AND YEAR(Survey.LocalDate)>='$yearStart' AND YEAR(Survey.LocalDate)<='$yearEnd'");
		mysqli_close($dbconn);
		while ($row = mysqli_fetch_assoc($query)){
			$rowArray = array();
			for($i = 0; $i < count($colHeaders); $i++){
				if(array_key_exists($colHeaders[$i], $row)){
					$rowArray[] = $row[$colHeaders[$i]];
				}
				else if($colHeaders[$i] == "OriginalArthropodGroup" || $colHeaders[$i] == "UpdatedArthropodGroup"){
					$rowArray[] = "none";
				}
				else{
					$rowArray[] = "";
				}
			}
			$tableArray[] = $rowArray;
		}
		return $tableArray;
	}

	function customSort($a, $b){
		$alphabeticalResult = strcmp($a[0], $b[0]);
		if($alphabeticalResult != 0){
			return $alphabeticalResult;
		}
		$aTime = date_create_from_format("Y-m-d H:i", $a[5] . " " . $a[6]);
		$aTime = $aTime->getTimestamp();
		$bTime = date_create_from_format("Y-m-d H:i", $b[5] . " " . $b[6]);
		$bTime = $bTime->getTimestamp();
		return $bTime - $aTime;
	}

	function generateRandomString($length) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
	function zipAndDownload($pathnames){
		$zip = new ZipArchive();
		$zipPathname = "CaterpillarsCountData_timestamp-" . time() . "_uniqueid-" . generateRandomString(5) . ".zip";
		
		if($zip->open($zipPathname, ZipArchive::CREATE) === true){
			for($i = 0; $i < count($pathnames); $i++){
				$zip->addFile($pathnames[$i]);
			}
			
			$zip->close();
		}
		
		if(file_exists($zipPathname)){
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="'.basename($zipPathname).'"');
			header('Content-Length: ' . filesize($zipPathname));
			//header("Pragma: no-cache");
			//header("Expires: 0"); 
			
			flush();
			readfile($zipPathname);
			unlink($zipPathname);
		}
	}

	if($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['download'])){
		$dbconn = (new Keychain)->getDatabaseConnection();
		
		$siteID = $_POST["siteID"];
    $siteName = "All sites.";
		if($siteID != "%"){
			$siteID = intval($siteID);
      
      $siteName = "";
      $query = mysqli_query($dbconn, "SELECT `Name` FROM `Site` WHERE `ID`='$siteID'");
      while ($row = mysqli_fetch_assoc($query)){
        $siteName .= $row["Name"] . ". ";
      }
      $siteName = trim($siteName);
      if($siteName == ""){
        $siteName = "[Site Name].";
      }
		}
		$yearStart = intval($_POST["yearStart"]);
		$yearEnd = intval($_POST["yearEnd"]);
		$arthropod = mysqli_real_escape_string($dbconn, htmlentities($_POST["arthropod"]));
		mysqli_close($dbconn);
		
		$tableArray = getArrayFromTable($siteID, $yearStart, $yearEnd, $arthropod);
		usort($tableArray, "customSort");
		array_unshift($tableArray, $colHeaders);
		ob_end_clean();
		
		
		$csvFilename = "CaterpillarsCountData";
		while(file_exists($csvFilename . ".csv")){
			$csvFilename .= "-" . generateRandomString(5);
		}
		$csvFilename .= ".csv";
		
		$fp = fopen($csvFilename, 'w');
		foreach ($tableArray as $line) fputcsv($fp, $line);
    fclose($fp);
    
	$txtFilename = "metadata";
	while(file_exists($txtFilename . ".txt")){
		$txtFilename .= "-" . generateRandomString(5);
	}
	$txtFilename .= ".txt";
	
    $metadataFile = fopen($txtFilename, "w");
    fwrite($metadataFile, "The columns in the included spreadsheet are as follows:\n\nSiteName: Name of the Caterpillars Count! site\nSiteDescription: Description of the Caterpillars Count! site\nLatitude: Latitude of the Caterpillars Count! site\nLongitude: Longitude of the Caterpillars Count! site\nRegion: State or Province of the Caterpillars Count! site\nLocalDate: Date of the observation\nLocalTime: Time of the observation\nSurveyLocationCode: Unique 3-letter code specifying a particular survey branch\nCircle: Circle number of the survey branch\nOrientation: A-E, identifying one of the 5 survey branches within the specified circle\nPlantSpeciesMarkedByObserver: Plant species of the survey branch as entered by the local site coordinator\nOfficialPlantSpecies: Standardized plant species name as specified by a site administrator\nPlantIsConiferous: Is the plant a conifer; 0 = no, 1 = yes\nObservationMethod: Survey method, either \"Visual\" or \"Beat Sheet\"\nSurveyNotes: Any notes applicable to the survey branch as a whole\nWetLeavesOrNeedles: Were the leaves (or, if the plant is coniferous, needles) wet at the time of the survey; 0 = no, 1 = yes\nNumberOfLeaves: For coniferous plants, -1. For non-coniferous plants, number of leaves examined on the survey branch; for Visual surveys this is 50, but it may be variable for Beat Sheet surveys\nAverageLeafLength: For coniferous plants, -1. For non-coniferous plants, average length of the leaf (or leaflet) blade not counting the petiole, in cm\nHerbivoryScore: For coniferous plants, -1. For non-coniferous plants, categorical herbivory score for the set of leaves examined; 0 = None, 1 = Trace (<5%), 2 = Light (5-10%), 3 = Moderate (10-25%), 4 = Heavy (>25%)\nAverageNeedleLength: For coniferous plants, average length of the needle, in cm. For non-coniferous plants, -1\nLinearBranchLength: For coniferous plants, a rough sum of the branch lengths observed, in cm. For non-coniferous plants, -1\nOriginalArthropodGroup: Originally reported arthropod group\nUpdatedArthropodGroup: Arthropod group that includes any corrected id's based on iNaturalist user feedback\nArthropodLength: Length of the observed arthropod from the tip of the head to the tip of the abdomen, in mm (doesn't include antennae or legs)\nArthropodQuantity: Number of arthropods of a given group and length observed\nArthropodPhotoURL: For observations with photos, a link to the observation on iNaturalist\nArthropodNotes: Any notes applicable to the arthropod observed\nIsCaterpillarAndIsHairy: If the observed arthropod is a caterpillar that is significantly hairy or spiny, 1, otherwise 0\nIsCaterpillarAndIsInLeafRoll: If the observed arthropod is a caterpillar that is inside a rolled or folded leaf, 1, otherwise 0\nIsCaterpillarAndIsInSilkTent: If the observed arthropod is a caterpillar that is inside a silk tent, 1, otherwise 0\nIsButterflyOrMothAndIsPupa: If the observed arthropod is a butterfly or moth and is in the pupa stage, 1, otherwise 0\nOriginalIsBeetleLarva: If the observed arthropod is a beetle larva, 1, otherwise 0 (as specified by the original observer)\nUpdatedIsBeetleLarva: If the observed arthropod is a beetle larva, 1, otherwise 0 (includes any corrected id's based on iNaturalist user feedback)\nOriginalIsSawflyLarva: If the observed arthropod is a sawfly larva, 1, otherwise 0 (as specified by the original observer)\nUpdatedIsSawflyLarva: If the observed arthropod is a sawfly larva, 1, otherwise 0 (includes any corrected id's based on iNaturalist user feedback)\n\n\nRecommended citation:\n\nCaterpillars Count! " . date("Y") . ". Foliage Arthropod Data. Data type: Raw Data. 01/01/" . $yearStart . "-12/31/" . $yearEnd . " for Site: " . $siteName . " Chapel Hill, North Carolina, USA. Data set accessed " . date("m/d/Y") . " at https://caterpillarscount.unc.edu.");
    fclose($metadataFile);
		
		zipAndDownload(array($csvFilename, $txtFilename));
		
		/*
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary"); 
		header("Content-disposition: attachment; filename=\"" . basename($csvFilename) . "\"");
		readfile($csvFilename);
		*/
		
		unlink($csvFilename);
		exit();
	}
?>

<html>
	<head>
		<!-- Google Analytics -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-113319025-1"></script>
		<script>
  			window.dataLayer = window.dataLayer || [];
  			function gtag(){dataLayer.push(arguments);}
  			gtag('js', new Date());
			gtag('config', 'UA-113319025-1');
		</script>
		<!-- End of Google Analytics -->

		<title>Data Download | Caterpillars Count!</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="apple-touch-icon-precomposed" sizes="57x57" href="../images/favicon/apple-touch-icon-57x57.png" />
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="../images/favicon/apple-touch-icon-114x114.png" />
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="../images/favicon/apple-touch-icon-72x72.png" />
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="../images/favicon/apple-touch-icon-144x144.png" />
		<link rel="apple-touch-icon-precomposed" sizes="60x60" href="../images/favicon/apple-touch-icon-60x60.png" />
		<link rel="apple-touch-icon-precomposed" sizes="120x120" href="../images/favicon/apple-touch-icon-120x120.png" />
		<link rel="apple-touch-icon-precomposed" sizes="76x76" href="../images/favicon/apple-touch-icon-76x76.png" />
		<link rel="apple-touch-icon-precomposed" sizes="152x152" href="../images/favicon/apple-touch-icon-152x152.png" />
		<link rel="icon" type="image/png" href="../images/favicon/favicon-196x196.png" sizes="196x196" />
		<link rel="icon" type="image/png" href="../images/favicon/favicon-96x96.png" sizes="96x96" />
		<link rel="icon" type="image/png" href="../images/favicon/favicon-32x32.png" sizes="32x32" />
		<link rel="icon" type="image/png" href="../images/favicon/favicon-16x16.png" sizes="16x16" />
		<link rel="icon" type="image/png" href="../images/favicon/favicon-128.png" sizes="128x128" />
		<meta name="msapplication-TileColor" content="transparent" />
		<meta name="msapplication-TileImage" content="../images/favicon/mstile-144x144.png" />
		<meta name="msapplication-square70x70logo" content="../images/favicon/mstile-70x70.png" />
		<meta name="msapplication-square150x150logo" content="../images/favicon/mstile-150x150.png" />
		<meta name="msapplication-wide310x150logo" content="../images/favicon/mstile-310x150.png" />
		<meta name="msapplication-square310x310logo" content="../images/favicon/mstile-310x310.png" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
		<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
		<link href="https://fonts.googleapis.com/css?family=Kaushan+Script" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css?family=Montserrat:400,700" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css?family=Fanwood+Text:400i" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css?family=Roboto+Slab" rel="stylesheet">
		<link href="../css/template.css" rel="stylesheet">
		<link href="../css/checkbox.css?v=1" rel="stylesheet">
		<script src="../js/template.js?v=1"></script>
		<script src="../js/checkbox.js?v=1"></script>
		<style>
			.loadingDiv{
				background:#e6e6e6;
				text-align:center;
				padding:16px;
				border-radius:4px;
    				overflow:hidden;
			}
			.loadingDiv img{
				height:22px;
			}
			
			h3{
				display:block;
				margin-top:40px;
			}
			h3>first-of-type{
				margin-top:0px;
			}
			
			.select{
				width:100%;
				-webkit-appearance: none;
    				-moz-appearance: none;
    				appearance: none;
    				border-radius:4px;
    				overflow:hidden;
    				background:#fff;
				border:1px solid #ddd;
				border-bottom:2px solid #ddd;
				color:#aaa;
				box-sizing:border-box;
				margin-top:5px;
				cursor:pointer;
			}
			.select{
				padding:0px;
				color:#aaa;
				font-family: 'Montserrat', 'Helvetica Neue', Helvetica, Arial, sans-serif;
			}
			.select .option{
				max-height:0px;
				overflow:hidden;
			}
			.select .option.selected{
				max-height:250px;
				overflow:hidden;
			}
			.select .option .value{
				display:none;
			}
			.select .option .shown{
				padding:16px;
				position:relative;
				font-size:16px;
			}
			.select .option:nth-of-type(even){
				background:#f7f7f7;
			}
			.select .option .shown .image{
				height:34px;
				width:34px;
				background-size:contain;
				background-repeat:no-repeat;
				background-position:center;
				display:inline-block;
				opacity:.4;
				position:absolute;
				right:20px;
				top:10px;
			}
			.select .option .shown .text{
				display:inline-block;
			}
			
			#years{
				text-align:center;
				font-size:12px;
				margin-top:5px;
				color:#999;
				text-transform:uppercase;
				font-family:'Montserrat', 'Helvetica Neue', Helvetica, Arial, sans-serif;
				display:none;
			}
			
			#yearsSlider.rangeSlider{
				margin:20px;
				height:2px;
				border:0px none transparent;
				background:rgba(0,0,0,0.1);
				cursor:pointer;
			}
			#yearsSlider.rangeSlider{
				display:none;
			}
			.rangeSlider .ui-slider-range{
				background:#777;
				border-radius:0px;
			}
			.rangeSlider .ui-slider-handle{
				cursor: -webkit-grab; cursor: grab;
				background:#eee;
				outline:none;
				border:0px none transparent;
				height:16px;
				width:16px;
				border-radius:9999px;
				top:-8px;
				margin-left:-8px;
				border:1px solid #ddd;
				-webkit-box-shadow: 1px 1px 2px 0px rgba(0,0,0,0.22);
				-moz-box-shadow: 1px 1px 2px 0px rgba(0,0,0,0.22);
				box-shadow: 1px 1px 2px 0px rgba(0,0,0,0.22);
			}
			.rangeSlider .ui-slider-handle:active{
				cursor: -webkit-grabbing; cursor: grabbing;
			}
			
			main .panel #downloadButton{
				display:block;
				margin:50px auto;
				border:0px none transparent;
				padding:20px 40px;
				font-size:18px;
				background:#fed136;
				color:#fff;
				text-transform:uppercase;
				font-family: 'Montserrat', 'Helvetica Neue', Helvetica, Arial, sans-serif;
				font-weight:700;
				cursor:pointer;
				border-radius:5px;
			}
			
			#disclaimer{
				margin-top:-20px;
			}
		</style>
		<script>
			$(document).ready(function(){
				loadBackgroundImage($("#splashImage"), "../images/splash.png");
				populateSurveySites();
				setYearFilter();
			});
			
			var yearStart = 1980;
			var yearEnd = (new Date()).getFullYear();
			var currentSiteID = "%";
			var sameYear = false;
			function setYearFilter(){
				var siteIDParameter = "";
				if($("#siteSelect").length > 0){
					var siteID = getSelectValue($("#siteSelect"));
					if(siteID == currentSiteID){
						return false;
					}
					currentSiteID = siteID;
				}
				
				$("#yearsLoading").stop().fadeIn(300);
				$("#yearsSlider")[0].outerHTML = "<div id=\"yearsSlider\" class=\"rangeSlider\"></div>";
				$("#years")[0].outerHTML = "<div id=\"years\">" + yearStart + " - " + yearEnd + "</div>";
				
				$.get("../php/getYearRange.php?siteID=" + currentSiteID, function(data){
					//success
					if(data.indexOf("true|") == 0){
						data = JSON.parse(data.replace("true|", ""));
						var date = new Date();
						var currentYear = date.getFullYear();
						
						sameYear = false;
						if(Number(data[0]) == Number(data[1])){
							sameYear = true;
						}
						
						var latestYear = Number(data[1]);
						if(latestYear == 0){
							latestYear = currentYear;
						}
						var earliestYear = Number(data[0]);
						if(earliestYear == 0){
							earliestYear = latestYear;
						}
						
						yearStart = earliestYear;
						yearEnd = latestYear;
						
						var minYear = earliestYear;
						if(sameYear){
							minYear--;
						}
						
						$("#yearsSlider").slider({
							range: true,
							min: minYear,
							max: latestYear,
							values: [minYear, latestYear],
							slide: function(event, ui){
								if(!sameYear){
									yearStart = ui.values[0];
									yearEnd = ui.values[1];
									$("#years")[0].innerHTML = yearStart + " - " + yearEnd;
								}
							}
						});
						$("#years")[0].innerHTML = earliestYear + " - " + latestYear;
						$("#yearsLoading").stop().fadeOut(0);
						$("#yearsSlider").stop().fadeIn();
						$("#years").stop().fadeIn();
					}
					else{
						$("#yearsLoading").stop().fadeOut(0);
						$("#years")[0].innerHTML = "Could not load year filter.";
					}
				})
				.fail(function(){
					//error
					$("#yearsLoading").stop().fadeOut(0);
					$("#years")[0].innerHTML = "Could not load year filter.";
				})
				.always(function() {
					//complete
				});
			}
			
			function toggleMaxHeight(elements){
				//switch the max height of all elements specified as "elements" between 0px and 250px
				elements = $(elements);
				for(var i = 0; i < elements.length; i++){
					if(elements.eq(i)[0].style.maxHeight != "250px"){
						elements.eq(i).stop().animate({maxHeight:"250px"});
					}
					else{
						elements.eq(i).stop().animate({maxHeight:"0px"});
					}
				}
			}
			var selectToggling = false;
			function autoOpenSelect(selectElement){
				if(!selectToggling){
					selectToggling = true;
					if(getSelectValue(selectElement) == ""){
						if($(selectElement)[0].className.indexOf("active") > -1){
							var activeClassName = $(selectElement)[0].className.substring($(selectElement)[0].className.indexOf("active"));
							if(activeClassName.indexOf(" ") > -1){
								activeClassName = activeClassName.substring(0, activeClassName.indexOf(" "));
							}
							$(selectElement)[0].className = $(selectElement)[0].className.replace(activeClassName, "").trim();
						}
						$(selectElement)[0].className = $(selectElement)[0].className + " active" + (getYPosition(selectElement) - 100);
						elements = $(selectElement).find(".option").animate({maxHeight:"250px"}, "swing", function(){selectToggling = false;});
					}
				}
			}
			function selectOption(optionElement){
				if(!selectToggling){
					selectToggling = true;
					//select an option in a custiom .select or open the custom .select
					if(optionElement.parentNode.className.indexOf("active") > -1){
						var preScrollTop = Number(optionElement.parentNode.className.replace(/\D/g, ""));
						optionElement.parentNode.className = optionElement.parentNode.className.replace("active" + preScrollTop, "").trim();
						$('html, body').animate({ scrollTop: preScrollTop }, 400);
					}
					else{
						optionElement.parentNode.className = optionElement.parentNode.className + " active" + $(document).scrollTop();
					}
					toggleMaxHeight($(optionElement.parentNode).find(".option"));
					var selectedElement = $(optionElement.parentNode).find(".selected").eq(0)[0];
					selectedElement.className = selectedElement.className.replace("selected", "").trim();
					optionElement.className = optionElement.className + " selected";
					$(optionElement).stop().animate({maxHeight:"250px"}, "swing", function(){selectToggling = false});
				}
			}
			function setSelectValue(selectElement, val){
				//set the value of a custom .select and show the selected option
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					options[i].className = "option";
					options[i].style.maxHeight = "0px";
					options[i].style.overflow = "hidden";
					if(options[i].getElementsByClassName("value")[0].innerHTML == val){
						options[i].className = "option selected";
						options[i].style.maxHeight = "250px";
						options[i].style.overflow = "hidden";
					}
				}
			}
			function getSelectValue(selectElement){
				//return the value of a custom .select
				if($(selectElement).find(".selected .value").length < 1){
					return "";
				}
				return $(selectElement).find(".selected .value")[0].innerHTML;
			}
			function getSelectText(selectElement){
				//return the show text of a custom .select
				return $(selectElement).find(".selected .text")[0].innerHTML;
			}
			function getSelectTextByValue(selectElement, val){
				//given the value of a custom .select, return that values corresponding text
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					if($(options[i]).find(".value").eq(0)[0].innerHTML == val){
						return $(options[i]).find(".text").eq(0)[0].innerHTML;
					}
				}
			}
			function getSelectValueByText(selectElement, txt){
				//given the value of a custom .select, return that values corresponding text
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					if($(options[i]).find(".text").eq(0)[0].innerHTML == txt){
						return $(options[i]).find(".value").eq(0)[0].innerHTML;
					}
				}
			}
			function getSelectImageByText(selectElement, txt){
				//given the value of a custom .select, return that values corresponding text
				selectElement = $(selectElement)[0];
				var options = selectElement.getElementsByClassName("option");
				for(var i = 0; i < options.length; i++){
					if($(options[i]).find(".text").eq(0)[0].innerHTML == txt){
						bgimg = $(options[i]).find(".image").eq(0)[0].style.backgroundImage;
						return bgimg.substring(bgimg.indexOf("(") + 1, bgimg.lastIndexOf(")")).replace(/"/g, "").replace(/'/g, "");
						//TODO: remove this line. "
					}
				}
			}
			
			function populateSurveySites(){
				$.get("../php/getSitesLIGHT.php", function(data){
					//success
					if(data.indexOf("true|") == 0){
						var sites = JSON.parse(data.replace("true|", ""));
						sites.sort(function(a, b){
							if((a["Name"] + " (" + a["Region"] + ")").toLowerCase() < (b["Name"] + " (" + b["Region"] + ")").toLowerCase()){return -1;}
							if((a["Name"] + " (" + a["Region"] + ")").toLowerCase() > (b["Name"] + " (" + b["Region"] + ")").toLowerCase()){return 1;}
							return 0;
						});
						var htmlToAdd = "<div class=\"select\" id=\"siteSelect\">";
						htmlToAdd += "<div class=\"option selected\" onclick=\"selectOption(this);setYearFilter();\">	<div class=\"value\">%</div>			<div class=\"shown\"><div class=\"image\" style=\"background-image:url('../images/selectIcons/notselected.png');\"></div>		<div class=\"text\">All sites</div></div></div>";
						for(var i = 0; i < sites.length; i++){
							htmlToAdd += "<div class=\"option\" onclick=\"selectOption(this);setYearFilter();\">	<div class=\"value\">" + sites[i]["ID"] + "</div>			<div class=\"shown\"><div class=\"image\"></div>		<div class=\"text\">" + sites[i]["Name"] + " (" + sites[i]["Region"] + ")</div></div></div>";
						}
						htmlToAdd += "</div>";
						$("#siteFilter")[0].innerHTML = htmlToAdd;
					}
					else{
						queueNotice("error", data.replace("false|", ""));
					}
				})
				.fail(function(){
					//error
					queueNotice("error", "Your request did not process. You may have a weak internet connection, or our servers might be busy. Please try again.");
				});
			}
			
			function download(){
				if(!checkboxIsChecked($("#acknowledgmentCheckbox"))){
					queueNotice("error", "Use the checkbox to verify that you have read and acknowledged the Caterpillars Count! data use and attribution policies before downloading.");
					return false;
				}
				
				var downloadedFile = "Dynamically Generated CSV";
				var downloadFilters = "Site: " + getSelectValue($("#siteSelect")).replace("%", "All") + ", YearStart: " + yearStart + ", YearEnd: " + yearEnd + ", Arthropod: " + getSelectValue($("#arthropodSelect")).replace("%", "All");
				var page = window.location.toString().replace("http://", "").replace("https://", "");
				page = page.substring(page.indexOf("/") + 1);
				if(page.slice(-1) == "/"){
					page = page.substring(0, page.length - 1);
				}
				$.get("../php/trackDownload.php?page=" + page + "&file=" + downloadedFile + "&filters=" + downloadFilters, function(data){});
				
				$("#siteID")[0].value = getSelectValue($("#siteSelect"));
				$("#yearStart")[0].value = yearStart;
				$("#yearEnd")[0].value = yearEnd;
				$("#arthropod")[0].value = getSelectValue($("#arthropodSelect"));
				$("#downloadButton")[0].click();
				queueNotice("confirmation", "We are preparing your file for download! If you've requested a large file, please allow a minute before your download starts.");
			}
		</script>
	</head>
	<body>
		<div id="managerRequest">
			<div id="managerRequestMessage"></div>
			<div id="managerRequestButtons">
				<div class="managerRequestButton" onclick="hideNotice();respondToManagerRequest('deny');">Deny</div>
				<div class="managerRequestButton" onclick="hideNotice();respondToManagerRequest('approve');">Accept</div>
			</div>
		</div>
		<div id="error" onclick="hideNotice();"></div>
		<div id="confirmation" onclick="hideNotice();"></div>
		<div id="alert" onclick="hideNotice();"></div>
		<div id="promptInteractionBlock"></div>
		<div id="noticeInteractionBlock" onclick="hideNotice();"></div>
		<div id="confirm">
			<div></div>
			<div>
				<button>OK</button>
				<button>Cancel</button>
				<div class="clearBoth"></div>
			</div>
		</div>

		<div id="splash">
			<div id="splashImage"></div>
			<div id="splashOverlay">
				<div id="splashIntroText">Welcome to</div>
				<div id="splashMainText">Caterpillars Count!</div>
				<button id="splashButton" onclick="this.blur();scrollToPanel(1);">Explore maps & graphs</button>
			</div>
		</div>
		<header>
			<h1><a href="../">Caterpillars Count!</a></h1>
			<div id="hamburger" onclick="toggleNav();">
				<div></div>
				<div></div>
				<div></div>
			</div>
			<div id="navKnockDown" class="clearBoth"></div>
			<nav class="loadingNav">
				<ul>
					<li onclick="accessSubMenu(this);">
						<span>Participate</span>
						<ul onclick="event.stopPropagation();">
							<li class="closeSubmenu" onclick="closeSubmenu(this.parentNode);"><img src="../images/arrow.png"/></li>
							<li><a href="../getStarted">Get Started</a></li>
							<li><a href="../conductASurvey">Conduct a Survey</a></li>
							<li><a href="../submitObservations">Submit Observations</a></li>
							<li><a href="../hostASurveySite">Host a Survey Site</a></li>
							<li><a href="../resources">Resources</a></li>
						</ul>
					</li>
					<li onclick="accessSubMenu(this);">
						<span>Explore</span>
						<ul onclick="event.stopPropagation();">
							<li class="closeSubmenu" onclick="closeSubmenu(this.parentNode);"><img src="../images/arrow.png"/></li>
							<li><a href="../mapsAndGraphs">Maps & Graphs</a></li>
							<li><a href="https://www.inaturalist.org/observations?place_id=any&subview=grid&user_id=caterpillarscount&verifiable=any">Recent Observations</a></li>
							<li><a href="../dataDownload">Data Download</a></li>
						</ul>
					</li>
					<li onclick="accessSubMenu(this);">
						<span>Learn</span>
						<ul onclick="event.stopPropagation();">
							<li class="closeSubmenu" onclick="closeSubmenu(this.parentNode);"><img src="../images/arrow.png"/></li>
							<li><a href="../identificationSkills">Identification Skills</a></li>
							<li><a href="../virtualSurvey">Virtual Survey Game</a></li>
							<li><a href="../forEducators">For Educators</a></li>
						</ul>
					</li>
					<li onclick="window.location = '../news';">
						<span>News</span>
					</li>
					<li onclick="window.location = '../faq';">
						<span>FAQ</span>
					</li>
					<li onclick="window.location = '../signIn';">
						<span>Sign In</span>
						<ul onclick="event.stopPropagation();">
							<li class="closeSubmenu" onclick="closeSubmenu(this.parentNode);"><img src="../images/arrow.png"/></li>
							<li><a href="../createNewSite">Create New Site</a></li>
							<li><a href="../manageMySites">Manage My Sites</a></li>
							<li><a href="../manageMySurveys">Manage My Surveys</a></li>
							<li><a href="../settings">Settings</a></li>
							<li><a href="" onclick="logOut();">Sign Out</a></li>
						</ul>
					</li>
				</ul>
			</nav>
			<div id="navBack" onclick="resetMenu(false);">&#10094;</div>
		</header>
		<main>
			<div class="panel">
				<h2>Data Download</h2>
				<div class="tagline">Just select your filters and then download!</div>
				<div class="content">
					<h3>Site:</h3>
					<div id="siteFilter">
						<div class="loadingDiv" id="sitesLoading">
							<img src="../images/rolling.svg"/>
						</div>
					</div>

					<h3>Year Range:</h3>
					<div class="loadingDiv" id="yearsLoading">
						<img src="../images/rolling.svg"/>
					</div>
					<div id="yearsSlider" class="rangeSlider"></div>
					<div id="years"></div>

					<h3>Arthropod:</h3>
					<div class="select" id="arthropodSelect">
						<div class="option selected" onclick="selectOption(this);">	<div class="value">%</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/notselected.png');"></div>		<div class="text">All arthropods</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">ant</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/ant.png');"></div>			<div class="text">Ants</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">aphid</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/aphid.png');"></div>		<div class="text">Aphids and Psyllids</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">bee</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/bee.png');"></div>			<div class="text">Bees and Wasps</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">beetle</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/beetle.png');"></div>		<div class="text">Beetles</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">caterpillar</div>	<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/caterpillar.png');"></div>		<div class="text">Caterpillars</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">daddylonglegs</div>	<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/daddylonglegs.png');"></div>	<div class="text">Daddy longlegs</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">fly</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/fly.png');"></div>			<div class="text">Flies</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">grasshopper</div>	<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/grasshopper.png');"></div>		<div class="text">Grasshoppers, Crickets</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">leafhopper</div>	<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/leafhopper.png');"></div>		<div class="text">Leaf Hoppers and Cicadas</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">moths</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/moths.png');"></div>		<div class="text">Moths, Butterflies</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">spider</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/spider.png');"></div>		<div class="text">Spiders</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">truebugs</div>	<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/truebugs.png');"></div>		<div class="text">True Bugs</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">other</div>		<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/other.png');"></div>		<div class="text">Other</div></div></div>
						<div class="option" onclick="selectOption(this);">		<div class="value">unidentified</div>	<div class="shown"><div class="image" style="background-image:url('../images/selectIcons/orders/unidentified.png');"></div>	<div class="text">Unidentified</div></div></div>
					</div>
					
					<h3>Policies:</h3>
					<table class="checkboxTable">
						<tr onclick="toggleCheckbox($(this).find('.checkbox'));">
							<td><div class="checkbox" id="acknowledgmentCheckbox"></div></td>
							<td>I have read and acknowledged the Caterpillars Count! <a href="../dataUsePolicy" target="_blank">data use</a> and <a href="../dataAttributionPolicy" target="_blank">attribution</a> policies.</td>
						<tr>
					</table>
					
					<button id="shownDownloadButton" onclick="download();">Download</button>
					<!--<p id="disclaimer">Data submitted by Caterpillars Count! participants are provided "as is", and no warranty, express or implied, is made regarding their accuracy, completeness, or reliability. These data are licensed under a <a href="https://creativecommons.org/publicdomain/zero/1.0/legalcode" target="_blank">Creative Commons CCZero 1.0 License</a>.</p>-->

					<form action="" method="post" style="display:none;">
						<input type="text" name="siteID" id="siteID"/>
						<input type="text" name="yearStart" id="yearStart"/>
						<input type="text" name="yearEnd" id="yearEnd"/>
						<input type="text" name="arthropod" id="arthropod"/>
						<input type="submit" name="download" id="downloadButton" value="Download"/>
					</form>
				</div>
			</div>
		</main>
		<footer>
			<div>Part of the <a href="http://pheno-mismatch.org" target="_blank">Pheno Mismatch</a> project funded by the National Science Foundation</div>

			<div><img src="../images/unc.png"/></div><div>
				<a target="_blank" href="https://www.facebook.com/Caterpillars-Count-1854259101283140/"><img src="../images/facebook.png" alt="facebook"/></a><a target="_blank" href="https://twitter.com/CaterpillarsCt"><img src="../images/twitter.png" alt="twitter"/></a>
			</div>

			<div>Contact us: <a href="mailto:caterpillarscount@gmail.com">caterpillarscount@gmail.com</a></div>

			<div>View our <a href="../privacyPolicy">privacy policy</a></div>
		</footer>
	</body>
</html>
