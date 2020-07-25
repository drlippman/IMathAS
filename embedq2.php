<?php

// embedq2.php: Embed one or more questions via an iframe
// Operates without requiring login
// Can passback results to embedding page
// (c) 2020 David Lippman

$init_skip_csrfp = true;
require "./init_without_validate.php";

require_once './assess2/AssessStandalone.php';
require("includes/JWT.php");

$assessver = 2;
$courseUIver = 2;
$assessUIver = 2;
$qn = 5; //question number to use
$_SESSION = array();
$inline_choicemap = !empty($CFG['GEN']['choicesalt']) ? $CFG['GEN']['choicesalt'] : 'test';
$statesecret = !empty($CFG['GEN']['embedsecret']) ? $CFG['GEN']['embedsecret'] : 'test';

$issigned = false;
// Get basic settings from JWT or query string
if (isset($_POST['state'])) {
    $state = json_decode(json_encode(JWT::decode($_POST['state'], $statesecret)), true);
    $QS = $state;
    $QS['id'] = $state['qsid'][$qn];
} else if (isset($_REQUEST['jwt'])) {
    try {
        // decode JWT.  Stupid hack to convert it into an assoc array
        // verification using 'auth' is built-into the JWT method
        $QS = json_decode(json_encode(JWT::decode($_REQUEST['jwt'])), true);
    } catch (Exception $e) {
        echo "JWT Error: " . $e->getMessage();
        exit;
    }
    if (!empty($QS['auth'])) {
        $issigned = true;
        if (isset($QS['showscored'])) {
            // want to redisplay question; set as state
            $_POST['state'] = $QS['showscored'];
        } if (isset($QS['redisplay'])) {
            // want to redisplay question; set as state
            $_POST['state'] = $QS['redisplay'];
        }
    }
} else {
    $QS = $_GET;
}
if (empty($QS['auth'])) {
    $QS['auth'] = '';
}

if (empty($QS['id'])) {
    echo 'Need to supply an id';
    exit;
}

// set user preferences
$prefdefaults = array(
    'mathdisp' => 6, //default is katex
    'graphdisp' => 1,
    'drawentry' => 1,
    'useed' => 1,
    'livepreview' => 1);

// override via cookie if set
if (!empty($_COOKIE["embedq2userprefs"])) {
    $prefcookie = json_decode($_COOKIE["embedq2userprefs"], true);
}
$_SESSION['userprefs'] = array();
foreach ($prefdefaults as $key => $def) {
    if (isset($QS[$key])) { // can overwrite via JWT
        $_SESSION['userprefs'][$key] = filter_var($QS[$key], FILTER_SANITIZE_NUMBER_INT);
    } else if ($prefcookie !== null && isset($prefcookie[$key])) {
        $_SESSION['userprefs'][$key] = filter_var($prefcookie[$key], FILTER_SANITIZE_NUMBER_INT);
    } else {
        $_SESSION['userprefs'][$key] = $def;
    }
}
// override via query string or post value; record into cookie
if (isset($_REQUEST['graphdisp'])) { //currently same is used for graphdisp and drawentry
    $_SESSION['userprefs']['graphdisp'] = filter_var($_REQUEST['graphdisp'], FILTER_SANITIZE_NUMBER_INT);
    $_SESSION['userprefs']['drawentry'] = filter_var($_REQUEST['graphdisp'], FILTER_SANITIZE_NUMBER_INT);
    setsecurecookie("embedq2userprefs", json_encode(array(
        'graphdisp' => $_SESSION['userprefs']['graphdisp'],
        'drawentry' => $_SESSION['userprefs']['drawentry'],
    )), time() + 60 * 60 * 24 * 365);
}
foreach (array('graphdisp', 'mathdisp', 'useed') as $key) {
    $_SESSION[$key] = $_SESSION['userprefs'][$key];
}

// get parameter values based on query string / JWT values
$qsid = intval($QS['id']);

if (isset($QS['jssubmit'])) {
    $jssubmit = $QS['jssubmit'];
} else {
    $jssubmit = $issigned;
}
if (isset($QS['showans'])) {
    $showans = $QS['showans'];
} else {
    $showans = !$issigned;
}
if (isset($QS['showhints'])) {
    $showhints = $QS['showhints'];
} else {
    $showhints = 3;
}

