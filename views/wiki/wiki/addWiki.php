    <?php
use app\components\AppUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
use app\components\AppConstant;
use app\components\AssessmentUtility;
$this->title = $defaultValue['pageTitle'];
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/course?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<form enctype="multipart/form-data" method=post action="<?php echo $page_formActionTag ?>"
      xmlns="http://www.w3.org/1999/html">
<?php if($this->title=="Modify Wiki") {?>
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'Modify Wiki'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
    </div>
<?php } else{ ?>
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'Add Wiki'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
    </div>
<?php } ?>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>

    <div class="tab-content shadowBox non-nav-tab-item">
        <div class="name-of-item">
            <div class="col-md-2 col-sm-2 padding-top-pt-five-em"><?php AppUtility::t('Name of Wiki')?></div>
            <div class="col-md-10 col-sm-10">
                <?php $title = AppUtility::t('Enter title here', false);
                if($defaultValue['name']){
                      $title = $defaultValue['name'];
                    } ?>
                <input class="input-item-title form-control" id="title-blank" type=text size=0 name=name value="<?php echo $defaultValue['title'];?>">
                <span id="title-error" class="error-message col-md-10 col-sm-10 col-md-offset-1 col-sm-offset-1"></span>
            </div>
        </div>
        <BR class=form>

        <div class="editor-summary">
            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Description')?></div>
            <div class="col-md-10 col-sm-10">
                <div class="editor add-wiki-summary-textarea">
                    <textarea cols=5 rows=12 id=description name=description style="width: 100%;">
                        <?php $text = "enter data";
                        if($defaultValue['description'])
                        {
                            $text = $defaultValue['description'];
                        }
                        echo htmlentities($text);?>
                    </textarea>
                </div>
            </div>
        </div><BR class=form>

        <div class="visibility-item">
            <div class="col-md-2 col-sm-2"><?php AppUtility::t('Visibility')?></div>
            <div class="col-md-10 col-sm-10">

                <span class="col-md-3 col-sm-3 padding-left-zero">
                    <input type=radio name="avail" value="1" <?php AppUtility::writeHtmlChecked($defaultValue['avail'], AppConstant::NUMERIC_ONE);?> onclick="document.getElementById('datediv').style.display='block';"/>
                    <span class='padding-left'><?php AppUtility::t('Show by Dates')?></span>
                </span>

                <span class="col-md-2 col-sm-2 padding-left-zero">
                    <label class="non-bold">
                        <input type=radio name="avail" value="0" <?php AppUtility::writeHtmlChecked($defaultValue['avail'],0);?>/>
                        <span class='padding-left'><?php AppUtility::t('Hide')?></span>
                    </label>
                </span>

                <span class="col-md-3 col-sm-3 padding-left-zero">
                    <label class="non-bold">
                        <input type=radio name="avail" value="2" <?php AppUtility::writeHtmlChecked($defaultValue['avail'],2);?> onclick="document.getElementById('datediv').style.display='none';"/>
                        <span class='padding-left'><?php AppUtility::t('Show Always')?></span>
                    </label>
                </span>

            </div>
            <div>
                <div id="datediv" style="display:<?php echo ($defaultValue['avail']==1)?"block":"none"; ?>"><BR class=form><br>
                <div class="col-md-2 col-sm-2 padding-top-pt-five-em">
                    <?php AppUtility::t('Available After')?>
                </div>
                <div class="col-md-10 col-sm-10">
                    <span class="floatleft padding-top-pt-five-em col-md-3 col-sm-4 padding-left-zero">
                        <input type=radio name="available-after" class="pull-left" value="0" <?php AssessmentUtility::writeHtmlChecked($defaultValue['startDate'], "0", 0); ?>/>
                        <span class="pull-left padding-left"><?php AppUtility::t('Always until end date')?></span>
                    </span>
                    <label class="padding-top-pt-five-em pull-left">
                        <input type=radio name="available-after" class="pull-left" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultValue['startDate'], "0", 1); ?>/>
                    </label>

                    <div class = "time-input pull-left col-md-3 col-sm-4 padding-left-one-pt-five-em">
                    <?php  echo DatePicker::widget([
                        'name' => 'sdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => $defaultValue['sDate'],
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]); ?>
                    </div>
                    <label class="end pull-left non-bold padding-top-pt-five-em">at</label>
                    <div class="mobile-add-wiki-time-picker pull-left col-md-4 col-sm-4">
                        <?php  echo TimePicker::widget([
                            'name' => 'stime',
                            'value' => $defaultValue['sTime'],
                            'pluginOptions' => [
                                'showSeconds' => false,
                                'class' => 'time'
                            ]
                        ]); ?>
                    </div>
                </div><BR class=form><br>

                 <div class="col-md-2 col-sm-2 padding-top-pt-five-em"><?php AppUtility::t('Available Until')?></div>
                <div class="col-md-10 col-sm-10">
                    <label class="pull-left non-bold padding-top-pt-five-em col-md-3 col-sm-4 padding-left-zero padding-right-zero">
                        <input type=radio name="available-until" class="pull-left" value="2000000000" <?php AssessmentUtility::writeHtmlChecked($defaultValue['endDate'], "2000000000", 0); ?>/>
                        <span class="padding-left"><?php AppUtility::t('Always after start date')?></span>
                    </label>
                    <label class="padding-top-pt-five-em pull-left"><input type=radio name="available-until" class="pull-left" value="1" <?php AssessmentUtility::writeHtmlChecked($defaultValue['endDate'], "2000000000", 1); ?>/></label>
                    <?php
                    echo '<div class = "time-input col-md-3 col-sm-4">';
                    echo DatePicker::widget([
                        'name' => 'edate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => $defaultValue['eDate'],
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]);
                    echo '</div>';?>
                    <?php
                    echo '<label class="end pull-left non-bold padding-top-pt-five-em"> at </label>';
                    echo '<div class="pull-left col-md-4 col-sm-4 mobile-add-wiki-time-picker">';

                    echo TimePicker::widget([
                        'name' => 'etime',
                        'value' => $defaultValue['eTime'],
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
        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Group wiki?')?></div>
        <div class="col-md-3 col-sm-3 dropdown">
<?php
if ($started) {
    AppUtility::writeHtmlSelect("ignoregroupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0,$started?'disabled="disabled"':'');
    echo '<input type="hidden" name="groupsetid" value="'.$line['groupsetid'].'" />';
} else {
    AppUtility::writeHtmlSelect("groupsetid",$page_groupSelect['val'],$page_groupSelect['label'],$line['groupsetid'],"Not group wiki",0);
}
$revisedate=$wiki['editbydate'];
?>
        </div>
    </div>
  <br class="form"/><br class="form"/>

        <div class="col-md-2 col-sm-2 padding-right-zero"><?php AppUtility::t('Students can edit')?></div>
		<div class="col-md-10 col-sm-10 padding-left-zero">
            <span class="col-md-12 col-sm-12">
                <input type=radio name="rdatetype" value="Always" <?php if ($revisedate==2000000000) { echo "checked=1";}?>/>
                <span class='padding-left'><?php AppUtility::t('Always')?></span>
            </span>
            <span class="col-md-12 col-sm-12 padding-top-pt-five-em">
                <input type=radio name="rdatetype" value="Never" <?php if ($revisedate==0) { echo "checked=1";}?>/>
                <span class="padding-left"><?php AppUtility::t('Never')?></span>
            </span>

            <span class="col-md-12 col-sm-12 padding-top-pt-five-em">
                <label class="pull-left non-bold padding-top-pt-five-em">
                    <input type=radio name="rdatetype" value="Date" <?php if ($revisedate<2000000000 && $revisedate>0) { echo "checked=1";}?>/>
                    <span class='padding-left'><?php AppUtility::t('Before')?></span>
                </label>
               <div class = "col-md-3 col-sm-4 time-input">
                    <?php    echo DatePicker::widget([
                        'name' => 'rdate',
                        'type' => DatePicker::TYPE_COMPONENT_APPEND,
                        'value' => $rdate,
                        'removeButton' => false,
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'mm/dd/yyyy' ]
                    ]); ?>
                </div>
                <label class="end floatleft non-bold padding-top-pt-five-em"> at </label>
                <div class="pull-left col-md-4 col-sm-5">
                    <?php  echo TimePicker::widget([
                        'name' => 'rtime',
                        'value' => $rtime,
                        'pluginOptions' => [
                            'showSeconds' => false,
                            'class' => 'time'
                        ]
                    ]); ?>
                </div>
            </span>
        </div>

        <div class="header-btn col-md-6 col-sm-6 col-sm-offset-2 padding-top-thirty padding-bottom-thirty">
            <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo $defaultValue['saveTitle'] ?></button>
        </div>
    </form>
</div>
