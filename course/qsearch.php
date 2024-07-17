<?php 

require_once '../init.php';

if (!isset($teacherid) && !($_GET['cid'] == 'admin' && $myrights>74)) {
    echo 'You do not have access to this page';
    exit;
}

if (isset($_GET['aid'])) {
    $searchcontext = '';
    $aid = intval($_GET['aid']);
} else {
    $searchcontext = 'c';
    $aid = Sanitize::courseId($_GET['cid']);
}


$libs = explode(',', $_POST['libs']);
$_SESSION['searchtype'.$searchcontext.$aid] = $_POST['searchtype'];
$_SESSION['searchin'.$searchcontext.$aid] = $libs;
$_SESSION['lastsearch'.$searchcontext.$aid] = $_POST['search'];
if ($_POST['searchtype'] == 'libs') {
    $_SESSION['lastsearchlibs'.$searchcontext.$aid] = $_POST['libs'];
} else {
    unset($_SESSION['lastsearchlibs'.$searchcontext.$aid]);
}

header('Content-Type: application/json; charset=utf-8');

if (isset($_GET['getassess'])) {
    // get assessment list 
    $stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
    $stm->execute(array(':id'=>$cid));
    $items = unserialize($stm->fetchColumn(0));

    $itemassoc = array();
    $query = "SELECT ii.id AS itemid,ia.id,ia.name,ia.summary FROM imas_items AS ii JOIN imas_assessments AS ia ";
    $query .= "ON ii.typeid=ia.id AND ii.itemtype='Assessment' WHERE ii.courseid=:courseid AND ia.id<>:aid";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':courseid'=>$cid, ':aid'=>($searchcontext=='c'?0:$aid)));
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $itemassoc[$row['itemid']] = $row;
    }

    $i=0;
    $page_assessmentList = array();
    function addtoassessmentlist($items) {
        global $page_assessmentList, $itemassoc, $i;
        foreach ($items as $item) {
            if (is_array($item)) {
                addtoassessmentlist($item['items']);
            } else if (isset($itemassoc[$item])) {
                $page_assessmentList[$i]['id'] = $itemassoc[$item]['id'];
                $page_assessmentList[$i]['name'] = $itemassoc[$item]['name'];
                $itemassoc[$item]['summary'] = strip_tags($itemassoc[$item]['summary']);
                if (strlen($itemassoc[$item]['summary'])>100) {
                    $itemassoc[$item]['summary'] = substr($itemassoc[$item]['summary'],0,97).'...';
                }
                $page_assessmentList[$i]['summary'] = $itemassoc[$item]['summary'];
                $i++;
            }
        }
    }
    addtoassessmentlist($items);
    echo json_encode($page_assessmentList);
    exit;
}

require_once '../includes/questionsearch.php';

$search = parseSearchString($_POST['search']);
$offset = intval($_POST['offset']);

$options = [];
if (!empty($search['unused']) && $searchcontext!='c') {
    // populate existing if unused is set
    $query = 'SELECT iqs.id FROM imas_questionset AS iqs
        JOIN imas_questions AS iq ON iq.questionsetid=iqs.id
        WHERE iq.assessmentid=?';
    $stm = $DBH->prepare($query);
    $stm->execute(array($aid));
    $options['existing'] = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
}
if ($searchcontext=='c') {
    $options['includelastmod'] = 1;
}
if ($_GET['cid'] == 'admin') {
    $options['includeowner'] = 1;
    if ($myrights == 100) {
        $options['isadmin'] = true;
    } else if ($myrights > 74) {
        $options['isgroupadmin'] = $groupid;
    }
}

$res = searchQuestions($search, $userid, $_POST['searchtype'], $libs, $options, $offset);

echo json_encode($res, JSON_INVALID_UTF8_IGNORE);

