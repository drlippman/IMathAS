<?php

// multiembedq2.php: Embed one or more questions via an iframe
// Operates without requiring login
// (c) 2020 David Lippman

$init_skip_csrfp = true;
require "./init_without_validate.php";

require_once './assess2/AssessStandalone.php';
require("includes/JWT.php");

$assessver = 2;
$courseUIver = 2;
$assessUIver = 2;
$_SESSION = array();
$inline_choicemap = !empty($CFG['GEN']['choicesalt']) ? $CFG['GEN']['choicesalt'] : 'test';
$statesecret = !empty($CFG['GEN']['embedsecret']) ? $CFG['GEN']['embedsecret'] : 'test';
$cid = 'embedq';
$_SESSION['secsalt'] = "12345";

$issigned = false;
// Get basic settings from JWT or query string
if (isset($_POST['state'])) {
    $state = json_decode(json_encode(JWT::decode($_POST['state'], $statesecret)), true);
    $QS = $state;
    $QS['id'] = $state['qsid'];
} else {
    $QS = $_GET;
}

if (empty($QS['id'])) {
    echo 'Need to supply an id';
    exit;
}
if (!is_array($QS['id'])) {
    $QS['id'] = explode('-', $QS['id']);
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
    } else if (!empty($prefcookie) && isset($prefcookie[$key])) {
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

// defaults
$eqnhelper = 4;
$useeqnhelper = 4;
$showtips = 2;

$numq = count($QS['id']);

$a2 = new AssessStandalone($DBH);

if (isset($_POST['state'])) {
    $state = json_decode(json_encode(JWT::decode($_POST['state'], $statesecret)), true);
} else {
    $seeds = array();
    for($qn=0; $qn<$numq; $qn++) {
        $seeds[$qn] = rand(0, 9999)+10000;
    }
    $state = array(
        'seeds' => $seeds,
        'qsid' => $QS['id'],
        'stuanswers' => array(),
        'stuanswersval' => array(),
        'scorenonzero' => array_fill(1, $numq, false),
        'scoreiscorrect' => array_fill(1, $numq, false),
        'partattemptn' => array_fill(0, $numq, array()),
        'rawscores' => array_fill(0, $numq, array()),
    );
}

if (isset($QS['jssubmit'])) {
    $state['jssubmit'] = $QS['jssubmit'];
} else {
    $state['jssubmit'] = 0;
}

if (isset($QS['showhints'])) {
    $state['showhints'] = $QS['showhints'];
} else {
    $state['showhints'] = 3;
}

if (isset($QS['maxtries'])) {
    $state['maxtries'] = intval($QS['maxtries']);
} else {
    $state['maxtries'] = 0;
}
if (isset($QS['showansafter'])) {
    $state['showansafter'] = $QS['showansafter'];
} else if ($state['maxtries'] > 0) {
    $state['showansafter'] = $state['maxtries'];
} else {
    $state['showansafter'] = 1;
}
if (isset($QS['showscoredonsubmit'])) {
    $state['showscoredonsubmit'] = $QS['showscoredonsubmit'];
} else {
    $state['showscoredonsubmit'] = 1;
}
$state['hidescoremarkers'] = !$state['showscoredonsubmit'];
if (isset($QS['hidescoremarkers'])) {
    $state['hidescoremarkers'] = $QS['hidescoremarkers'];
}
$state['showans'] = 0;

if (isset($QS['allowregen'])) {
    $state['allowregen'] = $QS['allowregen'];
} else {
    $state['allowregen'] = 1;
}
if (isset($QS['submitall'])) {
    $state['submitall'] = $QS['submitall'];
} else {
    $state['submitall'] = 0;
}
if (isset($QS['autoseq'])) {
    $state['autoseq'] = $QS['autoseq'];
} else {
    $state['autoseq'] = 1;
}

if (!empty($_POST['regen'])) {
    $qntoregen = $_POST['regen'];
    $seed = rand(0, 9999) + 10000;
    $state['seeds'][$qntoregen] = $seed;
    unset($state['stuanswers'][$qntoregen+1]);
    unset($state['stuanswersval'][$qntoregenn+1]);
    $state['scorenonzero'][$qntoregen+1] = false;
    $state['scoreiscorrect'][$qntoregen+1] = false;
    $state['partattemptn'][$qntoregen] = array();
    $state['rawscores'][$qntoregen] = array();
}

$a2->setState($state);

if (isset($_POST['toscoreqn'])) {
    $toscoreqn = json_decode($_POST['toscoreqn'], true);
    $qns = array_keys($toscoreqn);
    if (count($qns)>1) {
        echo "Error - can only handle submitting one question at a time";
    }
    $qn = $qns[0];
    $parts_to_score = array();
    if (isset($toscoreqn[$qn])) {
      foreach ($toscoreqn[$qn] as $pn) {
        $parts_to_score[$pn] = true;
      };
    }
    $qsid = $QS['id'][$qn];

    // load question data
    $stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id=:id");
    $stm->execute(array(':id' => $qsid));
    $line = $stm->fetch(PDO::FETCH_ASSOC);
    $a2->setQuestionData($qsid, $line);

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

    if ($state['showscoredonsubmit'] || !$res['allans']) {
        $disp = $a2->displayQuestion($qn);
        $out['disp'] = $disp;
    }
    echo json_encode($out);
    exit;
} else if (isset($_POST['regen'])) {
    $qn = intval($_POST['regen']);
    $qsid = $QS['id'][$qn];

    // clear values 
    $seed = rand(0, 9999) + 10000;
    $state['seeds'][$qn] = $seed;
    unset($state['stuanswers'][$qn+1]);
    unset($state['stuanswersval'][$qn+1]);
    $state['scorenonzero'][$qn+1] = false;
    $state['scoreiscorrect'][$qn+1] = false;
    $state['partattemptn'][$qn] = array();
    $state['rawscores'][$qn] = array();
    $a2->setState($state);
    
    // load question data
    $stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id=:id");
    $stm->execute(array(':id' => $qsid));
    $line = $stm->fetch(PDO::FETCH_ASSOC);
    $a2->setQuestionData($qsid, $line);
    $disp = $a2->displayQuestion($qn);
    $out = array(
        'state' => JWT::encode($a2->getState(), $statesecret),
        'disp' => $disp
    );
    echo json_encode($out);
    exit;
}

// load question data and load/set state
$ph = Sanitize::generateQueryPlaceholders($QS['id']);
$stm = $DBH->prepare("SELECT * FROM imas_questionset WHERE id IN ($ph)");
$stm->execute($QS['id']);
$qsdata = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $qsdata[$row['id']] = $row;
}

