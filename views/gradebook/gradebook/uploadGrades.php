<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
$this->title = 'Upload Grades';

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
 ?>

<div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name,'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>

    <div class="col-md-12 padding-left-zero padding-right-zero">
        <div class="title-container col-md-8 padding-left-zero">
            <div class="row">
                <div class="pull-left page-heading">
                    <div class="vertical-align title-page"><?php echo $this->title ?> </div>
                </div>
            </div>
        </div>
    </div>
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
                'action' => '',
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"col-md-5 col-sm-7\">{input}</div>\n<div class=\"col-md-5 clear-both col-md-offset-3\">{error}</div>",
                    'labelOptions' => ['class' => 'col-md-3 col-sm-4  text-align-left'],
                ],
            ]); ?>
            <?php
            $model->gradesColumn = 2;
            $model->feedbackColumn =  0;
            ?>

    <div class="tab-content shadowBox col-md-12 padding-thirty margin-top-fourty">

        <div class="col-md-12 padding-left-zero">
            <?php echo $form->field($model, 'file')->fileInput();?>
        </div>
        <div class="col-md-12 padding-left-zero padding-top-ten file-header">
            <?php echo $form->field($model, 'fileHeaderRow')->radioList([AppConstant::NUMERIC_ZERO => 'No header',AppConstant::NUMERIC_ONE => 'Has 1 row header',AppConstant::NUMERIC_TWO => 'Has 2 row header']);?>
        </div>
        <div class="col-md-12 padding-left-zero padding-top-five grade-in-column">
        <?= $form->field($model,'gradesColumn')->textInput(['class' => 'width-thirty-per form-control']); ?>
        </div>
        <div class="col-md-12 padding-left-zero padding-top-fifteen feedback-in-column">
        <?= $form->field($model,'feedbackColumn')->textInput(['class' => 'width-thirty-per form-control']); ?>
        </div>

        <div class="col-md-12 padding-left-zero">
            <span class="col-md-3 col-sm-4 padding-left-zero"><b>User is identified by</b></span>
            <span class="col-md-9 col-sm-8 padding-left-five">
                <div class="col-md-12 padding-left-zero padding-bottom-one-em">
                    <div class="col-md-5 col-sm-8 padding-left-zero select-text-margin">
                            <input type="radio" name="userIdType" value="0" checked="1">
                            <span class="padding-left-three">
                                <b>Username (login name) in column</b>
                            </span>
                    </div>
                    <div class="col-md-2 col-sm-4 padding-left-zero margin-left-minus-twenty">
                        <input class="form-control " type="text"   value="1" name="userNameCol">
                    </div>
                </div>
                <div class="col-md-12 padding-left-zero padding-top-twenty-five">
                    <div class="col-md-5 col-sm-8 padding-left-zero select-text-margin">
                        <input type="radio" name="userIdType" value="1">
                        <span class="padding-left-three">
                            <b>Lastname, Firstname in column</b>
                        </span>
                    </div>
                    <div class="col-md-2 col-sm-4 padding-left-zero margin-left-minus-twenty">
                        <input class="form-control " type="text"   value="2" name="fullNameCol">
                    </div>
                </div>
            </span>
        </div>

        <div class="col-md-12 col-sm-12 padding-right-zero">
            <div class="padding-left-zero  col-sm-5 col-sm-offset-4 col-sm-4 col-md-offset-3">
                <?php echo Html::submitButton('Submit', ['class' => 'btn btn-primary   upload-grade-submit-btn']) ?>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-grades?cid='.$course->id)  ?>">Back</a>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>