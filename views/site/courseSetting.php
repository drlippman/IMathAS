<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */

$this->title = 'Course Settings';

$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" href="../../web/css/courseSetting.css" />
<div class="site-login">
    <fieldset>
        <legend>Course Settings</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 control-label'],
            ],
        ]); ?>


        <?php  echo '12: Will be assigned when the course is created'?>

        <?= $form->field($model, 'courseName')->textInput() ?>

        <?= $form->field($model, 'enrollmentKey')->textInput() ?>
        <?= $form->field($model, 'time')->textInput() ?>
        <div class="datetime form-group">
            <?php
            echo '<label class="start col-lg-2 pull-left"> Start Time </label>';
            echo '<div class = "pull-left">';
            echo TimePicker::widget([
              'name' => 'start_time',
               'value' => '10:00 AM',
               'pluginOptions' => [
               'showSeconds' => true
                                ]
                ]);
            echo '</div>';?>
           <?php
            echo '<label class="end"> End Time </label>';
            echo'<div style="float: left">';
            echo TimePicker::widget([
                'name' => 'end_time',
                'value' => '10:00 PM',
                'pluginOptions' => [
                    'showSeconds' => true,
                    'class' => 'time'
                ]
            ]);

            echo'</div>';?>
        </div>

        <div style="clear: both"></div>
        <?= $form->field($model, 'available')->checkboxList(['2' => 'Available to students','1' => 'Show on instructors home page'],['checked' => '1']) ?>

        <?= $form->field($model, 'theme')->dropDownList(['facebookish.css' => 'Facebookish','modern.css' => 'Mordern','default.css' => 'Default','angelish.css' => 'Angelish','angelishmore.css' => 'Angelishmore'],['prompt'=>'Default']) ?>

        <?= $form->field($model, 'icons')->inline()->radioList(['1' => 'Text-based', '2' => 'Images']) ?>
        <?= $form->field($model, 'showIcons')->inline()->label('Assessments:')->radioList(['1' => 'Show','values'=>'asse', '2' => 'Hide'], ['class' => 'radio-assesments']) ?>
        <?= $form->field($model, 'showIcons')->inline()->label('Inline Text:')->radioList(['1' => 'Show', '2' => 'Hide'], ['class' => 'radio-inline']) ?>
        <?= $form->field($model, 'showIcons')->inline()->label('Linked Text:')->radioList(['1' => 'Show', '2' => 'Hide'], ['class' => 'radio-linked']) ?>
        <?= $form->field($model, 'showIcons')->inline()->label('Forums:')->radioList(['1' => 'Show', '2' => 'Hide'], ['class' => 'radio-forums']) ?>
        <?= $form->field($model, 'showIcons')->inline()->label('Blocks:')->radioList(['1' => 'Show', '2' => 'Hide'], ['class' => 'radio-blocks']) ?>

        <?= $form->field($model, 'selfUnenroll')->inline()->radioList(['1' => 'No', '2' => 'Yes']) ?>
        <?= $form->field($model, 'selfEnroll')->inline()->radioList(['1' => 'No', '2' => 'Yes']) ?>
        <?= $form->field($model, 'copyCourse')->radioList(['1' => 'Require enrollment key from everyone', '2' => 'No key required for group members, require key from others ', '3' => 'No key required from anyone']) ?>
        <?= $form->field($model, 'messageSystem')->radioList(['1' => 'On for send and receive', '2' => 'On for receive, students can only send to instructor', '3' => 'On for receive, students can only send to students', '4' => 'On for receive, students cannot send', '5' => 'Off ']) ?>

        <?= $form->field($model, 'navigationLink')->checkboxList(['1' => 'Calender', '2' => 'Forum List','3' => 'Show']) ?>
        <?= $form->field($model, 'latePasses')->textInput(); ?>
        <?= $form->field($model, 'remainingLatePasses')->checkboxList(['1' => '']) ?>
        <?= $form->field($model, 'studentQuickPick')->checkboxList(['0' => 'Messages', '3' => 'Forums ', '1' => ' Gradebook ', '2' => 'Calendar ', '9' => 'Log Out',]) ?>
        <?= $form->field($model, 'instructorQuickPick')->checkboxList(['0' => 'Messages', '6' => 'Forums ', '1'=>'Student View','2' => ' Gradebook ', '3' => 'Roster ', '7' => 'Group', '4' => 'Calender', '5' => 'Quick View', '9' => 'Log Out',]) ?>
        <?= $form->field($model, 'quickPickBar')->inline()->radioList(['1' => 'Top of course page', '2' => 'Top of all pages',]) ?>
        <?= $form->field($model, 'courseManagement')->inline()->radioList(['1' => 'Bottom of page', '2' => 'Left side bar',]) ?>
        <?= $form->field($model, 'viewControl')->inline()->radioList(['1' => 'With other course management links', '2' => 'Buttons at top right',]) ?>
        <?= $form->field($model, 'studentLink')->inline()->radioList(['1' => 'Bottom of page', '2' => 'Left side bar',]) ?>
        <?= $form->field($model, 'courseAsTemplate')->checkboxList(['2' => 'Mark as group template course', '1' => 'Mark as global template course', '4' => 'Mark as self-enroll course']) ?>
    </fieldset>
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Update Info', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>




</div>
