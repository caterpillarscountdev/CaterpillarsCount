<?php
  $photos = scandir("../images/arthropods");
  for($i = 0; $i < count($photos); $i++){
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
