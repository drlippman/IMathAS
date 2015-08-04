<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
if($commentType == "instr"){
    $this->title = AppUtility::t('Upload Instructor Notes', false);
}else{
    $this->title = AppUtility::t('Upload Gradebook Comments', false);
}
$this->params['breadcrumbs'][] = $this->title;
echo '<div class="item-detail-header">';
$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid='.$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
if($commentType == "instr") {
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false), AppUtility::t('Instructor Notes', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . '/gradebook/gradebook/gradebook?cid=' . $course->id, AppUtility::getHomeURL() . '/gradebook/gradebook/gb-comments?cid=' . $course->id.'&comtype=instr']]);
//    $this->params['breadcrumbs'][] = ['label' => 'Instructor Notes ', 'url' => ['/gradebook/gradebook/gb-comments?cid=' . $course->id.'&comtype=instr']];
}else{
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false), AppUtility::t('Gradebook Comments', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . '/gradebook/gradebook/gradebook?cid=' . $course->id, AppUtility::getHomeURL() . '/gradebook/gradebook/gb-comments?cid=' . $course->id]]);
//    $this->params['breadcrumbs'][] = ['label' => 'Gradebook Comments', 'url' => ['/gradebook/gradebook/gb-comments?cid=' . $course->id]];
} ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content shadowBox"">
<div class="inner-content-gradebook">

<?php
if($userCol == AppConstant::NUMERIC_NEGATIVE_ONE){
    echo "<p class='alert alert-danger'>".AppUtility::t('Enter column to identify user.', false)."<br/>";
}
if ($successes>0) {
    echo "<p class='alert alert-success'>".AppUtility::t('Comments uploaded ', false). $successes .AppUtility::t('records.', false)."</p> ";
}
if (count($failures)>0) {
    echo "<p class='alert alert-danger'>".AppUtility::t('Comment upload failure on: ', false)."<br/>";
    echo implode('<br/>',$failures);
    echo '</p>';
} ?>
<div>
    <fieldset>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-sm-2\">{input}</div>\n<div class=\"col-sm-5 clear-both col-sm-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-sm-3  text-align-left'],
            ],
        ]); ?>
        <?php echo $form->field($model, 'file')->fileInput();?>
        <?php echo $form->field($model, 'fileHeaderRow')->radioList([AppConstant::NUMERIC_ZERO => AppUtility::t('No header', false),AppConstant::NUMERIC_ONE => AppUtility::t('Has 1 row header', false),AppConstant::NUMERIC_TWO => AppUtility::t('Has 2 row header', false)]);?>
        <?php echo $form->field($model, 'commentsColumn')->textInput();?>
<div >
    <span class="pull-left"><b><?php AppUtility::t('User is identified by')?></b></span>
            <span class="user-identity ">
                <div class="col-sm-7" style="padding-left: 0px"><input type="radio" name="userIdType" value="0" checked="1">&nbsp;<b><?php AppUtility::t('Username (login name) in column')?></b></div>
                <div class="col-sm-2"><input class="form-control" type="text" size="4" name="userNameCol"><br></div>
                <div class="col-sm-7" style="padding-left: 0px"><input type="radio" name="userIdType" value="1">&nbsp;<b><?php AppUtility::t('Lastname, Firstname in column')?></b></div>
                <div class="col-sm-2"><input class="form-control" type="text" size="4" name="fullNameCol"></div>
            </span>
</div>
    </fieldset>
    <div class="form-group">
        <div class="col-sm-offset-3"><br class="form">
            <?php echo Html::submitButton(AppUtility::t('Submit', false), ['class' => 'btn btn-primary']) ?>
            <?php if ($commentType == "instr"){ ?>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id.'&comtype=instr')  ?>"><?php AppUtility::t('Back')?></a>
            <?php } else {?>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id)  ?>"><?php AppUtility::t('Back')?></a>
            <?php }?>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
    </div>
</div>