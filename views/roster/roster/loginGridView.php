<?php
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\controllers\AppController;
use yii\helpers\Html;

$this->title = AppUtility::t('Login Grid View', false);
$this->params['breadcrumbs'][] = $this->title;
  ?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid='.$course->id]]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]); ?>
</div>

<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="inner-content">
     <div class="midwrapper">
        <form method="post" id="form-id" action="login-grid-view?cid=<?php echo $course->id;?>">
        <input type="hidden" value="daterange" name="daterange">
            <div id="headerlogo" class="hideinmobile" onclick="mopen('homemenu',1)" onmouseout="mclosetime()"></div>
        <div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()"></div>
        <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
            <p><?php AppUtility::t('Showing Number of Logins');?> <?php echo "$starttime through $endtime";?></p>
            <div class="pull-left select-text-margin">
            <a href="<?php echo AppUtility::getURLFromHome('roster','roster/login-grid-view?cid='.$course->id.'&start='.($start-7*24*60*60));?>"><?php AppUtility::t('Show previous week')?>.</a> &nbsp;
        <?php  if ($end < $now) { ?>
            <a href="<?php echo AppUtility::getURLFromHome('roster','roster/login-grid-view?cid='.$course->id.'&start='.($start + 7*24*60*60));?>"><?php AppUtility::t('Show following week')?>.</a> &nbsp;
        <?php } ?>
        </div>
        <div class="pull-left select-text-margin"><?php AppUtility::t('Show')?></div>
        <div class="pull-left" id="datepicker-id">
            <?php
            echo DatePicker::widget([
                'name' => 'sdate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' =>  $sdate,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy'
                ]
            ]);
            ?>
        </div>
        <div class="pull-left select-text-margin"><?php AppUtility::t('through')?></div>
        <div class="pull-left" id="datepicker-id1" >
            <?php
            echo DatePicker::widget([
                'name' => 'edate',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $edate,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy' ]
            ]);
            ?>
        </div>
        <div class="pull-left">
            <input type="button" id="go-button"  name="daterange" value="<?php AppUtility::t('Go')?>"/>
        </div>
            </form>
        <div id="  " class="clear myScrollTable" >
        <table class="login-table table scrollit table-striped table-hover datatable"  bPaginate='false' id=" ">
            <thead>
            <tr>
             <div>   <th>Name</th> </div>
                <?php
                foreach ($dates as $date) {
                    echo '<th>'.$date.'</th>';
                }
                ?>
            </tr></thead>
            <tbody>
            <?php

            $alt = 0;
            $n = count($dates);
            foreach ($stus as $stu)
            {
                if ($alt==0)
                {
                    echo '<tr class="even">'; $alt=1;} else {echo '<tr class="odd">'; $alt=0;
                } ?>
            <td class="left"> <a href="<?php echo AppUtility::getURLFromHome('roster','roster/login-log?cid='.$course->id.'&uid='.$stu[1]);?>"><?php echo $stu[0]?></a></td>
                <?php for ($i=0;$i<$n;$i++) {
                    echo '<td>';
                    if (isset($logins[$stu[1]][$i])) {
                        echo $logins[$stu[1]][$i];
                    } else {
                        echo ' ';
                    }
                    echo '</td>';
                }
                echo '<tr/>';
            }
            ?>
            </tbody>
        </table>
        <p><?php AppUtility::t('Note: Be aware that login sessions last for 24 hours, so if a student logins in Wednesday at 7pm and never
        closes their browser, they can continue using the same session on the same computer until 7pm Thursday.');?></p>
        <div class="clear"></div>
</div>
<div class="footerwrapper"></div>
</div>
</div>