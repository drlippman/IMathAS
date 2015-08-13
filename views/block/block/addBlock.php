<?php
use app\components\AssessmentUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;

$this->title = $defaultBlockData['pageTitle'];
$this->params['breadcrumbs'][] = ['label' => $courseName, 'url' => ['/instructor/instructor/index?cid='.$courseId]];
$this->params['breadcrumbs'][] = $this->title;
?>

<form method=post action="create-block?courseId=<?php echo $courseId; if(isset($block)){echo "&block=$block";} if(isset($toTb)){echo "&toTb=$toTb";} if(isset($id)){echo "&id=$id";}?>">
    <div class="item-detail-header">
            <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$courseName], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$courseId], 'page_title' => $this->title]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?><i class="fa fa-question help-icon"></i></div>
            </div>
            <div class="pull-left header-btn">
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo $defaultBlockData['saveTitle'] ?></button>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox non-nav-tab-item">
        <div class="name-of-item">
            <div class=col-lg-2><?php AppUtility::t('Name of Block')?> </div>
            <div class=col-lg-10>
                <input class="input-item-title" type=text size=0 name=title value="<?php echo str_replace('"','&quot;',$defaultBlockData['title']);?>" >
            </div>
        </div>
        <BR class=form>
        <div class="item-alignment">
            <div class=col-lg-2><?php AppUtility::t('Visibility')?></div>
            <div class=col-lg-10>
                <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['avail'], 0); ?> onclick="document.getElementById('datediv').style.display='none';"/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Hide')?></td></div>
                <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['avail'], 1); ?> onclick="document.getElementById('datediv').style.display='block';" /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show by Dates')?></td></div>
                <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name="avail" value="2" <?php AssessmentUtility:: writeHtmlChecked($defaultBlockData['avail '], 2); ?> onclick="document.getElementById('datediv').style.display='none'; "/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show Always')?></td></div>
            </div><br class="form"/>

            <!--Show by dates-->
            <div id="datediv" style="display:<?php echo ($forum['avail'] == 1) ? "block" : "none"; ?>">

                <div class=col-lg-2><?php AppUtility::t('Available After')?></div>
		        <div class=col-lg-10>
                    <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name="available-after" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '0', 0) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Always until end date')?></td></div>
                    <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name="available-after" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '1', 1) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Now')?></td></div>
                    <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible pull-left'><td><input type=radio name="available-after" class="pull-left" value="sdate" <?php echo AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '0', 1) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td>
                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input" style="padding-left: 0">';
                    echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left col-lg-1"> at </label>';
                    echo '<div class="pull-left col-lg-6">';

                    echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		        </div></div><BR class=form>

                <div class=col-lg-2><?php AppUtility::t('Available Until')?></div>
		        <div class=col-lg-10>
                    <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name="available-until" value="2000000000" <?php echo AssessmentUtility::writeHtmlChecked($defaultBlockData['endDate'], '2000000000', 0) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Always after start date')?></td></div>
                    <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible pull-left'><td><input type=radio name="available-until" class="pull-left"  value="edate" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['endDate'], '2000000000', 1) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td>

                    <?php
                    echo '<div class = "pull-left col-lg-4 time-input" style="padding-left: 0">';
                    echo DatePicker::widget([
                        'name' => 'edate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy']
                    ]);
                    echo '</div>'; ?>
                    <?php
                    echo '<label class="end pull-left col-lg-1"> at </label>';
                    echo '<div class="pull-left col-lg-6">';

                    echo TimePicker::widget([
                        'name' => 'etime',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		    </div></div><BR class=form>
            </div>
    <div class="item-alignment">
        <div class=col-lg-2><?php AppUtility::t('When available')?></div>
        <div class=col-lg-10>
            <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name=availBeh value="O" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'O')?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show Expanded')?></td></div>
            <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name=availBeh value="C" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'C')?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show Collapsed')?></td></div>
            <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name=availBeh value="F" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'F')?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show as Folder')?></td></div>
            <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name=availBeh value="T" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'T')?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show as TreeReader')?></td></div>
        </div></div> <br class=form />

    <div class="item-alignment">
        <div class=col-lg-2><?php AppUtility::t('When not available')?></div>
            <div class=col-lg-10>
                <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name=showhide value="H" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['showHide'],'H') ?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Hide from Students')?></td></div>
                <div class='radio student-enroll visibility override-hidden'><label class='checkbox-size label-visibility label-visible'><td><input type=radio name=showhide value="S" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['showHide'],'S') ?> /><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show Collapsed/as folder')?></td></div>

    </div></div><br class=form />

     <div class="item-alignment" ">
     <div class="col-lg-2"><?php AppUtility::t('If expanded, limit height to')?></div>
        <div class="col-lg-10">
        <input type="text" name="fixedheight" size="4" value="<?php if ($defaultBlockData['fixedHeight']>0) {echo $defaultBlockData['fixedHeight'];};?>" />pixels (blank for no limit)
     </div></div><br class="form" />

        <div class="item-alignment">
            <div class=col-lg-2><?php AppUtility::t('Restrict access to students in section')?></div>
                <div class=col-lg-10>
                  <?php AssessmentUtility::writeHtmlSelect('grouplimit',$page_sectionListVal,$page_sectionListLabel,$grouplimit[0]); ?>
            </div></div><br class=form>

     <div class="item-alignment">
        <div class=col-lg-2><?php AppUtility::t('Make items publicly accessible')?><sup>*</sup>:</div>
        <div class=col-lg-10>
            <div class="checkbox override-hidden"><label class="inline-checkbox label-visible"><input type=checkbox name=public value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['public'],'1') ?> /><span class="cr"><i class="cr-icon fa fa-check"></i></span></label></div>

	    </div></div><br class=form />
    </form>
    <p class="small"><sup>*</sup>If a parent block is set to be publicly accessible, this block will automatically be publicly accessible, regardless of your selection here.<br/>
        Items from publicly accessible blocks can viewed without logging in at <?php echo "" ?>/public.php?cid=<?php echo ""?>. </p>



