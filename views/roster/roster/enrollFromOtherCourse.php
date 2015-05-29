<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Enroll From Other Course';
$this->params['breadcrumbs'][] = ['label' => 'List students', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;
?>
<h2>Enroll Student From Another Course</h2>
<div class="site-login">
    <?php $form =ActiveForm::begin(
        [
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-7 col-lg-offset-2\">{error}</div>",
                'labelOptions' => ['class' => 'col-lg-2 select-text-margin'],
            ],
        ]
    ) ?>
      <div><br>
          <h4>Select a course to choose students from:</h4>
        <?php
             foreach($data as $value)
             {
                 echo "<tr><td><input type='radio' name='name' value='{$value['id']}'></td>"." " ."<td>{$value['name']}</td></tr><br>";
             }
        ?>
    </div>
    <div class="form-group">
        <div class="col-lg-11">
            <br>
            <?= Html::submitButton('Choose Students', ['class' => 'btn btn-primary','name' => 'choose-button']) ?>
            <a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>">Back</a>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>

