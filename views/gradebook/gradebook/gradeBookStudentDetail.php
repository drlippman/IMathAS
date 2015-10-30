<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;

use app\components\AssessmentUtility;
$urlmode = AppUtility::urlMode();
if($defaultValuesArray['studentId'] > 0){
    $this->title = AppUtility::t('Grade Book Student Detail', false);
}else{
    $this->title = AppUtility::t('Grade Book Averages', false);
}

//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid=' . $course->id]];

 $this->params['breadcrumbs'][] = $this->title;
   //show student view
$gradebook = $totalData['gradebook'];
$hidenc = $defaultValuesArray['hidenc'];
$gbmode =  ' ';
$availshow= $defaultValuesArray['availshow'];
$catfilter = $defaultValuesArray['catfilter'];
$showpics = $defaultValuesArray['showpics'];
$hidelocked = $defaultValuesArray['hidelocked'];
$totonleft = $defaultValuesArray['totonleft'];
$avgontop = $defaultValuesArray['avgontop'];
$links = $defaultValuesArray['links'];
$urlmode =  'http://';
$includeduedate = $defaultValuesArray['includeduedate'];
$includelastchange = $defaultValuesArray['includelastchange'];
$lastlogin = $defaultValuesArray['lastlogin'];
$latepasshrs = $course['latepasshrs'];
$isteacher = false;
$canviewall = false;
if($totalData['isTeacher']){
    $isteacher = true;
    $canviewall = true;
}
$studentId = $defaultValuesArray['studentId'];

$istutor = false;
if($totalData['isTutor']){
    $istutor = true;
    $canviewall = true;
}
?>
<input type="hidden" id="course-id" value="<?php echo $course->id ?>">
<input type="hidden" id="student-id" value="<?php echo $studentId ?>">
<input type="hidden" id="totonleft" value="<?php echo $totonleft ?>">
<input type="hidden" id="avgontop" value="<?php echo $avgontop?>">
<input type="hidden" id="includelastchange" value="<?php echo $includelastchange?>">
<input type="hidden" id="lastlogin" value="<?php echo $lastlogin?>">
<input type="hidden" id="includeduedate" value="<?php echo $includeduedate?>">
<div class="item-detail-header">
<?php if(!isset($params['from']))
{ ?>
    <?php  if($currentUser['rights'] > 10){
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id,AppUtility::getHomeURL().'gradebook/gradebook/gradebook?cid=' . $course->id]]);
}else{
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/index?cid=' . $course->id,AppUtility::getHomeURL().'gradebook/gradebook/grade-book-student-detail?cid=' . $course->id]]);
}?>
<?php }else{ ?>
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Roster'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id,AppUtility::getHomeURL().'roster/roster/student-roster?cid=' . $course->id]]); ?>
<?php } ?>


</div>

<div class = "title-container">
    <div class = "row">
        <div class = "pull-left page-heading">
            <div class = "vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php if($currentUser['rights'] > 10) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);
    } elseif($currentUser['rights'] == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'Forums']);
    }?>
</div>

<div class="tab-content shadowBox">

