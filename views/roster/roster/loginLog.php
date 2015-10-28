<?php
use \app\components\AppConstant;
use app\components\AppUtility;
$this->title = AppUtility::t('Login Log', false);
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="item-detail-header">
    <?php if(isset($from) && $from=='gb'){
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false),AppUtility::t('Student Detail', false)],
            'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id,
                AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId=0'),AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId='.$userId)]]);
    }else{
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Roster', false)],
            'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid=' . $course->id, AppUtility::getURLFromHome('roster','roster/student-roster?cid='.$course->id)]]);

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
    <div class="login-log-header">
        <a class="padding-left-thirty" href="<?php echo AppUtility::getURLFromHome('roster','roster/activity-log?cid='.$course->id.'&uid='.$userId) ;?>">View Activity Log</a>
    </div>
    <div class="roster-login-log">
       <div class="col-md-12">
           <h4 class="padding-top-twenty padding-bottom-fifteen">
               <strong>Login Log for <?php echo $userFullName ?></strong>
           </h4>
       </div>
        <table class="login-log-table table table-bordered table-striped table-hover data-table">
            <thead>
            <tr>
                <th>Sr. No.</th>
                <th>Login Log</th>
            </tr>
            </thead>
            <tbody class="user-table-body">
        <?php
        foreach($lastlogin as $key => $login) { ?>
            <tr>
                <td><?php echo($key + AppConstant::NUMERIC_ONE); ?></td>
                <td>
                    <?php echo $login['logDateTime'];?>
                </td>
            </tr>
        <?php } ?>
            </tbody>
            </table>
    </div>
</div>