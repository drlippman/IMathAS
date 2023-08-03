<?php
require_once("../includes/sanitize.php");

if (!isset($_GET['cid']) || $_GET['cid']==="embedq") {
	$_SESSION = array();
	require("../init_without_validate.php");

	$cid = "embedq";
	$_SESSION['secsalt'] = "12345";
	$_SESSION['graphdisp'] = 1;
	$_SESSION['mathdisp'] = 1;
    $_SESSION['useed'] = 0;
	if (isset($_GET['theme'])) {
		$coursetheme = 	$_GET['theme'];
	}
    $myrights = 5;
} else {
	require("../init.php");
}

$id = Sanitize::onlyInt($_GET['id']);
$sig = $_GET['sig'] ?? '';
//$t = Sanitize::onlyInt($_GET['t']);
$_SESSION['coursetheme'] = $coursetheme;

$flexwidth = true;
$isdiag = false;
$useeqnhelper = false;
$useeditor = 0;
$isfw = false;
require("header.php");
echo '<p><b style="font-size:110%">'._('Written Example').'</b> '._('of a similar problem').'</p>';
if ($sig != md5($id.$_SESSION['secsalt'])) {
	echo "invalid signature - not authorized to view the solution for this problem";
	exit;
}

//require("displayq2.php");
//$txt = displayq(0,$id,100000,false,false,0,2+$t);
//echo printfilter(filter($txt));

require_once '../assess2/AssessStandalone.php';
$assessver = 2;
$courseUIver = 2;
$assessUIver = 2;
$qn = 5; //question number to use
// load question data and load/set state
$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id=:id");
$stm->execute(array(':id' => $id));
$line = $stm->fetch(PDO::FETCH_ASSOC);
$showq = ($line['solutionopts']&1);

$line['solutionopts'] = ($line['solutionopts']|1); // hide "this soln is for a similar problem"

$a2 = new AssessStandalone($DBH);
$a2->setQuestionData($line['id'], $line);
$state = array(
    'seeds' => array($qn => 100000),
    'qsid' => array($qn => $id),
    'stuanswers' => array(),
    'stuanswersval' => array(),
    'scorenonzero' => array(($qn + 1) => false),
    'scoreiscorrect' => array(($qn + 1) => false),
    'partattemptn' => array($qn => array()),
    'rawscores' => array($qn => array()),
);
$a2->setState($state);
$disp = $a2->displayQuestion($qn, [
    'showallparts' => true,
    'showans' => false,
    'showhints' => 0
]);
if ($showq) {
    echo printfilter(filter($disp['html']), false);
}
echo printfilter(filter($disp['soln']), false);

require("../footer.php");

?>
