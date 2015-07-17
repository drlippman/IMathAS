<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = 'Thread';
if ($users->rights > AppConstant::STUDENT_RIGHT){

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
}
else{
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
//$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => [Yii::$app->session->get('referrer')]];
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="forumResult"><h4><strong>Forum Search Results</strong></h4></div>
<div class="threadDetails">
    <div id="search">
        <span>Search: <input type=text id="searchText" name="search" /></span>
        &nbsp;&nbsp;<span><input type=checkbox id="searchAll" name="allforums" /> All forums in course</span>
        &nbsp;&nbsp;<span><?= Html::submitButton('Search', ['id' => 'change-button','class' => 'btn btn-primary btn-sm', 'name' => 'search-button',]) ?></span>
    </div>
    <div id="thread">
        <span>
            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-new-thread?forumid=' .$forumid.'&cid='.$course->id); ?>"
               class="btn btn-primary btn-sm">Add New Thread</a></span> |
        <span></span><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/list-post-by-name?forumid=' .$forumid.'&cid='.$course->id); ?>">List Posts by Name</a> </span>
        <span><a id="limit-to-new-link" href="#"> <span style="color: #000;">|</span> Limit to New</a> | <a id="limit-to-tag-link" href="#"> Limit to Flagged </a> <a id="show-all-link" href="#"> Show All </a></span>
        <span><?= Html::submitButton('Mark all Read', ['id' => 'markRead','class' => 'btn btn-primary btn-sm ', 'name' => 'markallread-button']) ?></span>
    </div>
    <input type="hidden" id="forumid" value="<?php echo $forumid ?>">
    <input type="hidden" id="courseid" value="<?php echo $course->id ?>">
    <input type="hidden" id="user-id" value="<?php echo $users['id']?>">

    <div id="data">
        <table id="forum-table displayforum" class="forum-table table table-bordered table-striped table-hover data-table">
            <thead>
            <th>Topic</th>
            <th>Flag</th>
            <th>Actions</th>
            <th>Replies</th>
            <th>Views(Unique)</th>
            <th>Last Post Date</th>
            </thead>
            <tbody class="forum-table-body">
            </tbody>
        </table>
    </div>
</div>
<div id="searchpost"></div>

<div id="result">
    <h5><Strong>No result found for your search.</Strong></h5>
</div>
<div id="noThread">
    <h5><Strong>No posts have been made yet. Click Add New Thread to start a new discussion</Strong></h5>
</div>
