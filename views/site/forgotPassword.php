<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Forgot Password';
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
    <div class="site-login">
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"margin-left-ten col-md-3 col-sm-4\">{input}</div>\n<div class=\"col-md-10 col-md-offset-1 col-sm-10 col-sm-offset-2 padding-left-fifteen\">{error}</div>",
                'labelOptions' => ['class' => 'col-md-1 col-sm-2 control-label'],
            ],
        ]); ?>
        <div style="padding: 30px">
            <div style="background-color:#fafafa; padding: 30px ">
                <p>Enter your User Name below and click Submit. An email will be sent to your email address on file. A link in that
                    email will reset your password.</p>
                <div class="margin-top-seventeen">
                    <?= $form->field($model, 'username') ?>
                </div>
                <div class="form-group">
                    <div class="padding-left-twenty-five col-md-offset-1 col-md-11 col-sm-offset-2 col-sm-10">
                        <?= Html::submitButton('Submit', ['id' => 'change-button','class' => 'btn btn-primary', 'name' => 'forgetpassword-button']) ?>
                         &nbsp; &nbsp;         <a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('site', 'login'); ?>">Back</a>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
<div style="height: 50px; width: 100%"></div>