<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Course';
$this->params['breadcrumbs'][] = $this->title;

$model->courseName = AppUtility::getStringVal($course->name);
$model->enrollmentKey = AppUtility::getStringVal($course->enrollkey);
$model->theme = AppUtility::getStringVal($course->theme);
$model->copyCourse = AppUtility::getIntVal($course->copyrights);
$model->messageSystem = AppUtility::getIntVal($course->msgset);
$model->latePasses = AppUtility::getIntVal($course->deflatepass);
$model->available = $selectionList['available'];
$model->navigationLink = $selectionList['toolset'];
$model->courseAsTemplate = $selectionList['isTemplate'];

$dispTime = AppUtility::calculateTimeToDisplay($course->deftime);

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
            <?= $form->field($model, 'available')->checkboxList([AppConstant::NUMERIC_TWO => 'Available to students', AppConstant::NUMERIC_ONE => 'Show on instructors home page'], ['checked' => AppConstant::NUMERIC_ONE]) ?>
            <?= $form->field($model, 'theme')->dropDownList(['facebookish.css' => 'Facebookish', 'modern.css' => 'Mordern', 'default.css' => 'Default', 'angelish.css' => 'Angelish', 'angelishmore.css' => 'Angelishmore'], ['prompt' => 'Default']) ?>
            <?= $form->field($model, 'selfEnroll')->radioList([AppConstant::NUMERIC_ONE => 'No', AppConstant::NUMERIC_TWO => 'Yes']) ?>
            <?= $form->field($model, 'copyCourse')->radioList([AppConstant::NUMERIC_ONE => 'Require enrollment key from everyone', AppConstant::NUMERIC_TWO => 'No key required for group members, require key from others ', AppConstant::NUMERIC_THREE => 'No key required from anyone']) ?>
            <?= $form->field($model, 'messageSystem')->radioList([AppConstant::NUMERIC_ONE => 'On for send and receive', AppConstant::NUMERIC_TWO => 'On for receive, students can only send to instructor', AppConstant::NUMERIC_THREE => 'On for receive, students can only send to students', AppConstant::NUMERIC_FOUR => 'On for receive, students cannot send', AppConstant::NUMERIC_FIVE => 'Off ']) ?>
            <?= $form->field($model, 'navigationLink')->checkboxList([AppConstant::NUMERIC_ONE => 'Calender', AppConstant::NUMERIC_TWO => 'Forum List', AppConstant::NUMERIC_FOUR => 'Show']) ?>
            <?= $form->field($model, 'latePasses')->textInput(); ?>
            <?= $form->field($model, 'courseAsTemplate')->checkboxList([AppConstant::NUMERIC_TWO => 'Mark as group template course', AppConstant::NUMERIC_ONE => 'Mark as global template course', AppConstant::NUMERIC_FOUR => 'Mark as self-enroll course']) ?>
    </fieldset>
</div>
<div class="form-group">
    <div class="col-lg-offset-2  col-lg-11">
        <?= Html::submitButton('Update Info', ['class' => 'btn btn-primary', 'name' => 'Submit']) ?>
    </div>
</div>

<?php ActiveForm::end(); ?>