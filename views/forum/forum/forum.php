<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;

$this->title = ucfirst($course->name);
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithButton",['item_name'=>'Message', 'link_title'=>'Home', 'link_url' => AppUtility::getHomeURL().'site/index', 'page_title' => $this->title]); ?>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<input type="hidden" id="courseId" class="courseId" value="<?php echo $cid ?>">
<div class="tab-content shadowBox ">

    <div class="site-login ">
        <div class="forum-background">
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-5\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-1 control-label'],
            ],
        ]); ?>
          <div class="pull right ">
              <span class="">
                 <input type="text" id="search_text" placeholder="Enter Search Terms">
               </span>
              <span class="search-dropdown">
                    <select name="seluid" class="form-control-forum select_option " id="">
                        <option value="-1">Select</option>
                            <option value="0">All Thread Subject</option>
                            <option value="1">All Post</option>
                    </select>
              </span>
              <input type="button" id="forum_search" class="Search-btn " value="Search"/>
         </div>
 </div>
        <div class="main-div">
        <div id="display">
            <table id="forum-table display-forum" class="forum-table table table-bordered table-striped table-hover data-table">
                <thead>
                <tr>
                    <th>&nbsp;&nbsp;Forum Name</th>
                    <th>&nbsp;&nbsp;Modify</th>
                    <th>&nbsp;&nbsp;Threads</th>
                    <th>&nbsp;&nbsp;Posts</th>
                    <th>&nbsp;&nbsp;Last Post Date</th>
                </tr>
                </thead>
                <tbody class="forum-table-body">
                </tbody>
            </table>
        </div>
        <div id="search-thread">
            <table id="forum-search-table display-forum" class="forum-search-table table table-bordered table-striped table-hover data-table" bPaginate="false">
                <thead>

                <th>Topic</th>
                <th>Replies</th>
                <th>Views</th>
                <th>Last Post Date</th>


                </thead>
                <tbody class="forum-search-table-body">
                </tbody>
            </table>

        </div>
        <div id="search-post"></div>
        <div id="result">
            <h5><Strong>No result found for your search.</Strong></h5>
        </div>



        <?php ActiveForm::end(); ?>
</div>

</div>