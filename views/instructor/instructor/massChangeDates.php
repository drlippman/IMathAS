<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
use kartik\time\TimePicker;
use kartik\date\DatePicker;
$this->title = AppUtility::t("Mass Change Dates", false);
$this->params['breadcrumbs'][] = $this->title;
$courseId = $course->id;
$imasroot2 = AppUtility::getHomeURL();
$imasroot = AppUtility::getURLFromHome('instructor' ,'instructor/mass-change-dates?cid='.$courseId.'&orderby='.$orderby);
$imasroot1 = AppUtility::getURLFromHome('instructor', 'instructor/mass-change-dates?cid='.$courseId.'&filter='.$filter)
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course', 'course/course?cid='.$course->id]]); ?>
</div>

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]); ?>
</div>
<div class="tab-content shadowBox">
<?php
if ($overwriteBody == 1) {
    echo $body;
} else {

    $shortdays = array("Su","M","Tu","W","Th","F","Sa");
    function getshortday($atime) {
        global $shortdays;
        return $shortdays[AppUtility::tzdate('w',$atime)];
    }

    $availnames = array(_("Hidden"),_("By Dates"),_("Always"));

    echo '<script type="text/javascript">';
    echo 'var basesdates = new Array(); var baseedates = new Array(); var baserdates = new Array();';
    echo '</script>';

    echo "<script type=\"text/javascript\">
    var filteraddr = \"$imasroot\";";

    echo "var orderaddr = \"$imasroot1\";</script>";  ?>
<div class="col-md-12">
    <div class="col-md-6">
        <?php echo '<div class="col-md-3">Order by</div>
        <div class="col-md-6">
         <select id="orderby" class="form-control col-md-4" onchange="chgorderby()">';
        echo '<option value="0" ';
        if ($orderby == 0) {
            echo 'selected="selected"';
        }
        echo '>Start Date</option>';
        echo '<option value="1" ';
        if ($orderby==1) {
            echo 'selected="selected"';
        }
        echo '>End Date</option>';
        echo '<option value="2" ';
        if ($orderby==2) {
            echo 'selected="selected"';
        }
        echo '>Name</option>';
        echo '<option value="3" ';
        if ($orderby==3) {
            echo 'selected="selected"';
        }

        echo '>Course page</option>';
        echo '</select> </div>';
        ?>
    </div>
    <div class="col-md-6">
        <?php
        echo '<div class="col-md-3">Filter by type</div>
        <div class="col-md-6">
        <select id="filter" class="form-control col-md-4" onchange="filteritems()">';
        echo '<option value="all" ';
        if ($filter=='all') {echo 'selected="selected"';}
        echo '>All</option>';
        echo '<option value="assessments" ';
        if ($filter=='assessments') {echo 'selected="selected"';}
        echo '>Assessments</option>';
        echo '<option value="inlinetext" ';
        if ($filter=='inlinetext') {echo 'selected="selected"';}
        echo '>Inline Text</option>';
        echo '<option value="linkedtext" ';
        if ($filter=='linkedtext') {echo 'selected="selected"';}
        echo '>Linked Text</option>';
        echo '<option value="forums" ';
        if ($filter=='forums') {echo 'selected="selected"';}
        echo '>Forums</option>';
        echo '<option value="wikis" ';
        if ($filter=='wikis') {echo 'selected="selected"';}
        echo '>Wikis</option>';
        echo '<option value="blocks" ';
        if ($filter=='blocks') {echo 'selected="selected"';}
        echo '>Blocks</option>';
        echo '</select></div>';?>
    </div>
</div>

<?php
    echo "<p class='col-md-12'><input type=checkbox id=\"onlyweekdays\" checked=\"checked\"> Shift by weekdays only</p>";
    echo "<div class='col-md-12'>Once changing dates in one row, you select <i>Send down date and time change</i> from the Action pulldown to send the date change ";
    echo "difference to all rows below.  You can select <i>Copy down time</i> or <i>Copy down date &amp; time</i>to copy the same time/date to all rows below.  ";
    echo "If you click the checkboxes on the left, you can limit the update to those items. ";
    echo "Click the <img src=\"$imasroot2/img/swap.gif\"> icon in each cell to swap from ";
    echo "Always/Never to Dates.  Swaps to/from Always/Never and Show changes cannot be sent down the list, but you can use the checkboxes and the pulldowns to change those settings for many items at once.</div>";
    echo "<form id=\"qform\">";

    echo '<div class="col-md-12">
    <div class="col-md-4">
    <div class="col-md-2">Check</div>
    <a href="#" class="col-md-1" onclick="return chkAllNone(\'qform\',\'all\',true)">All</a>
    <a href="#"  class="col-md-1" onclick="return chkAllNone(\'qform\',\'all\',false)">None</a></div> ';?>
    <div class="col-md-8">
<?php
    echo 'Change selected items
    <select id="swaptype" onchange="chgswaptype(this)">
        <option value="s">Start Date</option>
        <option value="e">End Date</option>
        <option value="r">Review Date</option>
        <option value="a">Show</option>
    </select>';
    echo ' to <select id="swapselected">
        <option value="always">Always</option>
        <option value="dates">Dates</option>
    </select>';
    echo ' <input type="button" value="Go" onclick="MCDtoggleselected(this.form)" /> &nbsp;';
    echo ' <button type="button" onclick="submittheform()">'._("Save Changes").'</button></div>';

    if ($picicons) {
        echo '<table class=gb>
        <thead>
        <tr><th></th>
            <th class="col-md-1">Name</th>
            <th class="col-md-1">Show</th>
            <th class="col-md-3">Start Date</th>
            <th class="col-md-3">End Date</th>
            <th class="col-md-3">Review Date</th>
            <th class="col-md-2">Send Date Chg / Copy Down List</th>
        </thead>
        <tbody>';
    } else {
        echo '<table class=gb>
        <thead>
        <tr><th></th>
            <th class="col-md-1">Name</th>
            <th class="col-md-1">Type</th>
            <th>Show</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Review Date</th>
            <th>Send Date Chg / Copy Down List</th>
        </thead><tbody>';
    }

    foreach ($keys as $i) {
        echo '<tr class=grid>';
        echo '<td>';
        echo "<input type=\"checkbox\" id=\"cb$cnt\" value=\"".strlen($pres[$i])."\" ";
        if ($types[$i]=='Block') {echo 'onchange="MCDselectblockgrp(this,'.strlen($pres[$i]).')"';}
        echo "/></td>";
        if ($filter=='all') {
            echo '<td class="mcind'.strlen($pres[$i]).' togdishid'.($avails[$i]==0?' dis':'').'">';
        } else {
            echo '<td class="togdishid'.($avails[$i]==0?' dis':'').'">';
        }
        if ($picicons>0) {
            echo "<input type=hidden id=\"type$cnt\" value=\"{$types[$i]}\"/>";
            echo '<img alt="'.$types[$i].'" title="'.$types[$i].'" src="'.$imasroot.'/img/';
            switch ($types[$i]) {
                case 'Calendar': echo $CFG['CPS']['miniicons']['calendar']; break;
                case 'InlineText': echo $CFG['CPS']['miniicons']['inline']; break;
                case 'Link': echo $CFG['CPS']['miniicons']['linked']; break;
                case 'Forum': echo $CFG['CPS']['miniicons']['forum']; break;
                case 'Wiki': echo $CFG['CPS']['miniicons']['wiki']; break;
                case 'Block': echo $CFG['CPS']['miniicons']['folder']; break;
                case 'Assessment': echo $CFG['CPS']['miniicons']['assess']; break;
                case 'Drill': echo $CFG['CPS']['miniicons']['drill']; break;
            }
            echo '"/><div>';
        }
        echo "{$names[$i]}<input type=hidden id=\"id$cnt\" value=\"{$ids[$i]}\"/></div>";
        echo "<script> basesdates[$cnt] = ";
        //if ($startdates[$i]==0) { echo '"NA"';} else {echo $startdates[$i];}
        echo $startdates[$i];

        echo "; baseedates[$cnt] = ";
        //if ($enddates[$i]==0 || $enddates[$i]==2000000000) { echo '"NA"';} else {echo $enddates[$i];}
        echo $enddates[$i];
        echo "; baserdates[$cnt] = ";
        //if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {echo '"NA"';} else { echo $reviewdates[$i];}
        if ($reviewdates[$i]==-1) {echo '"NA"';} else { echo $reviewdates[$i];}
        echo ";</script>";
        echo "</td>";
        if ($picicons==0) {
            echo "<td>";
            echo "{$types[$i]}<input type=hidden id=\"type$cnt\" value=\"{$types[$i]}\"/>";
            echo "</td>";
        }

        echo '<td><span class="nowrap"><img src="'.$imasroot2.'img/swap.gif" onclick="MCDtoggle(\'a\','.$cnt.')"/>
        <span id="availname'.$cnt.'">'.$availnames[$avails[$i]].'</span>
        <input type="hidden" id="avail'.$cnt.'" value="'.$avails[$i].'"/></span></td>';

        echo "<td class=\"togdis".($avails[$i]!=1?' dis':'')."\"><img src=\"$imasroot2/img/swap.gif\" onclick=\"MCDtoggle('s',$cnt)\"/>";
        if ($startdates[$i]==0) {
            echo "<input type=hidden id=\"sdatetype$cnt\" name=\"sdatetype$cnt\" value=\"0\"/>";
        } else {
            echo "<input type=hidden id=\"sdatetype$cnt\" name=\"sdatetype$cnt\" value=\"1\"/>";
        }
        if ($startdates[$i]==0) {
            echo "<span id=\"sspan0$cnt\" class=\"show\">Always</span>";
        } else {
            echo "<span id=\"sspan0$cnt\" class=\"hide\">Always</span>";
        }
        if ($startdates[$i]==0) {
            echo "<span id=\"sspan1$cnt\" class=\"hide\">";
        } else {
            echo "<span id=\"sspan1$cnt\" class=\"show\">";
        }
        if ($startdates[$i]==0) {
            $startdates[$i] = time();
            $sdate = AppUtility::tzdate("m/d/Y",$startdates[$i]);

        } else {
            $sdate = AppUtility::tzdate("m/d/Y",$startdates[$i]);
            $stime = AppUtility::tzdate("g:i a",$startdates[$i]);
        }
        echo "<input type=hidden size=10 onblur=\"ob(this)\"/>";
        echo "<span id=\"sd$cnt\">".getshortday($startdates[$i]).'</span>';
                                        echo '<div class = "col-md-10 time-input"">';
                                        echo DatePicker::widget([
                                            'name' => 'sdate'.$cnt,
                                            'type' => DatePicker::TYPE_COMPONENT_APPEND,
                                            'value' => $sdate,
                                            'id' => 'sdate'.$cnt,
                                            'removeButton' => false,
                                            'pluginOptions' => [
                                                'autoclose' => true,
                                                'format' => 'mm/dd/yyyy']
                                        ]);
                                        echo '</div>';

//        echo "<input type=hidden size=8 id=\"stime$cnt\" name=\"stime$cnt\" value=\"$stime\">";

                                        echo '<label class="end pull-left non-bold"> at </label>';
                                        echo '<div class="col-md-12 padding-top">';
                                        echo TimePicker::widget([
                                            'name' => 'postTime',
                                            'id' => 'stime'.$cnt,
                                            'value' => $stime,
                                            'pluginOptions' => [
                                                'showSeconds' => false,
                                                'class' => 'time'
                                            ]
                                        ]);
                                        echo '</div>';

        echo '</span></td>';

        echo "<td class=\"togdis".($avails[$i]!=1?' dis':'')."\"><img src=\"$imasroot2/img/swap.gif\"  onclick=\"MCDtoggle('e',$cnt)\"/>";
        if ($enddates[$i]==2000000000) {
            echo "<input type=hidden id=\"edatetype$cnt\" name=\"edatetype$cnt\" value=\"0\"/>";
        } else {
            echo "<input type=hidden id=\"edatetype$cnt\" name=\"edatetype$cnt\" value=\"1\"/>";
        }
        if ($enddates[$i]==2000000000) {
            echo "<span id=\"espan0$cnt\" class=\"show\">Always</span>";
        } else {
            echo "<span id=\"espan0$cnt\" class=\"hide\">Always</span>";
        }
        if ($enddates[$i]==2000000000) {
            echo "<span id=\"espan1$cnt\" class=\"hide\">";
        } else {
            echo "<span id=\"espan1$cnt\" class=\"show\">";
        }

        if ($enddates[$i]==2000000000) {
            $enddates[$i]  = $startdates[$i] + 7*24*60*60;
            $edate = AppUtility::tzdate("m/d/Y",$enddates[$i]);
        } else {
            $edate = AppUtility::tzdate("m/d/Y",$enddates[$i]);
            $etime = AppUtility::tzdate("g:i a",$enddates[$i]);
        }
        echo "<input type=hidden size=10  onblur=\"ob(this)\"/>";
        echo "<span id=\"ed$cnt\">".getshortday($enddates[$i]).'</span>';
        //echo ") <a href=\"#\" onClick=\"cal1.select(document.forms[0].edate$cnt,'anchor2$cnt','MM/dd/yyyy',document.forms[0].edate$cnt.value); return false;\" NAME=\"anchor2$cnt\" ID=\"anchor2$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
                echo '<div class = "col-md-10 time-input">';
                echo DatePicker::widget([
                    'name' => 'edate'.$cnt,
                    'id' => 'edate'.$cnt,
                    'type' => DatePicker::TYPE_COMPONENT_APPEND,
                    'value' => $edate,
                    'removeButton' => false,
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'mm/dd/yyyy']
                ]);
                echo '</div>';
//        echo " <input type=hidden size=8 id=\"etime$cnt\" name=\"etime$cnt\" value=\"$etime\">";
                echo '<label class="end pull-left non-bold"> at </label>';
                echo '<div class=" col-md-12 padding-top">';
                echo TimePicker::widget([
                    'name' => 'etime'.$cnt,
                    'value' => $etime,
                    'id' => 'etime'.$cnt,
                    'pluginOptions' => [
                        'showSeconds' => false,
                        'class' => 'time'
                    ]
                ]);
                echo '</div>';

        echo '</span></td>';

        echo "<td class=\"togdis".($avails[$i]!=1?' dis':'')."\">";
        if ($types[$i]=='Assessment') {
            echo "<img src=\"$imasroot2/img/swap.gif\"  onclick=\"MCDtoggle('r',$cnt)\"/>";
            if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
                echo "<input type=hidden id=\"rdatetype$cnt\" name=\"rdatetype$cnt\" value=\"0\"/>";
            } else {
                echo "<input type=hidden id=\"rdatetype$cnt\" name=\"rdatetype$cnt\" value=\"1\"/>";
            }
            if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
                echo "<span id=\"rspan0$cnt\" class=\"show\">";
            } else {
                echo "<span id=\"rspan0$cnt\" class=\"hide\">";
            }
            echo "<input type=radio name=\"rdatean$cnt\" value=\"0\" id=\"rdateanN$cnt\" ";
            if ($reviewdates[$i]!=2000000000) {
                echo 'checked=1';
            }
            echo " />Never <input type=radio name=\"rdatean$cnt\" value=\"2000000000\"  id=\"rdateanA$cnt\"  ";
            if ($reviewdates[$i]==2000000000) {
                echo 'checked=1';
            }
            echo " />Always</span>";

            if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
                echo "<span id=\"rspan1$cnt\" class=\"hide\">";
            } else {
                echo "<span id=\"rspan1$cnt\" class=\"show\">";
            }
            if ($reviewdates[$i]==0 || $reviewdates[$i]==2000000000) {
                $reviewdates[$i] = $enddates[$i] + 7*24*60*60;
                $rdate = AppUtility::tzdate("m/d/Y",$reviewdates[$i]);
            } else {
                $rdate = AppUtility::tzdate("m/d/Y",$reviewdates[$i]);
                $rtime = AppUtility::tzdate("g:i a",$reviewdates[$i]);
            }
            echo "<input type=hidden onblur=\"ob(this)\"/>(";
            echo "<span id=\"rd$cnt\">".getshortday($reviewdates[$i]).'</span>';

            echo '<div class = "col-md-10 time-input">';
            echo DatePicker::widget([
                'name' => 'rdate'.$cnt,
                'id' => 'rdate'.$cnt,
                'type' => DatePicker::TYPE_COMPONENT_APPEND,
                'value' => $rdate,
                'removeButton' => false,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'mm/dd/yyyy']
            ]);
            echo '</div>';
            echo '<label class="end pull-left non-bold"> at </label>';
            echo '<div class=" col-md-12">';
            echo TimePicker::widget([
                'name' => 'rtime'.$cnt,
                'id' => 'rtime'.$cnt,
                'value' => $rtime,
                'pluginOptions' => [
                    'showSeconds' => false,
                    'class' => 'time'
                ]
            ]);
            echo '</div>';

            echo "</span>";
        }
        echo '</td>';
        echo "<td class='col-md-3'>
        <select id=\"sel$cnt\" class='form-control col-sm-10' onchange=\"senddownselect(this);\">
        <option value=\"0\" selected=\"selected\">Action...</option>";
        echo '<option value="1">Send down date &amp; time changes</option>';
        echo '<option value="2">Copy down times only</option>';
        echo '<option value="3">Copy down dates &amp; times</option>';
        echo '<option value="4">Copy down start date &amp; time</option>';
        echo '<option value="5">Copy down end date &amp; time</option>';
        echo '<option value="6">Copy down review date &amp; time</option>';
        echo '</select></td>';
        echo "</tr>";
        $cnt++;
    }
    echo '</tbody></table>';
    echo '</form>';
    echo "<form id=\"realform\" method=post action=\"mass-change-dates?cid=$courseId\" onsubmit=\"prepforsubmit(this)\">";
    echo "<input type=hidden id=\"chgcnt\" name=\"chgcnt\" value=\"$cnt\" />";
    echo '<input type=submit value="Save Changes"/>';
    echo '</form>';
}
?>
</div>