$disps = array();

for ($qn=0; $qn < $numq; $qn++) {
    $qsid = $QS['id'][$qn];
    $a2->setQuestionData($qsid, $qsdata[$qsid]);
    $disps[$qn] = $a2->displayQuestion($qn);
    // force submitall
    if ($state['submitall']) {
        $disps[$qn]['jsparams']['submitall'] = 1;
    }
}

if (isset($_GET['frame_id'])) {
    $frameid = preg_replace('/[^\w:.-]/', '', $_GET['frame_id']);
} else {
    $frameid = "embedq2-" . $qsid;
}
if (isset($_GET['theme'])) {
    $theme = preg_replace('/\W/', '', $_GET['theme']);
    $coursetheme = $theme . '.css';
}

$lastupdate = '20200422';
$placeinhead = '<link rel="stylesheet" type="text/css" href="' . $staticroot . '/assess2/vue/css/index.css?v=' . $lastupdate . '" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="' . $staticroot . '/assess2/vue/css/chunk-common.css?v=' . $lastupdate . '" />';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="' . $staticroot . '/assess2/print.css?v=' . $lastupdate . '" media="print">';
$placeinhead .= '<script src="' . $staticroot . '/mathquill/mathquill.min.js?v=022720" type="text/javascript"></script>';
if (!empty($CFG['assess2-use-vue-dev'])) {
    $placeinhead .= '<script src="' . $staticroot . '/javascript/drawing.js?v=041920" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $staticroot . '/javascript/AMhelpers2.js?v=052120" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $staticroot . '/javascript/eqntips.js?v=041920" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $staticroot . '/javascript/mathjs.js?v=041920" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $staticroot . '/mathquill/AMtoMQ.js?v=052120" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $staticroot . '/mathquill/mqeditor.js?v=041920" type="text/javascript"></script>';
    $placeinhead .= '<script src="' . $staticroot . '/mathquill/mqedlayout.js?v=041920" type="text/javascript"></script>';
} else {
    $placeinhead .= '<script src="' . $staticroot . '/javascript/assess2_min.js?v=111520" type="text/javascript"></script>';
}

