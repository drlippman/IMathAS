<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\widgets\FileInput;
use app\components\AppUtility;
use app\components\AppConstant;
use kartik\date\DatePicker;
use kartik\time\TimePicker;
use app\components\AssessmentUtility;

$this->title = 'upload Multiple Grades';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid=' . $course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div>
    <fieldset>
        <legend>Upload Multiple Grades</legend>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-5 clear-both col-lg-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3  text-align-left'],
            ],
        ]); ?>

        <p>The uploaded file must be in Comma Separated Values (.CSV) file format, and contain a column with
            the students' usernames.  If you are including feedback as well as grades, upload will be much easier if the
            feedback is in the column immediately following the scores, and if the column header contains the word Comment or Feedback</p>

        <?php echo $form->field($model, 'file')->fileInput();?>
        <?php echo $form->field($model, 'fileHeaderRow')->radioList([AppConstant::NUMERIC_ZERO => 'yes, No',AppConstant::NUMERIC_ONE => 'Yes, with second for points possible']);?>

    </fieldset>
    <div class="form-group">
        <div class="col-lg-offset-3"><br class="form">
            <?php echo Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
            <?php if ($commentType == "instr"){ ?>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id.'&comtype=instr')  ?>">Back</a>
            <?php } else {?>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id)  ?>">Back</a>
            <?php }?>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>

