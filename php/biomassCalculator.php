<?php
  function getBiomass($group, $length){
  		$a = 0.027;//unidentified default
  		$b = 2.494;//unidentified default
  		
  		if($group == "ant"){			$a = 0.009;	$b = 2.919;	}
  		else if($group == "aphid"){		$a = 0.0175;	$b = 2.629;	}
  		else if($group == "bee"){		$a = 0.014;	$b = 2.696;	}
  		else if($group == "beetle"){		$a = 0.039;	$b = 2.492;	}
  		else if($group == "caterpillar"){	$a = 0.003;	$b = 2.959;	}
  		else if($group == "daddylonglegs"){	$a = 0.162;	$b = 2.17;	}
  		else if($group == "fly"){		$a = 0.041;	$b = 2.213;	}
  		else if($group == "grasshopper"){	$a = 0.023;	$b = 2.27;	}
  		else if($group == "leafhopper"){	$a = 0.012;	$b = 3.13;	}
  		else if($group == "moths"){		$a = 0.006;	$b = 3.122;	}
  		else if($group == "other"){		$a = 0.027;	$b = 2.494;	}
  		else if($group == "spider"){		$a = 0.05;	$b = 2.74;	}
  		else if($group == "truebugs"){		$a = 0.008;	$b = 3.075;	}
  		
  		return ($a * pow(floatval($length), $b));
	}
?>