$placeinhead .= '<script src="' . $staticroot . '/javascript/assess2supp.js?v=041522" type="text/javascript"></script>';
$placeinhead .= '<link rel="stylesheet" type="text/css" href="' . $staticroot . '/mathquill/mathquill-basic.css">
  <link rel="stylesheet" type="text/css" href="' . $staticroot . '/mathquill/mqeditor.css">';

// setup resize message sender
$placeinhead .= '<script type="text/javascript">
  var frame_id = "' . $frameid . '";
  function sendresizemsg() {
   if(inIframe()){
      var default_height = Math.max(
        document.body.scrollHeight, document.body.offsetHeight)+20;
      var wrap_height = default_height - document.getElementById("embedspacer").offsetHeight;
      window.parent.postMessage( JSON.stringify({
        subject: "lti.frameResize",
        height: default_height,
        wrapheight: wrap_height,
        iframe_resize_id: "' . $frameid . '",
        element_id: "' . $frameid . '",
        frame_id: "' . $frameid . '"
      }), "*");
   }
  }
  $(function() {
    $(document).on("mqeditor:show", function() {
      $("#embedspacer").show();
      sendresizemsg();
    });
    $(document).on("mqeditor:hide", function() {
      $("#embedspacer").hide();
      sendresizemsg();
    });
  });
  if (mathRenderer == "Katex") {
     window.katexDoneCallback = sendresizemsg;
  } else if (typeof MathJax != "undefined") {
    if (MathJax.startup) {
        MathJax.startup.promise = MathJax.startup.promise.then(sendLTIresizemsg);
    } else if (MathJax.Hub) {
        MathJax.Hub.Queue(function () {
            sendresizemsg();
        });
    } 
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
if ($_SESSION['mathdisp']==1 || $_SESSION['mathdisp']==3) {
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
for ($qn=0; $qn < $numq; $qn++) {
    echo '<div class="questionwrap">';
    if (!$state['jssubmit']) {
        echo '<div id="results'.$qn.'"></div>';
    }
    echo '<div class="questionpane">';
    echo '<div class="question" id="questionwrap'.$qn.'">';
    echo '</div></div>';
    if (!$state['jssubmit']) {
        echo '<p>';
        echo '<button type=button onclick="submitq('.$qn.')" class="primary">'._("Submit").'</button>';
        if ($state['allowregen']) {
            echo ' <button type=button onclick="regenq('.$qn.')" class="secondary">'._('Try a similar question').'</button>';
        }
        echo '</p>';
    }
    echo '</div>';

    echo '<script>
        $(function() {
            showandinit('.$qn.','.json_encode($disps[$qn]).');
        });
        </script>';
}
echo '<input type=hidden name=toscoreqn id=toscoreqn value=""/>';
echo '<input type=hidden name=state id=state value="'.Sanitize::encodeStringForDisplay(JWT::encode($a2->getState(), $statesecret)).'" />';

echo '<div class="mce-content-body" style="text-align:right;font-size:70%;margin-right:5px;"><a style="color:#666" target="_blank" href="course/showlicense.php?id='.Sanitize::encodeUrlParam(implode('-', $QS['id'])).'">'._('License').'</a></div>';


if ($state['jssubmit']) {
    echo '<div id="embedspacer" style="display:none;height:200px">&nbsp;</div>';
} else {
    echo '<div id="embedspacer" style="display:none;height:150px">&nbsp;</div>';
}

$placeinfooter = '<div id="ehdd" class="ehdd" style="display:none;">
  <span id="ehddtext"></span>
  <span onclick="showeh(curehdd);" style="cursor:pointer;">'._('[more..]').'</span>
</div>
<div id="eh" class="eh"></div>';
require "./footer.php";
