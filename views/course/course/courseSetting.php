<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\time\TimePicker;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Course Setting';
$this->params['breadcrumbs'][] = ['label' => 'Admin', 'url' => ['/admin/admin/index']];
$this->params['breadcrumbs'][] = $this->title;
$model->courseId = $course->id;
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
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'options' => ['class' => 'form-horizontal'],
                'action' => '',
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"col-sm-10\">{input}</div>\n<div class=\"col-sm-10 col-sm-offset-2\">{error}</div>",
                    'labelOptions' => ['class' => 'col-sm-2  text-align-left'],
                ],
            ]); ?>
            <div class="item-detail-header">
                <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
            </div>
            <div class = "title-container">
                <div class="row">
                    <div class="pull-left page-heading">
                        <div class="vertical-align title-page"><?php echo $this->title ?></div>
                    </div>
                    <div class="pull-left header-btn">
                        <?php echo Html::submitButton('<i class="fa fa-share header-right-btn"></i>Save', ['class' => 'btn btn-primary pull-right page-settings', 'name' => 'Submit']) ?>
                    </div>
                </div>
            </div>
            <div class="tab-content shadowBox non-nav-tab-item">
                <br><br>
                <div class="form-label-alignment form-display">
                    <?php echo $form->field($model, 'courseId')->textInput(['class' => 'course-id']); ?>
                    <?php echo $form->field($model, 'courseName')->textInput(); ?>
                    <?php echo $form->field($model, 'enrollmentKey')->textInput() ?>

                    <div class="datetime form-group">
                        <?php
                        echo '<label class="start col-md-2 pull-left padding-top"> Start Time </label>';
                        echo '<div class = "pull-left col-md-3 time-input">';
                        echo TimePicker::widget([
                            'name' => 'start_time',
                            'value' => $dispTime['startTime'],
                            'pluginOptions' => [
                                'showSeconds' => false
                            ]
                        ]);
                        echo '</div>';?>
                        <?php
                        echo '<label class="end pull-left col-md-2 padding-top"> End Time </label>';
                        echo '<div class="pull-left col-md-3">';
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
                    <?php echo $form->field($model, 'available')->checkboxList([AppConstant::NUMERIC_TWO => 'Available to students', AppConstant::NUMERIC_ONE => 'Show on instructors home page'], ['checked' => AppConstant::NUMERIC_ONE]) ?>
                    <?php echo $form->field($model, 'theme')->dropDownList(['facebookish.css' => 'Facebookish', 'modern.css' => 'Modern', 'default.css' => 'Default', 'angelish.css' => 'Angelish', 'angelishmore.css' => 'Angelishmore'], ['prompt' => 'Default']) ?>
                    <?php echo $form->field($model, 'copyCourse')->radioList([AppConstant::NUMERIC_ONE => 'Require enrollment key from everyone', AppConstant::NUMERIC_TWO => 'No key required for group members, require key from others ', AppConstant::NUMERIC_THREE => 'No key required from anyone']) ?>
                    <?php echo $form->field($model, 'messageSystem')->radioList([AppConstant::NUMERIC_ONE => 'On for send and receive', AppConstant::NUMERIC_TWO => 'On for receive, students can only send to instructor', AppConstant::NUMERIC_THREE => 'On for receive, students can only send to students', AppConstant::NUMERIC_FOUR => 'On for receive, students cannot send', AppConstant::NUMERIC_FIVE => 'Off ']) ?>
                    <?php echo $form->field($model, 'navigationLink')->checkboxList([AppConstant::NUMERIC_ONE => 'Calender', AppConstant::NUMERIC_TWO => 'Forum List', AppConstant::NUMERIC_FOUR => 'Show']) ?>
                    <?php echo $form->field($model, 'latePasses')->textInput(); ?>
                    <?php echo $form->field($model, 'courseAsTemplate')->checkboxList([AppConstant::NUMERIC_TWO => 'Mark as group template course', AppConstant::NUMERIC_ONE => 'Mark as global template course', AppConstant::NUMERIC_FOUR => 'Mark as self-enroll course']) ?>
    </div>
    </div>
<?php ActiveForm::end(); ?>