<?php
use app\components\AssessmentUtility;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;

$this->title = AppUtility::t('Mass Change Assessment Settings',false);
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<form id="qform" method=post action="change-assessment?cid=<?php echo $course->id ?>">
    <div class="title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?> </div>
            </div>
            <?php if ($overWriteBody != 1) { ?>
                <div class="pull-left header-btn">
                    <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i
                            class="fa fa-share header-right-btn"></i><?php echo 'Apply Changes' ?></button>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="tab-content shadowBox non-nav-tab-item">
        <div class="change-assessment">
            <?php

            if ($overWriteBody == 1) {
                echo "<br><div style='  margin-left: 35%;'> <h4 class=''>$body </h4><br></div>";
            }else { ?>
            <p><?php AppUtility::t('This form will allow you to change the assessment settings for several or all assessments at once.');?></p>
            <p><b>Be aware</b> that changing default points or penalty after an assessment has been
                taken will not change the scores of students who have already completed the assessment.<br/>
                This page will <i>always</i> show the system default settings; it does not show the current settings for your
                assessments.</p>
            <h3>Assessments to Change</h3>

            <p><b><?php AppUtility::t('Be aware')?></b><?php AppUtility::t('that changing default points or penalty after an assessment has been
                taken will not change the scores of students who have already completed the assessment.')?><br/>
                <?php AppUtility::t('This page will')?> <i><?php AppUtility::t('always')?></i> <?php AppUtility::t('show the system default settings; it does not show the current settings for
                your assessments.')?></p>

            <h3><?php AppUtility::t('Assessments to Change')?></h3>

            <div class="col-lg-12">
                <div class="pull-left">
                    Check: <a href="#"
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
                    <?php
                    echo $page_assessListMsg;
                    $inblock = 0;
                    for ($i = 0; $i < (count($ids)); $i++) {

                        if (strpos($types[$i], 'Block') !== false) {
                            if ($blockout != '' && $blockid == $parents[$i]) {
                                echo "<li>$blockout</li>";
                                $blockout = '';
                            }
                            $blockout = "<input type=checkbox name='checked[]' value='0' id='{$parents[$i]}' ";
                            $blockout .= "onClick=\"chkgrp(this.form, '{$ids[$i]}', this.checked);\" ";
                            $blockout .= '/>';
                            $blockout .= '<i>' . $prespace[$i] . $names[$i] . '</i>';
                            $blockid = $ids[$i];

                        } else {
                            if ($blockout != '' && $blockid == $parents[$i]) {
                                echo "<li>$blockout</li>";
                                $blockout = '';
                            }
                            echo '<li>';
                            echo "<input type=checkbox name='checked[]' value='{$gitypeids[$i]}' id='{$parents[$i]}.{$ids[$i]}:{$agbcats[$gitypeids[$i]]}' checked=checked ";
                            echo '/>';
                            $pos = strrpos($types[$i], '-');
                            if ($pos !== false) {
                                echo substr($types[$i], 0, $pos + 1) . ' ';
                            }
                            echo $prespace[$i] . $names[$i];
                            echo '</li>';
                        }

                    }

                    /*for ($i=0;$i<count($page_assessSelect['val']);$i++) {
                    ?>
                            <li><input type=checkbox name='checked[]' value='<?php echo $page_assessSelect['val'][$i] ?>' checked=checked><?php echo $page_assessSelect['label'][$i] ?></li>
                <?php
                    }*/
                    ?>
                </ul>
            </div>
        </div>
        <div class="change-assessment">
            <legend><?php AppUtility::t('Assessment Options')?></legend>
            <table class="table table-bordered table-striped table-hover data-table" id="opttable">
                <thead>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <th><?php AppUtility::t('Change?')?></th>
                        </div>
                        <div class="col-lg-4">
                            <th class="text-align"><?php AppUtility::t('Option')?></th>
                        </div>
                        <div class="col-lg-6">
                            <th class="text-align"><?php AppUtility::t('Setting')?></th>
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
                            <td class="text-align"><?php AppUtility::t('Summary:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <div class="pull-left"><?php AppUtility::t('Copy from:')?></div>
                                <div class="">
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
                            <td class="text-align"><?php AppUtility::t('Instructions:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <div class="pull-left"><?php AppUtility::t('Copy from:')?></div>
                                <?php
                                AssessmentUtility::writeHtmlSelect("intro", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
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
                            <td class="text-align"><?php AppUtility::t('Dates and Times:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <div class="pull-left"><?php AppUtility::t('Copy from:')?></div>
                                <?php
                                AssessmentUtility::writeHtmlSelect("dates", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
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
                            <td class="text-align"><?php AppUtility::t('Show:');?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type=radio name="avail" value="0"/><?php AppUtility::t('Hide')?>
                                <input type=radio name="avail" value="1" checked="checked"/><?php AppUtility::t('Show by Dates')?>
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
                            <td class="text-align" style="border-bottom: 1px solid #000"><?php AppUtility::t('End of Assessment Messages:')?>
                            </td>
                        </div>
                        <div class="col-lg-6">
                            <td style="border-bottom: 1px solid #000">
                                <div class="pull-left"><?php AppUtility::t('Copy from:')?></div>
                                <?php
                                AssessmentUtility::writeHtmlSelect("copyendmsg", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
                                <br/><i style="font-size: 75%"><?php AppUtility::t('Use option near the bottom to define new messages')?></i>
                            </td>
                        </div>
                    </div>
                </tr>
                <tr>
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="docopyopt" class="chgbox"
                                       onClick="copyfromtoggle(this.form,this.checked)"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Copy remaining options')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <div class="pull-left"><?php AppUtility::t('Copy from:');?></div>
                                <?php
                                AssessmentUtility::writeHtmlSelect("copyopt", $page_assessSelect['val'], $page_assessSelect['label']);
                                ?>
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
                            <td class="text-align"><?php AppUtility::t('Require Password (blank for none):')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input type=text name="assmpassword" value="" autocomplete="off"></td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgtimelimit" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Time Limit (minutes, 0 for no time limit):')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input type=text size=4 name="timelimit" value="0"/>
                                <input type="checkbox" name="timelimitkickout"/> <?php AppUtility::t('Kick student out at timelimit')?>
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
                            <td class="text-align"><?php AppUtility::t('Display method:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <select name="displaymethod" class="form-control">
                                    <option
                                        value="AllAtOnce" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "AllAtOnce", 0) ?>>
                                        <?php AppUtility::t('Full test at once')?>
                                    </option>
                                    <option
                                        value="OneByOne" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "OneByOne", 0) ?>>
                                        <?php AppUtility::t('One question at a time')?>
                                    </option>
                                    <option
                                        value="Seq" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "Seq", 0) ?>>
                                        <?php AppUtility::t('Full test,submit one at time')?>
                                    </option>
                                    <option
                                        value="SkipAround" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "SkipAround", 0) ?>>
                                        <?php AppUtility::t('Skip Around')?>
                                    </option>
                                    <option
                                        value="Embed" <?php AssessmentUtility::writeHtmlSelected($line['displaymethod'], "Embed", 0) ?>>
                                        <?php AppUtility::t('Embedded')?>
                                    </option>
                                </select>
                            </td></div></div>
                </tr>

                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgdefpoints" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Default points per problem:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input type=text size=4 name=defpoints value="<?php echo $line['defpoints']; ?>"></td>
                        </div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2"><td><input type="checkbox" name="chgdefattempts" class="chgbox"/></td></div>
                        <div class="col-lg-4"><td class="text-align">Default attempts per problem (0 for unlimited):</td></div>
                        <div class="col-lg-6"><td>
                                <input type=text size=4 name=defattempts value="<?php echo $line['defattempts'];?>">
 					<span id="showreattdiffver" class="<?php if ($testtype != "Practice" && $testtype != "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?>">
 					<input type=checkbox name="reattemptsdiffver" <?php AssessmentUtility::writeHtmlChecked($line['shuffle'] & 8, 8); ?> />
 					Reattempts different versions</span>
                            </td></div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2"><td><input type="checkbox" name="chgdefpenalty" class="chgbox"/></td></div>
                        <div class="col-lg-4"><td class="text-align">Default penalty:</td></div>
                        <div class="col-lg-6"><td><input type=text size=4 name=defpenalty style="margin-bottom: 4px"
                                                         value="<?php echo $line['defpenalty'];?>" <?php if ($taken) {
                                    echo 'disabled=disabled';
                                }?>>%
                                <select class="form-control" name="skippenalty" <?php if ($taken) {
                                    echo 'disabled=disabled';
                                }?>>
                                    <option value="0" <?php if ($skippenalty == 0) {
                                        echo "selected=1";
                                    } ?>>per missed attempt
                                    </option>
                                    <option value="1" <?php if ($skippenalty == 1) {
                                        echo "selected=1";
                                    } ?>>per missed attempt, after 1
                                    </option>
                                    <option value="2" <?php if ($skippenalty == 2) {
                                        echo "selected=1";
                                    } ?>>per missed attempt, after 2
                                    </option>
                                    <option value="3" <?php if ($skippenalty == 3) {
                                        echo "selected=1";
                                    } ?>>per missed attempt, after 3
                                    </option>
                                    <option value="4" <?php if ($skippenalty == 4) {
                                        echo "selected=1";
                                    } ?>>per missed attempt, after 4
                                    </option>
                                    <option value="5" <?php if ($skippenalty == 5) {
                                        echo "selected=1";
                                    } ?>>per missed attempt, after 5
                                    </option>
                                    <option value="6" <?php if ($skippenalty == 6) {
                                        echo "selected=1";
                                    } ?>>per missed attempt, after 6
                                    </option>
                                    <option value="10" <?php if ($skippenalty == 10) {
                                        echo "selected=1";
                                    } ?>>on last possible attempt only
                                    </option>
                                </select>
                            </td></div></div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2"><td><input type="checkbox" name="chgfeedback" class="chgbox"/></td></div>
                        <div class="col-lg-4"><td class="text-align">Feedback method:<br/>and Show Answers:</td></div>
                        <div class="col-lg-6"><td>
                                <select class="form-control" id="deffeedback" name="deffeedback" onChange="chgfb()">
                                    <option value="NoScores" <?php if ($testtype == "NoScores") {
                                        echo "SELECTED";
                                    } ?>>No scores shown (use with 1 attempt per problem)
                                    </option>
                                    <option value="EndScore" <?php if ($testtype == "EndScore") {
                                        echo "SELECTED";
                                    } ?>>Just show final score (total points & average) - only whole test can be reattemped
                                    </option>
                                    <option value="EachAtEnd" <?php if ($testtype == "EachAtEnd") {
                                        echo "SELECTED";
                                    } ?>>Show score on each question at the end of the test
                                    </option>
                                    <option value="EndReview" <?php if ($testtype == "EndReview") {
                                        echo "SELECTED";
                                    } ?>>Reshow question with score at the end of the test
                                    </option>
                                    <option value="AsGo" <?php if ($testtype == "AsGo") {
                                        echo "SELECTED";
                                    } ?>>Show score on each question as it's submitted (does not apply to Full test at once display)
                                    </option>
                                    <option value="Practice" <?php if ($testtype == "Practice") {
                                        echo "SELECTED";
                                    } ?>>Practice test: Show score on each question as it's submitted & can restart test; scores not
                                        saved
                                    </option>
                                    <option value="Homework" <?php if ($testtype == "Homework") {
                                        echo "SELECTED";
                                    } ?>>Homework: Show score on each question as it's submitted & allow similar question to replace
                                        missed question
                                    </option>
                                </select>
                                <br/>
					<span id="showanspracspan" class="<?php if ($testtype == "Practice" || $testtype == "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?>">
					<select class="form-control" name="showansprac">
                        <option value="V" <?php if ($showans == "V") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Never, but allow students to review their own answers")?>
                        </option>
                        <option value="N" <?php if ($showans == "N") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Never, and don't allow students to review their own answers")?>
                        </option>
                        <option value="F" <?php if ($showans == "F") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After last attempt (Skip Around only)")?>
                        </option>
                        <option value="J" <?php if ($showans == "J") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After last attempt or Jump to Ans button (Skip Around only)")?>
                        </option>
                        <option value="0" <?php if ($showans == "0") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Always")?>
                        </option>
                        <option value="1" <?php if ($showans == "1") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 1 attempt")?>
                        </option>
                        <option value="2" <?php if ($showans == "2") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 2 attempts")?>
                        </option>
                        <option value="3" <?php if ($showans == "3") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 3 attempts")?>
                        </option>
                        <option value="4" <?php if ($showans == "4") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 4 attempts")?>
                        </option>
                        <option value="5" <?php if ($showans == "5") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("After 5 attempts")?>
                        </option>
                    </select>
					</span>
					<span id="showansspan" class="<?php if ($testtype != "Practice" && $testtype != "Homework") {
                        echo "show";
                    } else {
                        echo "hidden";
                    } ?>">
					<select class="form-control" name="showans">
                        <option value="V" <?php if ($showans == "V") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t('Never, but allow students to review their own answers')?>
                        </option>
                        <option value="N" <?php if ($showans == "N") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Never, and don't allow students to review their own answers")?>
                        </option>
                        <option value="I" <?php if ($showans == "I") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t("Immediately (in gradebook) - don't use if allowing multiple attempts per problem")?>
                        </option>
                        <option value="F" <?php if ($showans == "F") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t('After last attempt (Skip Around only)')?>
                        </option>
                        <option value="A" <?php if ($showans == "A") {
                            echo "SELECTED";
                        } ?>><?php AppUtility::t('After due date (in gradebook)')?>
                        </option>
                    </select>
					</span>
                            </td></div></div>
                </tr>

                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2"><td><input type="checkbox" name="chgeqnhelper" class="chgbox"/></td></div>
                        <div class="col-lg-4"><td class="text-align">Use equation helper?</td></div>
                        <div class="col-lg-6"><td>
                                <select class="form-control" name="eqnhelper">
                                    <option value="0" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 0) ?>>No</option>
                                    <?php
                                    //phase out unless a default
                                    if ($CFG['AMS']['eqnhelper'] == 1 || $CFG['AMS']['eqnhelper'] == 2) {
                                        ?>
                                        <option value="1" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 1) ?>>Yes, simple form (no
                                            logs or trig)
                                        </option>
                                        <option value="2" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 2) ?>>Yes, advanced form
                                        </option>
                                    <?php
                                    }
                                    ?>
                                    <option value="3" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 3) ?>>MathQuill, simple form
                                    </option>
                                    <option value="4" <?php AssessmentUtility::writeHtmlSelected($line['eqnhelper'], 4) ?>>MathQuill, advanced form
                                    </option>
                                </select>
                            </td></div></div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12"> <div class="col-lg-2"><td><input type="checkbox" name="chghints" class="chgbox"/></td></div>
                        <div class="col-lg-4"><td class="text-align">Show hints and video/text buttons when available?</td></div>
                        <div class="col-lg-6"><td>
                                <input type="checkbox" name="showhints" <?php AssessmentUtility::writeHtmlChecked($line['showhints'], 1); ?>>
                            </td></div>
                    </div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2"><td><input type="checkbox" name="chgmsgtoinstr" class="chgbox"/></td></div>
                        <div class="col-lg-4"><td class="text-align">Show "Message instructor about this question" links</td></div>
                        <div class="col-lg-6"><td>
                                <input type="checkbox" name="msgtoinstr" <?php AssessmentUtility::writeHtmlChecked($line['msgtoinstr'], 1); ?>/>
                            </td></div></div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12"><div class="col-lg-2" ><td><input type="checkbox" name="chgposttoforum" class="chgbox"/></td></div>
                        <div class="col-lg-4"><td class="text-align">Show "Post this question to forum" links?</td></div>
                        <div class="col-lg-6"><td>
                                <input type="checkbox"
                                       name="doposttoforum" <?php AssessmentUtility::writeHtmlChecked($line['posttoforum'], 0, true); ?> style="margin-bottom: 4px"/> To
                                forum <?php AssessmentUtility::writeHtmlSelect("posttoforum", $page_forumSelect['val'], $page_forumSelect['label'], $line['posttoforum']); ?>
                            </td></div></div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12"> <div class="col-lg-2"><td><input type="checkbox" name="chgshowtips" class="chgbox"/></td></div>
                        <div class="col-lg-4"><td class="text-align">Show answer entry tips?</td></div>
                        <div class="col-lg-6">
                            <td>
                                <select name="showtips" class="form-control">
                                    <option value="0" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 0) ?>>No</option>
                                    <option value="1" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 1) ?>>Yes, after question</option>
                                    <option
                                        value="0" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 0) ?>><?php AppUtility::t('No')?>
                                    </option>
                                    <option
                                        value="1" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 1) ?>>
                                        <?php AppUtility::t('Yes, after question')?>
                                    </option>
                                    <option
                                        value="2" <?php AssessmentUtility::writeHtmlSelected($line['showtips'], 2) ?>>
                                        <?php AppUtility::t('Yes, under answerbox')?>
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
                            <td class="text-align"><?php AppUtility::t('Allow use of LatePasses?:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <?php
                                AssessmentUtility::writeHtmlSelect("allowlate", $page_allowlateSelect['val'], $page_allowlateSelect['label'], 1);
                                ?>
                                <label><input type="checkbox" name="latepassafterdue"> <?php AppUtility::t('Allow LatePasses after due date,within 1 LatePass period')?></label>
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
                            <td class="text-align"><?php AppUtility::t('Make hard to print?:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type="radio" value="0"
                                       name="noprint" <?php AssessmentUtility::writeHtmlChecked($line['noprint'], 0); ?>/>
                                No
                                <input type="radio" value="1"
                                       name="noprint" <?php AssessmentUtility::writeHtmlChecked($line['noprint'], 1); ?>/>
                                Yes
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
                            <td class="text-align"><?php AppUtility::t('Shuffle item order:')?></td>
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
                            <td class="text-align"><?php AppUtility::t('Gradebook category:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <?php
                                AssessmentUtility::writeHtmlSelect("gbcat", $page_gbcatSelect['val'], $page_gbcatSelect['label'], null, null, null, " id=gbcat");
                                ?>
                            </td></div></div>
                </tr>
                <tr class="coptr">
                    <div class="col-lg-12">
                        <div class="col-lg-2">
                            <td><input type="checkbox" name="chgtutoredit" class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align"><?php AppUtility::t('Tutor Access:')?></td>
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
                            <td style="border-bottom: 1px solid #000"><input type="checkbox" name="chgcntingb"
                                                                             class="chgbox"/></td>
                        </div>
                        <div class="col-lg-4">
                            <td class="text-align" style="border-bottom: 1px solid #000">Count:</td>
                        </div>
                        <div class="col-lg-6">
                            <td style="border-bottom: 1px solid #000"><input name="cntingb" value="1" checked="checked"
                                                                             type="radio"> <?php AppUtility::t('Count in Gradebook')?><br>
                                <input name="cntingb" value="0" type="radio"> <?php AppUtility::t("Don't count in grade total and hide from students")?><br>
                                <input name="cntingb" value="3" type="radio"> <?php AppUtility::t("Don't count in grade total")?><br>
                                <input name="cntingb" value="2" type="radio"> <?php AppUtility::t('Count as Extra Credit')?>
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
                            <td class="text-align"><?php AppUtility::t('Calendar icon:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <?php AppUtility::t('Active:')?> <input name="caltagact" type=text size=1
                                               value="<?php echo $line['caltag']; ?>"/>,
                                <?php AppUtility::t('Review:')?> <input name="caltagrev" type=text size=1
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
                            <td class="text-align"><?php AppUtility::t('Minimum score to receive credit:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type=text size=4 name=minscore value="<?php echo $line['minscore']; ?>">
                                <input type="radio" name="minscoretype" value="0" checked="checked"> <?php AppUtility::t('Points')?>
                                <input type="radio" name="minscoretype" value="1"> <?php AppUtility::t('Percent')?>
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
                            <td class="text-align"><?php AppUtility::t('Default Feedback Text:')?></td>
                        </div>
                        <div class="</div>6">
                            <td><?php AppUtility::t('Use?')?> <input type="checkbox" name="usedeffb"><br/>
                                <?php AppUtility::t('Text:')?> <input type="text" size="60" name="deffb"
                                             value="<?php AppUtility::t('This assessment contains items that not automatically graded.  Your grade may be inaccurate until your instructor grades these items.')?>"/>
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
                            <td class="text-align"><?php AppUtility::t('Clear "show based on another assessment" settings.')?></td>
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
                            <td class="text-align"><?php AppUtility::t('All items same random seed:')?></td>
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
                            <td class="text-align"><?php AppUtility::t('All students same version of questions:')?></td>
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
                            <td class="text-align"><?php AppUtility::t('Penalty for questions done while in exception/LatePass:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td>
                                <input type=text size=4 name="exceptionpenalty"
                                       value="<?php echo $line['exceptionpenalty']; ?>">%
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
                            <td class="text-align"><?php AppUtility::t('Show question categories:')?></td>
                        </div>
                        <div class="col-lg-6">
                            <td><input name="showqcat" value="0" checked="checked" type="radio"><?php AppUtility::t('No ')?><br/>
                                <input name="showqcat" value="1" type="radio"><?php AppUtility::t('In Points Possible bar')?> <br/>
                                <input name="showqcat" value="2" type="radio"><?php AppUtility::t('In navigation bar (Skip-Around only)')?>
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
                            <td class="text-align"><?php AppUtility::t('Display for tutorial-style questions:')?></td>
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
                            <td class="text-align" style="border-top: 1px solid #000"><?php AppUtility::t('Define end of assessment messages?')?>
                            </td>
                        </div>
                        <div class="col-lg-6">
                            <td style="border-top: 1px solid #000"><input type="checkbox" name="chgendmsg"
                                                                          class="chgbox"/> <?php AppUtility::t('You will be taken to a page to change these after you hit submit')?>
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
                            <td class="text-align"><?php AppUtility::t('Remove per-question settings (points, attempts, etc.) for all questions in these assessments?')?>
                            </td>
                        </div>
                        <div class="col-lg-6">
                            <td><input type="checkbox" name="removeperq" class="chgbox"/></td>
                        </div>
                    </div>
                </tr>
                </tbody>
            </table>

        </div>
        <?php
        } ?>
    </div>
</form>
<style type="text/css">
    span.hidden {
        display: none;
    }
    span.show {
        display: inline;
    }
    table td {
        border-bottom: 1px solid #ccf;
    }
</style>
<script type="text/javascript">
    function chgfb() {
        if (document.getElementById("deffeedback").value == "Practice" || document.getElementById("deffeedback").value == "Homework") {
            document.getElementById("showanspracspan").className = "show";
            document.getElementById("showansspan").className = "hidden";
            document.getElementById("showreattdiffver").className = "hidden";
        } else {
            document.getElementById("showanspracspan").className = "hidden";
            document.getElementById("showansspan").className = "show";
            document.getElementById("showreattdiffver").className = "show";
        }
    }

    function copyfromtoggle(frm, mark) {
        var tds = frm.getElementsByTagName("tr");
        for (var i = 0; i < tds.length; i++) {
            try {
                if (tds[i].className == 'coptr') {
                    if (mark) {
                        tds[i].style.display = "none";
                    } else {
                        tds[i].style.display = "";
                    }
                }

            } catch (er) {
            }
        }

    }
    function chkgrp(frm, arr, mark) {
        var els = frm.getElementsByTagName("input");
        for (var i = 0; i < els.length; i++) {
            var el = els[i];
            if (el.type == 'checkbox' && (el.id.indexOf(arr + '.') == 0 || el.id.indexOf(arr + '-') == 0 || el.id == arr)) {
                el.checked = mark;
            }
        }
    }

    function chkgbcat(cat) {
        chkAllNone('qform', 'checked[]', false);
        var els = document.getElementById("alistul").getElementsByTagName("input");
        var regExp = new RegExp(":" + cat + "$");
        for (var i = 0; i < els.length; i++) {
            var el = els[i];
            if (el.type == 'checkbox' && el.id.match(regExp)) {
                el.checked = true;
            }
        }
    }
    //    function valform() {
    //        if ($("#qform input:checkbox[name='checked[]']:checked").length == 0) {
    //            if (!confirm("No assessments are selected to be changed. Cancel to go back and select some assessments, or click OK to make no changes")) {
    //                return false;
    //            }
    //
    //        }
    //        if ($(".chgbox:checked").length == 0) {
    //            if (!confirm("No settings have been selected to be changed. Use the checkboxes along the left to indicate that you want to change that setting. Click Cancel to go back and select some settings to change, or click OK to make no changes")) {
    //                return false;
    //            }
    //        }
    //        return true;
    //    }
    $(function() {
        $(".chgbox").change(function() {
            $(this).parents("tr").toggleClass("odd");
            /*
             var chk = $(this).is(':checked');
             if (chk) {
             $(this).parents("tr").addClass("odd");
             } else {
             $(this).parents("tr").removeClass("odd");
             }*/
        });

    })
</script>






