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

<div class="site-login">

    <?php
    $model->FirstName = AppUtility::getStringVal($user['FirstName']);
    $model->LastName = AppUtility::getStringVal($user['LastName']);
    $model->email = AppUtility::getStringVal($user['email']);
    ?>
    <div class="tab-content shadowBox non-nav-tab-item">
        <br/>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-md-3\">{input}</div>\n<div class=\"col-md-10 col-md-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-md-2'],
            ],
        ]); ?>

        <div class="col-md-12"><?php echo $form->field($model, 'FirstName')->textInput(); ?></div>

        <div class="col-md-12"><?php echo $form->field($model, 'LastName')->textInput() ?></div>

        <div class="row password_checkbox">
            <?php echo $form->field($model, 'changePassword')->checkbox(['id' => 'pwd']) ?>
        </div>

        <div class="col-md-12"> <div class="change-password-content">

            <?php echo $form->field($model, 'oldPassword')->passwordInput() ?>

            <?php echo $form->field($model, 'password')->passwordInput() ?>

            <?php echo $form->field($model, 'rePassword')->passwordInput() ?>
        </div></div>
        <div class="col-md-12"><?php echo $form->field($model, 'email') ?></div>

        <div class="col-md-12"><div class="notify_checkbox">
            <?php echo $form->field($model, 'NotifyMeByEmailWhenIReceiveANewMessage')->checkbox() ?>
        </div></div>
        <br>

        <div class="col-md-12"><div class="col-md-10 col-md-offset-2 user-image">

            <?php
            if($user['hasuserimg']==0)
            {?>
                <img src="<?php echo AppUtility::getHomeURL()?>Uploads/dummy_profile.jpg" class = "image" alt="file not found" /><br><br>
                <?php
                echo "Upload profile picture";
            }
            else
            {?>
                <img src="<?php echo AppUtility::getHomeURL()?>Uploads/<?php echo $user['id'] ?>.jpg?ver=<?php echo time()?>" class = "image" alt="file not found" /></br>
                <div class="update-checkbox">
                    <?php echo $form->field($model, 'remove')->checkbox() ?>
                </div>
            <?php }?>
        </div ></div>

        <div class="col-md-12"><?php echo $form->field($model, 'file')->fileInput() ?></div>

        <div class="col-md-12"><?php echo $form->field($model, 'message')->dropDownList(array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100)) ?></div>

        <div class="col-md-12"><?php echo $form->field($model, 'homepage')->checkboxList(['NewMessagesWidget' => 'New Messages Widget', 'NewForumPostsWidget' => 'New Forum Posts Widget', 'NewMessagesNotesOnCourseList' => 'New Messages Notes On Course List', 'NewPostsNotesOnCourseList' => 'New Posts Notes On Course List']) ?></div>


        <div class="col-md-12">Due Dates and other times are being shown to you correct for the <b><?php echo $tzname; ?></b> timezone.</div><br class="form"><BR/>

        <div class="col-md-12">You may change the timezone the dates display based on if you would like. This change will only last until
            you close your browser or log out.</div><br class="form"><br/>

        <div class="col-md-12">Set timezone to: <select name="settimezone" id="settimezone" class="col-md-offset-1 form-control-1">
                <?php
                $timezones = array('Etc/GMT+12', 'Pacific/Pago_Pago', 'America/Adak', 'Pacific/Honolulu', 'Pacific/Marquesas', 'Pacific/Gambier', 'America/Anchorage', 'America/Los_Angeles', 'Pacific/Pitcairn', 'America/Phoenix', 'America/Denver', 'America/Guatemala', 'America/Chicago', 'Pacific/Easter', 'America/Bogota', 'America/New_York', 'America/Caracas', 'America/Halifax', 'America/Santo_Domingo', 'America/Santiago', 'America/St_Johns', 'America/Godthab', 'America/Argentina/Buenos_Aires', 'America/Montevideo', 'Etc/GMT+2', 'Etc/GMT+2', 'Atlantic/Azores', 'Atlantic/Cape_Verde', 'Etc/UTC', 'Europe/London', 'Europe/Berlin', 'Africa/Lagos', 'Africa/Windhoek', 'Asia/Beirut', 'Africa/Johannesburg', 'Asia/Baghdad', 'Europe/Moscow', 'Asia/Tehran', 'Asia/Dubai', 'Asia/Baku', 'Asia/Kabul', 'Asia/Yekaterinburg', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Kathmandu', 'Asia/Dhaka', 'Asia/Omsk', 'Asia/Rangoon', 'Asia/Krasnoyarsk', 'Asia/Jakarta', 'Asia/Shanghai', 'Asia/Irkutsk', 'Australia/Eucla', 'Australia/Eucla', 'Asia/Yakutsk', 'Asia/Tokyo', 'Australia/Darwin', 'Australia/Adelaide', 'Australia/Brisbane', 'Asia/Vladivostok', 'Australia/Sydney', 'Australia/Lord_Howe', 'Asia/Kamchatka', 'Pacific/Noumea', 'Pacific/Norfolk', 'Pacific/Auckland', 'Pacific/Tarawa', 'Pacific/Chatham', 'Pacific/Tongatapu', 'Pacific/Apia', 'Pacific/Kiritimati');
                foreach ($timezones as $tz) {
                    echo '<option value="' . $tz . '" ' . ($tz == $tzname ? 'selected' : '') . '>' . $tz . '</option>';
                } ?>
            </select></div><br class="form"><br/>
    <div class="form-group">
        <div class=" col-md-8 display_field">
            <?php echo Html::submitButton('Save', ['class' => 'btn btn-primary col-md-offset-3','id' => 'update-btn', 'name' => 'login-button']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
