<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use app\components\CourseItemsUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;

$this->title = AppUtility::t('Forums',false );
$this->params['breadcrumbs'][] = $this->title;
$currentTime = AppUtility::parsedatetime(date('m/d/Y'), date('h:i a'));
$now = $currentTime;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithButton",['item_name'=>'Message', 'link_title'=>AppUtility::t('Home',false), 'link_url' => AppUtility::getHomeURL().'site/index', 'page_title' => $this->title]); ?>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'Forums']);?>
</div>
<input type="hidden" id="courseId" class="courseId" value="<?php echo $cid ?>">
<div class="tab-content shadowBox ">


        <div class="forum-background">
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-5\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-1 control-label'],
            ],
        ]); ?>
          <div class="pull right second-level-div">
              <span class="">
                 <input type="text" id="search_text" maxlength="30" placeholder="<?php echo AppUtility::t('Enter Search Terms')?>">
               </span>
              <span class="search-dropdown">
                    <select name="seluid" class="form-control-forum select_option " id="">
                        <option value="-1"><?php echo AppUtility::t('Select')?></option>
                            <option value="0"><?php echo AppUtility::t('All Thread Subject')?></option>
                            <option value="1"><?php echo AppUtility::t('All Post')?></option>
                    </select>
              </span>
                 <span class="message-reply">
                         <a
                           id="forum_search" class="btn1  "><i class="fa fa-search"></i>&nbsp;&nbsp;<?php echo AppUtility::t('Search')?></a>
                    </span>
         </div>
 </div>
        <div class="main-div">
        <div id="display">
            <table id="forum-table display-forum" class="forum-table table table-bordered table-striped table-hover data-table">
                <thead>
                <tr>
                    <th><?php echo AppUtility::t('Forum Name')?></th>
                    <th><?php echo AppUtility::t('Modify')?></th>
                    <th><?php echo AppUtility::t('Threads')?></th>
                    <th><?php echo AppUtility::t('Posts')?></th>
                    <th><?php echo AppUtility::t('Last Post Date')?></th>
                </tr>
                </thead>
                <tbody class="forum-table-body">
                </tbody>
            </table>
        </div>
        <div id="search-thread">
            <table id="forum-search-table display-forum" class="forum-search-table table table-bordered table-striped table-hover data-table" bPaginate="false">
                <thead>

                <th><?php echo AppUtility::t('Topic')?></th>
                <th><?php echo AppUtility::t('Replies')?></th>
                <th><?php echo AppUtility::t('Views')?></th>
                <th><?php echo AppUtility::t('Last Post Date')?></th>


                </thead>
                <tbody class="forum-search-table-body">
                </tbody>
            </table>

        </div>
        <div id="search-post"></div>
        <div id="result">
            <h5><Strong><?php echo AppUtility::t('No result found for your search.')?></Strong></h5>
        </div>
        <?php ActiveForm::end(); ?>


</div>