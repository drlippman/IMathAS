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
if($userCol == AppConstant::NUMERIC_NEGATIVE_ONE){
    echo "<p class='alert alert-danger'>Enter column to identify user.<br/>";
}
if ($successes>0) {
    echo "<p class='alert alert-success'>Comments uploaded.  $successes records.</p> ";
}
if (count($failures)>0) {
    echo "<p class='alert alert-danger'>Comment upload failure on: <br/>";
    echo implode('<br/>',$failures);
    echo '</p>';
}
if($commentType == "instr") {
    echo "<h2>Upload Instructor Notes</h2>";
} else {
    echo "<h2>Upload Students Comments</h2>";
} ?>
<div>
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
<div >
    <span class="pull-left"><b>User is identified by</b></span>
            <span class="user-identity ">
                <input type="radio" name="userIdType" value="0" checked="1"><b>Username (login name) in column</b>
                <input type="text" size="4" name="userNameCol"><br>
                <input type="radio" name="userIdType" value="1"><b>Lastname, Firstname in column</b>
                <input type="text" size="4" name="fullNameCol">
            </span>
</div>

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