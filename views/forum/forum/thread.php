<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Thread';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => [Yii::$app->session->get('referrer')]];
$this->params['breadcrumbs'][] = ['label' => 'Forum', 'url' => ['/forum/forum/search-forum?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;

?>
<link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/dashboard.css"
      xmlns="http://www.w3.org/1999/html"/>
<!--<link rel="stylesheet" type="text/css" href="--><?php //echo AppUtility::getHomeURL() ?><!--css/forums.css"/>-->
<!-- DataTables CSS -->
<!--<link rel="stylesheet" type="text/css"-->
<!--      href="--><?php //echo AppUtility::getHomeURL() ?><!--js/DataTables-1.10.6/media/css/jquery.dataTables.css">-->
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<!--<script type="text/javascript" charset="utf8"-->
<!--        src="--><?php //echo AppUtility::getHomeURL() ?><!--js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>-->
<input type="hidden" id="course-id" value="<?php echo $cid ?>">
<div class="forumResult"><h2>Forum Search Results</h2></div>
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
        <span></span><a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/list-post-by-name?forumid=' .$forumid.'&cid='.$course->id); ?>">List Posts by Name</a> </span>|
        <span><a id="limit-to-new-link" href="#">Limit to New</a> | <a id="limit-to-tag-link" href="#">Limit to Flagged</a> <a id="show-all-link" href="#">Show All</a> </span>|
        <span><?= Html::submitButton('Mark all Read', ['class' => 'btn btn-primary btn-sm', 'name' => 'markallread-button']) ?></span>
    </div>
    <input type="hidden" id="forumid" value="<?php echo $forumid ?>">
    <input type="hidden" id="courseid" value="<?php echo $course->id ?>">

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
