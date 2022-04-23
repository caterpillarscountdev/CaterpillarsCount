$(document).ready(function(){
  //temporarily disable photo uploads
  $("#arthropodPhotoGroup").css({opacity:"0.5"});
  var arthropodFileInputHolderElement = $("#arthropodFileInputHolder");
  if(arthropodFileInputHolderElement.length > 0){
    arthropodFileInputHolderElement[0].onclick = function(){
      queueNotice("error", "Photo uploads are undergoing maintenance. Please submit this survey without a photo.");
    };
  }
});
