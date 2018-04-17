<?php
//Export assessment question IDs in JSON format
//for Lumen's OEA embed
//Based on work by @kdv24 and @AbeerKhakwani

require("../init.php");
if (isset($_GET['cid'])) {
  $cid = Sanitize::courseId($_GET['cid']);

  //get assessment items and associated data
  $assessinfo = array();
  //DB $query = "SELECT i_a.name, i_a.itemorder, i_i.id, i_i.typeid ";
  //DB $query .= "FROM imas_assessments as i_a JOIN imas_items AS i_i ON ";
  //DB $query .= "i_a.id=i_i.typeid AND i_i.itemtype='Assessment' ";
  //DB $query .= "WHERE i_i.courseid=$cid";
  //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
  //DB while ($row = mysql_fetch_assoc($result)) {
  $query = "SELECT i_a.name, i_a.itemorder, i_i.id, i_i.typeid ";
  $query .= "FROM imas_assessments as i_a JOIN imas_items AS i_i ON ";
  $query .= "i_a.id=i_i.typeid AND i_i.itemtype='Assessment' ";
  $query .= "WHERE i_i.courseid=:courseid";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':courseid'=>$cid));
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $assessinfo[$row['id']] = $row;
  }

  //DB $query = "SELECT itemorder FROM imas_courses WHERE id=$cid";
  //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
  //DB $items = unserialize(mysql_result($result,0,0));
  $stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
  $stm->execute(array(':id'=>$cid));
  $items = unserialize($stm->fetchColumn(0));

  $output_array = array();
  parseItemorder($items);

  //  exports the modules array as json
  $outputjson = json_encode($output_array, JSON_HEX_TAG);
  header("Content-type: application/json");
  header('Content-Disposition: attachment; filename="results.json"');
  header('Content-Length: ' . strlen($outputjson));
  echo $outputjson;
}
function parseItemorder($items, $blockname='Main Page') {
  global $assessinfo,$output_array;
  $thisblockassess = array();
  foreach ($items as $item) {
    if (is_array($item)) { //is block
      parseItemorder($item['items'], $item['name']);
    } else if (isset($assessinfo[$item])) { //is an assessment
      $assessqs = getAssessmentQuestionIds($assessinfo[$item]['typeid'], $assessinfo[$item]['itemorder']);
      if (count($assessqs)>0) {
        $thisblockassess[$assessinfo[$item]['name']] = $assessqs;
      }
    }
  }
  if (count($thisblockassess)>0) {
    $output_array[$blockname] = $thisblockassess;
  }
}
function getAssessmentQuestionIds($aid, $itemorder) {
  global $DBH;
  if ($itemorder=='') {return array();}
  //DB $query = "SELECT imas_questions.id, imas_questions.questionsetid FROM imas_questions JOIN imas_assessments ";
  //DB $query .= "ON imas_questions.assessmentid = imas_assessments.id WHERE imas_assessments.id=$aid";
  //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
  $query = "SELECT imas_questions.id, imas_questions.questionsetid FROM imas_questions JOIN imas_assessments ";
  $query .= "ON imas_questions.assessmentid = imas_assessments.id WHERE imas_assessments.id=:id";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':id'=>$aid));
  $questionsetids = array();
  //DB while($row = mysql_fetch_row($result)) {
  while($row = $stm->fetch(PDO::FETCH_NUM)) {
    $questionsetids[$row[0]] = $row[1]*1;
  }
  $qsetids_out = array();
  $qs = explode(',', $itemorder);
  foreach ($qs as $q) {
    if (strpos($q,'~')!==false) { //is group of questions
      $sub = explode('~',$q);
      $grpparts = explode('|',$sub[0]);
      for ($i=0;$i<$grpparts[0] && $i+1<count($sub);$i++) {
        $qsetids_out[] = $questionsetids[$sub[1+$i]];
      }
    } else {
      $qsetids_out[] = $questionsetids[$q];
    }
  }
  return $qsetids_out;
}
?>
