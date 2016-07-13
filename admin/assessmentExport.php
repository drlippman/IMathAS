<?php
require("../validate.php");
if(isset($_GET['cid']))
{
  //courseid 
  $cid = $_GET['cid'];
  //Empty array that will return the blocks and all contents
  $modules_array= array(); 
  //returns all blocks based on given course id
  $modules= returnCourseBlocks($cid);
  
  //finds all assessments for each block
  foreach ($modules as $block) {
      //creates an empty array to store names of all assessments in current block
      $assessment_names_array= array(); 
      //loops through an array of typeid(assessment typeids) in the block
      foreach($block['items'] as $typeid) {
        //finds names and ids of all assessments for the given block 
        $query = "SELECT imas_assessments.name,imas_assessments.id FROM imas_assessments
        join imas_items 
        On imas_assessments.id = imas_items.typeid 
        WHERE imas_items.id = ". $typeid .";" ;    
        $result = mysql_query($query) or die("Query failed : " . mysql_error());
        //adds the question ids to its specific assessment name
        while($assessment = mysql_fetch_array($result)){
          $AssessmentQuestionIds=returnAssessmentQuestionIds($assessment['id']);
          $assessment_names_array[$assessment['name']]=$AssessmentQuestionIds;
        }
      }    
      //adds the assessment names to the block array
      $modules_array[$block['name']]=$assessment_names_array;
    }

//  exports the modules array as json
    $outputjson = json_encode($modules_array);
    header("Content-type: application/json");
    header('Content-Disposition: attachment; filename="results.json"');
    header('Content-Length: ' . strlen($outputjson));
    echo $outputjson;
}

function returnCourseBlocks($cid){
  $query = "SELECT itemorder FROM imas_courses WHERE id=" . $cid .";";
  $result = mysql_query($query) or die("Query failed : " . mysql_error());
  $items = unserialize(mysql_result($result,0,0));
  return $items;
}
// takes an assessment id and returns all questions in a specific assessment
function returnAssessmentQuestionIds($assessment_id){
  $query ="SELECT imas_questions.questionsetid FROM `imas_questions` 
  join imas_assessments 
  ON imas_questions.assessmentid = imas_assessments.id
  WHERE imas_assessments.id =".$assessment_id . ";" ;
  $result = mysql_query($query) or die("Query failed : " . mysql_error());
  $questionsetids = [];
  while($row = mysql_fetch_array($result))
  $rows[] = $row;
  foreach($rows as $questionid) {
    $questionsetids[]=$questionid['questionsetid'];
  }
  return $questionsetids;
}
?>