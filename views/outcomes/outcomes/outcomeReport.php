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

     var type = 1;
    function outcomeReportResponse(response)
    {
        response = JSON.parse(response);


        var html="";
         console.log(response);
        if(response.status == 0)
        {
            var headerData = response.data.outcomeInfo;
            var finalData = response.data.finalData;
            var report = response.data.report;
            var outc = response.data.outCome;

             html +='<table id="myTable" class="gb"><thead><tr><th>'+finalData[0][0][0] +'</th>';
            var arr = '"S"';
            $.each(outc, function(index,data)
            {
                 html +='<th>'+headerData[data]+'<br/><a class="small" href="#">[Details]</a></th>';
                arr += ',"N"';
            });
            html += '</tr>';


            for(var i=0;i<finalData.length;i++)
            {
                html +='<tr class="'+(i%2==0?'even':'odd')+'">';
                html +='<td><a href="#"></a></td>';
                $.each(outc, function(index,data)
                {

                    if((finalData[i][3][type]) && (finalData[i][3][type][data]))
                    {
                        html += '<td>'+ round(100*finalData[i][3][type][data],1) +'%</td>';
                    }
                    else
                    {
                        html += '<td>-</td>';
                    }

                })
                html += '</tr>';

            }
            html +='</tbody></table>';
            html += initSortTable('myTable',Array(sarr),true,false);
            $('.outcomeReport-div').append(html);
            html +=' <p>Note:  The outcome performance in each gradebook category is weighted based on gradebook weights to produce these overview scores</p>';




        }
 }

    function createTableHeader()
    {

    }

</script>