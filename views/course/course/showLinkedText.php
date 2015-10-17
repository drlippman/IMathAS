<?php
use app\components\AppUtility;
$this->title = $course->name;
?>
<!-- Name of selected linked text-->
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home'], 'link_url' => [AppUtility::getHomeURL().'site/index']]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
<div class="tab-content shadowBox non-nav-tab-item">
    <div class="padding-left padding-top-fifteen">
            <h3><b><?php echo $links->title ?></b></h3>
            <div class="col-md-3">
                <h5><?php echo $links->text?></h5>
            </div>
    </div>

    <div class="col-lg-12 align-linked-text-right">
        <?php if($user->rights == 10){?>
            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $links->courseid) ?>"><?php AppUtility::t('Return to course page')?></a>
                <?php } elseif($user->rights == 100 || $user->rights == 20){?>
            <b><a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $links->courseid) ?>"><?php AppUtility::t('Return to course page')?></a>
        <?php } ?>
        <br><br>
    </div>
</div>