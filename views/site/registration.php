<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\RegistrationForm;
use app\components\AppUtility;

/* @var $this yii\web\View */
/* @var $model app\models\RegistrationForm */
/* @var $form ActiveForm */
$this->title = 'Registration';
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
    <div style="margin: 30px;"><br>
            <div class="vcenter"><h3 style="border-bottom: 2px solid #a9a9a9; margin-top: 4px; margin-bottom: 30px; padding-bottom: 25px;">Instructor Account Request</h3></div>
            <div class="registration col-md-9">
                <?php $form = ActiveForm::begin([
                    'id' => 'login-form',
                    'options' => ['class' => 'form-horizontal'],
                    'action' => '',
                    'fieldConfig' => [
                        'template' => "{label}\n<div class=\"col-sm-4\">{input}</div>\n<div class=\"col-sm-6 clear-both col-sm-offset-3\">{error}</div>",
                        'labelOptions' => ['class' => 'col-sm-3  text-align-left margin-top-eight'],
                    ],
                ]); ?>

                <?php echo $form->field($model, 'FirstName') ?>
                <?php echo $form->field($model, 'LastName') ?>
                <?php echo $form->field($model, 'email') ?>
                <?php echo $form->field($model, 'phoneno') ?>
                <?php echo $form->field($model, 'school') ?>
                <?php echo $form->field($model, 'username') ?>
                <?php echo $form->field($model, 'password')->passwordInput() ?>
                <?php echo $form->field($model, 'confirmPassword')->passwordInput() ?>
                <?php echo $form->field($model, 'terms')->checkbox(['labelOptions' => ['class' => 'register-terms-label']]) ?>
                <div class="form-group">
                    <div class="col-sm-offset-3"><?php echo Html::submitButton('Request Account', ['class' => 'btn btn-primary instructor-save']) ?></div>
                </div>

                <?php ActiveForm::end(); ?>
            </div><!-- registration -->

            <div class="col-md-12">
                <h4>Terms of Use</h4>

                <p><em>This software is made available with <strong>no warranty</strong> and <strong>no guarantees</strong>. The
                        server or software might crash or mysteriously lose all your data. Your account or this service may be
                        terminated without warning. No official support is provided. </em></p>

                <p><em>Copyrighted materials should not be posted or used in questions without the permission of the copyright
                        owner. You shall be solely responsible for your own user created content and the consequences of posting or
                        publishing them. This site expressly disclaims any and all liability in connection with user created
                        content.</em></p>
                <br>
                <div class="clear"></div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){

        $('.register-terms-label').closest('div').addClass('col-md-offset-3');
        $('.register-terms-label').closest('div').removeClass('col-sm-4');
        $('.register-terms-label').closest('div').addClass('col-sm-6');
    });

</script>