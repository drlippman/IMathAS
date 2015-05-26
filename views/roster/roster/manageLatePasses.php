<?php

use app\components\AppUtility;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Manage Late Passes';
$this->params['breadcrumbs'][] = $this->title;
//AppUtility::dump($studentInformation);

?>

<?php $form = ActiveForm::begin([
    'id' => 'login-form',
    'options' => ['class' => 'form-horizontal'],
    'action' => '',
    'fieldConfig' => [
        'template' => "{label}\n<div class=\"col-lg-6\">{input}</div>\n<div class=\"col-lg-8 col-lg-offset-4\">{error}</div>",
        'labelOptions' => ['class' => 'col-lg-4'],
    ],
]); ?>

    <div><h1>Manage Late Passes</h1></div>
    <p><em> Students can redeem LatePasses for automatic extensions to assessments where allowed by the instructor.
            Students must redeem the LatePass before the Due Date, unless you opt in your assessment settings to allow
            use after the due date (but within 1 LatePass period, specified below) </em></p>
    <p><em>Late Passes extend the due date by
<!--           --><?php // foreach($studentInformation as $singleStudentInformation){ ?>
<!--            <input type=text size=3 value="--><?php //echo $singleStudentInformation['latePassHrs']?><!-- "/> hours</em></p>-->
<!--            --><?php //} ?>
    <p><em>To all students: <input type="text" size="3" id="addpass"/>
            <?= Html::submitButton('Add', ['class' => 'btn btn-primary']) ?>
            <?= Html::submitButton('Replace', ['class' => 'btn btn-primary']) ?></em></p>

    <table class="student-data" id="student-data-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Section</th>
            <th>LatePasses Remaining</th>
        </tr>
        <?php
        foreach($studentInformation as $singleStudentInformation){ ?>
            <tr>
                <td><?php echo $singleStudentInformation['Name']?></td>
                <td><?php echo $singleStudentInformation['Section']?> </td>
                <td><input type="text" value="<?php echo $singleStudentInformation['Latepass']?> "> </td>
            </tr>
        <?php }?>
        </thead>
    </table>

    <div>
        <div class="col-lg-offset-2"><?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?></div>
    </div>
<?php ActiveForm::end(); ?>