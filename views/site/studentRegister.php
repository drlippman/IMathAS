<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\RegisterModel */

$this->title = 'Student Registration';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <br>
    <p style="padding-top:0"></p>
<div style="margin-right:30px; margin-left: 30px;margin-bottom: 30px; background-color: #fafafa">
    <div class="site-login" style="padding-top: 30px">
        <p style="padding-left: 315px; border-bottom: 2px solid #a9a9a9">Please fill out the following fields to <b>SignUp</b></p><br>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-sm-4\">{input}</div>\n<div class=\"col-sm-4 clear-both col-sm-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-sm-3  text-align-left'],
            ],
        ]); ?>
        <?= $form->field($model, 'username')->textInput(); ?>
        <?= $form->field($model, 'password')->passwordInput() ?>
        <?= $form->field($model, 'rePassword')->passwordInput() ?>
        <?= $form->field($model, 'FirstName')->textInput(); ?>
        <?= $form->field($model, 'LastName') ?>
        <?= $form->field($model, 'email') ?>
        <?=
        $form->field($model, 'NotifyMeByEmailWhenIReceiveANewMessage',
            ['template' => "<div class=\"col-lg-offset-3 col-lg-6\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
            ])->checkboxList(['1' => 'Notify Me By Email When I Receive A New Message']) ?>

        <p style="padding-left: 315px"><?php echo "If you already know your course ID, you can enter it now. Otherwise, leave this blank and you can enroll later." ?></p>
        <br><br>
        <?= $form->field($model, 'courseID') ?>
        <?= $form->field($model, 'EnrollmentKey') ?>

        <div class="form-group">
             <div class="col-lg-offset-3 col-lg-11">
                <?= Html::submitButton('Sign Up', ['id' => 'sign-up-button','class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                &nbsp; &nbsp;<a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('site', 'login'); ?>">Back</a>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
        <br>
        <br>
    </div>
    </div>
</div>