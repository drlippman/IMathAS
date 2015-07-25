<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;

$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<link href='<?php echo AppUtility::getHomeURL(); ?>css/course/course.css?<?php echo time(); ?>' rel='stylesheet' type='text/css'>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print'/>

<!--<div class="mainbody">-->
<input type="hidden" class="calender-course-id" id="courseIdentity" value="<?php echo $course->id ?>">
<input type="hidden" class="courseId" value="<?php echo $course->id?>">
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithButton",['item_name'=>'Course Settings', 'link_title'=>'Home', 'link_url' => AppUtility::getHomeURL().'site/index', 'page_title' => $this->title]); ?>
</div>

<div class="item-detail-content">
    <?php echo $this->render("_toolbarTeacher", ['course' => $course, 'section' => 'course']);?>
</div>

<div class="tab-content shadowBox">
    <div class="row course-copy-export">
        <div class="col-md-1 course-top-menu">
            <a href="#">Copy All</a>
        </div>
        <div class="col-md-2 course-top-menu">
            <a href="#">Export Course</a>
        </div>
    </div>
    <div class="clear-both"></div>
    <div class=" row add-item">
        <div class="col-md-1 plus-icon">
            <i class="fa fa-plus fa-2x"></i>
        </div>
        <div class=" col-md-2 add-item-text">
            <p>Add An Item...</p>
        </div>

    </div>
    <input type="hidden" class="home-path" value="<?php echo AppUtility::getURLFromHome('instructor', 'instructor/index?cid=' . $course->id) ?>">
    <input type="hidden" class="block-check" value="<?php echo $tb = 't'; ?>">
    <div class="display-item-details">
            <?php
            $parent = \app\components\AppConstant::NUMERIC_ZERO;

            $countCourseDetails = count($courseDetail);
            if ($countCourseDetails){

                $assessment = $blockList = array();
                foreach ($courseDetail as $key => $item){
                    echo AssessmentUtility::createItemOrder($key, $countCourseDetails, $parent, $blockList);
                    switch (key($item)):
                        case 'Assessment': ?>
                            <?php CourseItemsUtility::AddAssessment($assessment,$item,$course,$currentTime,$parent);?>
                            <input type="hidden" class="assessment-link" value="<?php echo $assessment->id?>">
                            <?php break; ?>
                            <!-- ///////////////////////////// Forum here /////////////////////// -->,
                        <?php case 'Forum': ?>
                        <?php CourseItemsUtility::AddForum($item,$course,$currentTime,$parent); ?>
                        <?php break; ?>
                        <!-- ////////////////// Wiki here //////////////////-->
                    <?php case 'Wiki': ?>
                        <?php CourseItemsUtility::AddWiki($item,$course,$parent); ?>
                        <?php break; ?>
                        <!-- ////////////////// Linked text here //////////////////-->
                    <?php
                        case 'LinkedText': ?>
                            <?php CourseItemsUtility::AddLink($item,$currentTime,$parent,$course);?>
                            <?php break; ?>
                            <!-- ////////////////// Inline text here //////////////////-->
                        <?php case 'InlineText': ?>
                        <?php CourseItemsUtility::AddInlineText($item,$currentTime,$course,$parent);?>
                        <?php break; ?>
                        <!-- Calender Here-->
                    <?php case 'Calendar': ?>
                        <?php CourseItemsUtility::AddCalendar($item,$parent,$course);?>
                        <?php break; ?>
                        <!--  Block here-->
                    <?php case  'Block': ?>
                        <?php  $cnt++; ?>
                        <?php $displayBlock = new CourseItemsUtility();
                        $displayBlock->DisplayWholeBlock($item,$currentTime,$assessment,$course,$parent,$cnt);
                        ?>
                        <?php break; ?>
                    <?php endswitch;
                    ?>

                <?php }?>

            <?php } ?>
       </div>
    </div>