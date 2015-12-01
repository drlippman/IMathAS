<?php
use app\components\AppUtility;
$this->title = 'View Group Scores';
?>
<?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id]]); ?>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<?php
$minscore = $assessment['minscore'];
$timelimit = $assessment['timelimit'];
$deffeedback = $assessment['deffeedback'];
$enddate = $assessment['enddate'];
$name = $assessment['name'];
$defpoints = $assessment['defpoints'];
$itemorder = $assessment['itemorder'];
$groupsetid =$assessment['groupsetid'];
$deffeedback = explode('-',$deffeedback);
$assessmenttype = $deffeedback[0];

$aitems = explode(',',$itemorder);
foreach ($aitems as $k=>$v) {
    if (strpos($v,'~')!==FALSE) {
        $sub = explode('~',$v);
        if (strpos($sub[0],'|')===false) { //backwards compat
            $aitems[$k] = $sub[0];
            $aitemcnt[$k] = 1;

        } else {
            $grpparts = explode('|',$sub[0]);
            $aitems[$k] = $sub[1];
            $aitemcnt[$k] = $grpparts[0];
        }
    } else {
        $aitemcnt[$k] = 1;
    }
}
$totalpossible = 0;
foreach ($questions as $r) {
    if (($k = array_search($r['id'],$aitems))!==false) { //only use first item from grouped questions for total pts
        if ($r['points']==9999) {
            $totalpossible += $aitemcnt[$k]*$defpoints; //use defpoints
        } else {
            $totalpossible += $aitemcnt[$k]*$r['points']; //use points from question
        }
    }
}
echo '<div class="tab-content shadowBox padding-one-em">';
echo '<div id="headerisolateassessgrade" class="pagetitle"><h2>';
echo "Group grades for $name</h2></div>";
echo "<p>$totalpossible points possible</p>";
$scoredata = array();
foreach ($AssessmentGroups as $line) {
    $scoredata[$line['agroupid']] = $line;
}
echo "<table id=myTable class='table table-bordered table-striped table-hover data-table'>
  <thead>
    <tr>
      <th>Group</th>";
echo "<th>Grade</th>
      <th>%</th>
      <th>Feedback</th>
    </tr>
  </thead>
  <tbody>";
$now = time();
$lc = 1;
$n = 0;
$tot = 0;
if($groupnames)
{
    natsort($groupnames);

foreach ($groupnames as $gid=>$gname) {
    if ($lc%2!=0) {
        echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">";
    } else {
        echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">";
    }
    $lc++;
    echo "<td class='word-break-break-all'>$gname</td>";
    if (!isset($scoredata[$gid])) {
        echo "<td>-</td><td>-</td><td></td>";
        continue;
    } else {
        $line = $scoredata[$gid];
    }
    $total = 0;
    $sp = explode(';',$line['bestscores']);
    $scores = explode(",",$sp[0]);
    if (in_array(-1,$scores)) { $IP=1;} else {$IP=0;}
    for ($i=0;$i<count($scores);$i++) {
        $total += getpts($scores[$i]);
    }
    $timeused = $line['endtime']-$line['starttime'];

    if ($line['id']==null) { ?>
         <td class='word-break-break-all'><a href="<?php echo  AppUtility::getURLFromHome('gradebook','gradebook/gradebook-view-assessment-details?gbmode='.$gbmode.'&cid='.$course->id.'&asid=new&uid='.$line['userid'].'&from=gisolate&aid='.$aid);?> ">-</a></td><td>-</td><td></td>
    <?php } else { ?>
         <td class='word-break-break-all'><a href="<?php echo  AppUtility::getURLFromHome('gradebook','gradebook/gradebook-view-assessment-details?gbmode='.$gbmode.'&cid='.$course->id.'&asid='.$line['id'].'&uid='.$line['userid'].'&from=gisolate&aid='.$aid)?> ">
        <?php //if ($total<$minscore) {
        if (($minscore<10000 && $total<$minscore) || ($minscore>10000 && $total<($minscore-10000)/100*$totalpossible)) {
            echo "{$total}&nbsp;(NC)";
        } else 	if ($IP==1 && $enddate>$now) {
            echo "{$total}&nbsp;(IP)";
        } else	if (($timelimit>0) &&($timeused > $timelimit*$line['timelimitmult'])) {
            echo "{$total}&nbsp;(OT)";
        } else if ($assessmenttype=="Practice") {
            echo "{$total}&nbsp;(PT)";
        } else {
            echo "{$total}";
            $tot += $total;
            $n++;
        }

        echo "</a></td>";
        if ($totalpossible>0) {
            echo '<td class="word-break-break-all">'.round(100*($total)/$totalpossible,1).'%</td>';
        } else {
            echo '<td></td>';
        }
        echo "<td class='word-break-break-all'>{$line['feedback']}</td>";
    }
    echo "</tr>";
}
        }
echo '<tr><td class="word-break-break-all">Average</td>'; ?>
 <td class='word-break-break-all'><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis?cid='.$course->id.'&aid='.$aid.'&from=gisolate');?>">
<?php if ($n>0) {
    echo round($tot/$n,1);
} else {
    echo '-';
}
if ($totalpossible > 0 ) {
    $pct = round(100*($tot/$n)/$totalpossible,1).'%';
} else {
    $pct = '';
}
echo "</a></td><td class='word-break-break-all'>$pct</td></tr>";
echo "</tbody></table>";
echo '</div>';
//echo "<script type='javascript'> initSortTable('myTable',Array('S','N','N'),true);</script>";


function getpts($sc) {
    if (strpos($sc,'~')===false) {
        if ($sc>0) {
            return $sc;
        } else {
            return 0;
        }
    } else {
        $sc = explode('~',$sc);
        $tot = 0;
        foreach ($sc as $s) {
            if ($s>0) {
                $tot+=$s;
            }
        }
        return round($tot,1);
    }
}
?>
