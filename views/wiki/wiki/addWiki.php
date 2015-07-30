<?php
use app\components\AppUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
$this->title = $pageTitle;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
 <form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>">
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?><i class="fa fa-question help-icon"></i></div>
            </div>
            <div class="pull-left header-btn">
                <button class="btn btn-primary pull-right page-settings" type="submit" value="Submit"><i class="fa fa-share" style="padding-right:10px;"></i><?php echo $saveTitle ?></button>
            </div>
        </div>
    </div>

    <div class="tab-content shadowBox" style="margin-top:30px">
        <div style="padding-top: 20px">
            <div class="col-lg-2"><?php AppUtility::t('Name of Wiki')?></div>
            <div class="col-lg-10">
                <?php $title = AppUtility::t('Enter title here');
                if($wiki['name']){
                      $title = $wiki['name'];
                    } ?>
                <input type=text size=0 style="width: 100%;height: 40px; border: #6d6d6d 1px solid;" name=name value="<?php echo $title;?>">
            </div>
        </div>
        <BR class=form>

        <div style="margin-top: 20px">
            <div class="col-lg-2"><?AppUtility::t('Description')?></div>
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
        </div>

        <div style="margin-top: 20px">
            <div class="col-lg-2">Visibility</div>
            <div class="col-lg-10">
                <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility'><td><input type=radio name="avail" value="0" <?php AppUtility::writeHtmlChecked($line['avail'],0);?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Hide')?></td></div>
                <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility'><td><input type=radio name="avail" value="1" <?php AppUtility::writeHtmlChecked($line['avail'],1);?> onclick="document.getElementById('datediv').style.display='block';"/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show by Dates')?></td></div>
                <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility'><td><input type=radio name="avail" value="2" <?php AppUtility::writeHtmlChecked($line['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';"/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Show Always')?></td></div>
            </div>


            <div>
                <div id="datediv" style="display:<?php echo ($wiki['avail']==1)?"block":"none"; ?>">
                    <div class="col-lg-2"><?php AppUtility::t('Available After')?></div>
                <div class=col-lg-10>
                    <div class='radio student-enroll visibility'><label class='checkbox-size visibility'><td><input type=radio name="sdatetype" class="pull-left" value="0" <?php AppUtility::writeHtmlChecked($startdate,'0',0) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Always until end date')?></td></div>

                    <div class='radio student-enroll visibility'><label class='checkbox-size visibility pull-left'><td><input type=radio name="sdatetype" class="pull-left" value="sdate" <?php AppUtility::writeHtmlChecked($startdate,'0',1) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td></div>
                    <?php
                    echo '<div class = "col-lg-4 time-input" style="padding:0">';
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
                    echo '<label class="end col-lg-1">at</label>';
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
                </div><BR class=form>

                 <div class=col-lg-2><?php AppUtility::t('Available Until')?></div>
                <div class=col-lg-10>
                    <div class='radio student-enroll visibility'><label class='checkbox-size visibility'><td><input type=radio name="edatetype" class="pull-left" value="2000000000" <?php AppUtility::writeHtmlChecked($enddate,'2000000000',0) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Always after start date')?></td></div>
                    <div class='radio student-enroll visibility'><label class='checkbox-size visibility pull-left'><td><input type=radio name="edatetype" class="pull-left" value="edate"  <?php AppUtility::writeHtmlChecked($enddate,'2000000000',1) ?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td></div>
                    <?php
                    echo '<div class = "col-lg-4 time-input" style="padding: 0">';
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
                    echo '<label class="end col-lg-1"> at </label>';
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
            <div style="padding-top: 15px">
        <div class="col-lg-2"><?php AppUtility::t('Group wiki?')?></div>
        <div class="col-lg-10 dropdown">
<?php
if ($started) {
    AppUtility::writeHtmlSelect("ignoregroupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0,$started?'disabled="disabled"':'');
    echo '<input type="hidden" name="groupsetid" value="'.$line['groupsetid'].'" />';
} else {
    AppUtility::writeHtmlSelect("groupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0);
}
?>
        </div>		</div><br class="form"/>

        <div class=col-lg-2><?php AppUtility::t('Students can edit')?></div>
		<div class=col-lg-10>
            <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility'><td><input type=radio name="rdatetype" value="Always" <?php if ($revisedate==2000000000) { echo "checked=1";}?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Always')?></td></div>
            <div class='radio student-enroll visibility'><label class='checkbox-size label-visibility'><td><input type=radio name="rdatetype" value="Never" <?php if ($revisedate==0) { echo "checked=1";}?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Never')?></td></div>
            <div class='radio student-enroll visibility pull-left'><label class='checkbox-size label-visibility pull-left'><td><input type=radio name="rdatetype" value="Date" <?php if ($revisedate<2000000000 && $revisedate>0) { echo "checked=1";}?>/><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td><td><?php AppUtility::t('Before')?></td></div>
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
            echo '<label class="end col-lg-1"> at </label>';
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
    </form><br><br>
</div>
