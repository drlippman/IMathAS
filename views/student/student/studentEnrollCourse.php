<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Enroll in a course';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home'], 'link_url' => [AppUtility::getHomeURL() . 'site/index'], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
<div class="site-login">
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-md-3 col-sm-5\">{input}</div>\n<div class=\"col-md-7 col-sm-9 col-md-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-md-2 col-sm-4 padding-left-fifteen'],
        ],
    ]); ?>
    <br>
   <div class="padding-left-fifteen"><?php echo $form->field($model, 'selectCourse')->dropDownList(['1' => 'Self study courses'],['prompt'=>'My teacher gave me a course ID (enter below)'], ['class' => 'form-alignment-dropDown-list']) ?></div>
   <div class="padding-left-fifteen"><?php echo $form->field($model, 'courseId') ?></div>
   <div class="padding-left-fifteen"><?php echo $form->field($model, 'enrollmentKey') ?></div>


        <div class="form-group col-md-6" style="text-align: center">
          <div class="col-md-3 col-sm-3">  <?php echo Html::submitButton('Sign Up', ['class' => 'btn btn-primary','id'=>'enroll-btn', 'name' => 'login-button']) ?></div>
          <div class="col-md-3 col-sm-3"> <a class="btn btn-primary back-button" style="margin-top: 0" href="<?php echo AppUtility::getURLFromHome('site', 'dashboard')  ?>">Back</a></div>
        </div>

    <?php ActiveForm::end(); ?>
</div>
</div>