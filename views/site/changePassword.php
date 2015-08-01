<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Change Password';
$this->params['breadcrumbs'][] = $this->title;
?>
<!--<div class="item-detail-header">-->
<!--    --><?php //echo $this->render("../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
<!--</div>-->
<!--<div class = "title-container">-->
<!--    <div class="row">-->
<!--        <div class="pull-left page-heading">-->
<!--            <div class="vertical-align title-page">--><?php //echo $this->title ?><!--</div>-->
<!--        </div>-->
<!--        <div class="pull-left header-btn">-->
<!--            --><?php //echo Html::submitButton('<i class="fa fa-share header-right-btn"></i>Save', ['class' => 'btn btn-primary pull-right page-settings', 'name' => 'Submit']) ?>
<!--        </div>-->
<!--    </div>-->
<!--</div>-->
<div class="site-login">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-2'],
        ],
    ]); ?>

    <?= $form->field($model, 'oldPassword')->passwordInput() ?>
    <?= $form->field($model, 'newPassword')->passwordInput() ?>
    <?= $form->field($model, 'confirmPassword')->passwordInput() ?>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-11">
            <?= Html::submitButton('Submit', ['id'=> 'change-button' ,'class' => 'btn btn-primary', 'name' => 'changepassword-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