<?php if ($canviewall) {  ?>
    <br>
    <div class="col-md-12 display-inline-block">
        <div class="display-inline-block width-twenty-five-per">
               <div class="display-inline-block"><?php AppUtility::t('Category')?></div>&nbsp;
               <div class="display-inline-block width-fifty-percentage">
                    <select id="filtersel" class="form-control display-inline-block" onchange="chgfilter()">
                        <option value="-1"
                        <?php if ($catfilter==-1) {echo "selected=1";} ?> >All
                        </option>
                        <option value="0"
                        <?php if ($catfilter==0) { echo "selected=1";} ?>>
                            <?php AppUtility::t('Default') ?>
                        </option>
                        <?php foreach($gbCatsData as $gbCats)
                        {
                            echo '<option value="'.$gbCats['id'].'"';
                            if ($catfilter==$gbCats['id']) {echo "selected=1";}
                            echo '>'.$gbCats['name'].'</option>';
                        } ?>
                         <option value="-2"
                        <?php if ($catfilter==-2) {echo "selected=1";} ?> >
                                <?php AppUtility::t('Category Totals'); ?>
                        </option>
                  </select>
               </div>
        </div>
        <div class="display-inline-block width-twenty-five-per">
            <div class="display-inline-block"> <?php AppUtility::t('Not Counted');?></div>&nbsp;
             <div class='display-inline-block width-fifty-percentage'>
                 <select id="toggle2" class='form-control' onchange=chgtoggle()>
                     <option value=0 <?php AssessmentUtility::writeHtmlSelected($hidenc,0);?> > <?php AppUtility::t('Show all')?> </option>
                     <option value=1 <?php AssessmentUtility::writeHtmlSelected($hidenc,1); ?> > <?php AppUtility::t('Show stu view')?> </option>
                     <option value=2 <?php AssessmentUtility::writeHtmlSelected($hidenc,2); ?> > <?php AppUtility::t('Hide all')?> </option>
                 </select>
             </div>
        </div>

        <div class="display-inline-block width-twenty-five-per">
            <div class="display-inline-block"> <?php AppUtility::t('Show');?></div>&nbsp;
            <div class='display-inline-block width-fifty-percentage'>
                <select class='form-control' id="toggle3" onchange="chgtoggle()">
                 <option value=0 <?php AssessmentUtility::writeHtmlSelected($availshow,0); ?> > <?php AppUtility::t('Past due') ?></option>
                 <option value=3 <?php AssessmentUtility::writeHtmlSelected($availshow,3); ?> > <?php AppUtility::t('Past &amp; Attempted')?> </option>
                 <option value=4 <?php AssessmentUtility::writeHtmlSelected($availshow,4); ?> > <?php AppUtility::t('Available Only') ?> </option>
                 <option value=1 <?php AssessmentUtility::writeHtmlSelected($availshow,1); ?> > <?php AppUtility::t('Past &amp; Available') ?></option>
                 <option value=2 <?php AssessmentUtility::writeHtmlSelected($availshow,2); ?> > <?php AppUtility::t('All')?></option>
                </select>
            </div>
        </div>
        <div class="display-inline-block width-twenty-four-per">
            <div class="display-inline-block"> <?php AppUtility::t('Links') ?></div>&nbsp;
            <div class=' display-inline-block width-eight-four-per'>
                <select class='form-control' id="toggle1" onchange="chgtoggle()">
                 <option value=0 <?php AssessmentUtility::writeHtmlSelected($links,0); ?> > <?php AppUtility::t('View/Edit')?> </option>
                 <option value=1 <?php AssessmentUtility::writeHtmlSelected($links,1); ?> > <?php AppUtility::t ('Scores')?> </option>
                </select>
                 <input type="hidden" id="toggle4" value="<?php $showpics ?> " />
                 <input type="hidden" id="toggle5" value="<?php $hidelocked?>" />
            </div>
        </div>
    </div>
    <?php } ?>
  <br><br>
    <div class="inner-content-gradebook">
       <div class="button-container col-md-12 padding-zero">
   <div class="col-md-9 margin-left-minus-fifteen">
        <?php
