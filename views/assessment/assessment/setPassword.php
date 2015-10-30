<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;

$this->title = 'Password';
$this->params['breadcrumbs'][] = $this->title;
?>

    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-sm-4\">{input}</div>\n<div class=\"col-sm-4 clear-both col-sm-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-sm-2 text-align-left'],
        ],
    ]); ?>
    <div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
    </div>
    <div class = "title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
            <div class="pull-left header-btn">
                <?php echo Html::submitButton('<i class="fa fa-share header-right-btn"></i>Save', ['class' => 'btn btn-primary pull-right page-settings', 'name' => 'Submit']) ?>
            </div>
        </div>
    </div>

<div class="tab-content shadowBox non-nav-tab-item">
    <div class="site-login">
        <div class="padding-top padding-left">
            <h3><b><?php echo $assessments->name;?></b></h3>
        </div>

        <p class="padding-left"> <?php AppUtility::t('Password required for access.')?></p>
        <div class="padding-left">
        <?php echo $form->field($model, 'password')->passwordInput() ?></div>
        <?php ActiveForm::end(); ?>

    </div>
    <br>
    <br>
</div>