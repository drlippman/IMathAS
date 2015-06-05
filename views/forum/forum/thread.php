<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
$this->title = 'Thread';
$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/dashboard.css"
      xmlns="http://www.w3.org/1999/html"/>
<link rel="stylesheet" type="text/css" href="<?php echo AppUtility::getHomeURL() ?>css/forums.css"/>
<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css"
      href="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/css/jquery.dataTables.css">
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js?ver=012115"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script type="text/javascript" charset="utf8"
        src="<?php echo AppUtility::getHomeURL() ?>js/DataTables-1.10.6/media/js/jquery.dataTables.js"></script>
<input type="hidden" id="course-id" value="<?php echo $cid ?>">
<input type="hidden" id="forumid" value="<?php echo $forumid ?>">

<div id="search">
    <span>Search: <input type=text name="search" /></span>
    &nbsp;&nbsp;<span><input type=checkbox name="allforums" /> All forums in course</span>
    &nbsp;&nbsp;<span><?= Html::submitButton('Search', ['id' => 'change-button','class' => 'btn btn-primary btn-sm', 'name' => 'search-button',]) ?></span>
</div>
<div id="thread">
    <span>
        <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-new-thread?id=' .$forumid ); ?>"
           class="btn btn-primary btn-sm">Add New Thread</a></span> |
    <span></span><a href="#">List Posts by Name</a> </span>|
    <span><a href="#">Limit to New</a> | <a href="#">Limit to Flagged</a></span>|
    <span><?= Html::submitButton('Mark all Read', ['class' => 'btn btn-primary btn-sm', 'name' => 'markallread-button']) ?></span>
</div>
<input type="hidden" id="forumid" value="<?php echo $forumid ?>">

<div>
    <table id="forum-table displayforum" class="forum-table">
        <thead>

        <th>Topic</th>
        <th>Replies</th>
        <th>Views</th>
        <th>Last Post Date</th>
        </thead>
        <tbody class="forum-table-body">
        </tbody>
    </table>
</div>

