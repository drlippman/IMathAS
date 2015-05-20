<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */

$this->title = 'Add new user';
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin/admin/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-login">

    <fieldset>
        <legend>New User</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 '],
            ],
        ]); ?>

        <?= $form->field($model, 'username')->textInput() ?>

        <?= $form->field($model, 'FirstName')->textInput() ?>

        <?= $form->field($model, 'LastName')->textInput() ?>

        <?= $form->field($model, 'email')->textInput() ?>

        <?= $form->field($model, 'password')->passwordInput() ?>

    <div class="col-lg-0 pull-left">   <a href="/var/www/openmath/views/site/help.php" target="_blank"> <img src="../../../web/img/help.gif"></a></div>
        <?=
        $form->field($model, 'rights')->inline()->radioList([AppConstant::GUEST_RIGHT => 'Guest User',
            AppConstant::STUDENT_RIGHT => 'Student',
            AppConstant::TEACHER_RIGHT => 'Teacher',
            AppConstant::LIMITED_COURSE_CREATOR_RIGHT => 'Limited Course Creator',
            AppConstant::DIAGNOSTIC_CREATOR_RIGHT => 'Diagnostic Creator ',
            AppConstant::GROUP_ADMIN_RIGHT => 'Group Admin ',
            AppConstant::ADMIN_RIGHT => 'Full Admin',]) ?>

        <?= $form->field($model, 'AssignToGroup')->dropDownList(array(''), ['prompt' => 'Default']) ?>

        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-5">
                <?= Html::submitButton('Save', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
            </div>
        </div>
    </fieldset>
    <script>
        function myFunction() {
            window.open("<?php AppUtility::getURLFromHome('site', 'help','top=0,width=400,height=500,scrollbars=1'); ?>");
        }
    </script>
    <?php ActiveForm::end(); ?>
<!--    <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=rights','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/>-->
</div>
