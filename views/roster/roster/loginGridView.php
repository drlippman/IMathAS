<?php
use kartik\date\DatePicker;

$this->title = 'Login Grid View';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = ['label' => 'Roster', 'url' => ['/roster/roster/student-roster?cid='.$_GET['cid']]];
$this->params['breadcrumbs'][] = $this->title;

?>
<p id="demo"></p>
<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">
        <div id="headerlogo" class="hideinmobile" onclick="mopen('homemenu',1)" onmouseout="mclosetime()"></div>
        <div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()"></div>
        <div id="headerlogingrid" class="pagetitle"><h2>Login Grid View</h2></div>
        <input type="hidden" id="course-id" value="<?php echo $course->id ?>">
        <p>Showing Number of Logins <label id="first-date-label"></label>  through  <label id="last-date-label"></label>
        <div class="pull-left select-text-margin">
           <a id="previous-link" href="#">Show previous week.</a>&nbsp;&nbsp;<a id="following-link" href="#">Show following week.</a>&nbsp;&nbsp;
            <div class="pull-right"> Show</div>
        </div>
        <div class="col-lg-3 pull-left" id="datepicker-id">
            <?php
            echo DatePicker::widget([
                'name' => 'dp_3',
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => date("m-d-Y",strtotime("-1 week")),
                'pluginOptions' => [

                    'autoclose' => true,
                    'format' => 'mm-dd-yyyy'
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
            'value' => date("m-d-Y"),
            'pluginOptions' => [
                'autoclose' => true,
                'format' => 'mm-dd-yyyy' ]
        ]);
        ?>
    </div>
    <div>
        <input type="submit" id="go-button" name="daterange" value="Go"/>
    </div>
    <div id="table_placeholder"></div>

    <p>Note: Be aware that login sessions last for 24 hours, so if a student logins in Wednesday at 7pm and never
        closes their browser, they can continue using the same session on the same computer until 7pm Thursday.</p>
    <div class="clear"></div>
</div>
<div class="footerwrapper"></div>
</div>

<script language="javascript" type="text/javascript">

    $( document ).ready(function() {
        var startDate = $( "#datepicker-id input" ).val();
        var endDate = $( "#datepicker-id1 input" ).val();

        $("#first-date-label").text(startDate);
        $('#last-date-label').text(endDate);

        var course_id =  $( "#course-id" ).val();
        var transferData = {newStartDate: startDate,newEndDate: endDate,cid: course_id};

        jQuerySubmit('login-grid-view-ajax', transferData, 'loginGridViewSuccess');

        $("#go-button").click(function () {
            var startDate = $( "#datepicker-id input" ).val();
            var endDate = $( "#datepicker-id1 input" ).val();

            $("#first-date-label").text(startDate);
            $('#last-date-label').text(endDate);

            var course_id =  $( "#course-id" ).val();
            var transferData = {newStartDate: startDate,newEndDate: endDate,cid: course_id};

            jQuerySubmit('login-grid-view-ajax', transferData, 'loginGridViewSuccess');
        });

    });

    function pad(number, length) {

        var str = '' + number;
        while (str.length < length) {
            str = '0' + str;
        }
        return str;
    }

    function toggleDate(selector, dayDiff, adjustment) {
        inputString = $("#"+selector).val();
        var dString = inputString.split('-');
        var dt = new Date(dString[2], dString[0] - 1, dString[1]);

        dayDiff = parseInt(dayDiff);
        if (adjustment == 'add') {
            dt.setDate(dt.getDate()+dayDiff);
        } else{
            dt.setDate(dt.getDate()-dayDiff);
        }

        var finalDate = pad(dt.getMonth()+1,2) + "-" + pad(dt.getDate(),2) + "-" + dt.getFullYear();
        return finalDate;
    }
    function lastDate(inputString, dayDiff, adjustment) {
         var dString = inputString.split('-');
        var dt = new Date(dString[2], dString[0] - 1, dString[1]);

        dayDiff = parseInt(dayDiff);
        if (adjustment == 'add') {
            dt.setDate(dt.getDate()+dayDiff);
        } else{
            dt.setDate(dt.getDate()+dayDiff);
        }
        var finalDate = pad(dt.getMonth()+1,2) + "-" + pad(dt.getDate(),2) + "-" + dt.getFullYear();
        return finalDate;
    }

    $( document ).ready(function() {
        previousWeekHandler();
        nextWeekHandler();
    });

    function previousWeekHandler(){
        var daysInAWeek = 6;
        $("#previous-link").click(function () {
            finalDate = toggleDate('w0', daysInAWeek, 'deduct');
            $( "#w0").val(finalDate);
            $("#first-date-label").text(finalDate);
            finalDate = lastDate(finalDate, daysInAWeek, 'deduct');
            $('#count').val(finalDate);
            $( "#w1").val(finalDate);
            $('#go-button').trigger('click');
            $('#last-date-label').text(finalDate);
        });
    }
    function nextWeekHandler(){
        var daysInAWeek = 6;
        $("#following-link").click(function () {
            finalDate = toggleDate('w0', daysInAWeek, 'add');
            $( "#w0").val(finalDate);
            finalDate = lastDate(finalDate, daysInAWeek, 'add');
            $( "#w1").val(finalDate);
            $('#go-button').trigger('click');
            $("#last-date-label").text(finalDate);
        });
    }

    function loginGridViewSuccess(response) {
        var data = JSON.parse(response);
        data = data.data;
        var tableString = '';
        headerArray = data.header;
        rows = data.rows;
        tableString = "<table border='1px'><tr>";
        for(i=0; i<headerArray.length; i++){
            tableString = tableString + "<th>" + headerArray[i]+"</th>";
        }
        tableString = tableString+ "</tr>";
        $.each( rows, function(id, studata){
            name = studata.name;
            rows = studata.row;
            tableString = tableString+ "<tr>";
             tableString = tableString + "<td>" + name + "</td>";
            for(i=1; i<headerArray.length; i++) {
                var headerVal = headerArray[i];
                tableString = tableString + "<td>" + rows[headerVal] + "</td>";
            }
            tableString = tableString+ "</tr>";
        });
        tableString = tableString + "</table>";
        $('#table_placeholder').html(tableString);
    }
</script>

