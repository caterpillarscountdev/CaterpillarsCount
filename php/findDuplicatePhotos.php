<?php
  $offset = intval($_GET["offset"]);
  $limit = intval($_GET["limit"]);
  
  $usedPhotoHashes = array();
  $photoHashes = array();
  $photos = scandir("../images/arthropods");
  for($i = 0; $i < count($photos); $i++){
    $photoHashes[$photos[$i]] = hash_file("md5", "../images/arthropods/" . $photos[$i]);
  }

  for($i = 0; $i < count($photoHashes); $i++){
    $isDuplicate = false;
    for($j = 0; $j < count($photoHashes); $j++){
      if($i != $j && !in_array($photoHashes[$photos[$i]], $usedPhotoHashes) && $photoHashes[$photos[$i]] == $photoHashes[$photos[$j]]){
        if(!$isDuplicate){
          echo "<a href=\"https://caterpillarscount.unc.edu/images/arthropods/" . $photos[$i] . "\" target=\"_blank\">" . $photos[$i] . "</a><br/>";
        }
        $isDuplicate = true;
        echo "<a href=\"https://caterpillarscount.unc.edu/images/arthropods/" . $photos[$j] . "\" target=\"_blank\">" . $photos[$j] . "</a><br/>";
      }
    }
    if($isDuplicate){
      $usedPhotoHashes[] = $photoHashes[$photos[$i]];
      echo "<br/>";
    }
  }
?>
