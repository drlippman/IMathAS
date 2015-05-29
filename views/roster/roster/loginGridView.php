<?php
use kartik\date\DatePicker;
use app\components\AppUtility;
use app\controllers\AppController;

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
                'value' => date("m-d-Y",strtotime("-1 week +1 day")),
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