if (isset($QS['maxtries'])) {
    $maxtries = $QS['maxtries'];
} else {
    $maxtries = 0;
}
if (isset($QS['showscoredonsubmit'])) {
    $showscoredonsubmit = $QS['showscoredonsubmit'];
} else {
    $showscoredonsubmit = !$issigned;
}
$hidescoremarkers = !$showscoredonsubmit;
if (isset($QS['showscored'])) {
  $hidescoremarkers = false;
}
if (isset($QS['hidescoremarkers'])) {
    $hidescoremarkers = $QS['hidescoremarkers'];
}
if (isset($QS['allowregen'])) {
    $allowregen = $QS['allowregen'];
} else {
    $allowregen = !$issigned;
}
if (isset($QS['submitall'])) {
    $submitall = $QS['submitall'];
} else {
    $submitall = $issigned;
}

// defaults
$eqnhelper = 4;
$useeqnhelper = 4;
$showtips = 2;

// load question data and load/set state
$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id=:id");
$stm->execute(array(':id' => $qsid));
$line = $stm->fetch(PDO::FETCH_ASSOC);

$a2 = new AssessStandalone($DBH);
$a2->setQuestionData($line['id'], $line);

if (isset($_POST['state'])) {
    $state = json_decode(json_encode(JWT::decode($_POST['state'], $statesecret)), true);
    $seed = $state['seeds'][$qn];
    if (!$issigned) {
        $seed = ($seed%10000) + 10000;
    }
} else {
    $seed = rand(0, 9999);
    if (!$issigned) {
        $seed += 10000;
    }
    $state = array(
        'seeds' => array($qn => $seed),
        'qsid' => array($qn => $qsid),
        'stuanswers' => array(),
        'stuanswersval' => array(),
        'scorenonzero' => array(($qn + 1) => false),
        'scoreiscorrect' => array(($qn + 1) => false),
        'partattemptn' => array($qn => array()),
        'rawscores' => array($qn => array()),
        'jssubmit' => $jssubmit,
        'showans' => $showans,
        'showhints' => $showhints,
        'showscoredonsubmit' => $showscoredonsubmit,
        'hidescoremarkers' => $hidescoremarkers,
        'allowregen' => $allowregen,
        'maxtries' => $maxtries,
        'auth' => $QS['auth']
    );
}

if (!empty($_POST['regen']) && !$issigned) {
    $seed = rand(0, 9999) + 10000;
    $state['seeds'][$qn] = $seed;
    unset($state['stuanswers'][$qn+1]);
    unset($state['stuanswersval'][$qn+1]);
    $state['scorenonzero'][$qn+1] = false;
    $state['scoreiscorrect'][$qn+1] = false;
    $state['partattemptn'][$qn] = array();
    $state['rawscores'][$qn] = array();
}

$a2->setState($state);

if (isset($_POST['toscoreqn'])) {
    $toscoreqn = json_decode($_POST['toscoreqn'], true);
    $parts_to_score = array();
    if (isset($toscoreqn[$qn])) {
      foreach ($toscoreqn[$qn] as $pn) {
        $parts_to_score[$pn] = true;
      };
    }
    $res = $a2->scoreQuestion($qn, $parts_to_score);
    $jwtcontents = array(
        'id' => $qsid,
        'score' => round(array_sum($res['scores']),2),
        'raw' => $res['raw'],
        'allans' => $res['allans'],
        'errors' => $res['errors'],
        'state' => JWT::encode($a2->getState(), $statesecret)
    );
    $out = array('jwt'=>JWT::encode($jwtcontents, $QS['auth']));

    if ($showscoredonsubmit || !$res['allans']) {
        $disp = $a2->displayQuestion($qn, [
            'showans' => $showans,
            'showhints' => $showhints,
            'hidescoremarkers' => $hidescoremarkers,
            'maxtries' => $maxtries
          ]);
        $out['disp'] = $disp;
    }
    echo json_encode($out);
    exit;
}

$disp = $a2->displayQuestion($qn, [
    'showhints' => $showhints,
    'hidescoremarkers' => $hidescoremarkers,
    'maxtries' => $maxtries
]);
// force submitall
if ($submitall) {
    $disp['jsparams']['submitall'] = 1;
}

// if ajax load of question, return values now
if (isset($_POST['ajax'])) {
    $out = array(
        'state' => JWT::encode($a2->getState(), $statesecret),
        'disp' => $disp
    );
    echo json_encode($out);
    exit;
}

if (isset($_GET['frame_id'])) {
    $frameid = preg_replace('/[^\w:.-]/', '', $_GET['frame_id']);
} else {
    $frameid = "embedq2-" . $qsid;
}
if (isset($_GET['theme'])) {
    $theme = preg_replace('/\W/', '', $_GET['theme']);
    $_SESSION['coursetheme'] = $theme . '.css';
}

