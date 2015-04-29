<?php
use yii\helpers\Html;
use app\components\AppUtility;
?>
<link rel="stylesheet" href="../../../web/css/_leftSide.css"/>
<link rel="stylesheet" href="../../../web/css/assessment.css"/>

<?php

//AppUtility::dump(date('h:i a'));
//AppUtility::dump(date('m/d/Y'));
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));

//AppUtility::dump($currentTime);
?>
<?php echo $this->render('_toolbar');?>

<div class="needed">
    <?php echo $this->render('_leftSide'); ?>
</div>

<!--Assessment here-->

<div class="course">
    <h3><b><?php echo $course->name ?></b></h3>
</div>

<div class="margin-top">
    <div class="inactivewrapper " onmouseover="this.className='activewrapper' "onmouseout="this.className='inactivewrapper'">
        <?php foreach($assessments as $key => $assessment) {?>
            <?php if($assessment->enddate > $currentTime && $assessment->startdate < $currentTime) {?>
                <div class=item>
                    <div class=icon style="background-color: #1f0;">?</div>
                        <div class=title>
                            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid='.$assessment->courseid)?>"><?php echo $assessment->name ?></a></b>
                            <?php if ($assessment->enddate != 2000000000) { ?>
                                <BR><?php echo 'Due '. AppUtility::formatDate($assessment->enddate); ?>
                            <?php }?>
                        </div>
                    <div class=itemsum>
                        <p><?php echo $assessment->summary ?></p>
                    </div>
                </div>
            <?php }
            elseif($assessment->enddate < $currentTime && ($assessment->reviewdate != 0) && ($assessment->reviewdate > $currentTime)){ ?>
                <div class=item>
                    <div class=icon style="background-color: #1f0;">?</div>
                    <div class=title>
                        <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid='.$assessment->courseid)?>"><?php echo $assessment->name ?></a></b>
                        <?php if ($assessment->reviewdate == 2000000000){ ?>
                            <BR><?php echo 'Past Due Date of '. AppUtility::formatDate($assessment->enddate).'. Showing as Review.'; ?>
                            <BR>This assessment is in review mode - no scores will be saved.
                        <?php }else{?>
                            <BR><?php echo 'Past Due Date of '. AppUtility::formatDate($assessment->enddate).'. Showing as Review until '.AppUtility::formatDate($assessment->reviewdate).'.'; ?>
                            <BR>This assessment is in review mode - no scores will be saved.
                        <?php }?>
                    </div>
                    <div class=itemsum>
                        <p><?php echo $assessment->summary ?></p>
                    </div>
                </div>
            <?php }?>
        <?php } ?>
    </div>
    <!--Forum here-->

    <?php foreach($forums as $key => $forum ) {?>
    <div class=item>
        </a><img alt="forum" class="floatleft" src="/IMathAS/img/forum.png"/>
        <div class=title>
            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/index?cid='.$forum->courseid)?>">
            <?php echo $forum->name ?></a></b>
        </div>
        <div class=itemsum><p>
            <p>&nbsp;<?php echo $forum->description ?></p></p></div>
    </div>

    <?php }?>

</div>

