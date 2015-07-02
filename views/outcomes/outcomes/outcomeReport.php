<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Outcome Report';
$this->params['breadcrumbs'][] = $this->title;
?>
<div><h3>Outcomes  Report</h3></div>
<div class="cpmid">
   <b>Show for scores</b> <select class=''>
        <option value='1'>Past Due scores</option>
        <option value='2' selected="selected">Past Due and Attempted scores</option></select>
</div>
<input type="hidden" id="course-id" value="<?php echo $courseId?>">
<script>
    $(document).ready(function ()
    {
        var courseId = $('#course-id').val();
        jQuerySubmit('get-outcome-report-ajax',{courseId:courseId},'outcomeReportResponse');
    });


    function outcomeReportResponse(response)
    {
        response = JSON.parse(response);
        console.log(response);
        var html="";
        if(response.status == 0)
        {

            var studentData = response.data.studentOutcomeReportArray;
            $.each(studentData, function(index,data))
            {


            }
        }
    }
</script>