if ($availshow==4) {
$availshow=1;
$hidepast = true;
}
if ($studentId>0) {
    $showlatepass = $course['showlatepass'];
    $latepasshrs = $course['latepasshrs'];
}
if ($studentId>0) {
?>
 <div style="font-size:1.1em;font-weight:bold">
   <?php  if ($isteacher || $istutor) {
    if ($gradebook[1][0][1] != '') {
        $usersort = $stugbmode['usersort'];
    } else {
    $usersort = 1;
    }
   echo '<div>';
    if ($gradebook[1][4][2]==1)
    {
    if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true)
    {
    echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$gradebook[1][4][0]}.jpg\" onclick=\"togglepic(this)\" class=\"mida margin-bottom-ten\"/> ";
    } else { ?>
 <img src=" <?php echo AppUtility::getAssetURL().AppConstant::UPLOAD_DIRECTORY.$gradebook[1][4][0].'.jpg' ?>" style="float: left;width: 40px;height: 35px; padding-right:5px;" onclick="togglepic(this)" class="mida margin-bottom-ten">
   <?php }
    } ?>
     </div>
                   <div class="">
                       <?php
                       if ($gradebook[1][4][2]==1)
                       { ?>
                       <span class="col-md-4">
                           <?php }else{ ?>
                           <span class="col-md-4 margin-left-minus-fifteen">
                               <?php }
    echo '<select id="userselect" class="form-control"   onchange="chgstu(this)">';
        $lastsec = '';
        foreach($allStudentsinformation as $studiinfo) {

        if ($studiinfo[3]!='' && $studiinfo[3]!=$lastsec && $usersort==0) {
        if ($lastsec=='') {echo '</optgroup>';}
        echo '<optgroup label="Section '.htmlentities($studiinfo[3]).'">';
            $lastsec = $studiinfo[3];
            }
            echo '<option value="'.$studiinfo[0].'"';
            if ($studiinfo[0]==$studentId) {
            echo ' selected="selected"';
            }
            echo '>'.$studiinfo[2].', '.$studiinfo[1].'</option>';
            }
            if ($lastsec!='') {echo '</optgroup>';}
        echo '</select>';
       ?>
       </span>
    <div class="section pull-left ">
       <?php
    echo '<img id="updatingicon" style="display:none" src="'.$imasroot.'/img/updating.gif"/>';
    echo ' <span class="small">('.$gradebook[1][0][1].')</span>';
    } else {
    echo strip_tags($gradebook[1][0][0]) ?> <span class="small"><?php echo $gradebook[1][0][1] ?></span>
   <?php  $viewedassess = array();
    foreach($contentTrackData as $contentTrack){
    $viewedassess[] = $row[0];
    }
    $now = time();
    }
    if (count($StudentData)==0) { //shouldn't happen
    echo 'Invalid student id';
    exit;
    }
    $gbcomment = $StudentData['gbcomment'];
    $stuemail = $currentUser['email'];
    $latepasses = $StudentData['latepass'];
    $stusection = $StudentData['section'];
    $lastaccess = $StudentData['lastaccess'];

    if ($stusection!='') { ?>
    <span class="small">Section: <?php echo $stusection ?></span>
   <?php } ?>
    <span class="small">Last Login: <?php echo AppUtility::tzdate('D n/j/y g:ia', $lastaccess);?></span>
    </div></div>
    </div>
    </div>
