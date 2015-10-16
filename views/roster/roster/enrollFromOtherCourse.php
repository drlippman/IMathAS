
<?php
use app\components\AppUtility;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
$this->title = AppUtility::t('Enroll Student From Another Course', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,AppUtility::t('Roster', false)], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id, AppUtility::getHomeURL().'roster/roster/student-roster?cid='.$course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course]);?>
</div>

<div class="tab-content shadowBox"">
    <?php echo $this->render("_toolbarRoster", ['course' => $course]);?>

<div class="inner-content">
    <?php $form =ActiveForm::begin(
        [
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-md-3\">{input}</div>\n<div class=\"col-md-7 col-md-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-md-2 select-text-margin'],
            ],
        ]
    ) ?>
      <div>
          <h4><?php AppUtility::t('Select a course to choose students from');?>:</h4>
        <?php
             foreach($data as $value)
             {
                 echo "<tr><div class='radio student-enroll override-hidden'><label class='checkbox-size'><td><input type='radio' name='name' value='{$value['id']}'><span class='cr'><i class='cr-icon fa fa-check'></i></span></label></td>"." " ."<td>{$value['name']}</td></div></tr>";
             }
        ?>
    </div>
    <div class="form-group">
        <div class="col-md-11">
            <br>
            <?php echo Html::submitButton(AppUtility::t('Choose Students', false), ['class' => 'btn btn-primary','id' => 'change-button','name' => 'choose-button']) ?>
            <a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>"><?php AppUtility::t('Back');?></a>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
</div>
