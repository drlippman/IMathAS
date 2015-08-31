<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
$this->title = AppUtility::t('Change student Information', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid=' . $course->id]]); ?>
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
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => 'change-student-information?cid='.$courseId.'&uid='.$userId,
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-sm-3\">{input}</div>\n<div class=\"col-sm-9 col-sm-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-sm-3 select-text-margin'],
            ],
        ]); ?>
        <?php echo $form->field($model, 'SID')->textInput(); ?>

        <?php echo $form->field($model, 'FirstName')->textInput(); ?>

        <?php echo $form->field($model, 'LastName')->textInput() ?>

        <?php echo $form->field($model, 'email') ?>
        <br>
        <div class="col-sm-offset-3 user-image">
            <?php
            if($user['hasuserimg']==0)
            {?>
                <img src="<?php echo AppUtility::getHomeURL()?>Uploads/dummy_profile.jpg" width="150" alt="<?php echo AppUtility::t('file not found')?>" /><br><br>
                <?php
                echo AppUtility::t("Upload profile picture");
            }
            else
            {?>
                 <img src="<?php echo AppUtility::getHomeURL()?>Uploads/<?php echo $user['id'] ?>.jpg?ver=<?php echo time()?>" width="150" alt="<?php echo AppUtility::t('file not found')?>" /></br>
                 <div class="update-checkbox">

                  <?php echo $form->field($model, 'remove')->checkbox() ?>
            </div>
           <?php }?>
        </div>
        <?php echo $form->field($model, 'file')->fileInput() ?>
        <?php echo $form->field($model, 'section')->textInput(); ?>
        <?php echo $form->field($model, 'code')->textInput(); ?>
        <?php echo $form->field($model, 'timelimitmult')->textInput(); ?>
        <div class="col-sm-offset-3 div-change-student-info" >
            <?php echo $form->field($model, 'locked')->checkbox(); ?>
            <?php echo $form->field($model, 'hidefromcourselist')->checkbox(); ?>
        </div>
        <div class="col-sm-offset-3 password_checkbox" style="margin-left: 27%">
            <?php echo $form->field($model, 'changePassword')->checkbox(['id' => 'pwd']) ?>
        </div>
        <div class="change-password-content">
            <?php echo $form->field($model, 'password')->textInput(); ?>
         </div>

    <br>
     <div class="form-group">
        <div class=" col-sm-9 col-sm-offset-3 display_field">
            <?php echo Html::submitButton(AppUtility::t('Save', false), ['class' => 'btn btn-primary col-sm-1', 'name' => 'login-button']) ?>
            <a class="btn btn-primary back-button-change-student-info col-sm-1"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>"><?php echo AppUtility::t('Back')?></a>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
</div>
