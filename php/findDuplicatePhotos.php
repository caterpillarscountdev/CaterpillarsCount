<?php
  $offset = intval($_GET["offset"]);
  $limit = intval($_GET["limit"]);

  $photos = scandir("../images/arthropods");
  for($i = $offset; $i < count($photos); $i++){
    if($i >= $limit){
      break;
    }
    
    //echo "checking...";
    $photoHash = hash_file("md5", "../images/arthropods/" . $photos[$i]);
    for($j = 0; $j < count($photos); $j++){
      if($i != $j && hash_file("md5", "../images/arthropods/" . $photos[$j]) == $photoHash){
        echo $photos[$i];
        break;
      }
    }
  }
?>
