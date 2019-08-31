<?php
  $photos = scandir("../images/arthropods");
  for($i = 0; $i < $photos.length; $i++){
    $photoHash = hash_file("md5", "../images/arthropods/" . $photos[$i]);
    for($j = 0; $j < $photos.length; $j++){
      if(hash_file("md5", "../images/arthropods/" . $photos[$j]) == $photoHash){
        echo $photos[$i];
        break;
      }
    }
  }
?>
