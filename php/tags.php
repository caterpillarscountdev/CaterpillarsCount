<html>
	<style>
		body{
			text-align:center;
			margin: 0px;
		}
		.tag{
			font-family:"Segoe UI", Frutiger, "Frutiger Linotype", "Dejavu Sans", "Helvetica Neue", Arial, sans-serif;
			padding:3px;
			display:inline-block;
			margin:10px;
			border-radius:0px;
			border:5px solid;
			border-top:38px solid;
			border-bottom:13px solid;
			position:relative;
		}
		.tag>div:nth-of-type(1), .tag>div:nth-of-type(2){
			color:#333;
			padding:5px;
			text-align:center;
			font-weight:bold;
		}
		.tag>div:nth-of-type(1){
			margin-top:-42px;
		}
		.tag>div:nth-of-type(2){
			margin-top:-16px;
		}
		
		.whiteBox{
			position:relative;
			background:#fff;
			text-align:center;
			font-size:50px;
			padding:0px 16px;
		}
		
		.urlStamp{
			position:absolute;
			bottom:-18px;
			left:13px;
		}
		
		@page {
		    margin:0cm;
		}
	</style>
	<script>
		function setSVGWidths(){
			var headers = document.getElementsByClassName("tag");
			var svgs = document.getElementsByTagName("svg");
			for(var i = 0; i < headers.length; i++){
				svgs[i].style.width = headers[i].clientWidth;
				svgs[i].style.display = "block";
				headers[i].getElementsByTagName("span")[0].outerHTML = "";
			}
		}
		
		function ptask(){
		}
	</script>
	<body onload="window.print();">
		<?php
			require_once("orm/Site.php");
			
			$siteID = $_GET["q"];
			$site = Site::findByID($siteID);
			
			function cmp($a, $b){
				if($a->getCircle() === $b->getCircle()){
					return strcmp($a->getOrientation(), $b->getOrientation());
				}
				return $a->getCircle() - $b->getCircle();
			}
			
			if(is_object($site)){
				$plants = $site->getPlants();
				usort($plants, "cmp");
				for($i = 0; $i < count($plants); $i++){
					$circle = $plants[$i]->getCircle();
					$color = $plants[$i]->getColor();
					$species = $plants[$i]->getSpecies();
					$name = $site->getName();
					
					if(strlen($name) > 32){
						$name = substr($name, 0, 30) . "...";
					}
					
					$line1 = $name . ", Circle " . $circle;
					$line2 = $species;
					
					$MIN_LENGTH = 40;
					if(strlen($line1) < $MIN_LENGTH){
						$line1 = $line1 . str_repeat("_", ($MIN_LENGTH - strlen($line1)));
					}
					
					if(strlen($line2) < $MIN_LENGTH){
						$line2 = $line2 . str_repeat("_", ($MIN_LENGTH - strlen($line2)));
					}
					
					echo "<div style=\"border-color:$color;\" class=\"tag\">";
					echo	"<div>";
					echo 		"<svg width=\"200px\" height=\"20px\" viewBox=\"0 0 300 24\">";
					echo 			"<text textLength=\"290\" lengthAdjust=\"spacing\" x=\"5\" y=\"14\" text-decoration='underline' font-weight='bold' font-size='16px' fill='#ffffff' font-family=\"font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\">";
					echo 				$line1;
					echo 			"</text>";
					echo 		"</svg>";
					echo	"</div>";
					echo	"<div>";
					echo 		"<svg width=\"200px\" height=\"20px\" viewBox=\"0 0 300 24\">";
					echo 			"<text textLength=\"290\" lengthAdjust=\"spacing\" x=\"5\" y=\"14\" text-decoration='underline' font-weight='bold' font-size='16px' fill='#ffffff' font-family=\"font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\">";
					echo 				$line2;
					echo 			"</text>";
					echo 		"</svg>";
					echo	"</div>";
					echo	"<div class=\"whiteBox\">";
					echo 		"<div style='color:$color;'>" . $plants[$i]->getCode() . "</div>";
					echo	"</div>";
					echo	"<div class=\"urlStamp\">";
					echo 		"<svg width=\"200\" height=\"20\" viewBox=\"0 0 300 24\">";
					echo 			"<text x=\"50%\" y=\"14\" text-anchor=\"middle\" font-weight='bold' font-size='12px' fill='#ffffff' font-family=\"font-family:'Segoe UI', Frutiger, 'Frutiger Linotype', 'Dejavu Sans', 'Helvetica Neue', Arial, sans-serif;\">";
					echo 				"caterpillarscount.unc.edu";
					echo 			"</text>";
					echo 		"</svg>";
					echo	"</div>";
					echo "</div>";
				}
			}
		?>
	</body>
</html>
