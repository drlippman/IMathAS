<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */
$this->title = AppUtility::t('Change student Information', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . '/roster/roster/student-roster?cid=' . $course->id]]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="inner-content">
    <?php
    $model->SID = AppUtility::getStringVal($user['SID']);
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
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'change-student-information?cid='.$courseId.'&uid='.$userId,
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-sm-3\">{input}</div>\n<div class=\"col-sm-9 col-sm-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-sm-3 select-text-margin'],
            ],
        ]); ?>
        <?= $form->field($model, 'SID')->textInput(); ?>

        <?= $form->field($model, 'FirstName')->textInput(); ?>

        <?= $form->field($model, 'LastName')->textInput() ?>

                <?= $form->field($model, 'email') ?>
        <br>
        <div class="col-sm-offset-3 user-image">
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
        <div class="col-sm-offset-3 div-change-student-info" >
        <?= $form->field($model, 'locked')->checkbox(); ?>
        <?= $form->field($model, 'hidefromcourselist')->checkbox(); ?>
        </div>
        <div class="col-sm-offset-3 password_checkbox">
            <?= $form->field($model, 'changePassword')->checkbox(['id' => 'pwd']) ?>
        </div>
        <div class="change-password-content">
            <?= $form->field($model, 'password')->textInput(); ?>
         </div>
    </fieldset>
     <div class="form-group">
        <div class=" col-sm-9 col-sm-offset-3 display_field">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
            <a class="btn btn-primary back-button-change-student-info"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>">Back</a>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
</div>
