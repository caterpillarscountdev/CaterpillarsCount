<?php
    require('tools/fpdf/fpdf.php');
    require_once("orm/Site.php");
	
	$siteID = $_GET["q"];
	$site = Site::findByID($siteID);
    
    class PDF extends FPDF{
        public $currentTagNumber = 0;
        
        function Header(){
            $this->SetFont('Arial','B',15);
            $w = $this->GetStringWidth("PRINT DOUBLE SIDED")+6;
            $this->SetX((210-$w)/2);
            $this->SetDrawColor(255, 255, 255);
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(200, 200, 200);
            $this->SetFont('', '', '16');            //fontWeight
            $this->SetLineWidth(0);
            $this->Cell($w,2,"PRINT DOUBLE-SIDED",1,1,'C',true);
            $this->Ln(10);
            // Save ordinate
            $this->y0 = $this->GetY();
        }
        
        function injectTag($row, $col, $hexColor, $line1, $line2, $plantCode){
            list($r, $g, $b) = sscanf($hexColor, "#%02x%02x%02x");
            
            $x = 4.1 + ($col - 1) * 65 + 1.75 * $col;
            $y = 20 + ($row - 1) * 32.24 + 1 * $row;
        
            $this->SetY($y);
        
            // Colors, line width and bold font
            $this->SetFillColor($r, $g, $b); //backgroundColor
            $this->SetTextColor(255);           //color
            $this->SetDrawColor($r, $g, $b); //borderColor
            $this->SetLineWidth(1.25);          //borderWidth
            $this->SetFont('', 'B', '8.18');            //fontWeight
            
            // Header
             $this->SetX($x);
            $fill = true;
            $this->Cell(65, 4.42, $line1, 1, 0, 'C', $fill);
            $this->Ln();
            $this->SetX($x);
            $this->Cell(65, 4.42, $line2, 1, 0, 'C', $fill);
            $this->Ln();
            
            // Color and font restoration
            $this->SetTextColor($r, $g, $b);
            $this->SetFont('', '', '36.92');
            
            // Data
            $this->SetX($x);
            $fill = false;
            $this->Cell(65, 20, $plantCode, 'LR', 0, 'C', $fill);
            $this->Ln();
            
            // Color and font restoration
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('', 'B', 6);
            
            //Footer
            $this->SetX($x);
            $fill = true;
            $this->Cell(65, 2.6, 'caterpillarscount.unc.edu', 1, 0, 'C', $fill);
            $this->Ln();
            
            // Closing line
            $this->SetX($x);
            $this->Cell(65, 0, '', 'T');
        }
        
        function injectBack(){
            $this->currentTagNumber++;
            
            $row = ceil($this->currentTagNumber / 3);
            $col = (($this->currentTagNumber - 1) % 3) + 1;
            
            //right align to match when printing double sided
            if($col == 3){
                $col = 1;
            }
            else if($col == 1){
                $col = 3;
            }
            
            $x = 3.6 + ($col - 1) * 65 + 1.75 * $col;
            $y = 19.5 + ($row - 1) * 32.24 + 1 * $row;
            
            $this->Image('../images/plantTagBack.png', $x, $y, 66.25);
        }
        
        function addTag($hexColor, $line1, $line2, $plantCode){
            $this->currentTagNumber++;
            if($this->currentTagNumber == 22){
                $this->AddPage();
                $this->currentTagNumber = 0;
                for($i = 0; $i < 21; $i++){
                    $this->injectBack();
                }
                
                $this->AddPage();
                $this->currentTagNumber = 1;
            }
            $this->injectTag(ceil($this->currentTagNumber / 3), (($this->currentTagNumber - 1) % 3) + 1, $hexColor, $line1, $line2, $plantCode);
        }
    }
	
	function cmp($a, $b){
		if($a->getCircle() === $b->getCircle()){
			return strcmp($a->getOrientation(), $b->getOrientation());
		}
		return $a->getCircle() - $b->getCircle();
	}
	
	if(is_object($site)){
		$plants = $site->getPlants();
		usort($plants, "cmp");
		
		$pdf = new PDF();
    	$pdf->SetTitle("Print Tags | Caterpillars Count!");
    	$pdf->SetFont('Arial', '', 14);
    	$pdf->AddPage();
    	
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
			
			$pdf->addTag($color, $line1, $line2, $plants[$i]->getCode());
		}
		
		//inject as many as 21 backs to match the last page of fronts
	    $tagsNeededOnLastPage = ((count($plants) - 1) % 21) + 1;
   		if($tagsNeededOnLastPage > 0){
        	$pdf->AddPage();
        	$pdf->currentTagNumber = 0;
    	}
    	for($i = 0; $i < $tagsNeededOnLastPage; $i++){
        	$pdf->injectBack();
    	}
    
    	$pdf->Output();
	}
	else{
		echo "We're having trouble finding this site.";
	}
?>
