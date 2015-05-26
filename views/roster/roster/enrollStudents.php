<?php
use app\components\AppUtility;
use app\components\AppConstant;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Enroll From Other Course';
//$this->params['breadcrumbs'][] = ['label' => '', 'url' => ['']]];
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
                'labelOptions' => ['class' => 'col-lg-2'],
            ],
        ]
    ) ?>



    <div><br>
        <h4>Select students to enroll: </h4>
        check: <a id="checkAll" class="check-all" href="#">All</a> /
        <a id="checkNone" class="uncheck-all" href="#">None</a>
        <br><br>
<div id="list">
        <?php
        foreach($data as $value){

            echo "<tr><td><input type='checkbox' name='studdent[".$value['id']."]' value='{$value['id']}' class='master'></td>"." " ."<td>{$value['lastName']}"." , " ."{$value['firstName']}</td></tr><br>";
        }
        ?>
</div>
        <br><br>
        <?= $form->field($model, 'section') ?>

    </div>

    <div class="form-group">
        <div class="col-lg-offset-0 col-lg-11">
            <br>
            <?= Html::submitButton('Enroll These Students', ['class' => 'btn btn-primary','name' => 'enroll-students']) ?>
            <a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'enroll-from-other-course?cid='.$cid)  ?>">Back</a>
        </div>
    </div>


    <?php ActiveForm::end(); ?>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $('#checkNone').click(function() {
            $('#list input[type="checkbox"]').prop('checked', false);
        });
        $('#checkAll').click(function() {
            $('#list input[type="checkbox"]').prop('checked', true);
        });
    });


</script>
