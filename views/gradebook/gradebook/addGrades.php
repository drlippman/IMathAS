<?php
use \app\components\AppUtility;
use app\components\AppConstant;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AssessmentUtility;
/*
 * Assign proper hyper link when handling breadcrumb
 */
//if ($params['stu'] > 0) {
//    echo "&gt; <a href=\"gradebook.php?stu={$_GET['stu']}&cid=$cid\">Student Detail</a> ";
//} else if ($params['stu'] == -1) {
//    echo "&gt; <a href=\"gradebook.php?stu={$_GET['stu']}&cid=$cid\">Averages</a> ";
//}
if ($params['gbitem'] == 'new') {
    $this->title = AppConstant::ADD_OFFLINE_GRADE;
} else {
    $this->title = AppConstant::MODIFY_OFFLINE_GRADE;
}
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<form id="mainform" method=post xmlns="http://www.w3.org/1999/html"
      action="add-grades?stu=<?php echo $params['stu']; ?>&cid=<?php echo $course->id ?>&gbmode=<?php echo $params['gbmode'] ?>&cid=<?php echo $params['cid'] ?>&gbitem=<?php echo $params['gbitem'] ?>&grades=<?php echo $params['grades'] ?>"
      onsubmit="return valform();">
    <div class="title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?> </div>
            </div>
            <div class="pull-left header-btn">
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i
                        class="fa fa-share header-right-btn"></i><?php echo 'Apply Changes' ?></button>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox non-nav-tab-item">
        <?php
        if ($istutor && $isTutorEdit) {
                echo AppConstant::NO_AUTHORITY;
        } else if (!$isteacher) {
            echo AppConstant::NO_TEACHER_RIGHTS;
        }else{
            if (isset($params['del']) && $isteacher) {
                if ($isDelete) { ?>
                    <p><?php AppConstant::CONFIRMATION_MESSAGE;?></p>
                    <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?stu=' . $params['stu'] . '&gbmode=' . $params['gbmode'] . '&cid=' . $params['cid'] . '&del=' . $params['del'] . '&confirm=' . true) ?>">
                        <?php AppConstant::CONFIRM_DELETE?></a>
                    <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?stu=' . $params['stu'] . '&gbmode=' . $params['gbmode'] . '&cid=' . $params['cid']); ?>"> </a>
                <?php }
            }
            if ($params['grades'] == 'all') {
                if (!isset($params['isolate'])) {
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
        <span ><?php AppUtility::t('Name:')?></span><span class><input type=text name="name" value="<?php echo $name; ?>"/></span><br class="form"/>
        <br>
        <span><?php AppUtility::t('Points:')?></span><span class><input type=text name="points" size=3 value="<?php echo $points; ?>"/></span><br class="form"/>

        <div class=col-lg-2><?php AppUtility::t('Show grade to students after:') ?></div>
        <div class=col-lg-10>
            <input type=radio name="available-after" class="pull-left" value="0"  /><span class="pull-left padding-left"><?php AppUtility::t('Always until end date') ?></span>
            <label class="pull-left non-bold" style="padding-left: 40px"><input type=radio name="available-after" class="pull-left" checked value="1" ?></label>
            <?php
                    echo '<div class = "time-input pull-left col-lg-4">';
                    echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => $defaultValuesArray['sdate'],
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left non-bold"> at </label>';
                    echo '<div class="pull-left col-lg-4">';
                    echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => $defaultValuesArray['stime'],
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
        </div><BR class=form><BR class=form>

        <div class="item-alignment">
            <div class=col-lg-2><?php AppUtility::t('Gradebook Category:');?></div>
            <div class=col-lg-10>
                <?php AssessmentUtility::writeHtmlSelect("gradebook-category", $gbcatsId, $gbcatsLabel, 0, "Default", 0); ?>
            </div>
            <br class=form>

        </div><BR class=form>
        <span class=form><?php AppUtility::t('Count:');?> </span>
        <span class="formright">
				<input type=radio name="cntingb" checked
                       value="1" /> <?php AppUtility::t('Count in Gradebook');?><br/>
				<input type=radio name="cntingb"
                       value="0" /> <?php AppUtility::t("Don't count in grade total and hide from students")?><br/>
				<input type=radio name="cntingb"
                       value="3" /> <?php AppUtility::t("Don't count in grade total")?><br/>
				<input type=radio name="cntingb"
                       value="2"/> <?php AppUtility::t('Count as Extra Credit');?>
			</span><br class=form>

        <?php $page_tutorSelect['label'] = array("No access to scores", "View Scores", "View and Edit Scores");
                    $page_tutorSelect['val'] = array(2, 0, 1); ?>

</span>
        <br class=form>
<span >Tutor Access:</span>
<span >
	<?php
                    AssessmentUtility::writeHtmlSelect("tutoredit", $page_tutorSelect['val'], $page_tutorSelect['label'], $checkboxesValues['tutoredit']);
                    echo '<input type="hidden" name="gradesecret" value="' . $checkboxesValues['gradesecret'] . '"/>';
                    ?>
			</span><br class="form"/>
        <br class=form>
<div class="item-alignment">
    <div class="col-lg-2"><?php AppUtility::t('Use Scoring Rubric')?></div>
    <div class=col-lg-10>
        <?php AssessmentUtility::writeHtmlSelect('rubric', $rubricsId, $rubricsLabel, 0, 'None', 0); ?>
        <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-rubric?cid=' . $course->id) ?>">Add
            <?php AppUtility::t('new rubric')?></a> | <a
            href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/edit-rubric?cid=' . $course->id) ?>"><?php AppUtility::t('Edit rubrics')?></a>
    </div>
    <br class=form>
</div>
<div class="item-alignment">
    <?php if (count($pageOutcomesList) > 0) { ?>
                        <div class="col-lg-2"><?php AppUtility::t('Associate Outcomes:')?></div>
                        <div class="col-lg-10">
                        <?php
                        $gradeoutcomes = array();
                        AssessmentUtility::writeHtmlMultiSelect('outcomes', $pageOutcomesList, $pageOutcomes, $gradeoutcomes, 'Select an outcome...'); ?>
                        <br class="form"/>
                    <?php } ?>
        <br class=form>
    </div>
</div>
<?php
                     if ($params['gbitem'] != 'new') { ?>
                        <br class=form /><div class="submit"><input type=submit value="Submit"/> <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-grades?stu='.$params['stu'].'&gbmode='.$params['gbmode'].'&cid='.$params['cid'].'&del='.$params['gbitem'])?>">Delete Item</a> </div><br class=form />
                    <?php } else { ?>
                      <span class=form><?php AppUtility::t('Upload grades?');?></span><span class=formright><input type=checkbox name="doupload" /> <input type=submit value="Submit"/></span><br class=form />
                   <?php }
                    if ($params['gbitem'] == 'new') { ?>
                         <span class="form"><?php AppUtility::t('Assessment snapshot?')?></span><span class="formright">
                        <?php echo '<input type="checkbox" name="assesssnapaid" onclick="if(this.checked){this.nextSibling.style.display=\'\';document.getElementById(\'gradeboxes\').style.display=\'none\';}else{this.nextSibling.style.display=\'none\';document.getElementById(\'gradeboxes\').style.display=\'\';}"/>';
                        echo '<span style="display:none;"><br/>';
                        AssessmentUtility::writeHtmlSelect('assessment', $assessmentId, $assessmentLabel, 0); ?>
                         <?php AppUtility::t('Grade type:')?><br/> <input type="radio" name="assesssnaptype" value="0" checked="checked"><?php AppUtility::t('Current score')?>
                         <br/><input type="radio" name="assesssnaptype" value="1"><?php AppUtility::t('Participation: give full credit if')?>
                         <input type="text" name="assesssnapatt" value="100" size="3">% <?php AppUtility::t('of problems attempted and')?>
                         <input type="text" name="assesssnappts" value="0" size="3"> <?php AppUtility::t('points earned')?>
                         <br/><input type=submit value="Submit"/></span><br class="form" />
                         </span>
                    <?php }
                } else {
                    echo '<h3>$gbItems[\'name\']</h3>';
                    $rubric = $gbItems['rubric'];
                    $points = $gbItems['points'];

                }
            } else { ?>
                <h3><?php echo $gbItems['name']; ?></h3>
                <?php $rubric = $gbItems['rubric'];
                $points = $gbItems['points'];
            }
            if ($gbItems['rubric'] != 0) {
                if (count($rubricFinalData) > 0) {
                    echo printrubrics(array($rubricFinalData));
                }
            }
            if ($params['grades'] == 'all' && $params['gbitem'] != 'new' && $isteacher) { ?>
                 <p><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/upload-grades?gbmode='.$params['gbmode'].'&cid='.$params.'&gbitem='.$params['gbitem'])?>">Upload Grades</a></p>
            <?php }

            echo '<div id="gradeboxes">';
            echo '<input type=button value="Expand Feedback Boxes" onClick="togglefeedback(this)"/> ';
            echo '<button type="button" id="useqa" onclick="togglequickadd(this)">' . "Use Quicksearch Entry" . '</button>';

            if ($hassection) {
                echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
            }

            if ($params['grades'] == 'all') {
                echo "<br/><span class=form>Add/Replace to all grades:</span><span class=formright><input type=text size=3 id=\"toallgrade\" onblur=\"this.value = doonblur(this.value);\"/>";
                echo ' <input type=button value="Add" onClick="sendtoall(0,0);"/> <input type=button value="Multiply" onclick="sendtoall(0,1)"/> <input type=button value="Replace" onclick="sendtoall(0,2)"/></span><br class="form"/>';
                echo "<span class=form>Add/Replace to all feedback:</span><span class=formright><input type=text size=40 id=\"toallfeedback\"/>";
                echo ' <input type=button value="Append" onClick="sendtoall(1,0);"/> <input type=button value="Prepend" onclick="sendtoall(1,1)"/> <input type=button value="Replace" onclick="sendtoall(1,2)"/></span><br class="form"/>';
            }

            echo '<div class="clear"></div>';
            echo "<table id=myTable><thead><tr><th>Name</th>";
            if ($hassection) {
                echo '<th>Section</th>';
            }
            echo "<th>Grade</th><th>Feedback</th></tr></thead><tbody>";
            echo '<tr id="quickadd" style="display:none;"><td><input type="text" id="qaname" /></td>';
            if ($hassection) {
                echo '<td></td>';
            }
            echo '<td><input type="text" id="qascore" size="3" onblur="this.value = doonblur(this.value);" onkeydown="return qaonenter(event,this);" /></td>';
            echo '<td><textarea id="qafeedback" rows="1" cols="40"></textarea>';
            echo '<input type="button" value="Next" onfocus="addsuggest()" /></td></tr>';
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
                    echo "<td><input type=\"text\" size=\"3\" autocomplete=\"off\" name=\"score[{$studentInfo[0]}]\" id=\"score{$studentInfo[0]}\" value=\"";
                    echo $score[$studentInfo[0]];
                } else {
                    echo "<td><input type=\"text\" size=\"3\" autocomplete=\"off\" name=\"newscore[{$studentInfo[0]}]\" id=\"score{$studentInfo[0]}\" value=\"";
                }

                echo "\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" onblur=\"this.value = doonblur(this.value);\" />";
                if ($rubric != 0) {
                    echo printrubriclink($rubric, $points, "score{$studentInfo[0]}", "feedback{$studentInfo[0]}");
                }
                echo "</td>";
                echo "<td><textarea cols=60 rows=1 id=\"feedback{$studentInfo[0]}\" name=\"feedback[{$studentInfo[0]}]\">{$feedback[$studentInfo[0]]}</textarea></td>";
                echo "</tr>";
            }

            echo "</tbody></table>";
            if ($hassection) {
                echo "<script> initSortTable('myTable',Array('S','S',false,false),false);</script>";
            }
        } ?>
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



