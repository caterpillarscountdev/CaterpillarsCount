<html>
	<head>
		<title>Print Tags | Caterpillars Count!</title>
		<meta name="robots" content="noindex"/>
		<link rel="apple-touch-icon-precomposed" sizes="57x57" href="../../images/favicon/apple-touch-icon-57x57.png" />
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="../../images/favicon/apple-touch-icon-114x114.png" />
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="../../images/favicon/apple-touch-icon-72x72.png" />
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="../../images/favicon/apple-touch-icon-144x144.png" />
		<link rel="apple-touch-icon-precomposed" sizes="60x60" href="../../images/favicon/apple-touch-icon-60x60.png" />
		<link rel="apple-touch-icon-precomposed" sizes="120x120" href="../../images/favicon/apple-touch-icon-120x120.png" />
		<link rel="apple-touch-icon-precomposed" sizes="76x76" href="../../images/favicon/apple-touch-icon-76x76.png" />
		<link rel="apple-touch-icon-precomposed" sizes="152x152" href="../../images/favicon/apple-touch-icon-152x152.png" />
		<link rel="icon" type="image/png" href="../../images/favicon/favicon-196x196.png" sizes="196x196" />
		<link rel="icon" type="image/png" href="../../images/favicon/favicon-96x96.png" sizes="96x96" />
		<link rel="icon" type="image/png" href="../../images/favicon/favicon-32x32.png" sizes="32x32" />
		<link rel="icon" type="image/png" href="../../images/favicon/favicon-16x16.png" sizes="16x16" />
		<link rel="icon" type="image/png" href="../../images/favicon/favicon-128.png" sizes="128x128" />
		<meta name="msapplication-TileColor" content="transparent" />
		<meta name="msapplication-TileImage" content="../../images/favicon/mstile-144x144.png" />
		<meta name="msapplication-square70x70logo" content="../../images/favicon/mstile-70x70.png" />
		<meta name="msapplication-square150x150logo" content="../../images/favicon/mstile-150x150.png" />
		<meta name="msapplication-wide310x150logo" content="../../images/favicon/mstile-310x150.png" />
		<meta name="msapplication-square310x310logo" content="../../images/favicon/mstile-310x310.png" />
		<style>
			body{
				text-align:center;
				margin: 0px;
			}
			.tag{
				font-family:"Segoe UI", Frutiger, "Frutiger Linotype", "Dejavu Sans", "Helvetica Neue", Arial, sans-serif;
				padding:3px;
				display:inline-block;
				margin:10px 10px 9px 10px;
				border-radius:0px;
				border:5px solid;
				border-top:38px solid;
				border-bottom:14px solid;
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
				left:8px;
				right: 8px;
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
	</head>
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
