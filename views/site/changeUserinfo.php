
<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */

$this->title = 'Profile Settings';
$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" href="../../web/css/dashboard.css" type="text/css"/>

<div class="site-login">

    <?php
    $model->FirstName = AppUtility::getStringVal($user['FirstName']);
    $model->LastName = AppUtility::getStringVal($user['LastName']);
    $model->email = AppUtility::getStringVal($user['email']);
    ?>

    <fieldset>
        <legend>Profile Settings</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 control-label'],
            ],
        ]); ?>

        <?= $form->field($model, 'FirstName')->textInput(); ?>

        <?= $form->field($model, 'LastName')->textInput() ?>

        <div class="row password_checkbox">
            <?= $form->field($model, 'changePassword')->checkbox(['id' => 'pwd']) ?>
        </div>

        <div class="row change-password-content">

            <?= $form->field($model, 'oldPassword')->passwordInput() ?>

            <?= $form->field($model, 'password')->passwordInput() ?>

            <?= $form->field($model, 'rePassword')->passwordInput() ?>
        </div>
        <?= $form->field($model, 'email') ?>

        <div class="notify_checkbox">
            <?= $form->field($model, 'NotifyMeByEmailWhenIReceiveANewMessage')->checkbox() ?>
        </div>
        <?= $form->field($model, 'file')->fileInput() ?>

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
                    echo '<option value="' . $tz . '" ' . ($tz == $tzname ? 'selected' : '') . '>' . $tz . '</option>';
                } ?>
            </select>
    </fieldset>
    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-8 display_field">
            <?= Html::submitButton('Update Info', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>
