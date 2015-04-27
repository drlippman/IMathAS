<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;


$this->title = 'Course';
$this->params['breadcrumbs'][] = $this->title;

$model->courseName = isset($course->name) ? $course->name : null;
$model->enrollmentKey = isset($course->enrollkey) ? $course->enrollkey : null;
$model->theme = isset($course->theme) ? $course->theme : null;
$model->copyCourse = isset($course->copyrights) ? $course->copyrights : 0;
$model->messageSystem = isset($course->msgset) ? $course->msgset : 0;
$model->latePasses = isset($course->deflatepass) ? $course->deflatepass : 0;

$model->available = $selectionList['availablel'];
$model->navigationLink = $selectionList['toolset'];
$model->courseAsTemplate = $selectionList['isTemplate'];

$dispTime = \app\components\AppUtility::calculateTimeToDisplay($course->deftime);

?>

<div class="site-login">
    <fieldset>
        <legend>Course Settings</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-5 clear-both col-lg-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3  text-align-left'],
            ],
        ]); ?>

        <div class="form-label-alignment">

            <?= $form->field($model, 'courseName')->textInput(); ?>

            <?= $form->field($model, 'enrollmentKey')->textInput() ?>

            <div class="datetime form-group">
                <?php
                echo '<label class="start col-lg-2 pull-left "> Start Time </label>';
                echo '<div class = "pull-left col-lg-4 time-input">';
                echo TimePicker::widget([
                    'name' => 'start_time',
                    'value' => $dispTime['startTime'],
                    'pluginOptions' => [
                        'showSeconds' => false
                    ]
                ]);
                echo '</div>';?>
                <?php
                echo '<label class="end pull-left col-lg-1"> End Time </label>';
                echo '<div class="pull-left col-lg-4">';
                echo TimePicker::widget([
                    'name' => 'end_time',
                    'value' => $dispTime['endTime'],
                    'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                    ]
                ]);

                echo '</div>';?>
            </div>

            <div style="clear: both"></div>
            <?= $form->field($model, 'available')->checkboxList(['2' => 'Available to students', '1' => 'Show on instructors home page'], array('checked' => '2')) ?>

            <?= $form->field($model, 'theme')->dropDownList(['facebookish.css' => 'Facebookish', 'modern.css' => 'Mordern', 'default.css' => 'Default', 'angelish.css' => 'Angelish', 'angelishmore.css' => 'Angelishmore'], ['prompt' => 'Default']) ?>

            <?= $form->field($model, 'copyCourse')->radioList(['1' => 'Require enrollment key from everyone', '2' => 'No key required for group members, require key from others ', '3' => 'No key required from anyone']) ?>
            <?= $form->field($model, 'messageSystem')->radioList(['1' => 'On for send and receive', '2' => 'On for receive, students can only send to instructor', '3' => 'On for receive, students can only send to students', '4' => 'On for receive, students cannot send', '5' => 'Off ']) ?>

            <?= $form->field($model, 'navigationLink')->checkboxList(['1' => 'Calender', '2' => 'Forum List', '4' => 'Show']) ?>
            <?= $form->field($model, 'latePasses')->textInput(); ?>
            <?= $form->field($model, 'courseAsTemplate')->checkboxList(['2' => 'Mark as group template course', '1' => 'Mark as global template course', '4' => 'Mark as self-enroll course']) ?>
    </fieldset>
</div>
<div class="form-group">
    <div class="col-lg-offset-2  col-lg-11">
        <?= Html::submitButton('Update Info', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>


