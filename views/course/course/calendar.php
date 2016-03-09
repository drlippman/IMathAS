<?php

use app\components\AppUtility;
use app\components\AppConstant;
$this->title = 'Calendar';
$this->params['breadcrumbs'][] = $this->title;
$currentDate = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
if($user['rights']!=100)
$isTeacher= false;
else
$isTeacher=true;
$groupAdmin = $user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT;
$currentUser = $user['id'];
?>

<input type="hidden" class="user-rights" value="<?php echo $user['rights']?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>

<div class = "title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php if($user['rights'] > 10 && ($isTutor && ($user['rights'] >= AppConstant::GROUP_ADMIN_RIGHT))) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'calendar']);
    } elseif($isStudent || ($isTutor && ($user['rights'] != $groupAdmin))){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course, 'section' => 'calendar', 'userId' => $currentUser]);
    } else if($user['rights'] > 10){
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'calendar']);
    }?>
</div>

<div class="tab-content col-md-12">
        <div class="col-md-12 padding-alignment calendar-container">
            <?php if(!$isStudent) {
                ?>
            <pre><a href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/manage-events?cid=' . $course->id); ?>"><?php AppUtility::t('Manage Events')?></a></pre>
            <?php }?>
            <div class ='calendar padding-alignment calendar-alignment col-md-9 pull-left'>
                <input type="hidden" class="current-time" value="<?php echo $currentDate?>">
                <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
            </div>
            <div class="calendar-day-details-right-side pull-left col-md-3">
                <div class="day-detail-border ">
                    <b style="font-size: 18px"><?php AppUtility::t('Day Details')?></b>
                    <a style="font-size: 12px; float: right" href="#" onclick="ShowAll();">Show All</a>
                    <div class="day-details"></div>
                </div>

                <div class="calendar-day-details word-wrap-break-word show-all"></div>
            </div>
        </div>
    </div>
