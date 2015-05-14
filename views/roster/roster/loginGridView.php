<?php
use kartik\date\DatePicker;

$this->title = 'Login Grid View';
$this->params['breadcrumbs'][] = $this->title;

?>
<!DOCTYPE html>


<script language="javascript">

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
//        console.log(JSON.parse(response));
//        alert(response.status);
        var data = JSON.parse(response);

        var end=data.endDate;
        var start=data.startDate;
      //  console.log(start);

        if (data.status) {

        }
        <?php
        $start =  strtotime($start);
        $end = strtotime($end);

        $dates = array();
        for ($time=$start;$time<$end;$time+=1) {
        $dates[] = tzdate("n/d",$time);
        }
        ?>
    <tr>
        <th>Name</th>
        <?php
            foreach ($dates as $currentDate1) {
                echo '<th>'.$currentDate1.'</th>';
//            }?>
  }
</script>

<body>

<div class=mainbody>
  <div class="headerwrapper"></div>
  <div class="midwrapper">
      <div id="headerlogo" class="hideinmobile" onclick="mopen('homemenu',1)" onmouseout="mclosetime()"></div>
      <div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()"></div>


      <div id="headerlogingrid" class="pagetitle"><h2>Login Grid View</h2></div>
      <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
      <p>Showing Number of Logins May 5, 2015, 12:00 am through May 11, 2015, 5:46 pm</p>

      <div class="pull-left select-text-margin">
          <a>Show previous week.</a>&nbsp;&nbsp;<a>Show following week.</a> &nbsp;&nbsp;
          <div class="pull-right"> Show</div>
      </div>


      <div class="col-lg-3 pull-left" id="datepicker-id">
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
        <thead>
        <?php
?>
<!--            <th>5/05</th>-->
<!--            <th>5/06</th>-->
<!--            <th>5/07</th>-->
<!--            <th>5/08</th>-->
<!--            <th>5/09</th>-->
<!--            <th>5/10</th>-->
<!--            <th>5/11</th>-->
        </tr>
        </thead>
    </table>


    <p>Note: Be aware that login sessions last for 24 hours, so if a student logins in Wednesday at 7pm and never
        closes their browser, they can continue using the same session on the same computer until 7pm Thursday.</p>

    <div class="clear"></div>
</div>
<div class="footerwrapper"></div>
</div>
</body>
</html>
