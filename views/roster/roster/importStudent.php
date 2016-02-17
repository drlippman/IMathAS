<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppUtility;
use app\components\AppConstant;
use app\components\AssessmentUtility;
$this->title = AppUtility::t('Import Students from File', false);
$this->params['breadcrumbs'][] = $this->title;
$model->headerRow = AppConstant::ZERO_VALUE;
$model->userName = AppConstant::ZERO_VALUE;
$model->setPassword = AppConstant::ZERO_VALUE;
$model->codeNumber = AppConstant::ZERO_VALUE;
$model->sectionValue = AppConstant::ZERO_VALUE;
?>
<div class="item-detail-header">
<?php if($courseId == 'admin'){ ?>
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index']]); ?>
<?php }else{?>
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'roster/roster/student-roster?cid=' . $course->id]]); ?>
<?php } ?>
</div>
    <div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'roster']); ?>
</div>
<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="inner-content col-md-12 col-sm-12">
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal col-md-12 col-sm-12', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-md-4 col-sm-6\">{input}</div>\n<div class=\"col-md-5 col-sm-5 clear-both col-md-offset-3 col-sm-offset-4\">{error}</div>",
                'labelOptions' => ['class' => 'col-md-3 col-sm-4  text-align-left'],
            ],
        ]); ?>
        <div class="text-label">
            <?php echo AppUtility::t("Register and enroll students from a CSV (comma separated values) file", false)?></div>
        <?php echo $form->field($model, 'file')->fileInput(); ?>
        <?php echo $form->field($model, 'headerRow')->radioList([AppConstant::NUMERIC_ONE => 'Yes',AppConstant::NUMERIC_ZERO=>'No']); ?>
        <?php echo $form->field($model, 'firstName')->textInput(); ?>
        <?php echo $form->field($model, 'nameFirstColumn')->dropDownList([AppConstant::NUMERIC_ZERO => 'Whole entry', AppConstant::NUMERIC_ONE => 'First word in entry', AppConstant::NUMERIC_TWO => 'Second word in entry', AppConstant::NUMERIC_THREE => 'Last word in entry',], ['prompt' => 'Select entry']) ?>
        <?php echo $form->field($model, 'lastName')->textInput(); ?>
        <?php echo $form->field($model, 'nameLastColumn')->dropDownList([AppConstant::NUMERIC_ZERO => 'Whole entry', AppConstant::NUMERIC_ONE => 'First word in entry', AppConstant::NUMERIC_TWO => 'Second word in entry', AppConstant::NUMERIC_THREE => 'Last word in entry',], ['prompt' => 'Select entry']) ?>
        <?php echo $form->field($model, 'emailAddress')->textInput(); ?>
        <?php echo $form->field($model, 'userName')->radioList([AppConstant::NUMERIC_ONE => '<span class="col-md-12 col-sm-12 margin-top-minus-eight padding-left-pt-five-em">Yes, Column <input class="form-control display-inline-block width-fourty-per margin-left-pt-five-em" type="text" name=unloc size=4 value="2"></span>',AppConstant::NUMERIC_ZERO=>'<span class="col-md-12 col-sm-12 padding-left-pt-five-em">No, Use as username: firstname_lastname</span>']); ?>
        <?php echo $form->field($model, 'setPassword')->radioList([AppConstant::NUMERIC_ZERO => '<span class="col-md-12 col-sm-12 padding-left-pt-five-em">First 4 characters of username</span>',AppConstant::NUMERIC_ONE=>'<span class="col-md-12 col-sm-12 padding-left-pt-five-em">Last 4 characters of username</span>',AppConstant::NUMERIC_THREE=>'<span class="col-md-12 col-sm-12 padding-left-pt-five-em margin-top-minus-eight">Use value in column <input class="form-control margin-left-pt-five-em width-thirty-per display-inline-block" type="text" name="pwcol" size=4 value="1"></span>',AppConstant::NUMERIC_TWO=>'<span class="col-md-12 col-sm-12 padding-left-pt-five-em margin-top-minus-eight">Set to <input class="form-control margin-left-pt-five-em width-seventy-eight-per display-inline-block" type="text" name="defpw" value="password"   ></span>']); ?>
        <?php echo $form->field($model, 'codeNumber')->radioList([AppConstant::NUMERIC_ZERO=>'<span class="col-md-12 col-sm-12 padding-left-pt-five-em">No</span>',AppConstant::NUMERIC_ONE => '<span class="col-md-12 col-sm-12 margin-top-minus-eight padding-left-pt-five-em">Yes, use value in column<input class="form-control margin-left-pt-five-em display-inline-block width-twenty-five-per" type="text" name="code" size=4 value="1"></span>']); ?>
        <?php echo $form->field($model, 'sectionValue')->radioList([AppConstant::NUMERIC_ZERO=>'<span class="col-md-12 col-sm-12 padding-left-pt-five-em">No</span>',AppConstant::NUMERIC_ONE => '<span class="col-md-12 col-sm-12 margin-top-minus-eight padding-left-pt-five-em">Yes, use<input class="form-control margin-left-pt-five-em display-inline-block width-fifty-five-per" type="text" name="secval" size=6 value=""></span>',AppConstant::NUMERIC_TWO => '<span class="col-md-12 col-sm-12 margin-top-minus-eight padding-left-pt-five-em">Yes, use value in column<input class="form-control margin-left-pt-five-em width-twenty-five-per display-inline-block" type="text" name="seccol" size=4 value="4"></span>']); ?>
        <span class="col-md-3 col-sm-4 padding-left-zero"><b>Enroll students in</b></span>
            <div class="col-md-4 col-sm-6 padding-left-pt-five-em">
        <?php
        if ($courseId == "admin")
        { ?>
            <select class="form-control" name="courseId">
                <?php foreach($allCourses as $singleCourse)
                {?>
                <option value="<?php echo $singleCourse['id']?>"><?php echo $singleCourse['name']. "(".$singleCourse['LastName']. " ".$singleCourse['FirstName'].")" ;?></option>
                <?php } ?>
                </select>
                <?php
        } else
        {
            echo "This class";
        } ?>
        </div>
    <div class="form-group col-md-12 col-sm-12 padding-top-one-em">
        <div class="col-sm-offset-4 col-md-offset-3 roster-submit">
            <div class="col-md-12 col-sm-12 padding-left-pt-five-em">
                <?php echo Html::submitButton(AppUtility::t('Submit and Review', false), ['class' => 'btn btn-primary', 'id' => 'submit_and_review', 'name' => 'Submit']) ?>
                <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>"><?php AppUtility::t('Back')?></a>
            </div>
        </div>
    </div>
<?php ActiveForm::end(); ?>
</div>
</div>