<?php
/*
 * IMathAS: Generate assessments for testing purposes
 * (c) 2019 David Lippman
 */

require('../init.php');
require_once("../includes/filehandler.php");


if ($myrights < 100) {
  echo "No rights";
  exit;
}

if (!isset($_POST['cid'])) {
  require('../header.php');
?>
  <h1>Generate Testing Scenarios</h1>
  <p>This page will generate a set of testing data for the new assessment player.
    It will overwrite any previously generated data.  It may overwrite other data
    in the database, so USE WITH CAUTION.</p>
  <p>The course should already exist, and the teacher and student should already be
    added to the course.</p>

  <form method="post">
    Course ID: <input name=cid value="13368" /> <br/>
    Teacher user ID: <input name=teacher value="2" /> <br/>
    Student user ID: <input name=stu value="3258" /> <br/>
    <input type=submit />
  </form>
<?php
  require('../footer.php');
  exit;
}

$cid = Sanitize::onlyInt($_POST['cid']);
$teacher = Sanitize::onlyInt($_POST['teacher']);
$stu = Sanitize::onlyInt($_POST['stu']);

/** Clean up old stuff **/
// Delete existing assesssments
$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));

while ($line = $stm->fetch(PDO::FETCH_NUM)) {
  //deleteallaidfiles($line[0]);
  $stm2 = $DBH->prepare("DELETE FROM imas_questions WHERE assessmentid=:assessmentid");
  $stm2->execute(array(':assessmentid'=>$line[0]));
  $stm2 = $DBH->prepare("DELETE FROM imas_assessment_records WHERE assessmentid=:assessmentid");
  $stm2->execute(array(':assessmentid'=>$line[0]));
  $stm2 = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:assessmentid AND itemtype='A'");
  $stm2->execute(array(':assessmentid'=>$line[0]));
  $stm2 = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
  $stm2->execute(array(':assessmentid'=>$line[0]));
}

$stm = $DBH->prepare("DELETE FROM imas_assessments WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));

$stm = $DBH->prepare("DELETE FROM imas_items WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));

// delete content tracking
$stm = $DBH->prepare("DELETE FROM imas_content_track WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));

// delete groups
$stm = $DBH->prepare("SELECT id FROM imas_stugroupset WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
  $stm2 = $DBH->prepare("SELECT id FROM imas_stugroups WHERE groupsetid=:groupsetid");
  $stm2->execute(array(':groupsetid'=>$row[0]));
  while ($row2 = $stm2->fetch(PDO::FETCH_NUM)) {
    $stm3 = $DBH->prepare("DELETE FROM imas_stugroupmembers WHERE stugroupid=:stugroupid");
    $stm3->execute(array(':stugroupid'=>$row2[0]));
  }
  $stm4 = $DBH->prepare("DELETE FROM imas_stugroups WHERE groupsetid=:groupsetid");
  $stm4->execute(array(':groupsetid'=>$row[0]));
}
$stm = $DBH->prepare("DELETE FROM imas_stugroupset WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));

/** Create new stuff **/
require("testdata.php");

// add questionset items
$qsetIds = array();
$stmsel = $DBH->prepare("SELECT id FROM imas_questionset WHERE uniqueid=?");
foreach ($questionSet as $n=>$data) {
  $stmsel->execute(array($data['uniqueid']));
  if ($stmsel->rowCount() > 0) {
    $qsetIds[$n] = $stmsel->fetchColumn(0);
  } else {
    $data['ownerid'] = $teacher;
    $keys = implode(',', array_keys($data));
    $ph = Sanitize::generateQueryPlaceholders($data);
    $stm = $DBH->prepare("INSERT INTO imas_questionset ($keys) VALUES ($ph)");
    $stm->execute(array_values($data));
    $qsetIds[$n] = $DBH->lastInsertId();
  }
}

// create assessments
$addedIds = array();
$now = time();
foreach ($assessGroups as $agroup) {
  foreach ($agroup['assessments'] as $n=>$data) {
    $data['courseid'] = $cid;
    $itemorder = $data['itemorder'];
    $questions = $data['questions'];
    unset($data['itemorder']);
    unset($data['questions']);
    $data['startdate'] = $now + $data['startdate']*60*60;
    $data['enddate'] = $now + $data['enddate']*60*60;
    $keys = implode(',', array_keys($data));
    $ph = Sanitize::generateQueryPlaceholders($data);
    if (isset($data['reqscoreaid'])) {
      // map reqscoreaid
      $data['reqscoreaid'] = $addedIds[$data['reqscoreaid']];
    }
    $stm = $DBH->prepare("INSERT INTO imas_assessments ($keys) VALUES ($ph)");
    $stm->execute(array_values($data));
    $addedIds[$n] = $DBH->lastInsertId();

    // add questions
    $qmap = array();
    foreach ($questions as $qn=>$qdata) {
      $qdata['assessmentid'] = $addedIds[$n];
      $qdata['questionsetid'] = $qsetIds[$qdata['questionsetid']];
      $keys = implode(',', array_keys($qdata));
      $ph = Sanitize::generateQueryPlaceholders($qdata);
      $stm = $DBH->prepare("INSERT INTO imas_questions ($keys) VALUES ($ph)");
      $stm->execute(array_values($qdata));
      $qmap[$qn] = $DBH->lastInsertId();
    }

    // update itemorder for assessment.  Set in testdata as arrays.
    for ($k=0; $k<count($itemorder); $k++) {
      if (is_array($itemorder[$k])) {
        for ($j=1; $j<count($itemorder[$k]); $j++) {
          $itemorder[$k][$j] = $qmap[$itemorder[$k][$j]];
        }
        $itemorder[$k] = implode('~', $itemorder[$k]);
      } else {
        $itemorder[$k] = $qmap[$itemorder[$k]];
      }
    }
    $itemorder = implode(',', $itemorder);
    $stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=? WHERE id=?");
    $stm->execute(array($itemorder, $addedIds[$n]));
  }
}

// create imas_items
$items = array();
$stm = $DBH->prepare("INSERT INTO imas_items (courseid, itemtype, typeid) VALUES (?,'Assessment',?)");
foreach ($addedIds as $n=>$aid) {
  $stm->execute(array($cid, $aid));
  $items[$n] = $DBH->lastInsertId();
}

$itemorder = [];
foreach ($assessGroups as $agroup) {
  $group = [
    'name' => $agroup['name'],
    'avail' => 2,
    'SH' => 'SF3',
    'items' => []
  ];
  foreach ($agroup['assessments'] as $n=>$data) {
    $group['items'][] = $items[$n];
  }
  $itemorder[] = $group;
}

// add to course
$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=? WHERE id=?");
$stm->execute(array(serialize($itemorder), $cid));

// TODO: Add exceptions, student attempt data, views, etc.

// give student latepasses
$stm = $DBH->prepare("UPDATE imas_students SET latepass=30 WHERE userid=? AND courseid=?");
$stm->execute(array($stu, $cid));

echo "Done";