$lastupdate = '20200422';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="' . $imasroot . '/assess2/vue/css/index.css?v=' . $lastupdate . '" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="' . $imasroot . '/assess2/vue/css/chunk-common.css?v=' . $lastupdate . '" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="' . $imasroot . '/assess2/print.css?v=' . $lastupdate . '" media="print">';
$placeinhead .= '<script src="' . $imasroot . '/mathquill/mathquill.min.js?v=022720" type="text/javascript"></script>';
if (!empty($CFG['assess2-use-vue-dev'])) {
    $placeinhead .= '<script src="' . $imasroot . '/javascript/drawing.js?v=041920" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $imasroot . '/javascript/AMhelpers2.js?v=052120" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $imasroot . '/javascript/eqntips.js?v=041920" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $imasroot . '/javascript/mathjs.js?v=041920" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $imasroot . '/mathquill/AMtoMQ.js?v=052120" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $imasroot . '/mathquill/mqeditor.js?v=041920" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $imasroot . '/mathquill/mqedlayout.js?v=041920" type="text/javascript"></script>';
} else {
    $placeinhead .= '<script src="' . $imasroot . '/javascript/assess2_min.js?v=052920" type="text/javascript"></script>';
}

$placeinhead .= '<script src="' . $imasroot . '/javascript/assess2supp.js?v=050120" type="text/javascript"></script>';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="' . $imasroot . '/mathquill/mathquill-basic.css">
  <link rel="stylesheet" type="text/css" href="' . $imasroot . '/mathquill/mqeditor.css">';

// setup resize message sender
$placeinhead .= '<script type="text/javascript">
  var frame_id = "' . $frameid . '";
  var qsid = '.$qsid.';
  var thisqn = '.$qn.';
  function sendresizemsg() {
   if(inIframe()){
      var default_height = Math.max(
        document.body.scrollHeight, document.body.offsetHeight)+20;
        console.log(default_height);
      window.parent.postMessage( JSON.stringify({
        subject: "lti.frameResize",
        height: default_height,
        iframe_resize_id: "' . $frameid . '",
        element_id: "' . $frameid . '",
        frame_id: "' . $frameid . '"
      }), "*");
   }
  }

  if (mathRenderer == "Katex") {
     window.katexDoneCallback = sendresizemsg;
  } else if (typeof MathJax != "undefined") {
      MathJax.Hub.Queue(function () {
          sendresizemsg();
      });
  } else {
      $(function() {
          sendresizemsg();
      });
  }
  </script>
  <style>
  body { margin: 0;}
  .question {
      margin-top: 0 !important;
  }
  .questionpane {
    margin-top: 0 !important;
    }
  #mqe-fb-spacer {
      height: 0 !important;
  }
  </style>';
if ($_SESSION['mathdisp'] == 1 || $_SESSION['mathdisp'] == 3) {
    //in case MathJax isn't loaded yet
    $placeinhead .= '<script type="text/x-mathjax-config">
      MathJax.Hub.Queue(function () {
          sendresizemsg();
      });
  </script>';
}
$flexwidth = true; //tells header to use non _fw stylesheet
$nologo = true;
require "./header.php";

echo '<div><ul id="errorslist" style="display:none" class="small"></ul></div>';
echo '<div class="questionwrap">';
if (!$jssubmit) {
    echo '<div id="results'.$qn.'"></div>';
}
echo '<div class="questionpane">';
echo '<div class="question" id="questionwrap'.$qn.'">';
echo '</div></div>';
if (!$jssubmit) {
    echo '<p>';
    echo '<button type=button onclick="submitq('.$qn.')" class="primary">'._("Submit").'</button>';
    if ($allowregen) {
        echo ' <button type=button onclick="regenq('.$qn.')" class="secondary">'._('Try a similar question').'</button>';
    }
    echo '</p>';
}
echo '</div>';
echo '<input type=hidden name=toscoreqn id=toscoreqn value=""/>';
echo '<input type=hidden name=state id=state value="'.Sanitize::encodeStringForDisplay(JWT::encode($a2->getState(), $statesecret)).'" />';

echo '<script>
    $(function() {
        showandinit('.$qn.','.json_encode($disp).');
    });
    </script>';

if ($jssubmit) {
    echo '<div style="height:200px">&nbsp;</div>';
} else {
    echo '<div style="height:150px">&nbsp;</div>';
}


$placeinfooter = '<div id="ehdd" class="ehdd" style="display:none;">
  <span id="ehddtext"></span>
  <span onclick="showeh(curehdd);" style="cursor:pointer;">'._('[more..]').'</span>
</div>
<div id="eh" class="eh"></div>';
require "./footer.php";