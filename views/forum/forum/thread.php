<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;

$this->title = AppUtility::t($forumData['name'],false );
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<?php if($page){?>
    <input type="hidden" id="page" value="<?php echo $page;?>">
<?php }?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Forums:',false);?><?php echo $this->title ?></div>
        </div>
        <div class="pull-left header-btn">
            <a href="<?php echo AppUtility::getURLFromHome('forum', 'forum/add-new-thread?forumid=' .$forumid.'&cid='.$course->id); ?>"
               class="btn btn-primary pull-right add-new-thread"><i class="fa fa-plus"></i>&nbsp;Add New Thread</a>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<input type="hidden" id="courseId" class="courseId" value="<?php echo $cid ?>">
<div class="tab-content shadowBox ">

    <div class="inner-content col-lg-12">

        <div class="view-drop-down  pull-left">
        <span class=""><?php echo AppUtility::t('View Options',false)?></span>
            <select name="seluid" class="form-control-forum select_option " id="">
                <option value="-1"><?php echo AppUtility::t('Select')?></option>
                <option value="0"><?php echo AppUtility::t('List Post by Name')?></option>
                <option value="1"><?php echo AppUtility::t('Limit to Flagged ')?></option>
                <option value="2"><?php echo AppUtility::t('Limit to New ')?></option>
                <option value="3"><?php echo AppUtility::t('Show All')?></option>
            </select>
        </div>

        <div class="mark-as-read-link pull-left col-lg-4 pull-left">
            <a href="#" id="markRead"><?php echo AppUtility::t('Mark as Read')?></a>
        </div>
        <div class="pull-right view-drop-down">
            <button class="btn btn-primary search-button" id="change-button"><i class="fa fa-search"></i>&nbsp;<b><?php echo AppUtility::t('Search')?></b></button>
        </div>
        <div class="checkbox checkbox-thread pull-right">
            <label>
                <input type="checkbox" name="allforums" id="searchAll" value=""><?php echo AppUtility::t('All Forum in Courses?')?>
                <span class="cr"><i class="cr-icon fa fa-check"></i></span>
            </label>
        </div>
        <div class="view-drop-down pull-right">
                <span class="">
                 <input type="text" id="search_text" maxlength="30" placeholder="<?php echo AppUtility::t('Enter Search Terms')?>">
               </span>
        </div>
</div>
    <input type="hidden" id="forumid" value="<?php echo $forumid ?>">
    <input type="hidden" id="courseid" value="<?php echo $course->id ?>">
    <input type="hidden" id="user-id" value="<?php echo $users['id']?>">
    <input type="hidden" id="settings" value="<?php echo $forumData['settings']?>">
    <div id="data">
        <table id="forum-table displayforum" class="forum-table table table-bordered table-striped table-hover data-table" bPaginate="false">
            <thead>
            <th><?php echo AppUtility::t('Topic')?></th>
            <?php if($forumData['groupsetid'] > 0 && $users['rights'] > 10){ ?>
                <th><?php echo AppUtility::t('Groups')?></th>
            <?php } ?>
            <th><?php echo AppUtility::t('Replies')?></th>
            <th><?php echo AppUtility::t('Views(Unique)')?></th>
            <th><?php echo AppUtility::t('Last Post Date')?></th>
            <th><?php echo AppUtility::t('Action')?></th>
            </thead>
            <tbody class="forum-table-body">
            </tbody>
        </table>
    </div>
    <div id="searchpost"></div>
</div>
