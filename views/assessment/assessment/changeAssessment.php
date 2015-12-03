<?php
use app\components\AssessmentUtility;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
$this->title = AppUtility::t('Mass Change Assessment Settings', false);
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<form id="qform" method=post action="change-assessment?cid=<?php echo $course->id ?>">
    <div class="title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?>
                    <a href="#" onclick="window.open('<?php echo AppUtility::getHomeURL().'docs/help.php?section=assessments' ?>','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"><i class="fa fa-question fa-fw help-icon"></i></a>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox margin-top-fourty padding-one-em">
        <div >
            <?php
            if ($overWriteBody == AppConstant::NUMERIC_ONE) {
                echo "<br><div> <h4 class='assessment-body'>$body </h4><br></div>";
            }else { ?>
             <?php AppUtility::t('This form will allow you to change the assessment settings for several or all assessments at once.'); ?>
            <div class="padding-top-one-em padding-bottom-one-em"><b><?php AppUtility::t('Be aware');?></b> <?php AppUtility::t('that changing default points or penalty after an assessment has been
                taken will not change the scores of students who have already completed the assessment.')?><br/>
                <?php AppUtility::t('This page will')?> <i><?php AppUtility::t('always')?></i> <?php AppUtility::t('show the system default settings; it does not show the current settings for your assessments.')?></div>
            <h3 class="margin-top-zero margin-bottom-zero" ><?php AppUtility::t('Assessments to Change') ?></h3>
            <div class="col-md-12 col-sm-12 padding-top-one-em">
                <div class="padding-left-zero margin-top-five col-sm-6 col-md-4">
                    Check <a href="#"
                              onclick="document.getElementById('selbygbcat').selectedIndex=0;return chkAllNone('qform','checked[]',true)">All</a>
                    <a href="#"
                       onclick="document.getElementById('selbygbcat').selectedIndex=0;return chkAllNone('qform','checked[]',false)">None</a>
                    Check by gradebook category:
                </div>
                <div class="col-md-4 col-sm-5 padding-left-zero">
                    <?php
                    AssessmentUtility::writeHtmlSelect("selbygbcat", $gbcatsId, $gbcatsLabel, null, "Select...", -1, ' onchange="chkgbcat(this.value);" id="selbygbcat" ');
                    ?>
                </div>
            </div>
            <?php
            /******* begin html output ********/
            ?>
            <div>
                <ul id="alistul" class=nomark>
                      <li> <?php echo $page_assessListMsg;?></li>
                    <?php
                    $inblock = 0;
                    for ($i = 0; $i < (count($ids)); $i++) {
                        if (strpos($types[$i], 'Block') !== false) {
                            if ($blockout != '' && $blockid == $parents[$i]) {
                                echo "<li class='margin-top-five'>$blockout</li>";
                                $blockout = '';
                            }
                            $blockout = "<input class='margin-bottom-three' type=checkbox name='checked[]' value='0' id='{$parents[$i]}' ";
                            $blockout .= "onClick=\"chkgrp(this.form, '{$ids[$i]}', this.checked);\" ";
                            $blockout .= '/>';
                            $blockout .= '<i class="margin-left-ten">' . $prespace[$i] . $names[$i] . '</i>';
                            $blockid = $ids[$i];
                        } else {
                            if ($blockout != '' && $blockid == $parents[$i]) {
                                echo "<li class='margin-top-five'>$blockout</li>";
                                $blockout = '';
                            }
                            echo '<li class="margin-top-five">';
                            echo "<input class='margin-bottom-three' type=checkbox name='checked[]' value='{$gitypeids[$i]}' id='{$parents[$i]}.{$ids[$i]}:{$agbcats[$gitypeids[$i]]}' checked=checked ";
                            echo '/>';
                            $pos = strrpos($types[$i], '-');
                            if ($pos !== false) {
                                echo  substr($types[$i], 0, $pos + 1) . ' ';
                            }
                            echo '<span class="margin-left-ten">'.$prespace[$i] . $names[$i].'</span>';
                            echo '</li>';
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
        <div class="col-md-12 col-sm-12">
            <div class="padding-bottom-one-em"><h3><?php AppUtility::t('Assessment Options') ?></h3></div>
            <table class="table table-bordered table-striped table-hover data-table" id="opttable">
                <thead>
                <tr>
                            <th class="col-sm-1 col-md-1"><?php AppUtility::t('Change?') ?></th>
                            <th class="col-sm-4 col-md-4 text-align"><?php AppUtility::t('Option') ?></th>
                            <th class="text-align col-sm-7 col-md-7"><?php AppUtility::t('Setting') ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgsummary" class="chgbox"/></td>
                            <td class="text-align col-sm-4 col-md-4"><?php AppUtility::t('Summary') ?></td>
                            <td class="col-sm-7 col-md-7">
                                <div class="col-sm-4 col-md-2 padding-left-zero">
                                    <?php AppUtility::t('Copy from') ?>
                                </div>
                                <div class="col-sm-8 col-md-9 padding-left-zero">
                                    <?php
                                    AssessmentUtility::writeHtmlSelect("summary", $page_assessSelect['val'], $page_assessSelect['label']);
                                    ?>
                                </div>
                            </td>
                </tr>
                <tr>
                            <td><input type="checkbox" name="chgintro" class="chgbox col-sm-1 col-md-1"/></td>
                            <td class="col-sm-4 col-md-4 text-align"><?php AppUtility::t('Instructions') ?></td>
                    <td class="col-sm-7 col-md-7">
                        <div class="col-sm-4 col-md-2 padding-left-zero"><?php AppUtility::t('Copy from') ?></div>
                                <div class="col-sm-8 col-md-9 padding-left-zero">
                                <?php
                                AssessmentUtility::writeHtmlSelect("intro", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                </div>
                            </td>
                </tr>
                <tr>
                            <td><input type="checkbox" name="chgdates" class="chgbox col-sm-1 col-md-1"/></td>
                            <td class="text-align col-sm-4 col-md-4"><?php AppUtility::t('Dates and Times') ?></td>
                    <td class="col-sm-7 col-md-7">
                        <div class="col-sm-4 col-md-2 padding-left-zero"><?php AppUtility::t('Copy from') ?></div>
                                <div class="col-sm-8 col-md-9 padding-left-zero">
                                <?php
                                AssessmentUtility::writeHtmlSelect("dates", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                </div>
                            </td>
                </tr>
                <tr>
                            <td><input type="checkbox" name="chgavail" class="col-sm-1 col-md-1 chgbox"/></td>
                            <td class="text-align col-sm-4 col-md-4"><?php AppUtility::t('Show'); ?></td>
                            <td class="col-sm-7 col-md-7">
                                <input type=radio name="avail" value="0"/><span class="margin-left-five"><?php AppUtility::t('Hide') ?></span>&nbsp;
                                <input class="" type=radio name="avail" value="1"
                                       checked="checked"/><span class="margin-left-five"><?php AppUtility::t('Show by Dates') ?></span>
                            </td>
                </tr>
                <tr>
                            <td class="col-md-1 col-sm-1" style="border-bottom: 1px solid #000"><input type="checkbox" name="chgcopyendmsg"/></td>
                            <td class="col-md-4 col-sm-4 text-align"
                                style="border-bottom: 1px solid #000"><?php AppUtility::t('End of Assessment Messages') ?>
                            </td>
                            <td class="col-md-7 col-sm-7" style="border-bottom: 1px solid #000">
                                <div class="col-sm-4 col-md-2 padding-left-zero"><?php AppUtility::t('Copy from') ?></div>
                                <div class="col-sm-8 col-md-9 padding-left-zero">
                                <?php
                                AssessmentUtility::writeHtmlSelect("copyendmsg", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                </div>
                                <div class="col-sm-8 col-md-9 padding-left-zero">
                                 <i style="font-size: 75%"><?php AppUtility::t('Use option near the bottom to define new messages') ?></i>
                                    </div>
                            </td>
                </tr>
                <tr>
                            <td><input type="checkbox" name="chgdocopyopt" class="col-md-1 col-sm-1 chgbox"
                                       onClick="copyfromtoggle(this.form,this.checked)"/></td>
                            <td class="col-md-4 col-sm-4 text-align"><?php AppUtility::t('Copy remaining options') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <div class="col-sm-4 col-md-2 padding-left-zero"><?php AppUtility::t('Copy from'); ?></div>
                                <div class="col-sm-8 col-md-9 padding-left-zero">
                                <?php
                                AssessmentUtility::writeHtmlSelect("copyopt", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgpassword" class="chgbox"/></td>
                            <td class="col-sm-4 col-md-4 text-align"><?php AppUtility::t('Require Password (blank for none)') ?></td>
                            <td class="col-sm-7 col-md-7 ">
                                <div class="col-md-11 col-sm-12 padding-left-zero">
                                <input class=" form-control" type=text name="assmpassword" value="" autocomplete="off">
                                </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgtimelimit" class="chgbox"/></td>
                            <td class="col-sm-4 col-md-4 text-align"><?php AppUtility::t('Time Limit (minutes, 0 for no time limit)') ?></td>
                            <td class="col-sm-7 col-md-7">
                                <div class="col-sm-3 col-md-2 padding-left-zero">
                                <input class="form-control display-inline-block" type=text size=4 name="timelimit" value="0"/>
                                </div>
                                <div class="padding-top-five">
                                <input class="margin-left-fifteen" type="checkbox"
                                       name="timelimitkickout"/>
                                <span class="margin-left-five">
                                    <?php AppUtility::t('Kick student out at timelimit') ?>
                                </span>
                                    </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgdisplaymethod" class="chgbox"/></td>
                            <td class="text-align col-sm-4 col-md-4"><?php AppUtility::t('Display method') ?></td>
                            <td class="col-sm-7 col-md-7">
                                <div class="col-md-11 col-sm-12 padding-left-zero">
                                <select name="displaymethod" class="  form-control">
                                    <option
                                        value="AllAtOnce" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "AllAtOnce", 0) ?>>
                                        <?php AppUtility::t('Full test at once') ?>
                                    </option>
                                    <option
                                        value="OneByOne" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "OneByOne", 0) ?>>
                                        <?php AppUtility::t('One question at a time') ?>
                                    </option>
                                    <option
                                        value="Seq" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "Seq", 0) ?>>
                                        <?php AppUtility::t('Full test,submit one at time') ?>
                                    </option>
                                    <option
                                        value="SkipAround" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "SkipAround", 0) ?>>
                                        <?php AppUtility::t('Skip Around') ?>
                                    </option>
                                    <option
                                        value="Embed" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "Embed", 0) ?>>
                                        <?php AppUtility::t('Embedded') ?>
                                    </option>
                                </select>
                                    </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgdefpoints" class="chgbox"/></td>
                            <td class="col-sm-4 col-md-4 text-align"><?php AppUtility::t('Default points per problem') ?></td>
                            <td class="col-sm-7 col-md-7">
                                <div class="col-sm-3 col-md-2 padding-left-zero">
                                <input class="form-control display-inline-block" type=text size=4 name=defpoints value="<?php echo $line['defpoints']; ?>"></div></td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgdefattempts" class="chgbox"/></td>
                            <td class="col-sm-4 col-md-4 text-align">Default attempts per problem (0 for unlimited):</td>
                            <td class="col-sm-7 col-md-7"><div class="col-sm-3 col-md-2 padding-left-zero">
                                <input class="floatleft form-control display-inline-block" type=text size=4 name=defattempts value="<?php echo $line['defattempts']; ?>"> </div>
 					<span id="showreattdiffver" class="<?php if ($testtype != "Practice" && $testtype != "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?> floatleft  margin-top-seven">
 					<input type=checkbox class="margin-left-fifteen"
                           name="reattemptsdiffver" <?php AssessmentUtility::writeHtmlChecked($line['shuffle'] & 8, 8); ?> />
 					<span class="margin-left-five"><?php AppUtility::t('Reattempts different versions')?></span>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgdefpenalty" class="chgbox"/></td>
                            <td class="text-align col-sm-4 col-md-4"><?php AppUtility::t('Default penalty');?></td>
                            <td class="col-sm-7 col-md-7 padding-left-zero">
                                <div class="col-sm-3 col-md-2">
                                <input class="form-control display-inline-block" type=text name=defpenalty style="margin-bottom: 4px"
                                       value="<?php echo $line['defpenalty']; ?>" <?php if ($taken) {
                                    echo 'disabled=disabled';
                                } ?>>
                                <span class="  floatleft margin-top-seven ">%</span>
                                </div>
                                    <div class="col-sm-9 col-md-10">
                                <select class="form-control display-inline-block  " name="skippenalty" <?php if ($taken) {
                                    echo 'disabled=disabled';
                                } ?>>
                                    <option value="0" <?php if ($skippenalty == 0) {
                                        echo "selected=1";
                                    } ?>><?php AppUtility::t('per missed attempt');?>
                                    </option>
                                    <option value="1" <?php if ($skippenalty == 1) {
                                        echo "selected=1";
                                    } ?>><?php AppUtility::t('per missed attempt, after 1');?>
                                    </option>
                                    <option value="2" <?php if ($skippenalty == 2) {
                                        echo "selected=1";
                                    } ?>><?php AppUtility::t('per missed attempt, after 2');?>
                                    </option>
                                    <option value="3" <?php if ($skippenalty == 3) {
                                        echo "selected=1";
                                    } ?>><?php AppUtility::t('per missed attempt, after 3');?>
                                    </option>
                                    <option value="4" <?php if ($skippenalty == 4) {
                                        echo "selected=1";
                                    } ?>><?php AppUtility::t('per missed attempt, after 4');?>
                                    </option>
                                    <option value="5" <?php if ($skippenalty == 5) {
                                        echo "selected=1";
                                    } ?>><?php AppUtility::t('per missed attempt, after 5');?>
                                    </option>
                                    <option value="6" <?php if ($skippenalty == 6) {
                                        echo "selected=1";
                                    } ?>><?php AppUtility::t('per missed attempt, after 6');?>
                                    </option>
                                    <option value="10" <?php if ($skippenalty == 10) {
                                        echo "selected=1";
                                    } ?>><?php AppUtility::t('on last possible attempt only');?>
                                    </option>
                                </select>
                                    </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgfeedback" class="chgbox"/></td>
                            <td class="col-sm-4 col-md-4 text-align">
                                <div class="padding-bottom-one-em">
                                    <?php AppUtility::t('Feedback method');?>
                                </div>
                                <div class=" ">
                                    <?php AppUtility::t('Show Answers');?>
                            </div>
                            </td>
                            <td class="col-sm-7 col-md-7">
                                <div class="col-md-11 col-sm-12 padding-left-zero padding-bottom-one-em">
                                <select class="form-control  " id="deffeedback" name="deffeedback" onChange="chgfb()">
                                    <option value="NoScores" <?php if ($testtype == "NoScores") {
                                        echo "SELECTED";
                                    } ?>><?php AppUtility::t('No scores shown (use with 1 attempt per problem)');?>
                                    </option>
                                    <option value="EndScore" <?php if ($testtype == "EndScore") {
                                        echo "SELECTED";
                                    } ?>><?php AppUtility::t('Just show final score (total points & average) - only whole test can be reattemped')?>
                                    </option>
                                    <option value="EachAtEnd" <?php if ($testtype == "EachAtEnd") {
                                        echo "SELECTED";
                                    } ?>><?php AppUtility::t('Show score on each question at the end of the test')?>
                                    </option>
                                    <option value="EndReview" <?php if ($testtype == "EndReview") {
                                        echo "SELECTED";
                                    } ?>><?php AppUtility::t('Reshow question with score at the end of the test')?>
                                    </option>
                                    <option value="AsGo" <?php if ($testtype == "AsGo") {
                                        echo "SELECTED";
                                    } ?>><?php AppUtility::t("Show score on each question as it's submitted (does not apply to Full test at once display)")?>
                                    </option>
                                    <option value="Practice" <?php if ($testtype == "Practice") {
                                        echo "SELECTED";
                                    } ?>><?php AppUtility::t("Practice test: Show score on each question as it's submitted & can restart test; scores not saved")?>
                                    </option>
                                    <option value="Homework" <?php if ($testtype == "Homework") {
                                        echo "SELECTED";
                                    } ?>><?php AppUtility::t("Homework: Show score on each question as it's submitted & allow similar question to replace missed question")?>
                                    </option>
                                </select>
                                    </div>
					<span id="showanspracspan" class="<?php if ($testtype == "Practice" || $testtype == "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?> margin-top-ten">
                        <div class="col-md-11 col-sm-12 padding-left-zero">
					<select class="form-control  " name="showansprac">
                        <option value="V" <?php if ($showans == "V") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Never, but allow students to review their own answers") ?>
                        </option>
                        <option value="N" <?php if ($showans == "N") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Never, and don't allow students to review their own answers") ?>
                        </option>
                        <option value="F" <?php if ($showans == "F") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After last attempt (Skip Around only)") ?>
                        </option>
                        <option value="J" <?php if ($showans == "J") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After last attempt or Jump to Ans button (Skip Around only)") ?>
                        </option>
                        <option value="0" <?php if ($showans == "0") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Always") ?>
                        </option>
                        <option value="1" <?php if ($showans == "1") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 1 attempt") ?>
                        </option>
                        <option value="2" <?php if ($showans == "2") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 2 attempts") ?>
                        </option>
                        <option value="3" <?php if ($showans == "3") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 3 attempts") ?>
                        </option>
                        <option value="4" <?php if ($showans == "4") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 4 attempts") ?>
                        </option>
                        <option value="5" <?php if ($showans == "5") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 5 attempts") ?>
                        </option>
                    </select>
                            </div>
					</span>
					<span id="showansspan" class="<?php if ($testtype != "Practice" && $testtype != "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?> margin-top-ten">
                        <div class="col-md-11 col-sm-12 padding-left-zero">
					<select class="  form-control" name="showans">
                        <option value="V" <?php if ($showans == "V") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t('Never, but allow students to review their own answers') ?>
                        </option>
                        <option value="N" <?php if ($showans == "N") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Never, and don't allow students to review their own answers") ?>
                        </option>
                        <option value="I" <?php if ($showans == "I") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Immediately (in gradebook) - don't use if allowing multiple attempts per problem") ?>
                        </option>
                        <option value="F" <?php if ($showans == "F") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t('After last attempt (Skip Around only)') ?>
                        </option>
                        <option value="A" <?php if ($showans == "A") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t('After due date (in gradebook)') ?>
                        </option>
                    </select>
                            </div>
					</span>
                            </td>
                </tr>

                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgeqnhelper" class="chgbox"/></td>
                            <td class="col-md-4 col-sm-4 text-align"><?php AppUtility::t('Use equation helper')?>?</td>
                            <td class="col-md-7 col-sm-7">
                                <div class="col-md-11 col-sm-12 padding-left-zero">
                                <select class="form-control" name="eqnhelper">
                                    <option
                                        value="0" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 0) ?>>
                                        No
                                    </option>
                                    <?php
                                    //phase out unless a default
                                    if ($CFG['AMS']['eqnhelper'] == 1 || $CFG['AMS']['eqnhelper'] == 2) {
                                        ?>
                                        <option
                                            value="1" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 1) ?>>
                                            <?php AppUtility::t('Yes, simple form (no logs or trig)')?>
                                        </option>
                                        <option
                                            value="2" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 2) ?>>
                                            <?php AppUtility::t('Yes, advanced form')?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                    <option
                                        value="3" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 3) ?>>
                                        <?php AppUtility::t('MathQuill, simple form');?>
                                    </option>
                                    <option
                                        value="4" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 4) ?>>
                                        <?php AppUtility::t('MathQuill, advanced form')?>
                                    </option>
                                </select>
                                    </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chghints" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Show hints and video/text buttons when available')?>?</td>
                            <td class="col-md-7 col-sm-7">
                                <input type="checkbox"
                                       name="showhints" <?php AssessmentUtility::writeHtmlChecked($line['showhints'], 1); ?>>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgmsgtoinstr" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Show "Message instructor about this question" links');?></td>
                            <td class="col-md-7 col-sm-7">
                                <input type="checkbox"
                                       name="msgtoinstr" <?php AssessmentUtility::writeHtmlChecked($line['msgtoinstr'], 1); ?>/>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgposttoforum" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Show "Post this question to forum" links');?>?</td>
                            <td class="col-md-7 col-sm-7">
                                <span class="pull-left"><input type="checkbox"
                                       name="doposttoforum" <?php AssessmentUtility::writeHtmlChecked($line['posttoforum'], 0, true); ?>
                                       style="margin-bottom: 4px"/> <span class="margin-left-five"> <?php AppUtility::t('To forum');?></span>
                                </span>
                                <div class="display-inline-block margin-left-one-per col-md-9 col-sm-12"> <?php AssessmentUtility::writeHtmlSelect("posttoforum", $page_forumSelect['val'], $page_forumSelect['label'], $line['posttoforum']); ?></div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" name="chgshowtips" class="chgbox"/></td>
                            <td class="col-sm-4 col-md-4 text-align"><?php AppUtility::t('Show answer entry tips')?>?</td>
                            <td class="col-sm-7 col-md-7">
                                <div class="col-md-11 col-sm-12 padding-left-zero">
                                <select name="showtips" class="  form-control">
                                    <option
                                        value="0" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 0) ?>><?php AppUtility::t('No')?>
                                    </option>
                                    <option
                                        value="1" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 1) ?>>
                                        <?php AppUtility::t('Yes, after question')?>
                                    </option>
                                    <option
                                        value="0" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 0) ?>><?php AppUtility::t('No') ?>
                                    </option>
                                    <option
                                        value="1" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 1) ?>>
                                        <?php AppUtility::t('Yes, after question') ?>
                                    </option>
                                    <option
                                        value="2" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 2) ?>>
                                        <?php AppUtility::t('Yes, under answerbox') ?>
                                    </option>
                                </select>
                                    </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-sm-1 col-md-1"><input type="checkbox" style="margin-bottom: 4px" name="chgallowlate" class="chgbox"/>
                            </td>
                            <td class="text-align col-sm-4 col-md-4">
                               <?php AppUtility::t('Allow use of LatePasses?') ?>
                            </td>
                            <td class="col-sm-7 col-md-7">
                                <span class="  display-inline-block col-md-3 col-sm-6 padding-left-zero">
                                <?php
                                AssessmentUtility::writeHtmlSelect("allowlate", $page_allowlateSelect['val'], $page_allowlateSelect['label'], 1);
                                ?>
                                </span>
                                <label class="col-sm-12 col-md-9 padding-left-zero padding-top-five">
                                    <input type="checkbox" name="latepassafterdue">
                                    <span class="margin-left-five"> <?php AppUtility::t('Allow LatePasses after due date, within 1 LatePass period') ?></span>
                                </label>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgnoprint" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Make hard to print?:') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <input type="radio" value="0"
                                       name="noprint" <?php AssessmentUtility::writeHtmlChecked($line['noprint'], 0); ?>/>
                                <span class="margin-left-five"><?php AppUtility::t('No');?></span>
                                <input class="" type="radio" value="1"
                                       name="noprint" <?php AssessmentUtility::writeHtmlChecked($line['noprint'], 1); ?>/>
                                <span class="margin-left-five"><?php AppUtility::t('Yes');?></span>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgshuffle" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Shuffle item order') ?></td>
                            <td class="col-md-7 col-sm-7">
				<span class=formright><input type="checkbox"
                                             name="shuffle" <?php AssessmentUtility::writeHtmlChecked($line['shuffle'] & 1, 1); ?>>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chggbcat" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Gradebook category') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <div class="col-md-11 col-sm-12 padding-left-zero"><?php AssessmentUtility::writeHtmlSelect("gbcat", $gbcatsId, $gbcatsLabel, null, null, null, " id=gbcat"); ?></div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgtutoredit" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Tutor Access') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <div class="col-md-11 col-sm-12 padding-left-zero"> <?php
                                $pageTutorSelect['label'] = array("No access", "View Scores", "View and Edit Scores");
                                $pageTutorSelect['val'] = array(2, 0, 1);
                                AssessmentUtility::writeHtmlSelect("tutoredit", $pageTutorSelect['val'], $pageTutorSelect['label'], $line['tutoredit']);
                                ?>
                                    </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1" style="border-bottom: 1px solid #000"><input type="checkbox" name="chgcntingb" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4" style="border-bottom: 1px solid #000">Count</td>
                            <td style="border-bottom: 1px solid #000" class="col-md-7 col-sm-7">
                                <div>
                                    <input name="cntingb" value="1" checked="checked" type="radio">
                                    <?php AppUtility::t('Count in Gradebook') ?>
                                </div>
                                <div class="margin-top-five">
                                    <input name="cntingb" value="0" type="radio">
                                    <?php AppUtility::t("Don't count in grade total and hide from students") ?>
                                </div>
                                <div class="margin-top-five">
                                    <input name="cntingb" value="3" type="radio">
                                    <?php AppUtility::t("Don't count in grade total") ?>
                                </div>
                                <div class="margin-top-five">
                                    <input name="cntingb" value="2" type="radio">
                                    <?php AppUtility::t('Count as Extra Credit') ?>
                                </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgcaltag" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Calendar icon') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <span>
                                    <span class="pull-left padding-top-five"><?php AppUtility::t('Active') ?></span>
                                    <div class="col-sm-3 col-md-2 padding-left-zero">
                                        <input class="margin-left-five form-control display-inline-block" name="caltagact" type=text size=1 value="<?php echo $line['caltag']; ?>"/>

                                    </div>
                                    <span class="floatleft">,</span>
                                </span>
                               <span><span class="margin-left-ten pull-left padding-top-five"> <?php AppUtility::t('Review') ?></span> <div class="col-sm-3 col-md-2 padding-left-zero"><input class="margin-left-five form-control display-inline-block" name="caltagrev" type=text size=1
                                                                         value="<?php echo $line['calrtag']; ?>"/></div></span>
                            </td>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgminscore" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Minimum score to receive credit') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <div class="col-sm-3 col-md-2 padding-left-zero">
                                    <input class="form-control display-inline-block" type=text size=4 name=minscore value="<?php echo $line['minscore']; ?>"></div>
                                <div class="padding-top-five">
                                <input class="margin-left-fifteen" type="radio" name="minscoretype" value="0" checked="checked">
                                <span class="margin-left-five "><?php AppUtility::t('Points') ?></span>
                                <input class="" type="radio" name="minscoretype" value="1">
                                <span class="class="margin-left-five""><?php AppUtility::t('Percent') ?></span></div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgdeffb" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Default Feedback Text') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <div class="floatleft margin-top-five"><span><?php AppUtility::t('Use?') ?></span>
                                    <input class="margin-left-five" type="checkbox" name="usedeffb">
                                </div>
                                <div class="margin-left-fifteen   floatleft width-eighty-per">
                                    <div class="floatleft margin-top-five padding-right-five"><?php AppUtility::t('Text') ?></div>
                                    <div class="col-md-11 col-sm-12 padding-left-zero">
                                    <input class="display-inline-block  form-control  " type="text" size="60" name="deffb" value="<?php AppUtility::t('This assessment contains items that not automatically graded.  Your grade may be inaccurate until your instructor grades these items.') ?>"/>
                                        </div>
                                </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgreqscore" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Clear "show based on another assessment" settings.') ?></td>
                            <td></td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgsameseed" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('All items same random seed') ?></td>
                            <td class="col-md-7 col-sm-7"><input type="checkbox" name="sameseed"></td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgsamever" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('All students same version of questions') ?></td>
                            <td class="col-md-7 col-sm-7"><input type="checkbox" name="samever"></td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgexcpen" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Penalty for questions done while in exception/LatePass') ?></td>
                            <td class="col-md-7 col-sm-7">
                             <div class="col-sm-3 col-md-2 padding-left-zero">   <input class="form-control display-inline-block" type=text size=4 name="exceptionpenalty"
                                       value="<?php echo $line['exceptionpenalty']; ?>"></div>
                                <div class="padding-top-five"> %</div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgshowqcat" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Show question categories') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <div class="">
                                    <input name="showqcat" value="0" checked="checked" type="radio">
                                    <span class="margin-left-five"><?php AppUtility::t('No ') ?></span>
                                </div>
                                <div class="margin-top-five">
                                    <input name="showqcat" value="1" type="radio">
                                    <span class="margin-left-five"><?php AppUtility::t('In Points Possible bar') ?></span>
                                </div>
                                <div class="margin-top-five">
                                    <input name="showqcat" value="2" type="radio">
                                    <span class="margin-left-five"><?php AppUtility::t('In navigation bar (Skip-Around only)') ?></span>
                                </div>
                            </td>
                </tr>
                <tr class=" ">
                            <td class="col-md-1 col-sm-1"><input type="checkbox" name="chgistutorial" class="chgbox"/></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Display for tutorial-style questions') ?></td>
                            <td class="col-md-7 col-sm-7">
                                <input type="checkbox" name="istutorial"/>
                            </td>
                </tr>
                <tr>
                            <td class="col-md-1 col-sm-1" style="border-top: 1px solid #000"></td>
                            <td class="text-align col-md-4 col-sm-4"
                                style="border-top: 1px solid #000"><?php AppUtility::t('Define end of assessment messages?') ?>
                            </td>
                            <td class="col-md-7 col-sm-7" style="border-top: 1px solid #000">
                                <input type="checkbox" name="chgendmsg" class="chgbox"/>
                                <span class="margin-left-five"><?php AppUtility::t('You will be taken to a page to change these after you hit submit') ?></span>
                            </td>
                </tr>
                <tr>
                            <td class="col-md-1 col-sm-1"></td>
                            <td class="text-align col-md-4 col-sm-4"><?php AppUtility::t('Remove per-question settings (points, attempts, etc.) for all questions in these assessments?') ?>
                            </td>
                            <td class="col-md-7 col-sm-7"><input type="checkbox" name="removeperq" class="chgbox"/></td>
                </tr>
                </tbody>
            </table>
        <?php if ($overWriteBody != AppConstant::NUMERIC_ONE) { ?>
            <div class="header-btn col-sm-4 padding-left-zero padding-top-ten padding-bottom-twenty">
                <button class="btn btn-primary page-settings" type="submit" value="Submit">
                    <i class="fa fa-share header-right-btn"></i><?php echo 'Apply Changes' ?>
                </button>
            </div>
        <?php } ?>
        </div>
        <?php } ?>
    </div>
</form>
