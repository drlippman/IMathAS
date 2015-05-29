<?php

use app\components\AppUtility;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Manage Late Passes';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;
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

           <?php
            $isCheck=false;
            foreach($studentInformation as $singleStudentInformation){
                if($isCheck==false)
                {
                    $isCheck=true;
                    ?>
                    <input type=text size=3 value="<?php echo $singleStudentInformation['latePassHrs']?>" name="passhours"/> hours</em></p>
                <?php } ?>


            <?php } ?>
    <p><em>To all students: <input type="text" size="3" id="txt_add" name="addpass" value="1"/>
            <input type="button" name="add" value="Add" class="btn btn-primary" onclick="addText()">
     <input type="button" name="replace" value="Replace" class="btn btn-primary" onclick="replaceText()">


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
                <td><input type="text" class="latepass-text-id" size="4" value="<?php echo $singleStudentInformation['Latepass']?>"name='code[<?php echo $singleStudentInformation['userid']?>]'> </td>
            </tr>
        <?php }?>
        </thead>
    </table>
   <div id="submit-button"> <input type="submit" class="btn btn-primary" value="Save Changes"></div>

 <?php ActiveForm::end(); ?>
<script  type="text/javascript">
   function addText()
   {
       var text_id =  document.getElementById("txt_add").value;
       $( ".latepass-text-id" ).each(function() {
           var oldlatepass = $(this).val();
           $(this).val(parseInt(oldlatepass) + parseInt(text_id));
       });
   }
   function replaceText()
   {
       var text_id =  document.getElementById("txt_add").value;
       $( ".latepass-text-id" ).each(function() {
           $(this).val(parseInt(text_id));
       });
   }
</script>

