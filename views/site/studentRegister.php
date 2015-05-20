<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\RegisterModel */

$this->title = 'New User Registration';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <p>Please fill out the following fields to SignUp</p>

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'action' => '',
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8 col-lg-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2'],
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
        ['template' => "<div class=\"col-lg-offset-2 col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
        ])->checkboxList(['1' => 'Notify Me By Email When I Receive A New Message']) ?>

    <?php echo "If you already know your course ID, you can enter it now. Otherwise, leave this blank and you can enroll later." ?>
<br><br>
    <?= $form->field($model, 'courseID') ?>

    <?= $form->field($model, 'EnrollmentKey') ?>

    <div class="form-group">

         <div class="col-lg-offset-2 col-lg-11">
            <?= Html::submitButton('SignUp', ['id' => 'sign-up-button','class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            &nbsp; &nbsp;         <a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('site', 'login'); ?>">Back</a>

        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
