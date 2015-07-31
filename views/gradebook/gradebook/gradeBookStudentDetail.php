<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AssessmentUtility;
$studentId = -1;
if($StudentData){
    $studentId = $currentUser['id'];
}
if($studentId > 0){

    $this->title = 'Grade Book Student Detail';
     ?><legend xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">Grade Book Student Detail</legend> <?php
}else{
    $this->title = 'Grade Book Averages';
?><legend xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">Grade Book Averages</legend><?php
}

//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid=' . $course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-5 clear-both col-lg-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3  text-align-left'],
            ],
        ]);  ?>

<?php
  //show student view
//     $pagetitle = _('Gradebook');

$gradebook = $totalData['gradebook'];
$hidenc = $defaultValuesArray['hidenc'];
$cid = $defaultValuesArray['hidenc'];
$gbmode =  ' ';
$availshow= $defaultValuesArray['availshow'];
$catfilter = $defaultValuesArray['catfilter'];
//$canviewall = ' ';
$urlmode =  'http://';
$includeduedate = $defaultValuesArray['includeduedate'];
$includelastchange = $defaultValuesArray['includelastchange'];
$latepasshrs = $course['latepasshrs'];
$isteacher = false;
$canviewall = false;
if($totalData['isTeacher']){
    $isteacher = true;
    $canviewall = true;
}
$istutor = false;
if($totalData['isTutor']){
    $istutor = true;
    $canviewall = true;
}
if ($canviewall) {
    echo "<div class=cpmid>";
    echo _('Category'), ': <select id="filtersel" onchange="chgfilter()">';
    echo '<option value="-1" ';
    if ($catfilter==-1) {echo "selected=1";}
    echo '>', _('All'), '</option>';
    echo '<option value="0" ';
    if ($catfilter==0) { echo "selected=1";}
    echo '>',AppUtility::t('Default'), '</option>';
     foreach($gbCatsData as $gbCats){

        echo '<option value="'.$gbCatsData[0].'"';
        if ($catfilter==$gbCatsData[0]) {echo "selected=1";}
        echo '>'.$gbCatsData[1].'</option>';
    }
    echo '<option value="-2" ';
    if ($catfilter==-2) {echo "selected=1";}
    echo '>',AppUtility::t('Category Totals'), '</option>';
    echo '</select> | ';
    echo _('Not Counted:'), " <select id=\"toggle2\" onchange=\"chgtoggle()\">";
    echo "<option value=0 "; AssessmentUtility::writeHtmlSelected($hidenc,0); echo ">",AppUtility::t('Show all'), "</option>";
    echo "<option value=1 "; AssessmentUtility::writeHtmlSelected($hidenc,1); echo ">",AppUtility::t('Show stu view'), "</option>";
    echo "<option value=2 "; AssessmentUtility::writeHtmlSelected($hidenc,2); echo ">",AppUtility::t('Hide all'), "</option>";
    echo "</select>";
    echo " | ", _('Show:'), " <select id=\"toggle3\" onchange=\"chgtoggle()\">";
    echo "<option value=0 "; AssessmentUtility::writeHtmlSelected($availshow,0); echo ">",AppUtility::t('Past due'), "</option>";
    echo "<option value=3 "; AssessmentUtility::writeHtmlSelected($availshow,3); echo ">",AppUtility::t('Past &amp; Attempted'), "</option>";
    echo "<option value=4 "; AssessmentUtility::writeHtmlSelected($availshow,4); echo ">",AppUtility::t('Available Only'), "</option>";
    echo "<option value=1 "; AssessmentUtility::writeHtmlSelected($availshow,1); echo ">",AppUtility::t('Past &amp; Available'), "</option>";
    echo "<option value=2 "; AssessmentUtility::writeHtmlSelected($availshow,2); echo ">",AppUtility::t('All'), "</option></select>";
    echo " | ", _('Links:'), " <select id=\"toggle1\" onchange=\"chgtoggle()\">";
    echo "<option value=0 "; AssessmentUtility::writeHtmlSelected($links,0); echo ">",AppUtility::t('View/Edit'), "</option>";
    echo "<option value=1 "; AssessmentUtility::writeHtmlSelected($links,1); echo ">",AppUtility::t ('Scores'), "</option></select>";
    echo '<input type="hidden" id="toggle4" value="'.$showpics.'" />';
    echo '<input type="hidden" id="toggle5" value="'.$hidelocked.'" />';
    echo "</div>";
}

if ($availshow==4) {
$availshow=1;
$hidepast = true;
}
$studentId = -1;
if($StudentData){
$studentId = $currentUser['id'];
}
if ($studentId>0) {
    $showlatepass = $course['showlatepass'];
  $latepasshrs = $course['latepasshrs'];
}

