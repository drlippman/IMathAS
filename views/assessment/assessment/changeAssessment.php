<?php
use app\components\AssessmentUtility;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
$this->title = AppUtility::t('Mass Change Assessment Settings', false);
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<form id="qform" method=post action="change-assessment?cid=<?php echo $course->id ?>">
    <div class="title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?><img class="help-img" src="<?php echo AppUtility::getAssetURL()?>img/helpIcon.png" alt="Help" onClick="window.open('<?php echo AppUtility::getHomeURL() ?>docs/help.php?section=assessments','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></div>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox margin-top-fourty padding-top-fifteen padding-bottom-ten">
        <div class="change-assessment">
            <?php
            if ($overWriteBody == AppConstant::NUMERIC_ONE) {
                echo "<br><div> <h4 class='assessment-body'>$body </h4><br></div>";
            }else { ?>
            <p><?php AppUtility::t('This form will allow you to change the assessment settings for several or all assessments at once.'); ?></p>
            <p><b><?php AppUtility::t('Be aware');?></b> <?php AppUtility::t('that changing default points or penalty after an assessment has been
                taken will not change the scores of students who have already completed the assessment.')?><br/>
                <?php AppUtility::t('This page will')?> <i><?php AppUtility::t('always')?></i> <?php AppUtility::t('show the system default settings; it does not show the current settings for your assessments.')?></p>
            <h3 class="margin-top-ten"><?php AppUtility::t('Assessments to Change') ?></h3>
            <div class="col-lg-12">
                <div class="pull-left margin-top-five">
                    Check <a href="#"
                              onclick="document.getElementById('selbygbcat').selectedIndex=0;return chkAllNone('qform','checked[]',true)">All</a>
                    <a href="#"
                       onclick="document.getElementById('selbygbcat').selectedIndex=0;return chkAllNone('qform','checked[]',false)">None</a>
                    Check by gradebook category:
                </div>
                <div class="col-lg-4">
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
        <div class="change-assessment">
            <div class="margin-top-fifteen"><?php AppUtility::t('Assessment Options') ?></div>
            <table class="table table-bordered table-striped table-hover data-table" id="opttable">
                <thead>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <th><?php AppUtility::t('Change?') ?></th>
                        </div>
                        <div class="col-lg-4">
                            <th class="text-align"><?php AppUtility::t('Option') ?></th>
                        </div>
                        <div class="col-lg-6">
                            <th class="text-align"><?php AppUtility::t('Setting') ?></th>
                        </div>
                    </div>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgsummary" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4 ">
                            <td class="text-align"><?php AppUtility::t('Summary') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <div class="pull-left margin-top-ten"><?php AppUtility::t('Copy from:') ?></div>
                                <div class="display-inline-block width-fifty-per margin-left-thirty assessment-copy-from clear-both">
                                    <?php
                                    AssessmentUtility::writeHtmlSelect("summary", $page_assessSelect['val'], $page_assessSelect['label']);
                                    ?>
                                </div>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgintro" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Instructions') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <div class="pull-left margin-top-ten"><?php AppUtility::t('Copy from:') ?></div>
                                <div class="display-inline-block width-fifty-per margin-left-thirty assessment-copy-from clear-both">
                                <?php
                                AssessmentUtility::writeHtmlSelect("intro", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                </div>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgdates" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Dates and Times') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <div class="pull-left margin-top-ten"><?php AppUtility::t('Copy from:') ?></div>
                                <div class="display-inline-block width-fifty-per margin-left-thirty assessment-copy-from clear-both">
                                <?php
                                AssessmentUtility::writeHtmlSelect("dates", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                </div>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgavail" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Show'); ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type=radio name="avail" value="0"/><span class="margin-left-five"><?php AppUtility::t('Hide') ?></span>
                                <input class="margin-left-fifty" type=radio name="avail" value="1"
                                       checked="checked"/><span class="margin-left-five"><?php AppUtility::t('Show by Dates') ?></span>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td style="border-bottom: 1px solid #000"><input type="checkbox" name="chgcopyendmsg"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"
                                style="border-bottom: 1px solid #000"><?php AppUtility::t('End of Assessment Messages') ?>
                            </td>
                        </div>
                        <div class="col-lg-6">
                            <td style="border-bottom: 1px solid #000">
                                <div class="pull-left margin-top-ten"><?php AppUtility::t('Copy from') ?></div>
                                <div class="display-inline-block width-fifty-per margin-left-thirty-five assessment-copy-from clear-both">
                                <?php
                                AssessmentUtility::writeHtmlSelect("copyendmsg", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                </div>
                                <br/><i
                                    style="font-size: 75%"><?php AppUtility::t('Use option near the bottom to define new messages') ?></i>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgdocopyopt" class="chgbox"
                                       onClick="copyfromtoggle(this.form,this.checked)"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Copy remaining options') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <div class="pull-left margin-top-ten"><?php AppUtility::t('Copy from'); ?></div>
                                <div class="display-inline-block width-fifty-per margin-left-thirty-five assessment-copy-from clear-both">
                                <?php
                                AssessmentUtility::writeHtmlSelect("copyopt", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                </div>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgpassword" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Require Password (blank for none)') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input class="form-control" type=text name="assmpassword" value="" autocomplete="off"></td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgtimelimit" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Time Limit (minutes, 0 for no time limit)') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input class="form-control width-ten-per display-inline-block" type=text size=4 name="timelimit" value="0"/>
                                <input class="margin-left-thirty" type="checkbox"
                                       name="timelimitkickout"/>
                                <span class="margin-left-five">
                                    <?php AppUtility::t('Kick student out at timelimit') ?>
                                </span>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgdisplaymethod" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Display method') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <select name="displaymethod" class="form-control">
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
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgdefpoints" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Default points per problem') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input class="form-control width-ten-per display-inline-block" type=text size=4 name=defpoints value="<?php echo $line['defpoints']; ?>"></td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgdefattempts" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align">Default attempts per problem (0 for unlimited):</td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input class="floatleft form-control width-ten-per display-inline-block" type=text size=4 name=defattempts value="<?php echo $line['defattempts']; ?>">
 					<span id="showreattdiffver" class="<?php if ($testtype != "Practice" && $testtype != "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?> floatleft margin-left-thirty margin-top-seven">
 					<input type=checkbox
                           name="reattemptsdiffver" <?php AssessmentUtility::writeHtmlChecked($line['shuffle'] & 8, 8); ?> />
 					<span class="margin-left-five"><?php AppUtility::t('Reattempts different versions')?></span>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgdefpenalty" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Default penalty');?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input class="col-lg-1 form-control width-ten-per display-inline-block" type=text size=4 name=defpenalty style="margin-bottom: 4px"
                                       value="<?php echo $line['defpenalty']; ?>" <?php if ($taken) {
                                    echo 'disabled=disabled';
                                } ?>><span class="floatleft margin-top-seven margin-left-five">%</span>
                                <select class="col-lg-5 form-control display-inline-block width-fifty-per margin-left-thirty" name="skippenalty" <?php if ($taken) {
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
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgfeedback" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align">
                                <div>
                                    <?php AppUtility::t('Feedback method');?>
                                </div>
                                <div class="margin-top-twenty-two">
                                    <?php AppUtility::t('Show Answers');?></td>
                                </div>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <select class="form-control" id="deffeedback" name="deffeedback" onChange="chgfb()">
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
					<span id="showanspracspan" class="<?php if ($testtype == "Practice" || $testtype == "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?> margin-top-ten">
					<select class="form-control" name="showansprac">
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
					</span>
					<span id="showansspan" class="<?php if ($testtype != "Practice" && $testtype != "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?> margin-top-ten">
					<select class="form-control" name="showans">
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
					</span>
                            </td>
                        </div>
                    </div>
                </tr>

                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgeqnhelper" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Use equation helper')?>?</td>
                        </div>
                        <div class="col-lg-6">
                            <td>
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
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chghints" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Show hints and video/text buttons when available')?>?</td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type="checkbox"
                                       name="showhints" <?php AssessmentUtility::writeHtmlChecked($line['showhints'], 1); ?>>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgmsgtoinstr" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Show "Message instructor about this question" links');?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type="checkbox"
                                       name="msgtoinstr" <?php AssessmentUtility::writeHtmlChecked($line['msgtoinstr'], 1); ?>/>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgposttoforum" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Show "Post this question to forum" links');?>?</td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type="checkbox"
                                       name="doposttoforum" <?php AssessmentUtility::writeHtmlChecked($line['posttoforum'], 0, true); ?>
                                       style="margin-bottom: 4px"/> <span class="margin-left-five"> <?php AppUtility::t('To forum');?></span><div class="display-inline-block margin-left-eighteen width-fifty-per"> <?php AssessmentUtility::writeHtmlSelect("posttoforum", $page_forumSelect['val'], $page_forumSelect['label'], $line['posttoforum']); ?></div>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgshowtips" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Show answer entry tips')?>?</td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <select name="showtips" class="form-control">
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
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" style="margin-bottom: 4px" name="chgallowlate" class="chgbox"/>
                            </td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align">
                               <?php AppUtility::t('Allow use of LatePasses?') ?>
                            </td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <span class="floatleft display-inline-block width-twenty-per">
                                <?php
                                AssessmentUtility::writeHtmlSelect("allowlate", $page_allowlateSelect['val'], $page_allowlateSelect['label'], 1);
                                ?>
                                </span>
                                <label class="floatleft margin-left-thirty margin-top-eight">
                                    <input type="checkbox" name="latepassafterdue">
                                    <span class="margin-left-five"> <?php AppUtility::t('Allow LatePasses after due date, within 1 LatePass period') ?></span>
                                </label>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgnoprint" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Make hard to print?:') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type="radio" value="0"
                                       name="noprint" <?php AssessmentUtility::writeHtmlChecked($line['noprint'], 0); ?>/>
                                <span class="margin-left-five"><?php AppUtility::t('No');?></span>
                                <input class="margin-left-thirty" type="radio" value="1"
                                       name="noprint" <?php AssessmentUtility::writeHtmlChecked($line['noprint'], 1); ?>/>
                                <span class="margin-left-five"><?php AppUtility::t('Yes');?></span>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgshuffle" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Shuffle item order') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
				<span class=formright><input type="checkbox"
                                             name="shuffle" <?php AssessmentUtility::writeHtmlChecked($line['shuffle'] & 1, 1); ?>>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chggbcat" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Gradebook category') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <?php
                                AssessmentUtility::writeHtmlSelect("gbcat", $gbcatsId, $gbcatsLabel, null, null, null, " id=gbcat");
                                ?>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgtutoredit" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Tutor Access') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <?php
                                $pageTutorSelect['label'] = array("No access", "View Scores", "View and Edit Scores");
                                $pageTutorSelect['val'] = array(2, 0, 1);
                                AssessmentUtility::writeHtmlSelect("tutoredit", $pageTutorSelect['val'], $pageTutorSelect['label'], $line['tutoredit']);
                                ?>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td style="border-bottom: 1px solid #000"><input type="checkbox" name="chgcntingb" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align" style="border-bottom: 1px solid #000">Count</td>
                        </div>
                        <div class="col-lg-6">
                            <td style="border-bottom: 1px solid #000">
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
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgcaltag" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Calendar icon') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <?php AppUtility::t('Active') ?> <input class="margin-left-five form-control width-ten-per display-inline-block" name="caltagact" type=text size=1
                                                                         value="<?php echo $line['caltag']; ?>"/>&nbsp;,
                               <span class="margin-left-ten"> <?php AppUtility::t('Review') ?></span> <input class="margin-left-five form-control width-ten-per display-inline-block" name="caltagrev" type=text size=1
                                                                         value="<?php echo $line['calrtag']; ?>"/>
                            </td>
                        </div>
                    </div>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgminscore" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Minimum score to receive credit') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input class="form-control width-ten-per display-inline-block" type=text size=4 name=minscore value="<?php echo $line['minscore']; ?>">
                                <input class="margin-left-thirty" type="radio" name="minscoretype" value="0" checked="checked">
                                <span class="margin-left-five"><?php AppUtility::t('Points') ?></span>
                                <input class="margin-left-thirty" type="radio" name="minscoretype" value="1">
                                <span class="class="margin-left-five""><?php AppUtility::t('Percent') ?></span>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgdeffb" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Default Feedback Text') ?></td>
                        </div>
                        <div class="</div>6">
                            <td>
                                <div class="floatleft margin-top-five"><span><?php AppUtility::t('Use?') ?></span>
                                    <input class="margin-left-five" type="checkbox" name="usedeffb">
                                </div>
                                <div class="margin-left-thirty display-inline width-fifty-per floatleft width-eighty-per">
                                    <span class="floatleft margin-top-five"><?php AppUtility::t('Text') ?></span>
                                    <input class="display-inline-block margin-left-five form-control width-eighty-per" type="text" size="60" name="deffb" value="<?php AppUtility::t('This assessment contains items that not automatically graded.  Your grade may be inaccurate until your instructor grades these items.') ?>"/>
                                </div>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgreqscore" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Clear "show based on another assessment" settings.') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td></td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgsameseed" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('All items same random seed') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input type="checkbox" name="sameseed"></td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgsamever" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('All students same version of questions') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input type="checkbox" name="samever"></td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgexcpen" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Penalty for questions done while in exception/LatePass') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input class="form-control width-ten-per display-inline-block" type=text size=4 name="exceptionpenalty"
                                       value="<?php echo $line['exceptionpenalty']; ?>"><span class="margin-left-five"> %</span>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgshowqcat" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Show question categories') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
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
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgistutorial" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Display for tutorial-style questions') ?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type="checkbox" name="istutorial"/>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td style="border-top: 1px solid #000"></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"
                                style="border-top: 1px solid #000"><?php AppUtility::t('Define end of assessment messages?') ?>
                            </td>
                        </div>
                        <div class="col-lg-6">
                            <td style="border-top: 1px solid #000">
                                <input type="checkbox" name="chgendmsg" class="chgbox"/>
                                <span class="margin-left-five"><?php AppUtility::t('You will be taken to a page to change these after you hit submit') ?></span>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Remove per-question settings (points, attempts, etc.) for all questions in these assessments?') ?>
                            </td>
                        </div>
                        <div class="col-lg-6">
                            <td><input type="checkbox" name="removeperq" class="chgbox"/></td>
                        </div>
                    </div>
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
