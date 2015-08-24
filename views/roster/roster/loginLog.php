<?php
use app\components\AppUtility;
$this->title = AppUtility::t('Login Log', false);
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="item-detail-header">
    <?php if(isset($from) && $from=='gb'){
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false),AppUtility::t('Student Detail', false)],
            'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id,
                AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId=0'),AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId='.$userId)]]);
    }else{
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)],
            'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getURLFromHome('roster','roster/student-roster?cid='.$course->id)]]);

    } ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">
    </br>
    <div class="align-login-view">
        <h3><strong>View Login Log</strong></h3>
        <pre><a href="<?php echo AppUtility::getURLFromHome('roster','roster/activity-log?cid='.$course->id.'&uid='.$userId) ;?>">View Activity Log</a></pre>
        <h4><strong>Login Log for <?php echo $userFullName ?></strong></h4>
        <?php
        foreach($lastlogin as $login) { ?>
            <p><?php echo $login['logDateTime'];?></p>
        <?php } ?>
    </div>
</div>