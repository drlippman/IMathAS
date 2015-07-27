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
$this->title = 'Add Rubric';
//$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid='.$course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Add Grade', 'url' => ['/gradebook/gradebook/add-grades?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<fieldset xmlns="http://www.w3.org/1999/html">
    <legend>Add Rubric</legend>
    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
        'action' => 'add-rubric',
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-4\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-3 select-text-margin'],
        ],
    ]); ?>
    <?php
    $model->Name = AppUtility::getStringVal('New Rubrics');
    ?>
    <?= $form->field($model, 'Name')->textInput(); ?>
    <?= $form->field($model, 'RubricType')->dropDownList([AppConstant::NUMERIC_ONE => 'Score breakdown, record score and feedback',AppConstant::NUMERIC_ZERO => 'Score breakdown, record score only',AppConstant::NUMERIC_THREE => 'Score total, record score and feedback',AppConstant::NUMERIC_FOUR => 'Score total, record score only',AppConstant::NUMERIC_TWO => 'Feedback only']); ?>

    <div class="col-lg-offset-3"> <?= $form->field($model, 'ShareWithGroup')->checkbox(); ?> <div>

            <table class="table table-bordered table-striped table-hover data-table" bPaginate="false">
                <thead>
                <tr>
                    <th>Rubric Item Shows in feedback</th>
                    <th>Instructor Note Not in feedback</th>
                    <th>Percentage of score Should add to 100</th>
                </tr>
                </thead>
                <tbody>

                <?php if($edit == false){ for ($i=0;$i<5; $i++) { ?>
                    <tr><td><input type="text" size="40" name="rubitem[<?php echo $i ?>]" /></td>
                        <td><input type="text" size="40" name="rubnote[<?php echo $i ?>]" /></td>
                        <td><input type="text" size="4" name="feedback[<?php echo $i ?>]"/></td></tr>
                <?php }}?>
                <?php if($edit == 1){ for ($i=0;$i<5; $i++) {
//                    AppUtility::dump($rubricItems);
                    ?>
                    <tr><td><input type="text" size="40" value="<?php echo $rubricItems[0][$i] ?>" name="rubitem[<?php echo $i ?>]" /></td>
                        <td><input type="text" size="40" value="<?php echo $rubricItems[1][$i] ?>" name="rubnote[<?php echo $i ?>]" /></td>
                        <td><input type="text" size="4" value="<?php echo $rubricItems[2][$i] ?>" name="feedback[<?php echo $i ?>]"/></td></tr>
                <?php }}?>

                <tbody>
            </table>

</fieldset>
<div class="form-group">
    <div class=" col-lg-8 display_field">
        <?= Html::submitButton('Save', ['class' => 'btn btn-primary col-lg-offset-3']) ?>
        <a class="btn btn-primary back-button-change-student-info"  href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$courseId) ?>">Back</a>
    </div>
</div>
</div>
<?php ActiveForm::end(); ?>
