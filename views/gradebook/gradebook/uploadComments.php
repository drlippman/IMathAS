<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
if($commentType == "instr"){
    $this->title = AppUtility::t('Upload Instructor Notes', false);
}else{
    $this->title = AppUtility::t('Upload Student Comments', false);
}?>
 <div class="item-detail-header">
     <?php
if($commentType == "instr") {
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false), AppUtility::t('Instructor Notes', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gb-comments?cid=' . $course->id.'&comtype=instr']]);
}else{
    echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false), AppUtility::t('Gradebook Comments', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gb-comments?cid=' . $course->id]]);
} ?>
</div>


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

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"padding-left-eight col-sm-8 col-md-8\">{input}</div>\n<div class=\"col-sm-5 clear-both col-sm-offset-3 col-md-5 clear-both col-md-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-sm-4 col-md-3 text-align-left'],
            ],
        ]); ?>
    <div class="title-container">
        <div class="row width-sixty-per">
            <div class="pull-left page-heading width-hundread-per">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
    <div class="item-detail-content">
        <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]); ?>
    </div>

    <div class="tab-content shadowBox upload-gradebook-comments-padding">

    <div class="col-md-12 col-sm-12 padding-top-fifteen">
    <div class="col-md-12 col-sm-12 padding-bottom-fifteen">
        <?php echo $form->field($model, 'file')->fileInput();?>
    </div>
    <div class="col-md-12 col-sm-12 padding-bottom-fifteen">
        <?php echo $form->field($model, 'fileHeaderRow')->radioList([AppConstant::NUMERIC_ZERO => AppUtility::t('No header', false),AppConstant::NUMERIC_ONE => AppUtility::t('Has 1 row header', false),AppConstant::NUMERIC_TWO => AppUtility::t('Has 2 row header', false)],['class' => 'file-has-header-row']);?>
    </div>
    <div class="col-md-12 col-sm-12 padding-bottom-fifteen comments-in-columns">
        <?php echo $form->field($model, 'commentsColumn')->textInput(['class' => 'form-control width-twenty-per','value' => '2']);?>
    </div>

    <div class="col-md-12 col-sm-12">
        <div class="col-md-3 col-sm-4 padding-left-zero select-text-margin">
            <b><?php AppUtility::t('User is identified by')?></b>
        </div>
        <div class="col-md-9 col-sm-8 padding-left-zero">
            <div class="col-md-12 col-sm-12 padding-left-zero">
                <div class="col-sm-8 col-md-5 padding-left-zero select-text-margin">
                    <input type="radio" name="userIdType" value="2" checked="1">&nbsp;<b>
                    <?php AppUtility::t('Username (login name) in column')?></b>
                </div>
                <div class="col-sm-3 col-md-3 padding-left-zero padding-bottom-ten margin-left-minus-twenty">
                    <input class="form-control" type="text" size="4" name="userNameCol" value="2">
                </div>
            </div>
            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-fifteen">
                <div class="col-sm-8 col-md-5 padding-left-zero select-text-margin">
                    <input type="radio" name="userIdType" value="1">
                    <span class="padding-left-five"><b><?php AppUtility::t('Lastname, Firstname in column')?></b></span>
                </div>
                <div class="col-sm-3 col-md-3 padding-left-zero margin-left-minus-twenty">
                    <input class="form-control" type="text" size="4" name="fullNameCol" value="1">
                </div>
            </div>
        </div>
    </div>
        <div class="col-sm-6 col-sm-offset-4 col-md-6 col-md-offset-3 padding-top-thirty padding-left-eight">
            <?php echo Html::submitButton(AppUtility::t('Submit', false), ['class' => 'btn btn-primary upload-comments-btn']) ?>
            <?php if ($commentType == "instr"){ ?>
                <a class="btn btn-primary upload-comments-btn margin-left-ten" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id.'&comtype=instr')  ?>"><i class="fa fa-share header-right-btn"></i><?php AppUtility::t('Back')?></a>
            <?php } else {?>
                <a class="btn btn-primary upload-comments-btn margin-left-ten" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id)  ?>"><i class="fa fa-share header-right-btn"></i><?php AppUtility::t('Back')?></a>
            <?php }?>
        </div>


  </div>
</div>
<?php ActiveForm::end(); ?>
</div>