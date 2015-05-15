<?php
use kartik\date\DatePicker;

$this->title = 'Login Grid View';
$this->params['breadcrumbs'][] = $this->title;

?>
<!DOCTYPE html>

<p id="demo"></p>
<body>

<div class=mainbody>
  <div class="headerwrapper"></div>
  <div class="midwrapper">
      <div id="headerlogo" class="hideinmobile" onclick="mopen('homemenu',1)" onmouseout="mclosetime()"></div>
      <div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()"></div>


      <div id="headerlogingrid" class="pagetitle"><h2>Login Grid View</h2></div>

      <input type="hidden" id="course-id" value="<?php echo $course->id ?>">

      <p>Showing Number of Logins May 5,2015 through May 17,2015

<!--          <input id="startlogincounter">-->

<!--           through  <input id="endlogincounter" > </p>-->

      <div class="pull-left select-text-margin">
          <a>Show previous week.</a>&nbsp;&nbsp;<a>Show following week.</a> &nbsp;&nbsp;
          <div class="pull-right"> Show</div>
      </div>


      <div class="col-lg-3 pull-left" id="datepicker-id">
          <?php

          echo DatePicker::widget([
              'name' => 'dp_3',
              'type' => DatePicker::TYPE_COMPONENT_APPEND,
              'value' => date("d-M-Y",strtotime("-1 week")),
              'pluginOptions' => [
                  'autoclose' => true,
                  'format' => 'dd-M-yyyy'
              ]
          ]);
          ?>
        </div>
    </div>
    <div class="pull-left select-text-margin"> through</div>
    <div class="col-lg-3 pull-left" id="datepicker-id1" >

        <?php
        echo DatePicker::widget([
            'name' => 'dp_3',
            'type' => DatePicker::TYPE_COMPONENT_APPEND,
            'value' => date("d-M-Y"),
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'dd-M-yyyy'
            ]
        ]);

        ?>
    </div>



    <div>
        <input type="submit" id="go-button" name="daterange" value="Go"/>

    </div>

    <table class="gb logingrid" id="myTable">
        <thead class="log-table-head">
        </thead>
            <tbody class="log-table-body">

            </tbody>
    </table>


    <p>Note: Be aware that login sessions last for 24 hours, so if a student logins in Wednesday at 7pm and never
        closes their browser, they can continue using the same session on the same computer until 7pm Thursday.</p>

    <div class="clear"></div>
</div>
<div class="footerwrapper"></div>
</div>
</body>


<script language="javascript" type="text/javascript">

    $( document ).ready(function() {

        $("#go-button").click(function () {
            var startDate = $( "#datepicker-id input" ).val();
            var endDate = $( "#datepicker-id1 input" ).val();

            var course_id =  $( "#course-id" ).val();
            var transferData = {newStartDate: startDate,newEndDate: endDate,cid: course_id};

            jQuerySubmit('login-grid-view-ajax', transferData, 'loginGridViewSuccess');


        });
    });

    function loginGridViewSuccess(response) {
     //   console.log(JSON.parse(response));
        var data = JSON.parse(response);

        var start = data.startDate;
        var end = data.endDate;
        var startDateLabel = new Date(start*1000);
        var endDateLabel = new Date(end*1000);
        $('#startlogincounter').val(startDateLabel);
        $('#endlogincounter').val(endDateLabel);

        if (data.status == 0) {
            var html = "";
            var i;
            html += "<tr><th>Name</th>"
            for (i = start  ; i <= end; i = (i + 86400)) {
                var date = new Date(i*1000);
                var  month = ('0' + (date.getMonth() + 1)).slice(-2);
                var day = ('0' + date.getDate()).slice(-2);

                html += "<th>"+day+"/"+month+"</th>";
            }
            html += "</tr>";
            $('.log-table-head tr').remove();
            $('.log-table-head').append(html);

            var htmlData = '';
            var logs = data.loginLog;
            $.each(logs, function(index, log){

                htmlData += "<tr>"
                $.each(log, function(index, value) {
                    htmlData += "<td>" + value + "</td>";
                });
                htmlData += "<tr>";
            });
            $('.log-table-body').append(htmlData);
        }

    }

</script>
</html>
