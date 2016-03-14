<?php
use \app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AssessmentUtility;
if ($params['gbitem'] == 'new') {
    $this->title = AppConstant::ADD_OFFLINE_GRADE;
} else {
    $this->title = AppConstant::MODIFY_OFFLINE_GRADE;
}
?>
<div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name,'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<form id="mainform" method=post action="add-grades?stu=<?php echo $params['stu']; ?>&cid=<?php echo $course->id ?>&gbmode=<?php echo $params['gbmode'] ?>&gbitem=<?php echo $params['gbitem'] ?>&grades=<?php echo $params['grades'] ?>" onsubmit="return valform();">
<div class="title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']); ?>
</div>
<div class="tab-content shadowBox">
<div class="col-sm-12 col-md-12 add-offline-grades-form">
<?php
 if (!$isteacher) {
    echo AppConstant::NO_TEACHER_RIGHTS;
}
if (isset($params['del']) && $isteacher) {
    if ($isDelete) { ?>
        <p><?php AppConstant::CONFIRMATION_MESSAGE;?></p>
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?stu=' . $params['stu'] . '&gbmode=' . $params['gbmode'] . '&cid=' . $params['cid'] . '&del=' . $params['del'] . '&confirm=' . true) ?>">
            <?php AppConstant::CONFIRM_DELETE?></a>
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?stu=' . $params['stu'] . '&gbmode=' . $params['gbmode'] . '&cid=' . $params['cid']); ?>"> </a>
    <?php }
}
if ($params['grades'] == 'all') {
    if (!($_GET['isolate'])) {
$name = $defaultValuesArray[0];
$points = $defaultValuesArray[1];
$showdate = $defaultValuesArray[2];
$gbcat = $defaultValuesArray[3];
$cntingb = $defaultValuesArray[4];
$tutoredit = $defaultValuesArray[5];
$rubric = $defaultValuesArray[6];
$gradeoutcomes = $defaultValuesArray[7];
if ($showdate != 0) {
    $sdate = AppUtility::tzdate("m/d/Y", $showdate);
    $stime = AppUtility::tzdate("g:i a", $showdate);
} else {
    $sdate = AppUtility::tzdate("m/d/Y", time() + 60 * 60);
    $stime = AppUtility::tzdate("g:i a", time() + 60 * 60);
}
$rubric_vals[] = $rubricsId;
$rubric_names[] = $rubricsLabel;
?>

<div class="col-sm-12 col-md-12">
    <div class="col-sm-3 col-md-2 select-text-margin display-inline-block">
        <?php AppUtility::t('Name')?>
    </div>
    <div class="col-sm-4 col-md-4 display-inline-block">
        <input class="form-control" type=text name="name" maxlength="30" value="<?php echo $name; ?>"/>
    </div>
</div>
<div class="col-sm-12 col-md-12 padding-top-twenty">
    <div class="col-sm-3 col-md-2 select-text-margin  display-inline-block">
        <?php AppUtility::t('Points')?>
    </div>
    <div class="col-sm-4 col-md-4">
        <div class="staticParent"><input class="form-control" id="child" type=text name="points" size=3 maxlength="10" value="<?php echo $points; ?>"/></div>
    </div>
</div>
<div class="col-sm-12 col-md-12 padding-top-twenty">
    <div class="col-sm-3 col-md-2">
        <?php AppUtility::t('Show grade to students after') ?>
    </div>
    <div class="col-sm-8 col-md-8 padding-left-zero">
        <div class="col-sm-12 col-md-12">
            <input class="margin-top-five" type=radio name="available-after" value="0"  />
                                    <span class="padding-left">
                                        <?php AppUtility::t('Always') ?>
                                    </span>
        </div>
        <div class="col-sm-12 col-md-12 padding-top-fifteen">
            <label class="non-bold floatleft margin-top-five">
                <input type=radio name="available-after" checked value="1" ?>
            </label>
            <?php
            echo '<div class="floatleft width-thirty-three-per margin-left-twenty time-input">';
            echo DatePicker::widget([
                'name' => 'sdate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $defaultValuesArray['sdate'],
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy']
            ]);
            echo '</div>';
            ?>
            <?php
            echo '<label class="padding-left-twenty end pull-left non-bold select-text-margin"> at </label>';
            echo '<div class="floatleft width-fifty-per padding-left-twenty">';
            echo TimePicker::widget([
                'name' => 'stime',
                'value' => $defaultValuesArray['stime'],
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';
            ?>
        </div>
    </div>
</div>
<div class="col-sm-12 col-md-12 padding-top-twenty">
    <div class="col-sm-3 col-md-2 select-text-margin">
        <span><?php AppUtility::t('Gradebook Category');?></span>
    </div>
    <div class="col-sm-4 col-md-4">
        <span class=formright><select name=gbcat id=gbcat class='form-control'>
               <?php echo "<option value=\"0\" ";
                if ($gbcat==0) {
                echo "selected=1 ";
                }
                echo ">Default</option>\n";
                if (count($gbcatsData)>0) {

                foreach($gbcatsData as $row){
                echo "<option value=\"{$row['id']}\" ";
                if ($gbcat==$row['id']) {
                echo "selected=1 ";
                }
                echo ">{$row['name']}</option>\n";
                }

                }
                echo "</select></span><br class=form>\n"; ?>
    </div>
</div>
<div class="col-sm-12 col-md-12 padding-top-twenty">
    <div class="col-sm-3 col-md-2 margin-top-thirty-eight">
        <?php AppUtility::t('Count');?>
    </div>
    <div class="col-sm-9 col-md-10 padding-left-zero">
        <div class="col-sm-12 col-md-12 ">
            <input type=radio name="cntingb" checked value="1" />
            <span class="margin-left-five"><?php AppUtility::t('Count in Gradebook');?></span>
        </div>
        <div class="col-sm-12 col-md-12 margin-top-five">
            <input type=radio name="cntingb" value="0" />
            <span class="margin-left-five"><?php AppUtility::t("Don't count in grade total and hide from students")?></span>
        </div>
        <div class="col-sm-12 col-md-12 margin-top-five">
            <input type=radio name="cntingb" value="3" />
            <span class="margin-left-five"><?php AppUtility::t("Don't count in grade total")?></span>
        </div>
        <div class="col-sm-12 col-md-12 margin-top-five">
            <input type=radio name="cntingb" value="2"/>
            <span class="margin-left-five"><?php AppUtility::t('Count as Extra Credit');?></span>
        </div>
    </div>
</div>
<?php
$page_tutorSelect['label'] = array("No access to scores", "View Scores", "View and Edit Scores");
$page_tutorSelect['val'] = array(2, 0, 1);
?>
<div class="col-sm-12 col-md-12 padding-top-twenty">
    <div class="col-sm-3 col-md-2 select-text-margin">Tutor Access</div>
    <div class="col-sm-4 col-md-4">
        <?php
        AssessmentUtility::writeHtmlSelect("tutoredit", $page_tutorSelect['val'], $page_tutorSelect['label'], $tutoredit);
        echo '<input type="hidden" name="gradesecret" value="' . $tutoredit . '"/>';
        ?>
    </div>
</div>

<div class="col-sm-12 col-md-12 padding-top-twenty">
    <div class="col-sm-3 col-md-2 select-text-margin">
        <?php AppUtility::t('Use Scoring Rubric')?>
    </div>
    <div class="col-sm-4 col-md-4">
        <div class="col-sm-12 col-md-12 padding-zero">
            <?php AssessmentUtility::writeHtmlSelect('rubric', $rubricsId, $rubricsLabel, $rubric); ?>
        </div>

        <div class="col-sm-12 col-md-12 padding-zero padding-top-fifteen">
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-rubric?cid=' . $course->id.'&id=new&from=addg&gbitem='.$params['gbitem']) ?>">
                <?php AppUtility::t('Add new rubric')?></a> |
            <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-rubric?cid=' . $course->id.'&from=addg&nomanage=&gbitem='.$params['gbitem']) ?>">
                <?php AppUtility::t('Edit rubrics')?>
            </a>
        </div>
    </div>
</div>
<div class="col-md-12 item-alignment">
    <?php if (count($pageOutcomesList) > 0) { ?>
        <div class="col-sm-3 col-md-2"><?php AppUtility::t('Associate Outcomes')?></div>
        <div class="col-sm-9 col-md-10 padding-left-zero">
            <?php
            $gradeoutcomes = array();
            AssessmentUtility::writeHtmlMultiSelect('outcomes', $pageOutcomesList, $pageOutcomes, $gradeoutcomes, 'Select an outcome...');
            ?>
        </div>
    <?php } ?>
</div>
<?php if ($params['gbitem'] != 'new') { ?>
    <div class="col-sm-12 col-md-12 margin-top-ten">
        <div class="col-sm-4 col-md-4 col-md-offset-2 col-sm-offset-3 padding-top-one-em">
            <input type=submit value="Submit"/>
            <a class="margin-left-twenty" href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-grades?stu='.$params['stu'].'&gbmode='.$params['gbmode'].'&cid='.$params['cid'].'&del='.$params['gbitem'])?>">Delete Item</a>
        </div>
    </div>
<?php } else { ?>
    <div class="col-sm-12 col-md-12 padding-top-twenty">
        <div class="col-sm-3 col-md-2 select-text-margin">
            <span><?php AppUtility::t('Upload grades?');?></span>
        </div>
        <div class="col-sm-9 col-md-10">
            <input type=checkbox name="doupload" />
            <input class="margin-left-thirty" type=submit value="Submit"/>
        </div>
    </div>
<?php }
if ($params['gbitem'] == 'new') { ?>
<div class="col-sm-12 col-md-12 padding-top-twenty">
    <div class="col-sm-3 col-md-2">
        <?php AppUtility::t('Assessment snapshot?')?>
    </div>
    <div class="col-sm-9 col-md-10 padding-left-zero">
        <?php echo '<input class="margin-left-sixteen" type="checkbox" name="assesssnapaid" onclick="
        if(this.checked){
            this.nextSibling.style.display=\'\';
            document.getElementById(\'gradeboxes\').style.display=\'none\';
            }else{
            this.nextSibling.style.display=\'none\';
            document.getElementById(\'gradeboxes\').style.display=\'\';
            }"/>';
        echo '<span style="display:none;"> <div class="col-sm-12 col-md-12 padding-top-twenty"><span>Assessment to snapshot: </span><span class="assessment-name">';
        AssessmentUtility::writeHtmlSelect('assessment', $assessmentId, $assessmentLabel, 0);
        echo '</span></div>';
        ?>
        <div class="col-sm-12 col-md-12 padding-top-twenty">
            <?php AppUtility::t('Grade type:')?><br/>
            <input class="margin-left-five" type="radio" name="assesssnaptype" value="0" checked="checked">
            <?php AppUtility::t('Current score')?><br/>
            <input class="margin-left-five" type="radio" name="assesssnaptype" value="1">
            <?php AppUtility::t('Participation give full credit if &ge;')?>
            <div class="grade">
            <input class="width-six-per form-control display-inline-block margin-left-five" id="grade-type" type="text" name="assesssnapatt" value="100" size="3">
                <span class="margin-left-five"> % </span> <?php AppUtility::t('of problems attempted and &ge;')?>
            <input id="grade-type" class="width-six-per form-control display-inline-block margin-left-five" type="text" name="assesssnappts" value="0" size="3">
                <span class="margin-left-five"> <?php AppUtility::t('points earned')?></span></div>
        </div>
        <div class="col-sm-12 col-md-12 padding-top-twenty"><input type=submit value="Submit"/></div>
        </span>
    </div>
    <?php }
    } else {
        ?>
        <div class="margin-left-twenty-eight"> <h3><?php echo $gbItems['name'];?></h3> </div>
        <?php     $rubric = $gbItems['rubric'];
                  $points = $gbItems['points'];
    }
    } else { ?>
        <h3 class="padding-left-thirty margin-top-minus-ten"><?php echo $gbItems['name']; ?></h3>
        <?php $rubric = $gbItems['rubric'];
        $points = $gbItems['points'];
    }
    if ($gbItems['rubric'] != 0) {
        if (count($rubricFinalData) > 0) {
            echo printrubrics(array($rubricFinalData));
        }
    }
    if ($params['grades'] == 'all' && $params['gbitem'] != 'new' && $isteacher) { ?>
        <div class="col-sm-12 col-md-12">
            <div class="col-sm-3 col-md-2">
                <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/upload-grades?gbmode='.$params['gbmode'].'&cid='.$params['cid'].'&gbitem='.$params['gbitem'])?>">Upload Grades</a>
            </div>
        </div>
    <?php }

    echo '<div id="gradeboxes" class="col-md-12 col-sm-12 padding-left-zero padding-right-zero">';
    echo '<div class="col-sm-12 col-md-12 padding-top-twenty">';
    echo '<div class="col-md-offset-2 col-sm-offset-3 col-sm-8 col-md-8 padding-left-six">
          <input type=button value="Expand Feedback Boxes" onClick="togglefeedback(this)"/>
          <button class="margin-left-twenty" type="button" id="useqa" onclick="togglequickadd(this)">' . "Use Quicksearch Entry" . '</button> </div>';
    echo '</div>';

    if ($hassection) {
        echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
    }
    if ($params['grades'] == 'all') {
        echo '<div class="col-sm-12 col-md-12 padding-top-twenty padding-left-zero">';
        echo "<div class='col-sm-3 col-md-2 padding-right-zero'>Add/Replace to all grades</div>
                                        <div class='col-sm-9 col-md-10 padding-left-seventeen'>
                                            <div class='floatleft all-grade'>
                                                <input class='width-seventy-seven form-control to-all-grade' type=text size=3 id=\"toallgrade\" maxlength='15' onblur=\"this.value = doonblur(this.value);\"/>
                                            </div>";
        echo '<div class="floatleft margin-left-twenty">';
        echo ' <input class="width-seventy-seven" type=button value="Add" onClick="sendtoall(0,0);"/>
                                                   </div>
                                            <div class="floatleft margin-left-twenty">
                                            <input type=button class="width-seventy-seven" value="Multiply" onclick="sendtoall(0,1)"/>
                                            </div>
                                            <div class="floatleft  margin-left-twenty">
                                            <input class="width-seventy-seven" type=button value="Replace" onclick="sendtoall(0,2)"/>
                                            </div>
                                        </div>';
        echo '</div>';

        echo '<div class="col-sm-12 col-md-12 padding-top-twenty padding-left-zero">';
        echo "<div class='col-sm-3 col-md-2'> Add/Replace to all feedback</div>
                                    <div class='col-sm-5 col-md-5 padding-left-seventeen'>
                                         <div class='col-sm-10 col-md-10 padding-left-zero'>
                                         <input class='floatleft form-control' type=text size=40 id=\"toallfeedback\"/></div>";
        echo '<div class="col-sm-12 col-md-12 clear-both padding-top-twenty padding-left-zero"><input class="floatleft margin-right-fifteen" type=button value="Append" onClick="sendtoall(1,0);"/>
                                          <input class="floatleft margin-right-fifteen" type=button value="Prepend" onclick="sendtoall(1,1)"/>
                                          <input class="floatleft" type=button value="Replace" onclick="sendtoall(1,2)"/>
                                          </div>
                                     </div>';
        echo '</div>';
    }
    echo '<div class="col-sm-12 col-md-12 padding-top-twenty">';
    echo "<table class='col-md-12 col-sm-12' id=myTable>
                                    <thead>
                                        <tr>
                                            <th class='col-md-2 col-sm-2'>Name</th>";
    if ($hassection) {
        echo '<th class="col-md-1 col-sm-1">Section</th>';
    }
    echo "<th class='col-md-2 col-sm-2'>Grade</th>
                                            <th class='col-md-7 col-sm-7'>Feedback</th>
                                        </tr>
                                    </thead>
                                    <tbody>";
    echo '<tr id="quickadd" style="display:none;">
               <td><input class="form-control" maxlength="40" type="text" id="qaname" /></td>';
    if ($hassection) {
        echo '<td></td>';
    }
    echo '<td class="quick-score"><input class="form-control quick-score-class" type="text" id="qascore" size="3" maxlength="15" onblur="this.value = doonblur(this.value);" onkeydown="return qaonenter(event,this);" /></td>';
    echo '<td><textarea class="form-control floatleft width-sixty-per max-width-three-hundread" id="qafeedback" rows="1" cols="40"></textarea>';
    echo '<input class="form-control floatleft width-ten-per margin-left-twenty"  type="button" value="Next" onfocus="addsuggest()" /></td></tr>';
    if ($params['gbitem'] != "new") {
        foreach ($gradeData as $row) {
            if ($row['score'] != null) {
                $score[$row['userid']] = $row['score'];
            } else {
                $score[$row['userid']] = '';
            }
            $feedback[$row['userid']] = $row['feedback'];
        }
    }
    foreach ($finalStudentArray as $studentInfo) {

        if ($studentInfo[4] > 0) {
            echo '<tr><td style="text-decoration: line-through;">';
        } else {
            echo '<tr><td>';
        }
        echo "{$studentInfo[1]}, {$studentInfo[2]}";
        echo '</td>';
        if ($hassection) {
            echo "<td>{$studentInfo[3]}</td>";
        }
        if (isset($score[$studentInfo[0]])) {
            echo "<td class='col-md-2 staticField'><input class='form-control score'  type=\"text\" size=\"3\" autocomplete=\"off\" maxlength='15' name=\"score[{$studentInfo[0]}]\" id=\"score{$studentInfo[0]}\" value=\"";
            echo $score[$studentInfo[0]];
        } else {
            echo "<td class='col-md-2 staticField'><input class='form-control score'  type=\"text\" size=\"3\" autocomplete=\"off\" maxlength='15' name=\"newscore[{$studentInfo[0]}]\" id=\"score{$studentInfo[0]}\" value=\"";
        }

        echo "\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" />";
        if ($rubric != 0) {
            echo printrubriclink($rubric, $points, "score{$studentInfo[0]}", "feedback{$studentInfo[0]}");
        }
        echo "</td>";
        echo "<td class='col-md-8'>
                  <textarea class='form-control col-md-12 max-width-six-hundread-ten mobile-text-area-max-width' cols=60 rows=1 id=\"feedback{$studentInfo[0]}\" name=\"feedback[{$studentInfo[0]}]\">{$feedback[$studentInfo[0]]}</textarea>
              </td>";
        echo "</tr>";
    }

    echo "</tbody>
                        </table>
            </div>"; ?>
    <div class="header-btn col-sm-9 col-md-10 padding-top-twenty padding-bottom-ten col-md-offset-2 col-sm-offset-3">
        <button class="btn btn-primary page-settings" type="submit" value="Submit">
            <i class="fa fa-share header-right-btn"></i><?php echo 'Submit' ?>
        </button>
    </div>
    <?php if ($hassection) {
        echo "<script type='javascript'> initSortTable('myTable',Array('S','S',false,false),false);</script>";
    }
    ?>
</div>
</div>
</div>
</form>

<?php
$placeinfooter = '<div id="autosuggest"><ul></ul></div>';


function getpts($sc)
{
    if (strpos($sc, '~') === false) {
        if ($sc > 0) {
            return $sc;
        } else {
            return 0;
        }
    } else {
        $sc = explode('~', $sc);
        $tot = 0;
        foreach ($sc as $s) {
            if ($s > 0) {
                $tot += $s;
            }
        }
        return round($tot, 1);
    }
}

function printrubrics($rubricarray)
{
    $out = '<script type="text/javascript">';
    $out .= 'var imasrubrics = new Array();';
    foreach ($rubricarray as $info) {
        $out .= "imasrubrics[{$info[0]}] = {'type':{$info[1]},'data':[";
        $data = unserialize($info[2]);
        foreach ($data as $i => $rubline) {
            if ($i != 0) {
                $out .= ',';
            }
            $out .= '["' . str_replace('"', '\\"', $rubline[0]) . '",';
            $out .= '"' . str_replace('"', '\\"', $rubline[1]) . '"';
            $out .= ',' . $rubline[2];
            $out .= ']';
        }
        $out .= ']};';
    }
    $out .= '</script>';
    return $out;
}

function printrubriclink($rubricid, $points, $scorebox, $feedbackbox, $qn = 'null', $width = 600)
{
    $out = "<a onclick=\"imasrubric_show($rubricid,$points,'$scorebox','$feedbackbox','$qn',$width); return false;\" href=\"#\">";
    $out .= "<img border=0 src='../../img/assess.png' alt=\"rubric\"></a>";
    return $out;
}

?>
<script>
    $(document).ready(function(){
        $(function() {
            $('.staticParent').on('keydown', '#child', function(e){-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()});
        })
        $(function() {
            $('.grade').on('keydown', '#grade-type', function(e){-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()});
        })
        $(function() {
            $('.staticField').on('keydown', '.score', function(e){-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()});
        })
        $(function() {
            $('.all-grade').on('keydown', '.to-all-grade', function(e){-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()});
        })
        $(function() {
            $('.quick-score').on('keydown', '.quick-score-class', function(e){-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()});
        })
    })

</script>


