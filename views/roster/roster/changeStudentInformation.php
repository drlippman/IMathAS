<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */
$this->title = 'Change student Information';
?>
<div>
    <?php
    $model->Username = AppUtility::getStringVal($user['SID']);
    $model->FirstName = AppUtility::getStringVal($user['FirstName']);
    $model->LastName = AppUtility::getStringVal($user['LastName']);
    $model->email = AppUtility::getStringVal($user['email']);
    $model->section = AppUtility::getStringVal($studentData['section']);
    $model->code = AppUtility::getStringVal($studentData['code']);
    if($studentData['locked'] == 0)
    {
        $model->locked =  0;
    }else{
        $model->locked =  1;
    }
    $model->timelimitmult = AppUtility::getStringVal($studentData['timelimitmult']);
    $model->hidefromcourselist = AppUtility::getStringVal($studentData['hidefromcourselist']);
    ?>
    <fieldset>
        <legend>Change Student Information</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'change-student-information?cid='.$courseId.'&uid='.$userId,
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 select-text-margin'],
            ],
        ]); ?>
        <?= $form->field($model, 'Username')->textInput(); ?>

        <?= $form->field($model, 'FirstName')->textInput(); ?>

        <?= $form->field($model, 'LastName')->textInput() ?>

                <?= $form->field($model, 'email') ?>
        <br>
        <div class="col-lg-offset-2 user-image">
            <?php
            if($user['hasuserimg']==0)
            {?>
                <img src="<?php echo AppUtility::getHomeURL()?>Uploads/dummy_profile.jpg" width="150" alt="file not found" /><br><br>
                <?php
                echo "Upload profile picture";
            }
            else
            {?>
                 <img src="<?php echo AppUtility::getHomeURL()?>Uploads/<?php echo $user['id'] ?>.jpg?ver=<?php echo time()?>" width="150" alt="file not found" /></br>
                 <div class="update-checkbox">
                <?= $form->field($model, 'remove')->checkbox() ?>
            </div>
           <?php }?>
        </div>
        <?= $form->field($model, 'file')->fileInput() ?>
        <?= $form->field($model, 'section')->textInput(); ?>
        <?= $form->field($model, 'code')->textInput(); ?>
        <?= $form->field($model, 'timelimitmult')->textInput(); ?>
        <div class="col-lg-offset-2 div-change-student-info" >
        <?= $form->field($model, 'locked')->checkbox(); ?>
        <?= $form->field($model, 'hidefromcourselist')->checkbox(); ?>
        </div>
        <div class="row password_checkbox">
            <?= $form->field($model, 'changePassword')->checkbox(['id' => 'pwd']) ?>
        </div>
        <div class="change-password-content">
            <?= $form->field($model, 'password')->textInput(); ?>
         </div>
    </fieldset>
     <div class="form-group">
        <div class=" col-lg-8 display_field">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary col-lg-offset-3','id' => 'update-btn', 'name' => 'login-button']) ?>
            <a class="btn btn-primary back-button-change-student-info"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>">Back</a>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
