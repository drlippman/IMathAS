<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\changeUserInfoForm */
$this->title = 'Upload Grades';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/roster/roster/student-roster?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
$model->header = AppConstant::ZERO_VALUE;
$model->userIdentifiedBy = AppConstant::ZERO_VALUE;
?>
<fieldset xmlns="http://www.w3.org/1999/html">
    <legend>Upload Grades</legend>
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
        'action' => 'upload-grades',
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-3 select-text-margin'],
        ],
    ]); ?>
    <input type="hidden" name="gb-items-id" value="<?php echo $gbItemsId?>" >
    <?= $form->field($model, 'file')->fileInput(); ?>
    <?= $form->field($model, 'header')->radioList([AppConstant::NUMERIC_ZERO => 'No header',AppConstant::NUMERIC_ONE => "Has 1 row header",AppConstant::NUMERIC_TWO =>'Has 2 row header' ]); ?>
    <?= $form->field($model,'gradesColumn')->textInput(); ?>
    <?= $form->field($model,'feedbackColumn')->textInput(); ?>
    <?= $form->field($model, 'userIdentifiedBy')->radioList([AppConstant::NUMERIC_ZERO => 'Username (login name) in column<input type="text" name="username" size=6 value="">',AppConstant::NUMERIC_ONE =>'Lastname, Firstname in column<input type="text" name="firstname-lastname" size=6 value="">' ]); ?>


</fieldset>
<div class="form-group">
    <div class=" col-lg-8 display_field">
        <?= Html::submitButton('Save', ['class' => 'btn btn-primary col-lg-offset-3']) ?>
        <a class="btn btn-primary back-button-change-student-info"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>">Back</a>
    </div>
</div>
</div>
<?php ActiveForm::end(); ?>
