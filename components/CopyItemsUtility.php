<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 9/7/15
 * Time: 12:14 PM
 */
namespace app\components;
use app\models\Assessments;
use app\models\Forums;
use app\models\GbItems;
use app\models\InlineText;
use app\models\InstrFiles;
use app\models\Items;
use app\models\LinkedText;
use app\models\Questions;
use app\models\Rubrics;
use \yii\base\Component;
use app\components\AppUtility;
use app\components\AppConstant;

class CopyItemsUtility extends Component
{

public  static function copyitem($itemid, $gbcats, $params,$sethidden = false)
{
    global $cid, $reqscoretrack, $assessnewid, $qrubrictrack, $frubrictrack, $copystickyposts, $userid, $exttooltrack, $outcomes, $removewithdrawn, $replacebyarr;
    global $posttoforumtrack, $forumtrack;
    if (!isset($copystickyposts)) {
        $copystickyposts = false;
    }
    if ($gbcats === false) {
        $gbcats = array();
    }
    if (!isset($outcomes)) {
        $outcomes = array();
    }
    if (strlen($params['append']) > 0 && $params['append']{0} != ' ') {
        $params['append'] = ' ' . $params['append'];
    }
    $now = time();
    $query = Items::getByTypeId($itemid);
    $itemtype = $query['itemtype'];
    $typeid = $query['typeid'];
    if ($itemtype == "InlineText") {
        $inlineTextData = InlineText::getById($typeid);
        $row = array(
            courseid => $inlineTextData['courseid'],
            title => $inlineTextData['title'],
            text => $inlineTextData['text'],
            startdate => $inlineTextData['startdate'],
            enddate => $inlineTextData['enddate'],
            avail => $inlineTextData['avail'],
            oncal => $inlineTextData['oncal'],
            caltag => $inlineTextData['caltag'],
            isplaylist => $inlineTextData['isplaylist'],
            fileorder => $inlineTextData['fileorder'],
        );
        if ($sethidden) {
            $row['avail'] = 0;
        }
        $row['title'] .= stripslashes($params['append']);
        $fileorder = $row['fileorder'];
        array_pop($row);
        $inlineText = new InlineText();
        $newtypeid = $inlineText->saveChanges($row);
        $instrFiles = InstrFiles::getAllData($typeid);
        $addedfiles = array();
        foreach ($instrFiles as $singleData){
            $curid = $singleData['id'];
            array_pop($singleData);
            $singleData = "'" . implode("','", AppUtility::addslashes_deep($singleData)) . "'";
            $instrFile = new InstrFiles();
            $newInstrFileId = $instrFile->saveFile($singleData, $newtypeid);
            $addedfiles[$curid] = $newInstrFileId;
        }
        if (count($addedfiles) > 0) {
            $addedfilelist = array();
            foreach (explode(',', $fileorder) as $fid) {
                $addedfilelist[] = $addedfiles[$fid];
            }
            $addedfilelist = implode(',', $addedfilelist);
            InlineText::setFileOrder($newtypeid,$addedfilelist);
        }
    } else if ($itemtype == "LinkedText") {
        $query = LinkedText::getById($typeid);
        $istool = (substr($query['text'], 0, 8) == 'exttool:');
        if ($istool) {
            $tool = explode('~~', substr($query['text'], 8));
            if (isset($tool[3]) && isset($gbcats[$tool[3]])) {
                $tool[3] = $gbcats[$tool[3]];
            } else if ($params['ctc'] != $cid) {
                $tool[3] = 0;
            }
            $query['text'] = 'exttool:' . implode('~~', $tool);
        }
        if ($sethidden) {
            $query['avail'] = 0;
        }
        $query['title'] .= stripslashes($params['append']);
        if ($query['outcomes'] != '') {
            $curoutcomes = explode(',', $query['outcomes']);
            $newoutcomes = array();
            foreach ($curoutcomes as $o) {
                if (isset($outcomes[$o])) {
                    $newoutcomes[] = $outcomes[$o];
                }
            }
            $query['outcomes'] = implode(',', $newoutcomes);
        }
        $linkText = new LinkedText();
        $newtypeid = $linkText->addLinkedText($query);
        if ($istool) {
            $exttooltrack[$newtypeid] = intval($tool[0]);
        }
    } else if ($itemtype == "Forum") {

    } else if ($itemtype == "Wiki") {
    } else if ($itemtype == "Assessment") {
        $assessmentData = Assessments::getByAssessmentId($typeid);
        if ($sethidden) {
            $assessmentData['avail'] = 0;
        }
        if (isset($gbcats[$assessmentData['gbcategory']])) {
            $assessmentData['gbcategory'] = $gbcats[$assessmentData['gbcategory']];
        } else if ($params['ctc'] != $params['courseId']) {
            $assessmentData['gbcategory'] = 0;
        }
        if (isset($outcomes[$assessmentData['defoutcome']])) {
            $assessmentData['defoutcome'] = $outcomes[$assessmentData['defoutcome']];
        } else {
            $assessmentData['defoutcome'] = 0;
        }
        if ($assessmentData['ancestors'] == '') {
            $assessmentData['ancestors'] = $typeid;
        } else {
            $assessmentData['ancestors'] = $typeid . ',' . $assessmentData['ancestors'];
        }
        if ($params['ctc'] != $params['courseId']) {
            $forumtopostto = $assessmentData['posttoforum'];
            unset($assessmentData['posttoforum']);
        }
        $reqscoreaid = $assessmentData['reqscoreaid'];
        unset($assessmentData['reqscoreaid']);
        $assessmentData['name'] .= stripslashes($params['append']);
        $assessment = new Assessments();
        $newtypeid = $assessment->copyAssessment($assessmentData);
        if ($reqscoreaid > 0) {
            $reqscoretrack[$newtypeid] = $reqscoreaid;
        }
        if ($params['ctc'] != $cid && $forumtopostto > 0) {
            $posttoforumtrack[$newtypeid] = $forumtopostto;
        }
        $assessnewid[$typeid] = $newtypeid;
        $thiswithdrawn = array();

        $query = Assessments::getByAssessmentId($typeid);
        $itemorder = explode(',',$query['itemorder']);
        if ($itemorder != '') {
            $query = Questions::getByItemOrder($itemorder);
            $inss = array();
            $insorder = array();
            foreach ($query as $singleData){
                if ($singleData['withdrawn'] > 0 && $removewithdrawn) {
                    $thiswithdrawn[$singleData['id']] = 1;
                    continue;
                }
                if (isset($replacebyarr[$singleData['questionsetid']])) {
                    $singleData['questionsetid'] = $replacebyarr[$singleData['questionsetid']];
                }
                if (is_numeric($singleData['category'])) {
                    if (isset($outcomes[$singleData['category']])) {
                        $singleData['category'] = $outcomes[$singleData['category']];
                    } else {
                        $singleData['category'] = 0;
                    }
                }
                $rubric[$singleData['id']] = $singleData['rubric'];
                $insorder[] = $singleData['id'];
                array_push($inss,$singleData);
            }
            $idtoorder = array_flip($insorder);
            if (count($inss) > 0) {
                $question = new Questions();
                $questionIdArray =array();
                foreach($inss as $in){
                    $firstnewid = $question->addQuestions($in);
                    array_push($questionIdArray,$firstnewid);
                }
                $aitems = $itemorder;
                $newaitems = array();
                foreach ($aitems as $k => $aitem) {
                    if (strpos($aitem, '~') === FALSE) {
                        if (isset($thiswithdrawn[$aitem])) {
                            continue;
                        }
                        if ($rubric[$aitem] != 0) {
                            $qrubrictrack[$firstnewid + $idtoorder[$aitem]] = $rubric[$aitem];
                        }
                        $newaitems[] = $firstnewid + $idtoorder[$aitem];
                    } else {
                        $sub = explode('~', $aitem);
                        $newsub = array();
                        $front = 0;
                        if (strpos($sub[0], '|') !== false) { //true except for bwards compat
                            $newsub[] = array_shift($sub);
                            $front = 1;
                        }
                        foreach ($sub as $subi) {
                            if (isset($thiswithdrawn[$subi])) {
                                continue;
                            }
                            if ($rubric[$subi] != 0) {
                                $qrubrictrack[$firstnewid + $idtoorder[$subi]] = $rubric[$subi];
                            }
                            $newsub[] = $firstnewid + $idtoorder[$subi];
                        }
                        if (count($newsub) == $front) {

                        } else if (count($newsub) == $front + 1) {
                            $newaitems[] = $newsub[$front];
                        } else {
                            $newaitems[] = implode('~', $newsub);
                        }
                    }
                }
                $newitemorder = implode(',', $newaitems);
                Assessments::setItemOrder($newitemorder,$newtypeid);
            }
        }
    } else if ($itemtype == "Calendar") {
    }
    $items = new Items();
    $newItemId = $items->saveItems($params['courseId'],$newtypeid,$itemtype);
    return $newItemId;
}

public  static function copysub($items, $parent, &$addtoarr, $gbcats, $sethidden = false)
{
    global $checked, $blockcnt;
    foreach ($items as $k => $item) {
        if (is_array($item)) {
            if (array_search($parent . '-' . ($k + 1), $checked) !== FALSE) { //copy block
                $newblock = array();
                $newblock['name'] = $item['name'] . stripslashes($_POST['append']);
                $newblock['id'] = $blockcnt;
                $blockcnt++;
                $newblock['startdate'] = $item['startdate'];
                $newblock['enddate'] = $item['enddate'];
                $newblock['avail'] = $sethidden ? 0 : $item['avail'];
                $newblock['SH'] = $item['SH'];
                $newblock['colors'] = $item['colors'];
                $newblock['public'] = $item['public'];
                $newblock['fixedheight'] = $item['fixedheight'];
                $newblock['grouplimit'] = $item['grouplimit'];
                $newblock['items'] = array();
                if (count($item['items']) > 0) {
                    copysub($item['items'], $parent . '-' . ($k + 1), $newblock['items'], $gbcats, $sethidden);
                }
                $addtoarr[] = $newblock;
            } else {
                if (count($item['items']) > 0) {
                    copysub($item['items'], $parent . '-' . ($k + 1), $addtoarr, $gbcats, $sethidden);
                }
            }
        } else {
            if (array_search($item, $checked) !== FALSE) {
                $addtoarr[] = copyitem($item, $gbcats, $sethidden);
            }
        }
    }

}

public  static function doaftercopy($sourcecid)
{
    global $cid, $reqscoretrack, $assessnewid, $forumtrack, $posttoforumtrack;
    if (intval($cid) == intval($sourcecid)) {
        $samecourse = true;
    } else {
        $samecourse = false;
    }
    //update reqscoreaids if possible.
    if (count($reqscoretrack) > 0) {
        foreach ($reqscoretrack as $newid => $oldreqaid) {
            //is old reqscoreaid in copied list?
            if (isset($assessnewid[$oldreqaid])) {
                $query = "UPDATE imas_assessments SET reqscoreaid='{$assessnewid[$oldreqaid]}' WHERE id='$newid'";
                mysql_query($query) or die("Query failed : $query" . mysql_error());
            } else if (!$samecourse) {
                $query = "UPDATE imas_assessments SET reqscore=0 WHERE id='$newid'";
                mysql_query($query) or die("Query failed : $query" . mysql_error());
            }
        }
    }
    if (count($posttoforumtrack) > 0) {
        foreach ($posttoforumtrack as $newaid => $oldforumid) {
            if (isset($forumtrack[$oldforumid])) {
                $query = "UPDATE imas_assessments SET posttoforum='{$forumtrack[$oldforumid]}' WHERE id='$newaid'";
                mysql_query($query) or die("Query failed : $query" . mysql_error());
            } else {
                $query = "UPDATE imas_assessments SET posttoforum=0 WHERE id='$newaid'";
                mysql_query($query) or die("Query failed : $query" . mysql_error());
            }
        }
    }
    if (!$samecourse) {
        handleextoolcopy($sourcecid);
    }
}

public  static function copyallsub($items, $parent, &$addtoarr, $gbcats, $sethidden = false)
{
    global $blockcnt, $reqscoretrack, $assessnewid;;
    if (strlen($_POST['append']) > 0 && $_POST['append']{0} != ' ') {
        $_POST['append'] = ' ' . $_POST['append'];
    }
    foreach ($items as $k => $item) {
        if (is_array($item)) {
            $newblock = array();
            $newblock['name'] = $item['name'] . stripslashes($_POST['append']);
            $newblock['id'] = $blockcnt;
            $blockcnt++;
            $newblock['startdate'] = $item['startdate'];
            $newblock['enddate'] = $item['enddate'];
            $newblock['avail'] = $sethidden ? 0 : $item['avail'];
            $newblock['SH'] = $item['SH'];
            $newblock['colors'] = $item['colors'];
            $newblock['public'] = $item['public'];
            $newblock['fixedheight'] = $item['fixedheight'];
            $newblock['grouplimit'] = $item['grouplimit'];
            $newblock['items'] = array();
            if (count($item['items']) > 0) {
                copyallsub($item['items'], $parent . '-' . ($k + 1), $newblock['items'], $gbcats, $sethidden);
            }
            $addtoarr[] = $newblock;
        } else {
            if ($item != null && $item != 0) {
                $addtoarr[] = copyitem($item, $gbcats, $sethidden);
            }
        }
    }

}
public  static function getiteminfo($itemid)
{
    $query = "SELECT itemtype,typeid FROM imas_items WHERE id='$itemid'";
    $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
    if (mysql_num_rows($result) == 0) {
        echo "Uh oh, item #$itemid doesn't appear to exist";
    }
    $itemtype = mysql_result($result, 0, 0);
    $typeid = mysql_result($result, 0, 1);
    if ($itemtype === 'Calendar') {
        return array($itemtype, 'Calendar', '');
    }
    switch ($itemtype) {
        case ($itemtype === "InlineText"):
            $query = "SELECT title,text FROM imas_inlinetext WHERE id=$typeid";
            break;
        case ($itemtype === "LinkedText"):
            $query = "SELECT title,summary FROM imas_linkedtext WHERE id=$typeid";
            break;
        case ($itemtype === "Forum"):
            $query = "SELECT name,description FROM imas_forums WHERE id=$typeid";
            break;
        case ($itemtype === "Assessment"):
            $query = "SELECT name,summary FROM imas_assessments WHERE id=$typeid";
            break;
        case ($itemtype === "Wiki"):
            $query = "SELECT name,description FROM imas_wikis WHERE id=$typeid";
            break;
    }
    $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
    $name = mysql_result($result, 0, 0);
    $summary = mysql_result($result, 0, 1);
    return array($itemtype, $name, $summary, $typeid);
}

public  static function getsubinfo($items, $parent, $pre, $itemtypelimit = false, $spacer = '|&nbsp;&nbsp;')
{
    global $ids, $types, $names, $sums, $parents, $gitypeids, $prespace, $CFG;
    if (!isset($gitypeids)) {
        $gitypeids = array();
    }

    foreach ($items as $k => $item) {
        if (is_array($item)) {
            $ids[] = $parent . '-' . ($k + 1);
            $types[] = "Block";
            $names[] = stripslashes($item['name']);
            $prespace[] = $pre;
            $parents[] = $parent;
            $gitypeids[] = '';
            $sums[] = '';
            if (count($item['items']) > 0) {
                getsubinfo($item['items'], $parent . '-' . ($k + 1), $pre . $spacer, $itemtypelimit, $spacer);
            }
        } else {
            if ($item == null || $item == '') {
                continue;
            }
            $arr = getiteminfo($item);
            if ($itemtypelimit !== false && $arr[0] != $itemtypelimit) {
                continue;
            }
            $ids[] = $item;
            $parents[] = $parent;
            $types[] = $arr[0];
            $names[] = $arr[1];
            $prespace[] = $pre;
            $gitypeids[] = $arr[3];
            $arr[2] = strip_tags($arr[2]);
            if (strlen($arr[2]) > 100) {
                $arr[2] = substr($arr[2], 0, 97) . '...';
            }
            $sums[] = $arr[2];
        }
    }
}

public  static function buildexistblocks($items, $parent, $pre = '')
{
    global $existblocks;
    foreach ($items as $k => $item) {
        if (is_array($item)) {
            $existblocks[$parent . '-' . ($k + 1)] = $pre . $item['name'];
            if (count($item['items']) > 0) {
                buildexistblocks($item['items'], $parent . '-' . ($k + 1), $pre . '&nbsp;&nbsp;');
            }
        }
    }
}

public static function copyrubrics($offlinerubrics = array())
{
    global $userid, $groupid, $qrubrictrack, $frubrictrack;
    if (count($qrubrictrack) == 0 && count($frubrictrack) == 0 && count($offlinerubrics) == 0) {
        return;
    }
    $list = implode(',', array_merge($qrubrictrack, $frubrictrack, $offlinerubrics));

    //handle rubrics which I already have access to
    $rubricData = Rubrics::getByUserIdAndGroupId($userid,$groupid,$list);
    foreach($rubricData as $singleData){
        $qfound = array_keys($qrubrictrack, $singleData['id']);
        if (count($qfound) > 0) {
            foreach ($qfound as $qid) {
                Questions::setRubric($qid,$singleData['id']);
            }
        }
        $ofound = array_keys($offlinerubrics, $singleData['id']);
        if (count($ofound) > 0) {
            foreach ($ofound as $oid) {
                GbItems::setRubric($oid,$singleData['id']);
            }
        }
        $ffound = array_keys($frubrictrack, $singleData['id']);
        if (count($ffound) > 0) {
            foreach ($ffound as $fid) {
                Forums::setRubric($fid,$singleData['id']);
            }
        }
    }

    //handle rubrics which I don't already have access to - need to copy them
    $rubricData = Rubrics::getByUserIdAndGroupIdAndList($userid,$groupid,$list);
    foreach($rubricData as $singleData){
        //echo "handing {$row[0]} which I don't have access to<br/>";
        $rubric = Rubrics::getById($singleData['id']);
        $rubrow = AppUtility::addslashes_deep($rubric);
        $rubricItems = Rubrics::getByUserIdAndGroupIdAndRubric($rubrow[2],$userid,$groupid);

        if ($rubricItems > 0) {
            $newid = $rubricItems['id'];
        } else {
            $rub = "'" . implode("','", $rubrow) . "'";
            $temp = new Rubrics();
            $rubricId = $temp->createNewEntry($userid,-1,$rub);
            $newid = $rubricId;
        }
        $qfound = array_keys($qrubrictrack, $singleData['id']);
        if (count($qfound) > 0) {
            foreach ($qfound as $qid) {
                Questions::setRubric($qid,$newid);
            }
        }
        $ffound = array_keys($frubrictrack, $singleData['id']);
        if (count($ffound) > 0) {
            foreach ($ffound as $fid) {
                Forums::setRubric($fid,$newid);
            }
        }
        $ofound = array_keys($offlinerubrics, $singleData['id']);
        if (count($ofound) > 0) {
            foreach ($ofound as $oid) {
                GbItems::setRubric($oid,$newid);
            }
        }
    }
}

public  static function handleextoolcopy($sourcecid)
{
    //assumes this is a copy into a different course
    global $cid, $userid, $groupid, $exttooltrack;
    if (count($exttooltrack) == 0) {
        return;
    }
    //$exttooltrack is linked text id => tool id
    $toolmap = array();
    $query = "SELECT id FROM imas_teachers WHERE courseid='$sourcecid' AND userid='$userid'";
    $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
    if (mysql_num_rows($result) > 0) {
        $oktocopycoursetools = true;
    }
    $toolidlist = implode(',', $exttooltrack);
    $query = "SELECT id,courseid,groupid,name,url,ltikey,secret,custom,privacy FROM imas_external_tools ";
    $query .= "WHERE id IN ($toolidlist)";
    $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
    while ($row = mysql_fetch_row($result)) {
        $doremap = false;
        if (!isset($toolmap[$row[0]])) {
            //try url matching of existing tools in the destination course
            $query = "SELECT id FROM imas_external_tools WHERE url='" . addslashes($row[4]) . "' AND courseid='$cid'";
            $res = mysql_query($query) or die("Query failed : $query " . mysql_error());
            if (mysql_num_rows($res) > 0) {
                $toolmap[$row[0]] = mysql_result($res, 0, 0);
            }
        }
        if (isset($toolmap[$row[0]])) {
            //already have remapped this tool - need to update linkedtext item
            $doremap = true;
        } else if ($row[1] > 0 && $oktocopycoursetools) {
            //do copy
            $rowsub = array_slice($row, 3);
            $rowsub = AppUtility::addslashes_deep($rowsub);
            $rowlist = implode("','", $rowsub);
            $query = "INSERT INTO imas_external_tools (courseid,groupid,name,url,ltikey,secret,custom,privacy) ";
            $query .= "VALUES ('$cid','$groupid','$rowlist')";
            mysql_query($query) or die("Query failed : " . mysql_error());
            $toolmap[$row[0]] = mysql_insert_id();
            $doremap = true;
        } else if ($row[1] == 0 && ($row[2] == 0 || $row[2] == $groupid)) {
            //no need to copy anything - tool will just work
        } else {
            //not OK to copy; must disable tool in linked text item
            $toupdate = implode(",", array_keys($exttooltrack, $row[0]));
            $query = "UPDATE imas_linkedtext SET text='<p>Unable to copy tool</p>' WHERE id IN ($toupdate)";
            mysql_query($query) or die("Query failed : " . mysql_error());
        }
        if ($doremap) {
            //update the linkedtext item with the new tool id
            $toupdate = implode(",", array_keys($exttooltrack, $row[0]));
            $query = "SELECT id,text FROM imas_linkedtext WHERE id IN ($toupdate)";
            $res = mysql_query($query) or die("Query failed : " . mysql_error());
            while ($r = mysql_fetch_row($res)) {
                $text = str_replace('exttool:' . $row[0] . '~~', 'exttool:' . $toolmap[$row[0]] . '~~', $r[1]);
                $query = "UPDATE imas_linkedtext SET text='" . addslashes($text) . "' WHERE id={$r[0]}";
                mysql_query($query) or die("Query failed : " . mysql_error());
            }
        }
    }
}
}

