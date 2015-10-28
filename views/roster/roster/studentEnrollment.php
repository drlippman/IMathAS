<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
$this->title = AppUtility::t('Enroll an Existing user', false);
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>[AppUtility::t('Home', false),$course->name,AppUtility::t('Roster',false)], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course', 'course/course?cid='.$course->id, AppUtility::getHomeURL().'roster/roster/student-roster?cid='.$course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]);?>
</div>

<div class="tab-content shadowBox"">
    <?php echo $this->render("_toolbarRoster", ['course' => $course]);?>
    <div class="inner-content">
        <?php $form =ActiveForm::begin(
            [
                'options' => ['class' => 'form-horizontal'],
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"col-md-3\">{input}</div>\n<div class=\"col-md-7 col-md-offset-2\">{error}</div>",
                    'labelOptions' => ['class' => 'col-md-2 select-text-margin'],
                ],
            ]
        ) ?>
        <?php echo $form->field($model, 'usernameToEnroll')->textInput(array('placeholder' => AppUtility::t('username', false))); ?>
        <?php echo $form->field($model, 'section')->textInput(array('placeholder' => AppUtility::t('Section', false))); ?>
        <?php echo $form->field($model, 'code')->textInput(array('placeholder' => AppUtility::t('Code', false))); ?>
        <div class="form-group">
            <div class="col-md-offset-2 col-md-3">
                <?php echo Html::submitButton(AppUtility::t('Enroll', false), ['class' => 'btn btn-primary','id'=>'enroll-btn', 'name' => 'enroll-button']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

