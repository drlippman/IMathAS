<?php
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\AppConstant;
$this->title = $defaultValues['saveTitle'];
?>
<?php if ($linkData['id']) { ?>
    <?php $form = ActiveForm::begin([
        'validateOnSubmit' => false,
        'options' => ['enctype' => 'multipart/form-data'],
        'action' => 'add-link?cid=' . $course['id'] . '&id='.$linkData['id'].'&block='.$block,
    ]);
   if ($linkData['id']){ ?>
        <input type="hidden" name="modifyFid" value="<?php echo $modifyForumId;?>">
    <?php } ?>
<?php } else { ?>
    <?php $form = ActiveForm::begin([
        'validateOnSubmit' => false,
        'options' => ['enctype' => 'multipart/form-data'],
        'action' => $page_formActionTag,
    ]);
    ?>
<?php }
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="col-sm-12 col-md-12 tab-content shadowBox non-nav-tab-item add-link-padding">
    <div class="name-of-item col-md-12 col-sm-12">
        <div class="col-md-2 col-sm-2 select-text-margin"><?php AppUtility::t('Name of Link') ?></div>
        <div class="col-md-10 col-sm-10">
            <input class="form-control input-item-title" type=text size=0 name=name maxlength="60"
                   value="<?php echo str_replace('"', '&quot;', $defaultValues['title']);  ?>">
        </div>
    </div>
    <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em padding-right-zero">
        <div class="col-md-2 col-sm-2">
            <?php AppUtility::t('Summary') ?>
        </div>
        <?php $summary = $defaultValues['summary']; ?>
        <?php echo "<div class='padding-left-zero col-md-10 col-sm-10'>
                <div class='editor col-md-12 col-sm-12 add-link-summary-textarea'>
                    <textarea cols=5 rows=12 id=summary name=summary style='width: 100%'>$summary</textarea>
                </div>
                </div>"; ?>
    </div>

    <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em">
        <div class="col-md-2 col-sm-2 select-text-margin"><?php AppUtility::t('Link type') ?> </div>
        <div class="col-md-4 col-sm-4">
            <select class="form-control" id="linktype" name="linktype" onchange="linktypeupdate(this)">
                <option value="text" <?php AssessmentUtility::writeHtmlSelected($defaultValues['type'], 'text'); ?>>Page
                    of text
                </option>
                <option value="web" <?php AssessmentUtility::writeHtmlSelected($defaultValues['type'], 'web'); ?>>Web
                    link
                </option>
                <option value="file" <?php AssessmentUtility::writeHtmlSelected($defaultValues['type'], 'file'); ?>>
                    File
                </option>
                <option value="tool" <?php AssessmentUtility::writeHtmlSelected($defaultValues['type'], 'tool'); ?>>
                    External Tool
                </option>
            </select>
        </div>
    </div>
    <div class="col-md-12 col-sm-12 padding-top-one-pt-five-em" id="textinput" <?php if ($defaultValues['type'] != 'text')
    {
        echo 'style="display:none;"';
    } ?> >
        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Text') ?></div>
        <?php $text = $defaultValues['text']; ?>
        <div class="col-md-10 col-sm-10 padding-left-zero padding-right-zero">
            <div class="editor col-md-12 col-sm-12 add-link-summary-textarea">
                <textarea cols=80 rows=20 id=text name=text style="width: 100%"><?php echo htmlentities($line['text']); ?><?php echo $text ?></textarea>
            </div>
        </div>
    </div>
    <div class="col-md-12 col-sm-12" id="webinput" <?php if ($defaultValues['type'] != 'web')
    {
        echo 'style="display:none;"';
    } ?>>
        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Weblink (start with http://)') ?></div>
        <div class="col-md-10 col-sm-10">
            <input size="80" class="form-control" name="web" value="<?php echo htmlentities($defaultValues['webaddr']); ?>"/>
        </div>
    </div>


    <div class="col-md-12 col-sm-12" id="fileinput" <?php if ($defaultValues['type'] != 'file')
    {
        echo 'style="display:none;"';
    } ?>>
        <div class="col-md-2 col-sm-2"><?php AppUtility::t('File') ?></div>
        <input type="hidden" name="MAX_FILE_SIZE" value="10000000"/>
    	<span class="formright add-link-type-file" style="margin-left: 14px;">
				<?php if ($defaultValues['filename'] != ' ')
            {
                echo '<input type="hidden" name="curfile" value="' . $defaultValues['filename'] . '"/>';
                $alink =  AppUtility::getHomeURL().'Uploads/'.$course->id.'/'.$defaultValues['filename'];
                				echo 'Current file: <a href="'.$alink.'">'.basename($defaultValues['filename']).'</a><br/>Replace ';
            } else
            {
                echo 'Attach ';
            } ?>
            file (Max 10MB)<sup>*</sup>:
            <div class="">
                <?php

                echo $form->field($model, 'file')->fileInput();
                ?>

            </div>
            </span>
    </div>
     <div id="toolinput" <?php if ($defaultValues['type'] != 'tool')
     {
        echo 'style="display:none;"';
     } ?>><br>
        <div class="col-md-2 col-sm-2"><?php AppUtility::t('External Tool') ?></div>
        <div class="col-md-4">
            <?php
            if (count($toolvals) > 0)
            {
                AssessmentUtility::writeHtmlSelect('tool', $toolvals, $toollabels, $defaultValues['selectedtool']);
                echo '<br/>Custom parameters <input class="form-control" type="text" name="toolcustom" size="40" value="' . htmlentities($defaultValues['toolcustom']) . '" /><br/>';
                echo 'Custom launch URL <input class="form-control" type="text" name="toolcustomurl" size="40" value="' . htmlentities($defaultValues['toolcustomurl']) . '" /><br/>';
            } else {
                echo 'No Tools defined yet<br/><br>';
            }
            if (!isset($CFG['GEN']['noInstrExternalTools'])) { ?>
                <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/external-tool?cid=' . $course->id) ?>">Add or
                    edit an external tool</a>
            <?php }
            ?>
        </div>
        <br class="form"/><br>

        <div class="col-md-2 col-sm-2"><?php AppUtility::t('If this tool returns scores, do you want to record them?') ?></div>
        <div class="col-md-10 col-sm-10">
            <input type=radio name="usegbscore" value="0" <?php if ($defaultValues['points'] == 0) {
                echo 'checked=1';
            } ?> onclick="toggleGBdetail(false)"/><span class="padding-left"><?php AppUtility::t('No') ?></span><br>
            <input type=radio name="usegbscore" value="1" <?php if ($defaultValues['points'] > 0) {
                echo 'checked=1';
            } ?> onclick="toggleGBdetail(true)"/><span class="padding-left"><?php AppUtility::t('Yes') ?></span>
        </div>
        <br class="form"/><br>

        <div id="gbdetail" <?php if ($defaultValues['points'] == 0) {
            echo 'style="display:none;"';
        } ?>>
            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Points') ?></div>
            <div class="col-md-10 col-sm-10">
                <input type=text size=4 name="points" value="<?php echo $defaultValues['points']; ?>"/><span
                    class="padding-left"><?php AppUtility::t('points') ?></span>
            </div>
            <br class="form"/><br>

            <div class=col-md-2 col-sm-2><?php AppUtility::t('Gradebook Category') ?></div>
            <div class=col-md-4>
                <?php AssessmentUtility::writeHtmlSelect("gbcat", $gbcatsId, $gbcatsLabel, $valuesOfcheckBoxes['gbcat'], "Default", 0); ?>
            </div>
            <br class=form><br>

            <div class=col-md-2 col-sm-2><?php AppUtility::t('Count') ?> </div>
            <div class="col-md-10 col-sm-10">
                <input type=radio name="cntingb"
                       value="1"<?php AssessmentUtility::writeHtmlChecked($defaultValues['cntingb'], 1, 0); ?>/><span
                    class="padding-left"><?php AppUtility::t('Count in Gradebook') ?></span><br>
                <input type=radio name="cntingb"
                       value="0"<?php AssessmentUtility::writeHtmlChecked($defaultValues['cntingb'], 0, 0); ?>/><span
                    class="padding-left"><?php AppUtility::t("Don't count in grade total and hide from students") ?></span><br>
                <input type=radio name="cntingb"
                       value="3"<?php AssessmentUtility::writeHtmlChecked($defaultValues['cntingb'], 3, 0); ?>/><span
                    class="padding-left"><?php AppUtility::t("Don't count in grade total") ?></span><br>
                <input type=radio name="cntingb"
                       value="2"<?php AssessmentUtility::writeHtmlChecked($defaultValues['cntingb'], 2, 0); ?>/><span
                    class="padding-left"><?php AppUtility::t("Count as Extra Credit") ?></span>
            </div>
            <br class=form><br>

            <?php $page_tutorSelect['label'] = array("No access to scores", "View Scores", "View and Edit Scores");
            $page_tutorSelect['val'] = array(2, 0, 1); ?>

            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Tutor Access') ?></div>
            <div class="col-md-4">
                <?php
                AssessmentUtility::writeHtmlSelect("tutoredit", $page_tutorSelect['val'], $page_tutorSelect['label'], $defaultValues['tutoredit']);
                echo '<input type="hidden" name="gradesecret" value="' . $defaultValues['gradesecret'] . '"/>';
                ?>
            </div>
            <br class="form"/>
        </div>
    </div>

<div class="col-md-12 col-sm-12 padding-top-one-em">
    <div class="col-md-2 col-sm-2"><?php AppUtility::t('Open page in') ?> </div>
    <div class="col-md-10 col-sm-10">
        <div class="col-md-3 col-sm-4 padding-left-zero">
            <input type=radio name="open-page-in" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultValues['open-page-in'], AppConstant::NUMERIC_ZERO); ?>  />
            <span class="padding-left-pt-five-em">
                <?php AppUtility::t("Current window/tab") ?>
            </span>
        </div>
        <div class="col-md-3 col-sm-4 padding-left-zero">
            <input type=radio name="open-page-in" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultValues['open-page-in'], AppConstant::NUMERIC_ONE); ?>  />
            <span class="padding-left-pt-five-em">
                <?php AppUtility::t("New window/tab") ?>
            </span>
        </div>
    </div>
 </div>

    <div class="col-md-12 col-sm-12 padding-top-one-em">
        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Visibility') ?></div>
        <div class="col-md-10 col-sm-10 padding-left-zero">
            <span class="col-md-3 col-sm-4">
                <input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultValues['avail'], AppConstant::NUMERIC_ONE); ?>onclick="document.getElementById('datediv').style.display='block';document.getElementById('altcaldiv').style.display='none';"/>
                <span class="padding-left-pt-five-em"><?php AppUtility::t("Show by Dates") ?></span>
            </span>
            <span class="col-md-2 col-sm-3 padding-left-pt-eight-em">
                <label class="non-bold">
                    <input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultValues['avail'], AppConstant::NUMERIC_ZERO); ?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='none';"/>
                    <span class="padding-left-pt-five-em"><?php AppUtility::t("Hide") ?></span>
                </label>
            </span>
            <span class="col-md-3 col-sm-4">
            <label class="non-bold">
                <input type=radio name="avail" value="2" <?php AssessmentUtility::writeHtmlChecked($defaultValues['avail'], AppConstant::NUMERIC_TWO); ?> onclick="document.getElementById('datediv').style.display='none';document.getElementById('altcaldiv').style.display='block';"/>
                <span class="padding-left-pt-five-em"><?php AppUtility::t("Show Always") ?></span>
            </label>
        </div>
    </div>

    <div id="datediv" style="display:<?php echo ($defaultValues['avail'] == AppConstant::NUMERIC_ONE) ? "block" : "none"; ?>">
        <div class='col-md-2 col-sm-2 select-text-margin '>
            <?php AppUtility::t('Available After') ?>
        </div>
        <div class="col-md-10 col-sm-10">
            <span class="col-md-3 col-sm-5 padding-left-zero padding-right-zero">
                <input type=radio name="available-after" class="pull-left select-text-margin" value="0" <?php if ($defaultValues['startDate'] == 0) { echo 'checked=1';} ?>/>
                <span class="pull-left padding-left-pt-eight-em select-text-margin">
                    <?php AppUtility::t("Always until end date") ?>
                </span>
            </span>
            <span class="col-md-3 col-sm-5 padding-left-zero padding-right-zero">
                <label class="pull-left non-bold">
                    <input type=radio name="available-after" class="select-text-margin pull-left" value="1" <?php if ($defaultValues['startDate'] == 1) { echo 'checked=1'; } ?>/>
                </label>
                <div class = "time-input pull-left col-md-10 col-sm-8 padding-left-pt-eight-em add-link-date-font padding-right-zero">
                    <?php echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => $defaultValues['sDate'],
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]); ?>
                </div>
            </span>

            <div class="col-md-5 col-sm-7 padding-left-zero">
                <label class="select-text-margin end pull-left non-bold"> at </label>
                <div class="pull-left col-md-10 col-sm-9 add-link-date-font">
                    <?php   echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => $defaultValues['sTime'],
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]); ?>
                </div>
            </div>
        </div>

        <div class='col-md-2 col-sm-2 padding-right-zero select-text-margin'>
            <?php AppUtility::t('Available Until') ?>
        </div>
        <div class="col-md-10 col-sm-10">
            <label class="col-md-3 col-sm-5 padding-left-zero padding-right-zero">
                <input class="floatleft select-text-margin" type=radio name="available-until" value="2000000000" <?php if ($defaultValues['endDate'] == AppConstant::ALWAYS_TIME) { echo 'checked=1';} ?>/>
                <span class="pull-left padding-left-pt-eight-em select-text-margin non-bold">
                    <?php AppUtility::t("Always after start date") ?>
                </span>
            </label>
            <span class="col-md-3 col-sm-5 padding-left-zero padding-right-zero">
                <label class="pull-left">
                    <input type=radio name="available-until" class="select-text-margin pull-left" value="1"  <?php if ($defaultValues['endDate'] == 1) { echo 'checked=1'; } ?>/>
                </label>
                <?php
                echo '<div class = "time-input pull-left col-md-10 col-sm-8 padding-left-pt-eight-em add-link-date-font padding-right-zero">';
                echo DatePicker::widget([
                    'name' => 'edate',
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => $defaultValues['eDate'],
                    'removeButton' => false,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'mm/dd/yyyy']
                ]); ?>
                </div>
            </span>

        <div class="col-md-5 col-sm-7 padding-left-zero">
           <label class="end pull-left non-bold select-text-margin"> at </label>
           <div class="pull-left col-md-10 col-sm-9 add-link-date-font">
               <?php echo TimePicker::widget([
                    'name' => 'etime',
                    'value' => $defaultValues['eTime'],
                    'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                    ]
                ]); ?>
            </div>
         </div>

            <div class="col-md-2 col-sm-2">
                <?php AppUtility::t('Place on Calendar?') ?>
            </div>
            <div class="col-md-10 col-sm-10 padding-left-zero">
                <div class="col-md-12 col-sm-12">
                    <input type=radio name="oncal" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultValues['calendar'], AppConstant::NUMERIC_ZERO); ?>  />
                    <span class="padding-left-pt-five-em"><?php AppUtility::t('No') ?></span>
                </div>
                <div class="col-md-12 col-sm-12 padding-top-ten">
                    <input type=radio name="oncal" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultValues['calendar'], AppConstant::NUMERIC_ONE); ?>  />
                    <span class="padding-left-pt-five-em">
                        <?php AppUtility::t('Yes, on Available after date (will only show after that date)') ?>
                    </span>
                </div>
                <div class="col-md-12 col-sm-12 padding-top-ten">
                    <input type=radio name="oncal" value="2" <?php AssessmentUtility::writeHtmlChecked($defaultValues['calendar'], AppConstant::NUMERIC_TWO); ?>  />
                    <span class="padding-left-pt-five-em">
                        <?php AppUtility::t(' Yes, on Available until date') ?>
                    </span>
                </div>
                <div class="col-md-12 col-sm-12 padding-top-ten">
                    <span class="padding-left-two-em">
                        <?php AppUtility::t('With tag') ?>
                    </span>
                    <span class="padding-left">
                        <input class="form-control width-five-per display-inline-block" type="text" size="10" maxlength="20" value=<?php echo $defaultValues['caltag'];?> name="tag">
                    </span>
                </div>
            </div>
    </div>
    </div>

    <div id="altcaldiv" class="col-md-12 col-sm-12 padding-left-zero" style="display:<?php echo ($defaultValues['avail'] == 2) ? "block" : "none"; ?>" >
        <div class="col-md-2 col-sm-2">
            <?php AppUtility::t('Place on Calendar?') ?>
        </div>
        <div class="col-md-10 col-sm-10 padding-left-zero">
            <div class="col-md-12 col-sm-12">
                <input type=radio name="altoncal" value="0" <?php if ($defaultValues['altoncal'] == 0) {
                    echo 'checked=1';
                } ?>  />
                <span class="padding-left-fifteen">
                    <?php AppUtility::t('No') ?>
                </span>
            </div>

            <div class='col-md-12 col-sm-12 padding-top-fifteen'>
                <label class="pull-left">
                    <input type=radio name="altoncal" class="pull-left" value="1"  <?php if ($defaultValues['altoncal'] == 1) {
                        echo 'checked=1';
                    } ?>/>
                </label>
                <span class="pull-left padding-left-twenty">
                        <?php AppUtility::t('Yes, on') ?>
                </span>

                <?php
                echo '<div class = "time-input pull-left col-md-4">';
                echo DatePicker::widget([
                    'name' => 'cdate',
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => $defaultValues['sDate'],
                    'removeButton' => false,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'mm/dd/yyyy']
                ]);
                echo '</div>'; ?>
            </div>
            <div class="col-md-12 col-sm-12 margin-left-thirty-five padding-top-twenty padding-bottom-twenty">
                <?php AppUtility::t('With tag') ?>
                <span class="padding-left-five">
                    <input class="form-control display-inline-block width-five-per" type="text" size="3" maxlength="20" value=<?php echo $defaultValues['caltag'];?> name="tag-always">
                </span>
            </div>

        </div>
    </div>
<!--    </div>-->
    <?php if (count($pageOutcomesList) > 0) { ?>
    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
        <div class="col-md-2 col-sm-2 select-text-margin"><?php AppUtility::t('Associate Outcomes') ?></div>
        <div class="col-md-8 col-sm-8 padding-left-eighteen">
            <?php
            AssessmentUtility::writeHtmlMultiSelect('outcomes', $pageOutcomesList, $pageOutcomes, $defaultValues['gradeoutcomes'], 'Select an outcome...'); ?>
        </div>
    </div>
        <?php } ?>

    <div class="col-md-offset-2 col-sm-offset-2 col-md-10 col-sm-10 padding-left-two-em padding-top-one-em">
        <div>
            <sup>*</sup>
            <span>Avoid quotes in the filename</span>
        </div>
    </div>
    <div class="header-btn col-md-6 col-sm-6 col-sm-offset-2 col-md-offset-2 padding-left-two-em padding-top-one-em padding-bottom-ten">
        <button class="btn btn-primary page-settings" type="submit" value="Submit">
            <i class="fa fa-share header-right-btn"></i><?php echo $defaultValues['saveButtonTitle']; ?></button>
    </div>
<!--</div>-->

<?php ActiveForm::end(); ?>