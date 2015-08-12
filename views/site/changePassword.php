<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Change Password';
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
<div class="item-detail-content">

</div>
<div class="tab-content shadowBox">
    <br>
    <p></p>
    <div class="inner-content-change-password">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-sm-4\">{input}</div>\n<div class=\"col-lg-8 col-lg-offset-3\">{error}</div>",
            'labelOptions' => ['class' => 'col-sm-3'],
        ],
    ]); ?>
        <div class="old-password">
            <br>
            <?php echo $form->field($model, 'oldPassword')->passwordInput() ?>
        </div>

        <div class="old-password">
            <br>
            <?php echo $form->field($model, 'newPassword')->passwordInput() ?>
        </div>
        <div class="old-password">
            <br>
            <?php echo $form->field($model, 'confirmPassword')->passwordInput() ?>

        </div>
        <div class="Submit-btn-change-password">
                <?= Html::submitButton('Submit', ['id'=> 'change-button' ,'class' => 'btn btn-primary submit-btn1', 'name' => 'changepassword-button']) ?>
            </div>

</div>
    <?php ActiveForm::end(); ?>
</div>
