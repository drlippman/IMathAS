<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
if($commentType == "instr"){
    $this->title = 'Upload Instructor Notes';
}else{
    $this->title = 'Upload Gradebook Comments';
}
$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
if($commentType == "instr") {
    $this->params['breadcrumbs'][] = ['label' => 'Instructor Notes ', 'url' => ['/gradebook/gradebook/gb-comments?cid=' . $course->id.'&comtype=instr']];
}else{
    $this->params['breadcrumbs'][] = ['label' => 'Gradebook Comments', 'url' => ['/gradebook/gradebook/gb-comments?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = $this->title;
if($commentType == "instr") {
    echo "<h2>Upload Instructor Notes</h2>";
} else {
    echo "<h2>Upload Students Comments</h2>";
} ?>
<div class="roster-import-student-csv">
    <fieldset>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-2\">{input}</div>\n<div class=\"col-lg-5 clear-both col-lg-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-3  text-align-left'],
            ],
        ]); ?>
        <?php echo $form->field($model, 'file')->fileInput();?>
        <?php echo $form->field($model, 'fileHeaderRow')->radioList([AppConstant::NUMERIC_ZERO => 'No header',AppConstant::NUMERIC_ONE => 'Has 1 row header',AppConstant::NUMERIC_TWO => 'Has 2 row header']);?>

        <?php echo $form->field($model, 'commentsColumn')->textInput();?>
            <span class="pull-left col-lg-3"><b>User is identified by</b></span>
            <span class="formright col-lg-2">
            <input type="radio" name="useridtype" value="0" checked="1">Username (login name) in column
            <input type="text" size="4" name="usernamecol"><br>
            <input type="radio" name="useridtype" value="1">Lastname, Firstname in column
            <input type="text" size="4" name="fullnamecol"></span>
    </fieldset>
    <div class="form-group">
        <div class="col-lg-offset-3 roster-submit"><br class="form">
            <?php echo Html::submitButton('Submit', ['class' => 'btn btn-primary', 'id' => 'submit', 'name' => 'Submit']) ?>
            <?php if ($commentType == "instr"){ ?>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id.'&comtype=instr')  ?>">Back</a>
            <?php } else {?>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id)  ?>">Back</a>
            <?php }?>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>