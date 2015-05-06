<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use \app\components\AppUtility;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\LoginForm */

$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please fill out the following fields to login:</p>

<!--    --><?//= $this->render('_flashMessage') ?>

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-10 col-lg-offset-1\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
    ]); ?>

    <?= $form->field($model, 'username') ?>
    <?= $form->field($model, 'password')->passwordInput() ?>

    <input type="hidden" id="tzoffset" name="tzoffset" value="">
    <input type="hidden" id="tzname" name="tzname" value="">
    <input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>"/>

    <div id="settings"></div>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Login', ['class' => 'btn btn-primary btn-min-width', 'name' => 'login-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

    <p><a href="<?php echo AppUtility::getURLFromHome('site', 'student-register');?>">Register as a new student</a></p>

    <p><a href="<?php echo AppUtility::getURLFromHome('site', 'forgot-password');?>">Forgot Password</a></p>

    <p><a href="<?php echo AppUtility::getURLFromHome('site', 'forgot-username');?>">Forgot Username</a></p>

    <p><a href="<?php echo AppUtility::getURLFromHome('site', 'check-browser');?>">Browser check</a></p>

</div>