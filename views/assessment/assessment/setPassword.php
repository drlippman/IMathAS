<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Password';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home'], 'link_url' => [AppUtility::getHomeURL().'site/index']]); ?>
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
        <div style="padding-top: 10px">
      <h3><b><?php echo $assessments->name;?></b></h3></div>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-sm-4\">{input}</div>\n<div class=\"col-sm-4 clear-both col-sm-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-sm-2 text-align-left'],
            ],
        ]); ?>
        <p> Password required for access.</p>
        <?= $form->field($model, 'password')->passwordInput() ?>
        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-11">
                <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'changepassword-button']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>