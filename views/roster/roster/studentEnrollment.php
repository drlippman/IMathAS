<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
$this->title = 'Student Enrollment';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'Roster'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id, AppUtility::getHomeURL().'/roster/roster/student-roster?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'roster']);?>
</div>

<div class="tab-content shadowBox"">
    <?php echo $this->render("_toolbarRoster", ['course' => $course]);?>
    <div class="inner-content">
        <div class="title-middle center"><?php AppUtility::t('Enroll an Existing user');?></div>
        <?php $form =ActiveForm::begin(
            [
                'options' => ['class' => 'form-horizontal'],
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                    'labelOptions' => ['class' => 'col-lg-2 select-text-margin'],
                ],
            ]
        ) ?>
        <?= $form->field($model, 'usernameToEnroll')->textInput(array('placeholder' => 'Username')); ?>
        <?= $form->field($model, 'section')->textInput(array('placeholder' => 'Section')); ?>
        <?= $form->field($model, 'code')->textInput(array('placeholder' => 'Code')); ?>
        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-3">
                <?= Html::submitButton('Enroll', ['class' => 'btn btn-primary','id'=>'enroll-btn', 'name' => 'enroll-button']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>

