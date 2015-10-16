<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;

$this->title = 'Add new user';
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin/admin/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item add-link-padding">
<div class="site-login">

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal col-md-12'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-md-3\">{input}</div>\n<div class=\"col-md-7 col-md-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-md-2 select-text-margin'],
            ],
        ]); ?><br/>

        <?php echo $form->field($model, 'username')->textInput() ?>

        <?php echo $form->field($model, 'FirstName')->textInput() ?>

        <?php echo $form->field($model, 'LastName')->textInput() ?>

        <?php echo $form->field($model, 'email')->textInput() ?>

        <?php echo $form->field($model, 'password')->passwordInput() ?>
        <div class="col-md-0 pull-left select-text-margin padding-left-zero">
            <a href="#"  onclick="window.open('help-of-rights','helpOfRights','top=0,width=400,height=500,scrollbars=1,left=150')"><img
                    src="<?php echo AppUtility::getAssetURL()?>img/helpIcon.png" alt="Help" class="help-img padding-left-zero"></a></div>
        <div class="rights_alignment">

            <?php echo
            $form->field($model, 'rights')->inline()->radioList([AppConstant::GUEST_RIGHT => 'Guest User',
                AppConstant::STUDENT_RIGHT => 'Student',
                AppConstant::TEACHER_RIGHT => 'Teacher',
                AppConstant::LIMITED_COURSE_CREATOR_RIGHT => 'Limited Course Creator',
                AppConstant::DIAGNOSTIC_CREATOR_RIGHT => 'Diagnostic Creator ',
                AppConstant::GROUP_ADMIN_RIGHT => 'Group Admin ',
                AppConstant::ADMIN_RIGHT => 'Full Admin']) ?>
        </div>
        <div class="clear-both"></div>
        <?php echo $form->field($model, 'AssignToGroup')->dropDownList(array(''), ['prompt' => 'Default']) ?>
        <div class="form-group">
            <div class="col-md-offset-2 col-md-5">
                <?php echo Html::submitButton('Save', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
            </div>
        </div><br/>
    <?php ActiveForm::end(); ?>
</div>
</div>