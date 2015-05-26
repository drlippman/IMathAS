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
                'labelOptions' => ['class' => 'col-lg-2 select-text-margin'],
            ],
        ]
    ) ?>
      <div>
        <?=
        $form->field($model, 'rights')->radioList([AppConstant::GUEST_RIGHT => 'Guest User',
        AppConstant::STUDENT_RIGHT => 'Student',]) ?>
    </div>

    <div class="form-group">
        <div class="col-lg-offset-2 col-lg-11">
            <?= Html::submitButton('Choose Students', ['class' => 'btn btn-primary', 'name' => 'choose-button']) ?>
            <a class="btn btn-primary back-button" href="<?php echo AppUtility::getURLFromHome('roster/roster', 'student-roster?cid='.$course->id)  ?>">Back</a>
        </div>
    </div>


    <?php ActiveForm::end(); ?>
</div>



<div class="site-login"><br>


    <table class="radio-div">
        <thead>
        <tr>
            <th></th>
            <th></th>

        </tr>
        </thead>
        <tbody class="table-body"></tbody>
    </table>
    <br>
    <a class="btn btn-primary" id="choose-students">Choose Students</a>
    <div class="radio-div"></div>


</div>

<div>
<h4>Select students to enroll:</h4>
    <br>
    check: <a id="check-all-box" class="check-all" href="#">All</a> /
    <a id="uncheck-all-box" class="uncheck-all" href="#">None</a>

    <table class="check-div">
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody class="check-table-body"></tbody>
    </table>

</div>



<script type="text/javascript">
    $(document).ready(function () {
        var cid = $(".send-msg").val();
        var userId = $(".send-userId").val();
        var allMessage = {cid: cid, userId: userId};
        jQuerySubmit('get-course-ajax',allMessage,'getCourseSuccess');
        chooseStudent();
        });
    var list;
    var studentList;
    function getCourseSuccess(response){
        var result= JSON.parse(response);
            if(result.status==0){
            list=result.query;
            showList(list);
          }
    }
    function showList(list)
    {
        var html=" ";
        var count=0;
        $.each(list,function(index,list){
            html+="<tr><td><input type='radio' name='radio' value='"+list+"'></td>";
            html+="<td>"+list+"</td>";


        });

        $('.table-body').append(html);

    }
    function chooseStudent()
    {
        $('#choose-students').click(function(){
            var markArray;
            $('.table-body input[name="radio"]:checked').each(function(){
                //markArray.push($(this).val());
                markArray=this.value;
                $(this).prop('checked',false);
            });
            var readvalue={checkedvalue: markArray};
           jQuerySubmit('get-student-ajax',readvalue,'getStudentSuccess');
    });
    }
    function getStudentSuccess(response){console.log(response);
        alert(response);
          var result=JSON.parse(response);

                     if(result.status==0){
                         var studentData = result.record;
                         $.each(studentData, function(index, student){
                             alert(JSON.stringify(student));
                             $.each(student, function(index, stud){
                                 alert(JSON.stringify(stud));
                                 alert('hii')

                             });
                         });
                  studentList=stud.data;
                showStudentList(studentList);
            }
        }

    </script>
