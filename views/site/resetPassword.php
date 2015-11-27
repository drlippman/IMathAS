<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Reset Password';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="site-login">
    <div class="tab-content shadowBox padding-two-em ">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-md-3 col-sm-3\">{input}</div>\n<div class=\"col-md-8 col-md-offset-2 col-sm-8 col-sm-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-md-2 col-sm-3'],
        ],
    ]); ?>

    <div class="text-gray-background padding-one-em">
        <p class="padding-bottom-thirty"><?php AppUtility::t('Please select a new password')?></p>

        <?php echo $form->field($model, 'newPassword')->passwordInput() ?>
    <?php echo $form->field($model, 'confirmPassword')->passwordInput() ?>

    <div class="form-group">
        <div class="col-md-offset-2 col-md-8 col-sm-offset-3 col-sm-8 display_field">
            <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Submit' ?></button>
        </div>
    </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
</div>
