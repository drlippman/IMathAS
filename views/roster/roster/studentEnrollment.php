<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
// use yii\helpers\HtmlPurifier;

$this->title = AppUtility::t('Enroll an Existing user', false);
?>

<div class="item-detail-header">
   <!-- html encode -->
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>[AppUtility::t('Home', false), Html::encode($course->name),AppUtility::t('Roster',false)], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id, AppUtility::getHomeURL().'roster/roster/student-roster?cid='.$course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo Html::encode($this->title) ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'roster']);?>
</div>

<div class="tab-content shadowBox"">

    <?php echo $this->render("_toolbarRoster", ['course' => $course]);?>
    <div class="inner-content col-md-12 col-sm-12">
      <!-- rules are defined in forms/StudentEnrollmentForm;  -->
        <?php $form =ActiveForm::begin(
            [
                'options' => ['class' => 'form-horizontal'],
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"col-md-3 col-sm-4\">{input}</div>\n<div class=\"col-md-7 col-md-offset-2 col-sm-7 col-sm-offset-3\">{error}</div>",
                    'labelOptions' => ['class' => 'col-md-2 col-sm-3 select-text-margin padding-right-zero'],
                ],
            ]
        ) ?>
        <div class="text-gray-background padding-one-em">
       <!-- usernameToEnroll and section is encoded in rules -->
        <?php echo $form->field($model, 'usernameToEnroll')->textInput(array('placeholder' => AppUtility::t('Username' , false))); ?>
        <?php echo $form->field($model, 'section')->textInput(array('placeholder' => AppUtility::t('Section', false))); ?>
        <?php echo $form->field($model, 'code')->textInput(array('placeholder' => AppUtility::t('Code', false))); ?>
        <div class="form-group">
            <div class="col-md-offset-2 col-md-1 col-sm-offset-3 col-sm-2 padding-right-zero">
                <?php echo Html::submitButton(AppUtility::t('Enroll', false), ['class' => 'btn btn-primary','id'=>'enroll-btn', 'name' => 'enroll-button']) ?>
            </div>
        </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

