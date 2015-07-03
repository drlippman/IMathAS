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
<div class="outcomeReport-div"></div>

<script>
    $(document).ready(function ()
    {
        var courseId = $('#course-id').val();
        jQuerySubmit('get-outcome-report-ajax',{courseId:courseId},'outcomeReportResponse');
    });


    function outcomeReportResponse(response)
    {
        response = JSON.parse(response);


        var html="";

        if(response.status == 0)
        {
            var studentData = response.data.studentOutcomeReportArray;
            var html = "<table id='outcomeReport-table display-outcomeReport-table' class='outcomeReport-table display-outcomeReport-table table table-bordered table-striped table-hover data-table'><thead>";
            for(i=0;i<studentData.header.length; i++)
            {

                html += "<th>"+studentData.header[i]+"</th>";
            }
            html += "</thead><tbody class='outcomeReport-table-body'>";
            $.each(studentData.data, function(index,outcomeData)
            {
                html += '<tr>';
                html += "<td><a href='#'>" + outcomeData.userName + "</a></td>";
                html += '</tr>';

            });
            html += "<tr><td><a href='#'>Average</a></td></tr>";
            html+= '</tbody></table>';
            $('.outcomeReport-div').append(html);
//           $(".outcomeReport-table").append(html);
            $('#display-outcomeReport-table').DataTable({"bPaginate": false});

        }


    }

    function createTableHeader() {

    }

</script>