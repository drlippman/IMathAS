<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */

$this->title = 'Profile Settings';

$this->params['breadcrumbs'][] = $this->title;

?>
<div class="site-login">

    <?php if (Yii::$app->session->hasFlash('error')): ?>

        <div class="alert alert-danger">
            <?php echo Yii::$app->session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <fieldset>
        <legend>Profile Settings:</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-1 control-label'],
            ],
        ]); ?>

        <?= $form->field($model, 'FirstName')->textInput(); ?>

        <?= $form->field($model, 'LastName')->textInput() ?>

        <?=
        $form->field($model, 'password',
            ['template' => "<div class=\"col-lg-offset-1 col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
            ])->checkbox() ?>
        <?= $form->field($model, 'password')->passwordInput() ?>

        <?= $form->field($model, 'rePassword')->passwordInput() ?>

        <?= $form->field($model, 'email') ?>

        <?=
        $form->field($model, 'NotifyMeByEmailWhenIReceiveANewMessage',
            ['template' => "<div class=\"col-lg-offset-1 col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
            ])->checkbox() ?>
        <?= $form->field($model, 'uploadPicture')->fileInput() ?>

        <?= $form->field($model, 'message')->dropDownList(array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100)) ?>

        <?= $form->field($model, 'homepage')->checkboxList(['NewMessagesWidget' => 'New Messages Widget', 'NewForumPostsWidget' => 'New Forum Posts Widget', 'NewMessagesNotesOnCourseList' => 'New Messages Notes On Course List', 'NewPostsNotesOnCourseList' => 'New Posts Notes On Course List']) ?>

    </fieldset>

    <fieldset>
        <legend>Timezone</legend>

        <p>Due Dates and other times are being shown to you correct for the <b><?php echo $tzname; ?></b> timezone.</p>

        <p>You may change the timezone the dates display based on if you would like. This change will only last until
            you close your browser or log out.</p>

        <p>Set timezone to: <select name="settimezone" id="settimezone">
                <?php
                $timezones = array('Etc/GMT+12', 'Pacific/Pago_Pago', 'America/Adak', 'Pacific/Honolulu', 'Pacific/Marquesas', 'Pacific/Gambier', 'America/Anchorage', 'America/Los_Angeles', 'Pacific/Pitcairn', 'America/Phoenix', 'America/Denver', 'America/Guatemala', 'America/Chicago', 'Pacific/Easter', 'America/Bogota', 'America/New_York', 'America/Caracas', 'America/Halifax', 'America/Santo_Domingo', 'America/Santiago', 'America/St_Johns', 'America/Godthab', 'America/Argentina/Buenos_Aires', 'America/Montevideo', 'Etc/GMT+2', 'Etc/GMT+2', 'Atlantic/Azores', 'Atlantic/Cape_Verde', 'Etc/UTC', 'Europe/London', 'Europe/Berlin', 'Africa/Lagos', 'Africa/Windhoek', 'Asia/Beirut', 'Africa/Johannesburg', 'Asia/Baghdad', 'Europe/Moscow', 'Asia/Tehran', 'Asia/Dubai', 'Asia/Baku', 'Asia/Kabul', 'Asia/Yekaterinburg', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Kathmandu', 'Asia/Dhaka', 'Asia/Omsk', 'Asia/Rangoon', 'Asia/Krasnoyarsk', 'Asia/Jakarta', 'Asia/Shanghai', 'Asia/Irkutsk', 'Australia/Eucla', 'Australia/Eucla', 'Asia/Yakutsk', 'Asia/Tokyo', 'Australia/Darwin', 'Australia/Adelaide', 'Australia/Brisbane', 'Asia/Vladivostok', 'Australia/Sydney', 'Australia/Lord_Howe', 'Asia/Kamchatka', 'Pacific/Noumea', 'Pacific/Norfolk', 'Pacific/Auckland', 'Pacific/Tarawa', 'Pacific/Chatham', 'Pacific/Tongatapu', 'Pacific/Apia', 'Pacific/Kiritimati');
                foreach ($timezones as $tz) {
                echo '<option value="'.$tz.'" '.($tz==$tzname?'selected':'').'>'.$tz.'</option>';
                } ?>
                </select>
    </fieldset>
    <?php ActiveForm::end(); ?>

    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Update Info', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>
    </div>


</div>
