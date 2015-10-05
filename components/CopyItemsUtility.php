<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 9/7/15
 * Time: 12:14 PM
 */
namespace app\components;
use app\models\Assessments;
use app\models\ExternalTools;
use app\models\ForumPosts;
use app\models\Forums;
use app\models\ForumThread;
use app\models\ForumView;
use app\models\GbItems;
use app\models\InlineText;
use app\models\InstrFiles;
use app\models\Items;
use app\models\LinkedText;
use app\models\Links;
use app\models\Questions;
use app\models\Rubrics;
use app\models\Teacher;
use app\models\Wiki;
use \yii\base\Component;
global $qrubrictrack,$frubrictrack;
$qrubrictrack = array();
$frubrictrack = array();
class CopyItemsUtility extends Component
{

public  static function copyitem($itemid, $gbcats, $params,$sethidden = false)
{
    global $cid, $reqscoretrack, $assessnewid, $qrubrictrack, $frubrictrack, $copyStickyPosts, $userid, $exttooltrack, $outcomes, $removewithdrawn, $replacebyarr;
    global $posttoforumtrack, $forumtrack;
    if (!isset($copyStickyPosts)) {
        $copyStickyPosts = false;
    }
    if ($gbcats === false) {
        $gbcats = array();
    }
    if (!isset($outcomes)) {
        $outcomes = array();
    }
    if (strlen($params['append']) > AppConstant::NUMERIC_ZERO && $params['append']{0} != ' ') {
        $params['append'] = ' ' . $params['append'];
    }
    $now = time();
    $query = Items::getByTypeId($itemid);
    $itemtype = $query['itemtype'];
    $typeid = $query['typeid'];
    if ($itemtype == "InlineText") {
        $inlineTextData = InlineText::getById($typeid);
        $row = array(
            'courseid' => $inlineTextData['courseid'],
            'title' => $inlineTextData['title'],
            'text' => $inlineTextData['text'],
            'startdate' => $inlineTextData['startdate'],
            'enddate' => $inlineTextData['enddate'],
            'avail' => $inlineTextData['avail'],
            'oncal' => $inlineTextData['oncal'],
            'caltag' => $inlineTextData['caltag'],
            'isplaylist' => $inlineTextData['isplaylist'],
            'fileorder' => $inlineTextData['fileorder'],
        );
        if ($sethidden) {
            $row['avail'] = AppConstant::NUMERIC_ZERO;
        }
        $row['title'] .= stripslashes($params['append']);
        $fileorder = $row['fileorder'];
        array_pop($row);
        $inlineText = new InlineText();
        $newtypeid = $inlineText->saveInlineText($row);
        $instrFiles = InstrFiles::getFileName($typeid);
        $addedfiles = array();
        foreach ($instrFiles as $singleData)
        {
            $curid = $singleData['id'];
            unset($singleData['id']);
            $instrFile = new InstrFiles();
            $newInstrFileId = $instrFile->insertFile($singleData, $newtypeid);
            $addedfiles[$curid] = $newInstrFileId;
        }
        if (count($addedfiles) > AppConstant::NUMERIC_ZERO)
        {
            $addedfilelist = array();
            foreach ((explode(',', $fileorder)) as $fid)
            {
                $addedfilelist[] = $addedfiles[$fid];
            }
            $addedfilelist = implode(',', $addedfilelist);
            InlineText::setFileOrder($newtypeid, $addedfilelist);
        }
    } elseif ($itemtype == "LinkedText") {
        $query = LinkedText::getById($typeid);
        $istool = (substr($query['text'], AppConstant::NUMERIC_ZERO, AppConstant::NUMERIC_EIGHT) == 'exttool:');
        if ($istool) {
            $tool = explode('~~', substr($query['text'], AppConstant::NUMERIC_EIGHT));
            if (isset($tool[3]) && isset($gbcats[$tool[3]])) {
                $tool[3] = $gbcats[$tool[3]];
            } else if ($params['ctc'] != $cid) {
                $tool[3] = AppConstant::NUMERIC_ZERO;
            }
            $query['text'] = 'exttool:' . implode('~~', $tool);
        }
        if ($sethidden) {
            $query['avail'] = AppConstant::NUMERIC_ZERO;
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
    } elseif ($itemtype == "Forum") {
        $ForumData = Forums::getById($typeid);
        if ($sethidden) {
            $ForumData['avail'] = AppConstant::NUMERIC_ZERO;
        }
        if (isset($gbcats[$ForumData['gbcategory']])) {
            $ForumData['gbcategory'] = $gbcats[$ForumData['gbcategory']];
        } else if ($params['ctc'] != $cid) {
            $ForumData['gbcategory'] = AppConstant::NUMERIC_ZERO;
        }
        $rubric = $ForumData['rubric'];
        unset($ForumData['rubric']);
        $ForumData['name'] .= stripslashes($params['append']);
        if ($ForumData['outcomes'] != '') {
            $curoutcomes = explode(',', $ForumData['outcomes']);
            $newoutcomes = array();
            foreach ($curoutcomes as $o) {
                if (isset($outcomes[$o])) {
                    $newoutcomes[] = $outcomes[$o];
                }
            }
            $ForumData['outcomes'] = implode(',', $newoutcomes);
        }
        $forum = new Forums();
        $newtypeid = $forum->addNewForum($ForumData);
        if ($params['ctc'] != $cid)
        {
            $forumtrack[$typeid] = $newtypeid;
        }
        if ($rubric != AppConstant::NUMERIC_ZERO) {
            $frubrictrack[$newtypeid] = $rubric;
        }
        if ($copyStickyPosts) {
            //copy instructor sticky posts
            $query = ForumPosts::getByForumId($typeid);
            foreach ($query as $row) {
                $forumPostArray = array(
                    'forumid' => $newtypeid,
                    'userid' => $userid,
                    'parent' => AppConstant::NUMERIC_ZERO,
                    'postdate' => $now,
                    'subject' => $row['subject'],
                    'message' => $row['message'],
                    'posttype' => $row['posttype'],
                    'isanon' => $row['isanon'],
                    'replyby' => $row['replyby'],
                );
                if (is_null($row['replyby']) || trim($row['replyby']) == '') {
                    $forumPostArray['replyby'] = NULL;
                }
                $forumPost = new ForumPosts();
                $threadid = $forumPost->savePost($forumPostArray);
                ForumPosts::setThreadIdById($threadid);
                $forumThread = new ForumThread();
                $forumThread->addThread($threadid, $forumPostArray);
                $forumView = new ForumView();
                $forumView->addView($threadid, $forumPostArray);
            }
        }
    } elseif ($itemtype == "Wiki") {
        $row = Wiki::getById($typeid);
        if ($sethidden) {
            $row['avail'] = AppConstant::NUMERIC_ZERO;
        }
        $row['name'] .= stripslashes($params['append']);
        $wiki = new Wiki();
        $newtypeid = $wiki->addWiki($row);
    } elseif ($itemtype == "Assessment")
    {
        $assessmentData = Assessments::getByAssessmentId(intval($typeid));
        if ($sethidden)
        {
            $assessmentData['avail'] = AppConstant::NUMERIC_ZERO;
        }
        if (isset($gbcats[$assessmentData['gbcategory']])) {
            $assessmentData['gbcategory'] = $gbcats[$assessmentData['gbcategory']];
        } else if ($params['ctc'] != $params['courseId']) {
            $assessmentData['gbcategory'] = AppConstant::NUMERIC_ZERO;
        }
        if (isset($outcomes[$assessmentData['defoutcome']])) {
            $assessmentData['defoutcome'] = $outcomes[$assessmentData['defoutcome']];
        } else {
            $assessmentData['defoutcome'] = AppConstant::NUMERIC_ZERO;
        }
        if ($assessmentData['ancestors'] == '')
        {
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
        if ($reqscoreaid > AppConstant::NUMERIC_ZERO) {
            $reqscoretrack[$newtypeid] = $reqscoreaid;
        }
        if ($params['ctc'] != $cid && $forumtopostto > AppConstant::NUMERIC_ZERO) {
            $posttoforumtrack[$newtypeid] = $forumtopostto;
        }
        $assessnewid[$typeid] = $newtypeid;
        $thiswithdrawn = array();
        $query = Assessments::getItemOrderById($typeid);
        if (trim($query['itemorder']) != '')
        {
            $itemorder = explode(',', $query['itemorder']);
            $query = Questions::getByItemOrder($itemorder);
            $inss = array();
            $insorder = array();
            foreach ($query as $singleData) {
                if ($singleData['withdrawn'] > AppConstant::NUMERIC_ZERO && $removewithdrawn) {
                    $thiswithdrawn[$singleData['id']] = AppConstant::NUMERIC_ONE;
                    continue;
                }
                if (isset($replacebyarr[$singleData['questionsetid']])) {
                    $singleData['questionsetid'] = $replacebyarr[$singleData['questionsetid']];
                }
                if (is_numeric($singleData['category'])) {
                    if (isset($outcomes[$singleData['category']])) {
                        $singleData['category'] = $outcomes[$singleData['category']];
                    } else {
                        $singleData['category'] = AppConstant::NUMERIC_ZERO;
                    }
                }
                $rubric[$singleData['id']] = $singleData['rubric'];
                $insorder[] = $singleData['id'];
                array_push($inss, $singleData);
            }
            $idtoorder = array_flip($insorder);
            if (count($inss) > AppConstant::NUMERIC_ZERO) {

                $questionIdArray = array();
                foreach ($inss as $in) {

                    unset($in['id']);
                    $in['assessmentid'] = ($newtypeid);
                    $question = new Questions();
                    $firstnewid = $question->copyQuestions($in);
                    array_push($questionIdArray, $firstnewid);
                }
                $aitems = $itemorder;
                $newaitems = array();
                foreach ($aitems as $k => $aitem) {
                    if (strpos($aitem, '~') === FALSE) {
                        if (isset($thiswithdrawn[$aitem])) {
                            continue;
                        }
                        if ($rubric[$aitem] != AppConstant::NUMERIC_ZERO) {
                            $qrubrictrack[$firstnewid + $idtoorder[$aitem]] = $rubric[$aitem];
                        }
                        $newaitems[] = $firstnewid + $idtoorder[$aitem];
                    } else {
                        $sub = explode('~', $aitem);
                        $newsub = array();
                        $front = AppConstant::NUMERIC_ZERO;
                        if (strpos($sub[0], '|') !== false) { //true except for bwards compat
                            $newsub[] = array_shift($sub);
                            $front = AppConstant::NUMERIC_ONE;
                        }
                        foreach ($sub as $subi) {
                            if (isset($thiswithdrawn[$subi])) {
                                continue;
                            }
                            if ($rubric[$subi] != AppConstant::NUMERIC_ZERO) {
                                $qrubrictrack[$firstnewid + $idtoorder[$subi]] = $rubric[$subi];
                            }
                            $newsub[] = $firstnewid + $idtoorder[$subi];
                        }
                        if (count($newsub) == $front) {

                        } else if (count($newsub) == $front + AppConstant::NUMERIC_ONE) {
                            $newaitems[] = $newsub[$front];
                        } else {
                            $newaitems[] = implode('~', $newsub);
                        }
                    }
                }
                $newitemorder = implode(',', $newaitems);
                Assessments::setItemOrder($newitemorder, $newtypeid);
            }
        }
    }
    elseif ($itemtype == "Calendar")
    {
        $newtypeid = AppConstant::NUMERIC_ZERO;
    }
    $items = new Items();
    $newItemId = $items->saveItems($params['courseId'], $newtypeid, $itemtype);
    return $newItemId;
}

public static function copySub($items, $parent, &$addtoarr, $gbCats, $sethidden = false,$params=null,$checked=null,$blockCnt=null)
{
    global $newItems;
    foreach ($items as $k => $item) {
        if (is_array($item)) {
            if (array_search($parent . '-' . ($k + AppConstant::NUMERIC_ONE), $checked) !== FALSE)
            { //copy block
                $newBlock = array();
                $newBlock['name'] = $item['name'] . stripslashes($_POST['append']);
                $newBlock['id'] = $blockCnt;
                $blockCnt++;
                $newBlock['startdate'] = $item['startdate'];
                $newBlock['enddate'] = $item['enddate'];
                $newBlock['avail'] = $sethidden ? AppConstant::NUMERIC_ZERO : $item['avail'];
                $newBlock['SH'] = $item['SH'];
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        $newBlock['colors'] = $item['colors'];
                $newBlock['public'] = $item['public'];
                $newBlock['fixedheight'] = $item['fixedheight'];
                $newBlock['grouplimit'] = $item['grouplimit'];
                $newBlock['items'] = array();
                if (count($item['items']) > AppConstant::NUMERIC_ZERO) {
                    CopyItemsUtility::copysub($item['items'], $parent . '-' . ($k + AppConstant::NUMERIC_ONE), $newBlock['items'], $gbCats, $sethidden,$params,$checked,$blockCnt);
                }
                $addToArr[] = $newBlock;
                $newItems = $addToArr;
            }else
            {
                if(count($item['items']) > AppConstant::NUMERIC_ZERO)
                {
                    CopyItemsUtility::copysub($item['items'], $parent . '-' . ($k + AppConstant::NUMERIC_ONE), $addtoarr, $gbCats, false,$params,$checked,$blockCnt);
                }
            }
        }else
        {
            if (array_search($item, $checked) !== FALSE)
            {
                $addToArr[] = CopyItemsUtility::copyitem($item, $gbCats,$params,$sethidden);
                $newItems = $addToArr;
            }
        }
    }

}

public  static function doaftercopy($sourceCid,$courseId)
{

    global $reqscoretrack, $assessnewid, $forumtrack, $posttoforumtrack;
    if (intval($courseId) == intval($sourceCid)) {
        $sameCourse = true;
    } else {
        $sameCourse = false;
    }
    if (count($reqscoretrack) > AppConstant::NUMERIC_ZERO)
    {
        foreach ($reqscoretrack as $newid => $oldreqaid) {
            if (isset($assessnewid[$oldreqaid]))
            {
                Assessments::updateAssessmentForCopyCourse($assessnewid[$oldreqaid],$newid,AppConstant::NUMERIC_ZERO);
            }else if(!$sameCourse)
            {
                Assessments::updateAssessmentForCopyCourse($assessnewid[$oldreqaid],$newid,AppConstant::NUMERIC_ONE);
            }
        }
    }
    if (count($posttoforumtrack) > AppConstant::NUMERIC_ZERO)
    {
        foreach ($posttoforumtrack as $newaid => $oldforumid)
        {
            if (isset($forumtrack[$oldforumid]))
            {
                Assessments::updatePostToForum($forumtrack[$oldforumid],$newaid,AppConstant::NUMERIC_ZERO);

            } else
            {
                Assessments::updatePostToForum($forumtrack[$oldforumid],$newaid,AppConstant::NUMERIC_ONE);

            }
        }
    }
    if (!$sameCourse) {
        CopyItemsUtility::handleextoolcopy($sourceCid,$courseId);
    }
}

public function copyAllSub($items, $parent, &$addToArr, $gbCats, $sethidden = false,$params,$blockCnt)
{
    if (strlen($params['append']) > AppConstant::NUMERIC_ZERO && $params['append']{0} != ' ') {
        $params['append'] = ' ' . $params['append'];
    }
    foreach ($items as $k => $item)
    {
        if (is_array($item)) {
            $newBlock = array();
            $newBlock['name'] = $item['name'] . stripslashes($params['append']);
            $newBlock['id'] = $blockCnt;
            $blockCnt++;
            $newBlock['startdate'] = $item['startdate'];
            $newBlock['enddate'] = $item['enddate'];
            $newBlock['avail'] = $sethidden ? AppConstant::NUMERIC_ZERO : $item['avail'];
            $newBlock['SH'] = $item['SH'];
            $newBlock['public'] = $item['public'];
            $newBlock['fixedheight'] = $item['fixedheight'];
            $newBlock['grouplimit'] = $item['grouplimit'];
            $newBlock['items'] = array();
            if (count($item['items']) > AppConstant::NUMERIC_ZERO) {
                $this->copyAllSub($item['items'], $parent . '-' . ($k + AppConstant::NUMERIC_ONE), $newBlock['items'], $gbCats, $sethidden,$params,$blockCnt);
            }
            $addToArr[] = $newBlock;
            $newItems = $addToArr;
        } else
        {
            if ($item != null && $item != AppConstant::NUMERIC_ZERO)
            {
                $addToArr[] = CopyItemsUtility::copyitem($item, $gbCats,$params,$sethidden);
                $newItems = $addToArr;
            }
        }
    }

}
public  static function getiteminfo($itemid)
{
    $items  = Items::getById($itemid);

    if (count($items) == AppConstant::NUMERIC_ZERO) {
        echo "Uh oh, item #$itemid doesn't appear to exist";
    }
    $itemtype = $items['itemtype'] ;
    $typeid = $items['typeid'] ;
    if ($itemtype === 'Calendar') {
        return array($itemtype, 'Calendar', '');
    }
    switch ($itemtype) {
        case ($itemtype === "InlineText"):
            $inLineText = InlineText::getById($typeid);
            $name = $inLineText['title'];
            $summary = $inLineText['text'];
            break;
        case ($itemtype === "LinkedText"):
            $link = Links::getById($typeid);
            $name = $link['title'];
            $summary = $link['summary'];
            break;
        case ($itemtype === "Forum"):
            $forum = Forums::getById($typeid);
            $name = $forum['name'];
            $summary = $forum['description'];
            break;
        case ($itemtype === "Assessment"):
            $assessment = Assessments::getByAssessmentId($typeid);
            $name = $assessment['name'];
            $summary = $assessment['summary'];
            break;
        case ($itemtype === "Wiki"):
            $wiki = Wiki::getById($typeid);
            $name = $wiki['name'];
            $summary = $wiki['description'];
            break;
    }
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
            $ids[] = $parent . '-' . ($k + AppConstant::NUMERIC_ONE);
            $types[] = "Block";
            $names[] = stripslashes($item['name']);
            $prespace[] = $pre;
            $parents[] = $parent;
            $gitypeids[] = '';
            $sums[] = '';
            if (count($item['items']) > AppConstant::NUMERIC_ZERO) {
                CopyItemsUtility::getsubinfo($item['items'], $parent . '-' . ($k + AppConstant::NUMERIC_ONE), $pre . $spacer, $itemtypelimit, $spacer);
            }
        } else {
            if ($item == null || $item == '') {
                continue;
            }
            $arr = CopyItemsUtility::getiteminfo($item);
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
            if (strlen($arr[2]) > AppConstant::NUMERIC_HUNDREAD) {
                $arr[2] = substr($arr[2], AppConstant::NUMERIC_ZERO, AppConstant::NINETY_SEVEN) . '...';
            }
            $sums[] = $arr[2];
        }
    }

}

public  static function buildexistblocks($items, $parent,$pre = '')
{
    global $existBlocks;

    foreach ($items as $k => $item) {
        if (is_array($item)) {
            $existBlocks[$parent . '-' . ($k + AppConstant::NUMERIC_ONE)] = $pre . $item['name'];
            if (count($item['items']) > AppConstant::NUMERIC_ZERO) {
               CopyItemsUtility::buildexistblocks($item['items'], $parent . '-' . ($k + AppConstant::NUMERIC_ONE), $pre . '&nbsp;&nbsp;');
            }
        }
    }
    return $existBlocks;
}

public static function copyrubrics($offlinerubrics = array(),$userid=false,$groupid = false)
{
    global $qrubrictrack, $frubrictrack;
    if (count($qrubrictrack) == AppConstant::NUMERIC_ZERO && count($frubrictrack) == AppConstant::NUMERIC_ZERO && count($offlinerubrics) == AppConstant::NUMERIC_ZERO)
    {
        return;
    }
    $list = implode(',', array_merge($qrubrictrack, $frubrictrack, $offlinerubrics));
    //handle rubrics which I already have access to
    $rubricData = Rubrics::getByUserIdAndGroupId($userid,$groupid,$list);
    foreach($rubricData as $singleData)
    {
        $qfound = array_keys($qrubrictrack, $singleData['id']);
        if (count($qfound) > AppConstant::NUMERIC_ZERO) {
            foreach ($qfound as $qid) {
                Questions::setRubric($qid,$singleData['id']);
            }
        }
        $ofound = array_keys($offlinerubrics, $singleData['id']);
        if (count($ofound) > AppConstant::NUMERIC_ZERO) {
            foreach ($ofound as $oid) {
                GbItems::setRubric($oid,$singleData['id']);
            }
        }
        $ffound = array_keys($frubrictrack, $singleData['id']);
        if (count($ffound) > AppConstant::NUMERIC_ZERO)
        {
            foreach ($ffound as $fid)
            {
                Forums::setRubric($fid,$singleData['id']);
            }
        }
    }

    //handle rubrics which I don't already have access to - need to copy them
    $rubricData = Rubrics::getByUserIdAndGroupIdAndList($userid,$groupid,$list);
    if($rubricData)
    {
        foreach($rubricData as $singleData)
        {
            //echo "handing {$row[0]} which I don't have access to<br/>";
            $rubric = Rubrics::getById($singleData['id']);
            $rubrow = AppUtility::addslashes_deep($rubric);
            $rubricItems = Rubrics::getByUserIdAndGroupIdAndRubric($rubrow[2],$userid,$groupid);

            if ($rubricItems > AppConstant::NUMERIC_ZERO) {
                $newid = $rubricItems['id'];
            } else {
                $rub = "'" . implode("','", $rubrow) . "'";
                $temp = new Rubrics();
                $rubricId = $temp->createNewEntry($userid,AppConstant::NUMERIC_NEGATIVE_ONE,$rub);
                $newid = $rubricId;
            }
            $qfound = array_keys($qrubrictrack, $singleData['id']);
            if (count($qfound) > AppConstant::NUMERIC_ZERO) {
                foreach ($qfound as $qid) {
                    Questions::setRubric($qid,$newid);
                }
            }
            $ffound = array_keys($frubrictrack, $singleData['id']);
            if (count($ffound) > AppConstant::NUMERIC_ZERO) {
                foreach ($ffound as $fid) {
                    Forums::setRubric($fid,$newid);
                }
            }
            $ofound = array_keys($offlinerubrics, $singleData['id']);
            if (count($ofound) > AppConstant::NUMERIC_ZERO) {
                foreach ($ofound as $oid) {
                    GbItems::setRubric($oid,$newid);
                }
            }
        }
    }
}

public static function handleextoolcopy($sourcecid,$courseId)
{
    global $userid, $groupid, $exttooltrack;
    if (count($exttooltrack) == AppConstant::NUMERIC_ZERO) {
        return;
    }
    $toolmap = array();
    $teacherData = Teacher::getByUserId($userid,$courseId);
    if (count($teacherData) > AppConstant::NUMERIC_ZERO)
    {
        $oktocopycoursetools = true;
    }
    $toolidlist = implode(',', $exttooltrack);
    $externalTool = ExternalTools::dataForCopy($toolidlist);
    foreach($externalTool as $row)
    {
        $doremap = false;
        if (!isset($toolmap[$row['id']]))
        {
            $query  = ExternalTools::getId($courseId,$row['url']);
            if(count($query) > AppConstant::NUMERIC_ZERO)
            {
                $toolmap[$row['id']] = $query[0]['id'];
            }
        }
        if (isset($toolmap[$row['id']]))
        {

            $doremap = true;
        }
        else if ($row['courseid'] > AppConstant::NUMERIC_ZERO && $oktocopycoursetools)
        {
            //do copy
            $rowsub = array_slice($row, AppConstant::NUMERIC_THREE);
            $insert = new ExternalTools();
            $insertId = $insert ->insertData($courseId,$groupid,$rowsub);
            $toolmap[$row['id']] = $insertId;
            $doremap = true;
        }
        else if ($row['courseid'] == AppConstant::NUMERIC_ZERO && ($row['groupid'] == AppConstant::NUMERIC_ZERO || $row['groupid'] == $groupid))
        {

        }
        else
        {
            $toupdate = implode(",", array_keys($exttooltrack, $row['id']));
            LinkedText::updateDataForCopyCourse($toupdate);
        }
        if ($doremap)
        {
            $toupdate = implode(",", array_keys($exttooltrack, $row['id']));
            $query = LinkedText::getByIdForCopy($toupdate);
            foreach($query as $data)
            {
                $text = str_replace('exttool:' . $row['id'] . '~~', 'exttool:' . $toolmap[$row['id']] . '~~', $data['text']);
                LinkedText::updateData($text,$data['id']);
            }
        }
    }
 }
}