if ($studentId>0) {
echo '<div style="font-size:1.1em;font-weight:bold">';
    if ($isteacher || $istutor) {
    if ($gradebook[1][0][1] != '') {
        $usersort = $stugbmode['usersort'];
    } else {
    $usersort = 1;
    }

    if ($gradebook[1][4][2]==1) {
    if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
    echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$gradebook[1][4][0]}.jpg\" onclick=\"togglepic(this)\" class=\"mida\"/> ";
    } else {
    echo "<img src=\"$imasroot/course/files/userimg_sm{$gradebook[1][4][0]}.jpg\" style=\"float: left; padding-right:5px;\" onclick=\"togglepic(this)\" class=\"mida\"/>";
    }
    }
    $query = "SELECT iu.id,iu.FirstName,iu.LastName,istu.section FROM imas_users AS iu JOIN imas_students as istu ON iu.id=istu.userid WHERE istu.courseid='$cid' ";
    if ($usersort==0) {
    $query .= "ORDER BY istu.section,iu.LastName,iu.FirstName";
    } else {
    $query .= "ORDER BY iu.LastName,iu.FirstName";
    }
    $result = mysql_query($query) or die("Query failed : " . mysql_error());
    echo '<select id="userselect" style="border:0;font-size:1.1em;font-weight:bold" onchange="chgstu(this)">';
        $lastsec = '';
        while ($row = mysql_fetch_row($result)) {
        if ($row[3]!='' && $row[3]!=$lastsec && $usersort==0) {
        if ($lastsec=='') {echo '</optgroup>';}
        echo '<optgroup label="Section '.htmlentities($row[3]).'">';
            $lastsec = $row[3];
            }
            echo '<option value="'.$row[0].'"';
            if ($row[0]==$stu) {
            echo ' selected="selected"';
            }
            echo '>'.$row[2].', '.$row[1].'</option>';
            }
            if ($lastsec!='') {echo '</optgroup>';}
        echo '</select>';
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
    </div>
<?php if ($isteacher) {
echo '<div style="clear:both;display:inline-block" class="cpmid secondary">';
    //echo '<a href="mailto:'.$stuemail.'">', _('Email'), '</a> | ';
    echo "<a href=\"#\" onclick=\"GB_show('Send Email','$imasroot/course/sendmsgmodal.php?to=$stu&sendtype=email&cid=$cid',800,'auto')\" title=\"Send Email\">", _('Email'), "</a> | ";

    //echo "<a href=\"$imasroot/msgs/msglist.php?cid={$_GET['cid']}&add=new&to=$stu\">", _('Message'), "</a> | ";
    echo "<a href=\"#\" onclick=\"GB_show('Send Message','$imasroot/course/sendmsgmodal.php?to=$stu&sendtype=msg&cid=$cid',800,'auto')\" title=\"Send Message\">", _('Message'), "</a> | ";
    echo "<a href=\"gradebook.php?cid={$_GET['cid']}&uid=$stu&massexception=1\">", _('Make Exception'), "</a> | ";
    echo "<a href=\"listusers.php?cid={$_GET['cid']}&chgstuinfo=true&uid=$stu\">", _('Change Info'), "</a> | ";
    echo "<a href=\"viewloginlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">", _('Login Log'), "</a> | ";
    echo "<a href=\"viewactionlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">", _('Activity Log'), "</a> | ";
    echo "<a href=\"#\" onclick=\"makeofflineeditable(this); return false;\">", _('Edit Offline Scores'), "</a>";
    echo '</div>';
} else if ($istutor) {
echo '<div style="clear:both;display:inline-block" class="cpmid">';
    echo "<a href=\"viewloginlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">", _('Login Log'), "</a> | ";
    echo "<a href=\"viewactionlog.php?cid={$_GET['cid']}&uid=$stu&from=gb\">", _('Activity Log'), "</a>";
    echo '</div>';
}

if (trim($gbcomment)!='' || $isteacher) {
if ($isTeacher) { ?>
 <form method=post action=\"gradebook.php?{$_SERVER['QUERY_STRING']}\">
    Gradebook Comment: <input type=submit value="Update Comment"><br/>
  <textarea name=\"usrcomments\" rows=3 cols=60><?php echo $gbcomment;?></textarea>
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
if ($isteacher || $istutor) {echo ']<br/>';}
$lpmsg = "$latepasses LatePass".($latepasses!=1?"es":"").' available';
}
if (!$isteacher && !$istutor) {
echo $lpmsg;
}

}
?>
 <form method=\"post\" id=\"qform\" action=\"gradebook.php?{$_SERVER['QUERY_STRING']}&uid=$studentId\">
