<?php                                           

require("SurveyFlaggingExceptions.php");

function SurveyFlaggingRules () {
  return array(
    "leafLengthExceptions" => SurveyFlaggingExceptions(),
    "compoundLeafExceptions" => SurveyFlaggingCompoundLeaves(),
    "minSafeLeaves" => 5,
    "maxSafeLeaves" => 600,
    "maxSafeLeafLength" => 30,
    "arthropodGroupFlaggingRules" => array(
      "ant" => array(
        "maxSafeLength" => 25,
        "maxSafeQuantity" => 1000
        ),
      "aphid" => array(
        "maxSafeLength" => 15,
        "maxSafeQuantity" => 1000
        ),
      "bee" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 50
        ),
      "sawfly" => array(
        "maxSafeLength" => 70,
        "maxSafeQuantity" => 100
        ),
      "beetle" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 20
        ),
      "caterpillar" =>  array(
        "maxSafeLength" => 60,
        "maxSafeQuantity" => 100
        ),
      "daddylonglegs" =>  array(
        "maxSafeLength" => 19,
        "maxSafeQuantity" => 10
        ),
      "fly" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 40
        ),
      "grasshopper" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 20
        ),
      "leafhopper" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 20
        ),
      "moths" =>  array(
        "maxSafeLength" => 60,
        "maxSafeQuantity" => 20
        ),
      "spider" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 20
        ),
      "truebugs" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 20
        ),
      "other" =>  array(
        "maxSafeLength" => 70,
        "maxSafeQuantity" => 100
        ),
      "unidentified" =>  array(
        "maxSafeLength" => 70,
        "maxSafeQuantity" => 100
        )
      )
    );
}
?>
