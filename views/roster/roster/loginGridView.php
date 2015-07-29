<?php
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\controllers\AppController;
use yii\helpers\Html;

$this->title = AppUtility::t('Login Grid View', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . '/roster/roster/student-roster?cid=' . $course->id]]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="inner-content">
    <div class="headerwrapper"></div>
    <div class="midwrapper">
        <div id="headerlogo" class="hideinmobile" onclick="mopen('homemenu',1)" onmouseout="mclosetime()"></div>
        <div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()"></div>
        <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
        <p><?php AppUtility::t('Showing Number of Logins')?>&nbsp;<label id="first-date-label"></label> &nbsp;<?php AppUtility::t('through')?>&nbsp;<label id="last-date-label"></label>
        <div class="pull-left select-text-margin">
           <a id="previous-link" href="#"><?php AppUtility::t('Show previous week.')?></a>&nbsp;&nbsp;<a id="following-link" href="#"><?php AppUtility::t('Show following week.')?></a>&nbsp;&nbsp;
        </div>
        <br><br>
        <div class="pull-left select-text-margin"><?php AppUtility::t('Show')?></div>
        <div class="pull-left" id="datepicker-id">
            <?php
            echo DatePicker::widget([
                'name' => 'First_Date_Picker',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => date("m-d-Y",strtotime("-1 week +1 day")),
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm-dd-yyyy'
                ]
            ]);
            ?>
        </div>
        <div class="pull-left select-text-margin"><?php AppUtility::t('through')?></div>
        <div class="pull-left" id="datepicker-id1" >
            <?php
            echo DatePicker::widget([
                'name' => 'Second_Date_Picker',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => date("m-d-Y"),
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm-dd-yyyy' ]
            ]);
            ?>
        </div>
        <div class="pull-left">
            <input type="submit" id="go-button"  name="daterange" value="<?php AppUtility::t('Go')?>"/>
        </div>
        <div id="table_placeholder" class="clear" ></div>

        <p><?php AppUtility::t('Note: Be aware that login sessions last for 24 hours, so if a student logins in Wednesday at 7pm and never
        closes their browser, they can continue using the same session on the same computer until 7pm Thursday.');?></p>
        <div class="clear"></div>
</div>
<div class="footerwrapper"></div>
</div>
</div>