<!--    <input type='button' onclick='conditionalColor(\"myTable\",1,50,80);' value='Color'/> -->
 <?php if ($isteacher && $studentId>0) {
    echo '<button type="submit" value="Save Changes" style="display:none"; id="savechgbtn">', _('Save Changes'), '</button> ';
    echo _('Check:'), ' <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'assesschk[]\',false)">', _('None'), '</a> ';
    echo _('With selected:'), ' <button type="submit" value="Make Exception" name="posted">',_('Make Exception'),'</button> '.$lpmsg.'';
    } ?>
    <table id="myTable" class="gb table table-bordered table-striped table-hover data-table" style="position:relative;">
    <?php
        echo '<thead><tr>';
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
  <?          $sarr = "false,'S','N','N','N','N'";
            if ($includelastchange) { ?>
             <th> <?php AppUtility::t('Last Changed');?></th>
           <?php $sarr .= ",'D'";
            }
            if ($includeduedate) {
            echo '<th>',AppUtility::t('Due Date'),'</th>';
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
            echo '</tr></thead><tbody>';
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
                )) {
                echo ' <span class="small"><a href="redeemlatepass.php?cid='.$cid.'&aid='.$gradebook[0][1][$i][7].'">[';
                        echo AppUtility::t('Use LatePass').']</a></span>';
                if ($now>$gradebook[0][1][$i][11]) {
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
                if (isset($gradebook[1][1][$i][0])) { //has score
                echo "<a href=\"gb-itemanalysis.php?stu=$studentId&cid=$cid&aid={$gradebook[0][1][$i][7]}\">";
                    $haslink = true;
                    }
                    } else {
                    if (isset($gradebook[1][1][$i][0])) { //has score
                    echo "<a href=\"gb-viewasid.php?stu=$studentId&cid=$cid&asid={$gradebook[1][1][$i][4]}&uid={$gradebook[1][4][0]}\"";
                             if ($afterduelatepass) {
                             echo ' onclick="return confirm(\''._('If you view this assignment, you will not be able to use a LatePass on it').'\');"';
                    }
                    echo ">";
                    $haslink = true;
                    } else if ($isteacher) {
                    echo "<a href=\"gb-viewasid.php?stu=$studentId&cid=$cid&asid=new&aid={$gradebook[0][1][$i][7]}&uid={$gradebook[1][4][0]}\">";
                        $haslink = true;
                        }
                        }
                        } else if ($gradebook[0][1][$i][6]==1) {//offline
                        if ($isteacher || ($istutor && $gradebook[0][1][$i][8]==1)) {
                        if ($studentId==-1) {
                        if (isset($gradebook[1][1][$i][0])) { //has score
                        echo "<a href=\"addgrades.php?stu=$studentId&cid=$cid&grades=all&gbitem={$gradebook[0][1][$i][7]}\">";
                            $haslink = true;
                            }
                            } else {
                            echo "<a href=\"addgrades.php?stu=$studentId&cid=$cid&grades={$gradebook[1][4][0]}&gbitem={$gradebook[0][1][$i][7]}\">";
                                $haslink = true;
                                }
                                }
                                } else if ($gradebook[0][1][$i][6]==2) {//discuss
                                if ($studentId != -1) {
                                echo "<a href=\"viewforumgrade.php?cid=$cid&stu=$studentId&uid={$gradebook[1][4][0]}&fid={$gradebook[0][1][$i][7]}\">";
                                    $haslink = true;
                                    }
                                    } else if ($gradebook[0][1][$i][6]==3) {//exttool
                                    if ($isteacher || ($istutor && $gradebook[0][1][$i][8]==1)) {
                                    echo "<a href=\"edittoolscores.php?cid=$cid&stu=$studentId&uid={$gradebook[1][4][0]}&lid={$gradebook[0][1][$i][7]}\">";
                                        $haslink = true;
                                        }
                                        }
                                        }
                                        if (isset($gradebook[1][1][$i][0])) {
                                        if ($gradebook[1][1][$i][3]>9) {
                                        $gradebook[1][1][$i][3] -= 10;
                                        }
                                        echo $gradebook[1][1][$i][0];
                                        if ($gradebook[1][1][$i][3]==1) {
                                        echo ' (NC)';
                                        } else if ($gradebook[1][1][$i][3]==2) {
                                        echo ' (IP)';
                                        } else if ($gradebook[1][1][$i][3]==3) {
                                        echo ' (OT)';
                                        } else if ($gradebook[1][1][$i][3]==4) {
                                        echo ' (PT)';
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

    echo '<table class="gb"><thead>';
        echo '<tr>';
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
            echo '</tr>';
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

echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";
/*
if ($hidepast) {
echo "<script>initSortTable('myTable',Array($sarr),false);</script>\n";
} else if ($availshow==2) {
echo "<script>initSortTable('myTable',Array($sarr),false,-3);</script>\n";
} else {
echo "<script>initSortTable('myTable',Array($sarr),false,-2);</script>\n";
}
*/
?>

<?php ActiveForm::end(); ?>
<?php
echo "<p>",AppUtility::t('Meanings: IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sub>d</sub> Dropped score.  <sup>e</sup> Has exception <sup>LP</sup> Used latepass'), "  </p>\n";
?>


 <script type="text/javascript">
    function showhidefb(el,n) {
        el.style.display="none";
        document.getElementById("feedbackholder"+n).style.display = "inline";
        return false;
    }
    function showhideallfb(s) {
        s.style.display="none";
        var els = document.getElementsByTagName("a");
        for (var i=0;i<els.length;i++) {
            if (els[i].className.match("feedbacksh")) {
                els[i].style.display="none";
            }
        }
        var els = document.getElementsByTagName("span");
        for (var i=0;i<els.length;i++) {
            if (els[i].id.match("feedbackholder")) {
                els[i].style.display="inline";
            }
        }

    }

 </script>


