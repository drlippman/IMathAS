<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'upload Multiple Grades';
 ?>
<div>

    <div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id], 'page_title' => $this->title]); ?>
    </div>

    <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-sm-9 col-md-10 padding-left-zero\">{input}</div>\n<div>{error}</div>",
                'labelOptions' => ['class' => 'col-sm-3 col-md-2  text-align-left'],
            ],
        ]); ?>
    <div class="title-container">
        <div class="row margin-bottom-ten">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?> </div>
            </div>

        </div>
    </div>

    <div class="tab-content shadowBox non-nav-tab-item padding-thirty">
       <div class="col-sm-12 col-md-12 text-gray-background  ">
            <p>The uploaded file must be in Comma Separated Values (.CSV) file format, and contain a column with
                the students' usernames.  If you are including feedback as well as grades, upload will be much easier if the
                feedback is in the column immediately following the scores, and if the column header contains the word Comment or Feedback</p>
<div class="padding-top-pt-five-em ">
            <?php echo $form->field($model, 'file')->fileInput();?>
            <?php echo $form->field($model, 'fileHeaderRow')->radioList([AppConstant::NUMERIC_ZERO => 'Yes, one',AppConstant::NUMERIC_ONE => 'Yes, with second for points possible']);?>
           <div class="header-btn margin-top-one-pt-five-em col-sm-6 col-sm-offset-2 col-md-6 col-md-offset-1 padding-left-seven-per">
                   <?php echo Html::submitButton('Submit', ['class' => 'btn btn-primary upload-multiple-grade-submit-btn']) ?>
                   <?php if ($commentType == "instr"){ ?>
                       <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id.'&comtype=instr')  ?>"><i class="fa fa-share header-right-btn"></i>Back</a>
                   <?php } else {?>
                       <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id)  ?>"><i class="fa fa-share header-right-btn"></i>Back</a>
                   <?php }?>
           </div>
           </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>
</div>
