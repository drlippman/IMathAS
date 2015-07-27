<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = 'Calendar';
$this->params['breadcrumbs'][] = $this->title;
$currentDate = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php
    if($user->rights == 100 || $user->rights == 20) {
        echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'calendar']);
    } elseif($user->rights == 10){
        echo $this->render("_toolbarStudent", ['course' => $course, 'section' => 'calendar']);
    }?>
    <div class="tab-content col-lg-12">
        <div class="col-lg-12 padding-alignment calendar-container">
            <?php if($user->rights == 100 || $user->rights == 20) {?>
            <pre><a href="#" onclick="deleteItem('<?php echo $item['Calendar'] ;?>','<?php echo AppConstant::CALENDAR ?>','<?php echo $parent ;?>','<?php echo $course->id ;?>')">Delete</a> | <a
                    href="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/manage-events?cid=' . $course->id); ?>">Manage Events</a></pre>
            <?php }?>
            <div class ='calendar padding-alignment calendar-alignment col-lg-9 pull-left'>

                <input type="hidden" class="current-time" value="<?php echo $currentDate?>">
                <div id="demo" style="display:table-cell; vertical-align:middle;"></div>
                <input type="hidden" class="calender-course-id" value="<?php echo $course->id ?>">
            </div>
            <div class="calendar-day-details-right-side pull-left col-lg-3">
                <div class="day-detail-border">
                    <b>Day Details:</b>
                </div>
                <div class="calendar-day-details"></div>
            </div>
        </div>
    </div>
</div>