<?php if ($isteacher) { ?>
    <span class="inner-page-options col-md-3 padding-zero pull-right">
        <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">

            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown"
                   href="#"><?php AppUtility::t('With selected'); ?><span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu with-selected">
                    <li>
                        <a class="with-selected-list" href="<?php echo AppUtility::getURLFromHome('roster','roster/activity-log?cid='.$course->id.'&uid='.$StudentData->userid)?>">
                            <i class="fa fa-clock-o"></i>&nbsp;<?php AppUtility::t('Activity Log'); ?></a>
                    </li>

                    <li>
                        <a class="with-selected-list" href="<?php echo AppUtility::getURLFromHome('roster','roster/change-student-information?cid='.$course->id.'&uid='.$StudentData->userid)?>">
                            <i class='fa fa-pencil fa-fw'></i>&nbsp;<?php AppUtility::t('Change Info'); ?></a>
                    </li>

                    <li>
                        <a href="#" onclick="makeofflineeditable(this); return false;">
                            <!--                            <a class="with-selected-list" href="javascript: studentCopyEmail()">-->
                            <i class="fa fa-clipboard fa-fw"></i>&nbsp;<?php AppUtility::t('Edit Offline Score'); ?></a>

                    </li>

                    <li>
                        <input type="hidden" id="student-id" name="student-data" value=""/>
                        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                        <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/send-message-model?sendto='.$studentId.'&sendtype=email&cid='.$course->id)?> "  >
                            <i class="fa fa-at fa-fw"></i>&nbsp;<?php AppUtility::t('Email'); ?></a>
                    </li>

                    <li>
                        <a class="with-selected-list" href="<?php echo AppUtility::getURLFromHome('roster','roster/login-log?cid='.$course->id.'&uid='.$StudentData->userid)?>">
                            <i class="fa fa-clipboard fa-fw"></i>&nbsp;<?php AppUtility::t('Login Log'); ?></a>
                    </li>

                    <li>
                        <form action="make-exception?cid=<?php echo $course->id ?>" id="make-exception-form"
                              method="post">
                            <input type="hidden" id="exception-id" name="student-data" value=""/>
                            <input type="hidden" id="section-name" name="section-data" value=""/>
                            <a class="with-selected-list" href="<?php echo AppUtility::getURLFromHome('roster','roster/make-exception?cid='.$course->id.'&student-data='.$StudentData->userid.'&section-data='.$StudentData['section'])?>"><i
                                    class='fa fa-plus-square fa-fw'></i>&nbsp;<?php AppUtility::t('Make Exception'); ?>
                            </a>
                        </form>
                    </li>
                    <li>
                        <input type="hidden" id="message-id" name="student-data" value=""/>
                        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                        <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/send-message-model?sendto='.$studentId.'&sendtype=msg&cid='.$course->id);?>" >
                            <i class="fa fa-envelope-o fa-fw"></i>&nbsp;<?php AppUtility::t('Message'); ?></a>
                    </li>
                </ul>
            </li>
        </ul>
    </span>
<?php } else if ($istutor) { ?>
    <?php
echo '<div style="clear:both;display:inline-block" class="cpmid">'; ?>
     <a href="<?php echo AppUtility::getURLFromHome('roster','roster/login-log?cid='.$course->id.'&uid='.$studentId.'&from=gb');?> "><?php AppUtility::t('Login Log');?></a> |
     <a href="<?php echo AppUtility::getURLFromHome('roster','roster/activity-log?cid='.$course->id.'&uid='.$studentId.'&from=gb');?>"><?php AppUtility::t('Activity Log') ?></a>
    </div>
    </div>

    <br/>
    </div>  </div> <?php
}
echo '<br><br><br>';
if (trim($gbcomment)!='' || $isteacher) {
if ($isteacher) { ?>
 <form method=post action="grade-book-student-detail?cid=<?php echo $course->id?>&studentId=<?php echo $studentId?>">
    Gradebook Comment: <input type=submit value="Update Comment"><br/><br/>
  <textarea class="text-area-padding" name="user-comments" rows=3 cols=60><?php echo $gbcomment;?></textarea>
     </form>
    <?php
} else { ?>
 <div style="clear:both;display:inline-block" class="cpmid"><?php echo $gbcomment?></div><br>
 <?php
}
}
//TODO i18n
if ($showlatepass==1) {
if ($latepasses==0) { $latepasses = 'No';}
if ($isteacher || $istutor) {echo '<br/>';}
$lpmsg = "$latepasses LatePass".($latepasses!=1?"es":"").' available';
}
if (!$isteacher && !$istutor) {
echo $lpmsg;
}

}
?>
<br>
 <form method=post id="qform" action="grade-book-student-detail?cid=<?php echo $course->id?>&studentId=<?php echo $studentId?>">
 <?php if ($isteacher && $studentId>0) {  ?>
        <input type="hidden" name="stusection" value="<?php echo $stusection?>">
     <?php echo '<button type="submit" value="Save Changes" style="display:none"; id="savechgbtn">', _('Save Changes'), '</button> ';
    echo _('Check:'), ' <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',false)">', _('None'), '</a> ';
    echo _('With selected:'), ' <button type="submit" value="Make Exception" name="posted">',_('Make Exception'),'</button> '.$lpmsg.'';
    } ?>
     <br><br>
          <table id="myTable" class="table table-bordered table-striped table-hover data-table" style="position:relative;">
    <?php
        echo '<thead>';
            $sarr = array();
            if ($studentId>0 && $isteacher) {
            echo '<th></th>';
            } ?>
             <th> <?php  AppUtility::t('Item') ?> </th>
             <th> <?php AppUtility::t('Possible')?></th>
             <th><?php AppUtility::t('Grade') ?></th>
             <th><?php AppUtility::t('Percent')?></th>
