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
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content shadowBox"">
<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="inner-content">
        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['class' => 'form-horizontal', 'enctype' => 'multipart/form-data'],
            'action' => '',
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-md-4 col-sm-4\">{input}</div>\n<div class=\"col-sm-5 clear-both col-sm-offset-3\">{error}</div>",
                'labelOptions' => ['class' => 'col-md-3 col-sm-3  text-align-left'],
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
        <?php echo $form->field($model, 'userName')->radioList([AppConstant::NUMERIC_ONE => 'Yes, Column: <input type="text" name=unloc size=4 value="2">',AppConstant::NUMERIC_ZERO=>'No, Use as username: firstname_lastname']); ?>
        <?php echo $form->field($model, 'setPassword')->radioList([AppConstant::NUMERIC_ZERO => 'First 4 characters of username',AppConstant::NUMERIC_ONE=>'Last 4 characters of username',AppConstant::NUMERIC_THREE=>'Use value in column: <input type="text" name="pwcol" size=4 value="1">',AppConstant::NUMERIC_TWO=>'Set to: <input type="text" name="defpw" value="password"   >']); ?>
        <?php echo $form->field($model, 'codeNumber')->radioList([AppConstant::NUMERIC_ZERO=>'No',AppConstant::NUMERIC_ONE => 'Yes, use value in column: <input type="text" name="code" size=4 value="1">']); ?>
        <?php echo $form->field($model, 'sectionValue')->radioList([AppConstant::NUMERIC_ZERO=>'No',AppConstant::NUMERIC_ONE => 'Yes, use: <input type="text" name="secval" size=6 value="">',AppConstant::NUMERIC_TWO => 'Yes, use value in column: <input type="text" name="seccol" size=4 value="4">']); ?>
        <span class="col-sm-3 padding-left-zero"><b>Enroll students in</b></span>
            <div class="col-sm-4 padding-left-ten">
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
    <div class="form-group">
        <div class="col-sm-offset-3 roster-submit">
            <?php echo Html::submitButton(AppUtility::t('Submit and Review', false), ['class' => 'btn btn-primary', 'id' => 'submit_and_review', 'name' => 'Submit']) ?>
            <a class="btn btn-primary back-btn" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>"><?php AppUtility::t('Back')?></a>
        </div>
    </div>
<?php ActiveForm::end(); ?>
</div>
</div>