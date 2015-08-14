<?php
use app\components\AppUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
$this->title = $pageTitle;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>"
      xmlns="http://www.w3.org/1999/html">
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?><i class="fa fa-question help-icon"></i></div>
            </div>
            <div class="pull-left header-btn">
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo $saveTitle ?></button>
            </div>
        </div>
    </div>

    <div class="tab-content shadowBox non-nav-tab-item">
        <div class="name-of-item">
            <div class="col-lg-2"><?php AppUtility::t('Name of Wiki')?></div>
            <div class="col-lg-10">
                <?php $title = AppUtility::t('Enter title here', false);
                if($wiki['name']){
                      $title = $wiki['name'];
                    } ?>
                <input class="input-item-title" type=text size=0 name=name value="<?php echo $title;?>">
            </div>
        </div>
        <BR class=form>

        <div class="editor-summary">
            <div class="col-lg-2"><?php AppUtility::t('Description')?></div>
            <div class="col-lg-10">
                <div class=editor>
                    <textarea cols=5 rows=12 id=description name=description style="width: 100%;">
                        <?php $text = AppUtility::t('Enter Wiki description here');
                        if($wiki['description'])
                        {
                            $text = $wiki['description'];
                        }
                        echo htmlentities($text);?>
                    </textarea>
                </div>
            </div>
        </div><BR class=form>

        <div class="visibility-item">
            <div class="col-lg-2"><?php AppUtility::t('Visibility')?></div>
            <div class="col-lg-10">
                <input type=radio name="avail" value="1" <?php AppUtility::writeHtmlChecked($line['avail'],1);?> onclick="document.getElementById('datediv').style.display='block';"/><span class='padding-left'><?php AppUtility::t('Show by Dates')?></span>
                <label class="non-bold" style="padding-left: 80px"><input type=radio name="avail" value="0" <?php AppUtility::writeHtmlChecked($line['avail'],0);?>/><span class='padding-left'><?php AppUtility::t('Hide')?></span></label>
                <label class="non-bold" style="padding-left: 80px"><input type=radio name="avail" value="2" <?php AppUtility::writeHtmlChecked($line['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';"/><span class='padding-left'><?php AppUtility::t('Show Always')?></span></label>
            </div>
            <div>
                <div id="datediv" style="display:<?php echo ($wiki['avail']==1)?"block":"none"; ?>"><BR class=form><br>
                    <div class="col-lg-2"><?php AppUtility::t('Available After')?></div>
                <div class=col-lg-10>
                    <input type=radio name="sdatetype" class="pull-left" value="0" <?php AppUtility::writeHtmlChecked($startdate,'0',0) ?>/><span class="pull-left padding-left"><?php AppUtility::t('Always until end date')?></span>
                    <label class="pull-left" style="padding-left: 41px"><input type=radio name="sdatetype" class="pull-left" value="sdate" <?php AppUtility::writeHtmlChecked($startdate,'0',1) ?>/></label>
                    <?php
                    echo '<div class = "time-input pull-left col-lg-4">';
                    echo DatePicker::widget([
                        'name' => 'StartDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]);
                    echo '</div>';?>
                    <?php
                    echo '<label class="end pull-left non-bold">at</label>';
                    echo '<div class="pull-left col-lg-4">';
                    echo TimePicker::widget([
                        'name' => 'start_end_time',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>';?>
                </div><BR class=form><br>

                 <div class=col-lg-2><?php AppUtility::t('Available Until')?></div>
                <div class=col-lg-10>
                    <label class="pull-left non-bold"><input type=radio name="edatetype" class="pull-left" value="2000000000" <?php AppUtility::writeHtmlChecked($enddate,'2000000000',0) ?>/><span class="padding-left"><?php AppUtility::t('Always after start date')?></span></label>
                    <label class="pull-left" style="padding-left: 34px"><input type=radio name="edatetype" class="pull-left" value="edate"  <?php AppUtility::writeHtmlChecked($enddate,'2000000000',1) ?>/></label>
                    <?php
                    echo '<div class = "time-input pull-left col-lg-4">';
                    echo DatePicker::widget([
                        'name' => 'EndDate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => date("m/d/Y"),
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]);
                    echo '</div>';?>
                    <?php
                    echo '<label class="end pull-left non-bold"> at </label>';
                    echo '<div class="pull-left col-lg-4">';

                    echo TimePicker::widget([
                        'name' => 'end_end_time',
                        'value' => time(),
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]);
                    echo '</div>';?>
                </div>
</div>
<BR class=form>
        </div>
            <div class="group-wiki">
        <div class="col-lg-2"><?php AppUtility::t('Group wiki?')?></div>
        <div class="col-lg-4 dropdown">
<?php
if ($started) {
    AppUtility::writeHtmlSelect("ignoregroupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0,$started?'disabled="disabled"':'');
    echo '<input type="hidden" name="groupsetid" value="'.$line['groupsetid'].'" />';
} else {
    AppUtility::writeHtmlSelect("groupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0);
}
?>
        </div>
    </div>
  <br class="form"/><br class="form"/>

        <div class=col-lg-2><?php AppUtility::t('Students can edit')?></div>
		<div class=col-lg-10>
            <input type=radio name="rdatetype" value="Always" <?php if ($revisedate==2000000000) { echo "checked=1";}?>/><span class='padding-left'><?php AppUtility::t('Always')?></span><br>
            <input type=radio name="rdatetype" value="Never" <?php if ($revisedate==0) { echo "checked=1";}?>/><span class="padding-left"><?php AppUtility::t('Never')?></span><br>
            <label class="pull-left non-bold"><input type=radio name="rdatetype" value="Date" <?php if ($revisedate<2000000000 && $revisedate>0) { echo "checked=1";}?>/><span class='padding-left'><?php AppUtility::t('Before')?></span></label>
            <?php
            echo '<div class = "col-lg-4 time-input">';
            echo DatePicker::widget([
                'name' => 'Calendar',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => date("m/d/Y"),
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy' ]
            ]);
            echo '</div>';?>
            <?php
            echo '<label class="end col-lg-1 non-bold"> at </label>';
            echo '<div class="pull-left col-lg-4">';

            echo TimePicker::widget([
                'name' => 'calendar_end_time',
                'value' => time(),
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';?>
		</div><br class="form" />
    </form><br>
</div>