<?php
            if ($studentId>0 && $isteacher) {
?>
            <th><?php AppUtility::t('Time Spent (In Questions)');?></th>
            <?php $sarr = "false,'S','N','N','N','N'";
            if ($includelastchange) { ?>
             <th> <?php AppUtility::t('Last Changed');?></th>
           <?php $sarr .= ",'D'";
            }
            if ($includeduedate) {
            echo '<th>',AppUtility::t('Due Date', false),'</th>';
            $sarr .= ",'D'";
            }
            } else {
            $sarr = "'S','N','N','N'";
            }
            if ($studentId>0) {
            echo '<th>', AppUtility::t('Feedback'), '<br/><a href="#" class="
Item pointer" onclick="return showhideallfb(this);">', AppUtility::t('Show Feedback'), '</a></th>';
            $sarr .= ",'N'";
            }
            echo '</thead><tbody>';
        if ($catfilter>-2) {

for ($i=0;$i<count($gradebook[0][1]);$i++) { //assessment headers

        if (!$isteacher && !$istutor && $gradebook[0][1][$i][4]==0) { //skip if hidden

            continue;
        }
        if ($hidenc==1 && $gradebook[0][1][$i][4]==0) { //skip NC

        continue;
        } else if ($hidenc==2 && ($gradebook[0][1][$i][4]==0 || $gradebook[0][1][$i][4]==3)) {//skip all NC

        continue;
        }
        if ($gradebook[0][1][$i][3]>$availshow) {
        continue;
        }
        if ($hidepast && $gradebook[0][1][$i][3]==0) {
        continue;
        }

        echo '<tr class="grid">';
            if ($studentId>0 && $isteacher) {
            if ($gradebook[0][1][$i][6]==0) {
            echo '<td><input type="checkbox" name="assesschk[]" value="'.$gradebook[0][1][$i][7] .'" /></td>';
            } else {
            echo '<td></td>';
            }
            }

            echo '<td class="cat'.$gradebook[0][1][$i][1].'">'.$gradebook[0][1][$i][0];
                $afterduelatepass = false;

                if (!$isteacher && !$istutor && $latepasses>0  &&	(
                (isset($gradebook[1][1][$i][10]) && $gradebook[1][1][$i][10]>0 && !in_array($gradebook[0][1][$i][7],$viewedassess)) ||  //started, and already figured it's ok
                (!isset($gradebook[1][1][$i][10]) && $now<$gradebook[0][1][$i][11]) || //not started, before due date
                (!isset($gradebook[1][1][$i][10]) && $gradebook[0][1][$i][12]>10 && $now-$gradebook[0][1][$i][11]<$latepasshrs*3600 && !in_array($gradebook[0][1][$i][7],$viewedassess)) //not started, within one latepass
                )) { ?>
                 <span class="small"><a href="<?php echo AppUtility::getURLFromHome('assessment','assessment/late-pass?cid='.$course->id.'&aid='.$gradebook[0][1][$i][7]); ?>">
                         [<?php  echo AppUtility::t('Use LatePass'); ?> ]</a></span>
                <?php if ($now>$gradebook[0][1][$i][11]) {
                $afterduelatepass = true;
                }

                }

                echo '</td>';
            echo '<td>';
                if ($gradebook[0][1][$i][4]==0 || $gradebook[0][1][$i][4]==3) {

                    echo $gradebook[0][1][$i][2].'&nbsp;',AppUtility::t('pts'), ' ',AppUtility::t('(Not Counted)');
                } else {

                echo $gradebook[0][1][$i][2].'&nbsp;',AppUtility::t('pts');
                if ($gradebook[0][1][$i][4]==2) {
                echo ' (EC)';
                }
                }
                if ($gradebook[0][1][$i][5]==1 && $gradebook[0][1][$i][6]==0) {
                echo ' (PT)';
                }

                echo '</td><td>';

                $haslink = false;

                if ($isteacher || $istutor || $gradebook[1][1][$i][2]==1) { //show link
                if ($gradebook[0][1][$i][6]==0) {//online
                if ($studentId==-1) { //in averages
                if (isset($gradebook[1][1][$i][0])) { //has score ?>
                 <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis?stu='.$studentId.'&cid='.$course->id.'&aid='.$gradebook[0][1][$i][7]);?>">
                    <?php $haslink = true;
                    }
                    } else {
                    if (isset($gradebook[1][1][$i][0])) { //has score
                    echo "<a href=\"gradebook-view-assessment-details?stu={$gradebook[1][4][0]}&cid=$course->id&asid={$gradebook[1][1][$i][4]}&uid={$gradebook[1][4][0]}\"";
                             if ($afterduelatepass) {
                             echo ' onclick="return confirm(\''._('If you view this assignment, you will not be able to use a LatePass on it').'\');"';
                    }
                    echo ">";
                    $haslink = true;
                    } else if ($isteacher) {
                    echo "<a href=\"gradebook-view-assessment-details?stu=$studentId&cid=$course->id&asid=new&aid={$gradebook[0][1][$i][7]}&uid={$gradebook[1][4][0]}\">";
                        $haslink = true;
                        }
                        }
                        } else if ($gradebook[0][1][$i][6]==1) {//offline
                        if ($isteacher || ($istutor && $gradebook[0][1][$i][8]==1)) {
                        if ($studentId==-1) {
                        if (isset($gradebook[1][1][$i][0])) { //has score ?>
                         <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-grades?stu='.$studentId.'&cid='.$course->id.'&grades=all&gbitem='.$gradebook[0][1][$i][7]);?> ">
                            <?php $haslink = true;
                            }
                            } else { ?>
                            <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-grades?stu='.$studentId.'&cid='.$course->id.'&grades='.$gradebook[1][4][0].'&gbitem='.$gradebook[0][1][$i][7])?> ">
                            <?php    $haslink = true;
                                }
                                }
                                } else if ($gradebook[0][1][$i][6]==2) {//discuss
                                if ($studentId != -1) { ?>
                                 <a href="<?php echo AppUtility::getURLFromHome('forum','forum/view-forum-grade?cid='.$course->id.'&stu='.$studentId.'&uid='.$gradebook[1][4][0].'&fid='.$gradebook[0][1][$i][7]);?>">
                                    <?php $haslink = true;
                                    }
                                    } else if ($gradebook[0][1][$i][6]==3) {//exttool
                                    if ($isteacher || ($istutor && $gradebook[0][1][$i][8]==1)) { ?>
                                     <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/edit-tool-score?cid='.$course->id.'&stu='.$studentId.'&uid='.$gradebook[1][4][0].'&lid='.$gradebook[0][1][$i][7]);?>">
                                        <?php $haslink = true;
                                        }
                                        }
                                        }
                                        if (isset($gradebook[1][1][$i][0]))
                                        {
                                        if ($gradebook[1][1][$i][3]>9) {
                                        $gradebook[1][1][$i][3] -= 10;
                                        }
                                        echo $gradebook[1][1][$i][0];
                                        if ($gradebook[1][1][$i][3]==1) {
                                        AppUtility::t('NC',false);
                                        } else if ($gradebook[1][1][$i][3]==2) {
                                            AppUtility::t('IP',false);
                                        } else if ($gradebook[1][1][$i][3]==3) {
                                            AppUtility::t('OT',false);
                                        } else if ($gradebook[1][1][$i][3]==4) {
                                            AppUtility::t('PT',false);
                                        }
                                        } else {
                                        echo '-';
                                        }
                                        if ($haslink) { //show link
                                        echo '</a>';
                                    }
                                    if (isset($gradebook[1][1][$i][6]) ) {  //($isteacher || $istutor) &&
                                    if ($gradebook[1][1][$i][6]>1) {
                                    if ($gradebook[1][1][$i][6]>2) {
                                    echo '<sup>LP ('.($gradebook[1][1][$i][6]-1).')</sup>';
                                    } else {
                                    echo '<sup>LP</sup>';
                                    }
                                    } else {
                                    echo '<sup>e</sup>';
                                    }
                                    }
                                    if (isset($gradebook[1][1][$i][5]) && ($gradebook[1][1][$i][5]&(1<<$availshow)) && !$hidepast) {
                                    echo '<sub>d</sub>';
                                    }
                                    echo '</td><td>';
                if (isset($gradebook[1][1][$i][0])) {
                if ($gradebook[0][1][$i][2]>0) {
                echo round(100*$gradebook[1][1][$i][0]/$gradebook[0][1][$i][2],1).'%';
                }
                } else {
                echo '0%';
                }
                echo '</td>';
            if ($studentId>0 && $isteacher) {
            if ($gradebook[1][1][$i][7] > -1) {
            echo '<td>'.$gradebook[1][1][$i][7].' min ('.$gradebook[1][1][$i][8].' min)</td>';
            } else {
            echo '<td></td>';
            }
            if ($includelastchange) {
            if ($gradebook[1][1][$i][9]>0) {
            echo '<td>'.tzdate('n/j/y g:ia', $gradebook[1][1][$i][9]);
                } else {
                echo '<td></td>';
            }
            }
            if ($includeduedate) {
            if ($gradebook[0][1][$i][11]<2000000000) {
            echo '<td>'.tzdate('n/j/y g:ia',$gradebook[0][1][$i][11]);
                } else {
                echo '<td>-</td>';
            }
            }
            }
            if ($studentId>0) {
            if ($gradebook[1][1][$i][1]=='') {
            echo '<td></td>';
            } else {
            echo '<td><a href="#" class="small feedbacksh pointer" onclick="return showhidefb(this,'.$i.')">',AppUtility::t('[Show Feedback]'), '</a><span style="display:none;" id="feedbackholder'.$i.'">'.$gradebook[1][1][$i][1].'</span></td>';
            }
            }
            echo '</tr>';
        }
        }
        echo '</tbody></table><br/>';

     if (!$hidepast) {

    $show =  $stugbmode['stugbmode'];

    echo '<table class="table table-bordered table-striped table-hover data-table"><thead>';
            echo '<th >',AppUtility::t('Totals'), '</th>';
            if (($show&1)==1) {
            echo '<th>',AppUtility::t('Past Due'), '</th>';
            }
            if (($show&2)==2) {
            echo '<th>',AppUtility::t('Past Due and Attempted'), '</th>';
            }
            if (($show&4)==4) {
            echo '<th>',AppUtility::t('Past Due and Available'), '</th>';
            }
            if (($show&8)==8) {
            echo '<th>',AppUtility::t('All'), '</th>';
            }
        echo '</thead><tbody>';

         if (count($gradebook[0][2])>1 || $catfilter!=-1) { //want to show cat headers?
        //$donedbltop = false;
        for ($i=0;$i<count($gradebook[0][2]);$i++) { //category headers

            if ($availshow<2 && $gradebook[0][2][$i][2]>1) {
        continue;
        } else if ($availshow==2 && $gradebook[0][2][$i][2]==3) {
        continue;
        }
        //if (!$donedbltop) {
        //	echo '<tr class="grid dbltop">';
            //	$donedbltop = true;
            //} else {
            echo '<tr class="grid">';
            //}
            echo '<td class="cat'.$gradebook[0][2][$i][1].'"><span class="cattothdr">'.$gradebook[0][2][$i][0].'</span></td>';
            if (($show&1)==1) {
            echo '<td>';
                //show points if not averaging or if points possible scoring

                if ($gradebook[0][2][$i][13]==0 || isset($gradebook[0][3][0])) {
                echo $gradebook[1][2][$i][0].'/'.$gradebook[0][2][$i][3].' (';
                }
                if ($gradebook[0][2][$i][3]>0) {
                echo round(100*$gradebook[1][2][$i][0]/$gradebook[0][2][$i][3],1).'%';
                } else {
                echo '0%';
                }
                if ($gradebook[0][2][$i][13]==0 || isset($gradebook[0][3][0])) {
                echo ')</td>';
            } else {
            echo '</td>';
            }
            }
            if (($show&2)==2) {
            echo '<td>';
                if ($gradebook[0][2][$i][13]==0 || isset($gradebook[0][3][0])) {
                echo $gradebook[1][2][$i][3].'/'.$gradebook[1][2][$i][4].' (';
                }
                if ($gradebook[1][2][$i][4]>0) {
                echo round(100*$gradebook[1][2][$i][3]/$gradebook[1][2][$i][4],1).'%';
                } else {
                echo '0%';
                }
                if ($gradebook[0][2][$i][13]==0 || isset($gradebook[0][3][0])) {
                echo ')</td>';
            } else {
            echo '</td>';
            }
            }
            if (($show&4)==4) {
            echo '<td>';
                if ($gradebook[0][2][$i][13]==0 || isset($gradebook[0][3][0])) {
                echo $gradebook[1][2][$i][1].'/'.$gradebook[0][2][$i][4].' (';
                }
                if ($gradebook[0][2][$i][4]>0) {
                echo round(100*$gradebook[1][2][$i][1]/$gradebook[0][2][$i][4],1).'%';
                } else {
                echo '0%';
                }
                if ($gradebook[0][2][$i][13]==0 || isset($gradebook[0][3][0])) {
                echo ')</td>';
            } else {
            echo '</td>';
            }
            }
            if (($show&8)==8) {
            echo '<td>';
                if ($gradebook[0][2][$i][13]==0 || isset($gradebook[0][3][0])) {
                echo $gradebook[1][2][$i][2].'/'.$gradebook[0][2][$i][5].' (';
                }
                if ($gradebook[0][2][$i][5]>0) {
                echo round(100*$gradebook[1][2][$i][2]/$gradebook[0][2][$i][5],1).'%';
                } else {
                echo '0%';
                }
                if ($gradebook[0][2][$i][13]==0 || isset($gradebook[0][3][0])) {
                echo ')</td>';
            } else {
            echo '</td>';
            }
            }

            echo '</tr>';
        }
        }
        //Totals
        if ($catfilter<0) {
        echo '<tr class="grid">';
            if (isset($gradebook[0][3][0])) { //using points based
            echo '<td>', _('Total'), '</td>';
            if (($show&1)==1) {
            echo '<td>'.$gradebook[1][3][0].'/'.$gradebook[0][3][0].' ('.$gradebook[1][3][3].'%)</td>';
            }
            if (($show&2)==2) {
            echo '<td>'.$gradebook[1][3][6].'/'.$gradebook[1][3][7].' ('.$gradebook[1][3][8].'%)</td>';
            }
            if (($show&4)==4) {
            echo '<td>'.$gradebook[1][3][1].'/'.$gradebook[0][3][1].' ('.$gradebook[1][3][4].'%)</td>';
            }
            if (($show&8)==8) {
            echo '<td>'.$gradebook[1][3][2].'/'.$gradebook[0][3][2].' ('.$gradebook[1][3][5].'%)</td>';
            }

            } else {
            echo '<td>', _('Weighted Total'), '</td>';
            if (($show&1)==1) { echo '<td>'.$gradebook[1][3][0].'%</td>';}
            if (($show&2)==2) {echo '<td>'.$gradebook[1][3][6].'%</td>';}
            if (($show&4)==4) {echo '<td>'.$gradebook[1][3][1].'%</td>';}
            if (($show&8)==8) {echo '<td>'.$gradebook[1][3][2].'%</td>';}
            }
            echo '</tr>';
         }
        echo '</tbody></table><br/>';
    echo '<p>';
        if (($show&1)==1) {
        echo AppUtility::t('<b>Past Due</b> total only includes items whose due date has passed.  Current assignments are not counted in this total.'), '<br/>';
        }
        if (($show&2)==2) {
        echo AppUtility::t('<b>Past Due and Attempted</b> total includes items whose due date has passed, as well as currently available items you have started working on.'), '<br/>';
        }
        if (($show&4)==4) {
        echo AppUtility::t('<b>Past Due and Available</b> total includes items whose due date has passed as well as currently available items, even if you haven\'t starting working on them yet.'), '<br/>';
        }
        if (($show&8)==8) {
        echo AppUtility::t('<b>All</b> total includes all items: past, current, and future to-be-done items.');
        }
        echo '</p>';
    }

    if ($hidepast && $isteacher && $studentId>0) {
    echo '<p><button type="submit" value="Save Changes" style="display:none"; id="savechgbtn">', _('Save Changes'), '</button>';
        echo '<button type="submit" value="Make Exception" name="massexception" >', _('Make Exception'), '</button> ', _('for selected assessments'), '</p>';
    }

    echo "</form>";

echo "<script class='javascript'>initSortTable('myTable',Array($sarr),false);</script>\n";

echo "<p class='text-area-padding'>",AppUtility::t('Meanings: IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sub>d</sub> Dropped score.  <sup>e</sup> Has exception <sup>LP</sup> Used latepass'), "  </p>\n";
                            echo '</div>';?>

