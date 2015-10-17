<?php
use app\components\AssessmentUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppUtility;

$this->title = $defaultBlockData['pageTitle'];
$this->params['breadcrumbs'][] = ['label' => $courseName, 'url' => ['/course/course/course?cid='.$courseId]];
$this->params['breadcrumbs'][] = $this->title;
?>

<form method=post action="create-block?courseId=<?php echo $courseId; if(isset($block)){echo "&block=$block";} if(isset($toTb)){echo "&toTb=$toTb";} if(isset($id)){echo "&id=$id";}?>">
    <div class="item-detail-header">
            <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$courseName], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$courseId], 'page_title' => $this->title]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?><img class="help-img" src="<?php echo AppUtility::getAssetURL()?>img/helpIcon.png" alt="Help" onClick="window.open('<?php echo AppUtility::getHomeURL() ?>docs/help.php?section=blocks','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/></div>
            </div>
        </div>
    </div>
    <div class="tab-content shadowBox non-nav-tab-item" style="padding-bottom: 10px">
        <div class="name-of-item">
            <div class=col-md-2><?php AppUtility::t('Name of Block')?> </div>
            <div class=col-md-10>
                <input class="input-item-title" type=text size=0 name=title value="<?php echo str_replace('"','&quot;',$defaultBlockData['title']);?>" >
            </div>
        </div>
        <BR class=form>
        <div class="item-alignment">
            <div class=col-md-2><?php AppUtility::t('Visibility')?></div>
            <div class=col-md-10>
                <input type=radio name="avail" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['avail'], 1); ?> onclick="document.getElementById('datediv').style.display='block';" /><span style="padding-left: 15px"><?php AppUtility::t('Show by Dates')?></span>
                <label class="non-bold" style="padding-left: 79px"><input type=radio name="avail" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['avail'], 0); ?> onclick="document.getElementById('datediv').style.display='none';"/><span style="padding-left: 15px"><?php AppUtility::t('Hide')?></span></label>
                <label class="non-bold" style="padding-left: 90px"><input type=radio name="avail" value="2" <?php AssessmentUtility:: writeHtmlChecked($defaultBlockData['avail '], 2); ?> onclick="document.getElementById('datediv').style.display='none'; "/><span style="padding-left: 15px"><?php AppUtility::t('Show Always')?></span></label><br>
            </div><br class="form"/><br>

            <!--Show by dates-->
            <div id="datediv" style="display:<?php echo ($forum['avail'] == 1) ? "block" : "none"; ?>">

                <div class=col-md-2><?php AppUtility::t('Available After')?></div>
		        <div class=col-md-10>
                    <label class="pull-left non-bold"><input type=radio name="available-after" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '0', 0) ?>/><span class="padding-left"><?php AppUtility::t('Always until end date')?></span></label>
                    <label class="pull-left non-bold" style="padding-left: 40px"><input type=radio  class="pull-left" name="available-after" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '1', 1) ?>/><span class="padding-left"><?php AppUtility::t('Now')?></span></label><br><br>
                    <label class="pull-left"><input type=radio name="available-after" class="pull-left" value="sdate" <?php echo AssessmentUtility::writeHtmlChecked($defaultBlockData['startDate'], '0', 1) ?>/></label>
                    <?php
                    echo '<div class = "time-input pull-left col-md-4">';
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
                    echo '<label class="end pull-left non-bold col-md-1"> at </label>';
                    echo '<div class="pull-left col-md-6">';
                    echo TimePicker::widget([
                        'name' => 'stime',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>'; ?>
		        </div><BR class=form><br>

                <div class=col-md-2><?php AppUtility::t('Available Until')?></div>
		        <div class=col-md-10>
                    <label class='pull-left non-bold'><input type=radio name="available-until" value="2000000000" <?php echo AssessmentUtility::writeHtmlChecked($defaultBlockData['endDate'], '2000000000', 0) ?>/><span style="padding-left: 15px"><?php AppUtility::t('Always after start date')?></span></label>
                    <label class='pull-left non-bold' style="padding-left: 30px"><input type=radio name="available-until" class="pull-left"  value="edate" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['endDate'], '2000000000', 1) ?>/></label>
                    <?php
                    echo '<div class = "time-input pull-left col-md-4">';
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
                    echo '<label class="end pull-left non-bold"> at </label>';
                    echo '<div class="pull-left col-md-4">';
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

    <div class="item-alignment">
        <div class=col-md-2><?php AppUtility::t('When available')?></div>
        <div class=col-md-10>
            <input type=radio name=availBeh value="O" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'O')?> /><span class="padding-left"><?php AppUtility::t('Show Expanded')?></span><br>
            <input type=radio name=availBeh value="C" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'C')?> /><span class="padding-left"><?php AppUtility::t('Show Collapsed')?></span><br>
            <input type=radio name=availBeh value="F" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'F')?> /><span class="padding-left"><?php AppUtility::t('Show as Folder')?></span><br>
            <input type=radio name=availBeh value="T" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['availBeh'],'T')?> /><span class="padding-left"><?php AppUtility::t('Show as TreeReader')?></span>
        </div></div> <br class=form />

    <div class="item-alignment">
        <div class=col-md-2><?php AppUtility::t('When not available')?></div>
            <div class=col-md-10>
                <input type=radio name=showhide value="H" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['showHide'],'H') ?> /><span class="padding-left"><?php AppUtility::t('Hide from Students')?></span><br>
                <input type=radio name=showhide value="S" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['showHide'],'S') ?> /><span class="padding-left"><?php AppUtility::t('Show Collapsed/as folder')?></span>

    </div></div><br class=form />

     <div class="item-alignment" ">
     <div class="col-md-2"><?php AppUtility::t('If expanded, limit height to')?></div>
        <div class="col-md-10">
        <input type="text" name="fixedheight" size="4" value="<?php if ($defaultBlockData['fixedHeight']>0) {echo $defaultBlockData['fixedHeight'];};?>" /><span class="padding-left"><?php AppUtility::t('pixels (blank for no limit)')?></span>
     </div></div><br class="form" />

        <div class="item-alignment">
            <div class=col-md-2><?php AppUtility::t('Restrict access to students in section')?></div>
                <div class=col-md-4>
                  <?php AssessmentUtility::writeHtmlSelect('grouplimit',$page_sectionListVal,$page_sectionListLabel,$grouplimit[0]); ?>
            </div></div><br class=form>

     <div class="item-alignment">
        <div class=col-md-2><?php AppUtility::t('Make items publicly accessible')?><sup>*</sup>:</div>
        <div class=col-md-10>
            <input type=checkbox name=public value="1" <?php AssessmentUtility::writeHtmlChecked($defaultBlockData['public'],'1') ?> /></div>

	    </div>
        <div class="header-btn col-sm-6 padding-top-thirty padding-bottom-thirty">
            <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo $defaultBlockData['saveTitle'] ?></button>
        </div>
    </form>
    <p class="small col-md-10" style="padding-left: 15px"><sup>*</sup><?php AppUtility::t('If a parent block is set to be publicly accessible, this block will automatically be publicly accessible, regardless of your selection here.')?><br/>
        <?php AppUtility::t('Items from publicly accessible blocks can viewed without logging in at ')?><?php echo "" ?>/public.php?cid=<?php echo ""?>. </p>
</div>
</div>