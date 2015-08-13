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
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">
    <br>
    <p></p>
    <?php
    $model->FirstName = AppUtility::getStringVal($user['FirstName']);
    $model->LastName = AppUtility::getStringVal($user['LastName']);
    $model->email = AppUtility::getStringVal($user['email']);
    ?>
    <fieldset>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-sm-4\">{input}</div>\n<div class=\"col-sm-4 col-lg-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-sm-3'],
            ],
        ]); ?>
        <div class="old-password">
            <br>
            <?php echo $form->field($model, 'FirstName')->textInput(); ?>
        </div>
        <div class="old-password">
            <br>
             <?php echo  $form->field($model, 'LastName')->textInput() ?>
        </div>
        <div class="password_checkbox">

            <?php echo $form->field($model, 'changePassword')->checkbox(['id' => 'pwd']) ?>
        </div>
        <div class="toggle-password">
            <div class="old-password">
                <br>
                <div class="margin-left-3">
                <?php echo $form->field($model, 'oldPassword')->passwordInput() ?>
                </div>
                <br>
                <?php echo $form->field($model, 'password')->passwordInput() ?>
                <br>
                <?php echo $form->field($model, 'rePassword')->passwordInput() ?>
            </div>
        </div>
        <br>
        <div class="password_checkbox">
            <?= $form->field($model, 'NotifyMeByEmailWhenIReceiveANewMessage')->checkbox() ?>
        </div>
        <br>
        <div class="alignuserpicture">
            <?php
            if($user['hasuserimg']==0)
            {?>
                <img src="<?php echo AppUtility::getHomeURL()?>Uploads/dummy_profile.jpg" class = "image" alt="file not found" /><br><br>
                <?php
                echo "Upload profile picture";
            }
            else
            {?>
                <img src="<?php echo AppUtility::getHomeURL()?>Uploads/<?php echo $user['id'] ?>.jpg?ver=<?php echo time()?>" class = "image" alt="file not found" />
                </br></br>
                <div class="update-checkbox">
                    <?= $form->field($model, 'remove')->checkbox() ?>
                </div>
            <?php }?>
        </div >

        <div class="old-password">
            <?php echo $form->field($model, 'file')->fileInput() ?>
            <?php echo $form->field($model, 'message')->dropDownList(array(10, 20, 30, 40, 50, 60, 70, 80, 90, 100)) ?>
            <?php echo $form->field($model, 'homepage')->checkboxList(['NewMessagesWidget' => 'New Messages Widget', 'NewForumPostsWidget' => 'New Forum Posts Widget', 'NewMessagesNotesOnCourseList' => 'New Messages Notes On Course List', 'NewPostsNotesOnCourseList' => 'New Posts Notes On Course List']) ?>

        </div>
    </fieldset>
    <div class="Timezone">
        <br>
        <div style="margin: 10px">
        <h4>TimeZone</h4>
        <p>Due Dates and other times are being shown to you correct for the <b><?php echo $tzname; ?></b> timezone.</p>

        <p>You may change the timezone the dates display based on if you would like. This change will only last until
            you close your browser or log out.</p>

        <p>Set timezone to: <select name="settimezone" id="settimezone" class="col-lg-offset-1">
                <?php
                $timezones = array('Etc/GMT+12', 'Pacific/Pago_Pago', 'America/Adak', 'Pacific/Honolulu', 'Pacific/Marquesas', 'Pacific/Gambier', 'America/Anchorage', 'America/Los_Angeles', 'Pacific/Pitcairn', 'America/Phoenix', 'America/Denver', 'America/Guatemala', 'America/Chicago', 'Pacific/Easter', 'America/Bogota', 'America/New_York', 'America/Caracas', 'America/Halifax', 'America/Santo_Domingo', 'America/Santiago', 'America/St_Johns', 'America/Godthab', 'America/Argentina/Buenos_Aires', 'America/Montevideo', 'Etc/GMT+2', 'Etc/GMT+2', 'Atlantic/Azores', 'Atlantic/Cape_Verde', 'Etc/UTC', 'Europe/London', 'Europe/Berlin', 'Africa/Lagos', 'Africa/Windhoek', 'Asia/Beirut', 'Africa/Johannesburg', 'Asia/Baghdad', 'Europe/Moscow', 'Asia/Tehran', 'Asia/Dubai', 'Asia/Baku', 'Asia/Kabul', 'Asia/Yekaterinburg', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Kathmandu', 'Asia/Dhaka', 'Asia/Omsk', 'Asia/Rangoon', 'Asia/Krasnoyarsk', 'Asia/Jakarta', 'Asia/Shanghai', 'Asia/Irkutsk', 'Australia/Eucla', 'Australia/Eucla', 'Asia/Yakutsk', 'Asia/Tokyo', 'Australia/Darwin', 'Australia/Adelaide', 'Australia/Brisbane', 'Asia/Vladivostok', 'Australia/Sydney', 'Australia/Lord_Howe', 'Asia/Kamchatka', 'Pacific/Noumea', 'Pacific/Norfolk', 'Pacific/Auckland', 'Pacific/Tarawa', 'Pacific/Chatham', 'Pacific/Tongatapu', 'Pacific/Apia', 'Pacific/Kiritimati');
                foreach ($timezones as $tz) {
                    echo '<option value="' . $tz . '" ' . ($tz == $tzname ? 'selected' : '') . '>' . $tz . '</option>';
                } ?>
            </select>
            <div class="submit-button-change-user-info">
                <?php echo Html::submitButton('Save', ['class' => 'btn btn-primary','id' => 'update-btn', 'name' => 'login-button']) ?>
           </div>
            <br>
        </div>

    </div>



    <?php ActiveForm::end(); ?>
</div>
