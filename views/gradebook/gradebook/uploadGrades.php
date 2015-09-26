<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
$this->title = 'Upload Grades';
//
//$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid='.$course->id]];
//$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
//    $this->params['breadcrumbs'][] = ['label' => 'Instructor Notes ', 'url' => ['/gradebook/gradebook/add-grades?cid=' . $course->id]];
//
//$this->params['breadcrumbs'][] = $this->title;
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
//    echo "<h2>Upload Grades</h2>";
 ?>
<div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name,'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id,AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
    </div>
</div>

    <div class="tab-content shadowBox col-md-12 padding-thirty">

            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
                'action' => '',
                'fieldConfig' => [
                    'template' => "{label}\n<div class=\"col-lg-2\">{input}</div>\n<div class=\"col-lg-5 clear-both col-lg-offset-3\">{error}</div>",
                    'labelOptions' => ['class' => 'col-lg-3  text-align-left'],
                ],
            ]); ?>
            <?php
            $model->gradesColumn = 2;
            $model->feedbackColumn =  0;
            ?>
            <?php echo $form->field($model, 'file')->fileInput();?>
            <?php echo $form->field($model, 'fileHeaderRow')->radioList([AppConstant::NUMERIC_ZERO => 'No header',AppConstant::NUMERIC_ONE => 'Has 1 row header',AppConstant::NUMERIC_TWO => 'Has 2 row header']);?>
                <?= $form->field($model,'gradesColumn')->textInput(); ?>
                <?= $form->field($model,'feedbackColumn')->textInput(); ?>
            <div >
                <span class="pull-left"><b>User is identified by</b></span>
            <span class="user-identity ">
                <div class="col-lg-7" style="padding-left: 0px"><input type="radio" name="userIdType" value="0" checked="1">&nbsp;<b>Username (login name) in column</b></div>
                <div class="col-lg-2"><input class="form-control" type="text" size="4" value="1" name="userNameCol"><br></div>
                <div class="col-lg-7" style="padding-left: 0px"><input type="radio" name="userIdType" value="1">&nbsp;<b>Lastname, Firstname in column</b></div>
                <div class="col-lg-2"><input class="form-control" type="text" size="4" value="2" name="fullNameCol"></div>
            </span>
            </div>
        <div class="form-group">
            <div class="col-md-offset-3 col-md-9">
                <?php echo Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
                     <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-grades?cid='.$course->id)  ?>">Back</a>
            </div>
        </div>
    </div>

<?php ActiveForm::end(); ?>