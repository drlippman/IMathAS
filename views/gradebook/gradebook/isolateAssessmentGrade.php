<?php
use app\components\AppUtility;
$this->title = AppUtility::t('View Scores',false);?>

<div class="item-detail-header">
 <?php
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?gbmode='.$gbmode.'&cid='.$course->id], 'page_title' => $this->title]);
?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
    </div>
</div>
<?php
echo '<div class="tab-content shadowBox non-nav-tab-item">';
 ?>
    <div class="item-analysis">
    <br><div class="cpmid"><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis?cid='.$course->id .'&amp;aid='.$assessmentId );?> ">View Item Analysis</a></div>
    <div id="headerisolateassessgrade" class="pagetitle"><h2>
 <?php   echo "Grades for $name</h2></div>"; ?>
    <p><?php echo $totalpossible;echo ' '; AppUtility::t('points possible')?></p>
    <table id=myTable class=table table-bordered table-striped table-hover data-table>
         <thead>
         <tr>
             <th><?php AppUtility::t('Name')?></th>
             <?php if ($hassection)
             { ?>
             <th><?php AppUtility::t('Section')?></th>
             <?php }  ?>
             <th><?AppUtility::t('Grade')?></th>
             <th>%</th>
             <th><?php AppUtility::t('Last Change')?></th>
             <th><?php AppUtility::t('Time Spent (In Questions)')?></th>
             <th><?php AppUtility::t('Feedback')?></th>
         </tr>
         </thead>
        <tbody>
         <?php
            $now = time();
            $lc = 1;
            $n = 0;
            $ntime = 0;
            $tot = 0;
            $tottime = 0;
            $tottimeontask = 0;
            foreach ($studentData as $line) {
                if ($lc % 2 != 0) {
                    echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">";
                } else {
                    echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">";
                }
                $lc++;
                if ($line['locked'] > 0) {
                    echo '<td><span style="text-decoration: line-through;">';
                    echo "{$line['LastName']}, {$line['FirstName']}</span></td>";
                } else {
                    echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
                }
                if ($hassection) {
                    echo "<td>{$line['section']}</td>";
                }
                $total = 0;
                $sp = explode(';', $line['bestscores']);
                $scores = explode(",", $sp[0]);
                if (in_array(-1, $scores)) {
                    $IP = 1;
                } else {
                    $IP = 0;
                }
                for ($i = 0; $i < count($scores); $i++) {
                    $total += getpts($scores[$i]);
                }
                $timeused = $line['endtime'] - $line['starttime'];
                $timeontask = round(array_sum(explode(',', str_replace('~', ',', $line['timeontask']))) / 60, 1);
                if ($line['id'] == null) {
            ?>
             <td><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/gradebook-view-assessment-details?gbmode='.$gbmode.'&cid='.$course->id.'&asid=new&uid='.$line['userid'].'&from=isolate&aid='.$assessmentId); ?>  ">-</a></td>
             <td>-</td>
             <td></td>
             <td></td>
             <td></td>
             <td><a href="#" >-</a></td>
             <td>-</td>
             <td></td>
             <td></td>
             <td></td>
        <? } else
         {
                if (isset($exceptions[$line['userid']])) {
                    $thisenddate = $exceptions[$line['userid']][0];
                } else {
                    $thisenddate = $enddate;
                } ?>
             <td><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/gradebook-view-assessment-details?gbmode='.$gbmode.'&cid='.$course->id.'&asid='.$line['id'].'&uid='.$line['userid'].'&from=isolate&aid='.$assessmentId);?>  ">
                             <?php if ($thisenddate > $now) {
                    echo '<i>' . $total;
                } else {
                    echo $total;
                }
                //if ($total<$minscore) {
                if (($minscore < 10000 && $total < $minscore) || ($minscore > 10000 && $total < ($minscore - 10000) / 100 * $totalpossible)) {
                    echo "&nbsp;(NC)";
                } else if ($IP == 1 && $thisenddate > $now) {
                    echo "&nbsp;(IP)";
                } else if (($timelimit > 0) && ($timeused > $timelimit * $line['timelimitmult'])) {
                    echo "&nbsp;(OT)";
                } else if ($assessmenttype == "Practice") {
                    echo "&nbsp;(PT)";
                } else {
                    $tot += $total;
                    $n++;
                }
                if ($thisenddate > $now) {
                    echo '</i>';
                }
                echo '</a>';
                if (isset($exceptions[$line['userid']])) {
                    if ($exceptions[$line['userid']][1] > 0) { ?>
                         <sup><?php AppUtility::t('LP');?></sup>
                    <?php } else { ?>
                         <sup><?php AppUtility::t('e')?></sup>
                    <?php }
                } ?>
             </td>
            <?php if ($totalpossible > 0)
            {
                echo '<td>' . round(100 * ($total) / $totalpossible, 1) . '%</td>';
            } else {
                echo '<td>&nbsp;</td>';
            }
            if ($line['endtime'] == 0) {
                if ($line['starttime'] == 0) { ?>
                     <td><?php AppUtility::t('Never started')?></td>
             <?php    } else { ?>
                     <td><?php AppUtility::t('Never submitted')?></td>
                <?php }
            } else {
                echo '<td>' . AppUtility::tzdate("n/j/y g:ia", $line['endtime']) . '</td>';
            }
            if ($line['endtime'] == 0 || $line['starttime'] == 0) {
                echo '<td>&nbsp;</td>';
            } else {
                echo '<td>' . round($timeused / 60) . ' min';
                if ($timeontask > 0) {
                    echo ' (' . $timeontask . ' min)';
                    $tottimeontask += $timeontask;
                }
                echo '</td>';
                $tottime += $timeused;
                $ntime++;
            }
            echo "<td>{$line['feedback']}&nbsp;</td>";
        }
        echo "</tr>";
    } ?>
     <tr><td><?php AppUtility::t('Average');?></td>
    <?php if ($hassection) {
        echo '<td></td>';
    } ?>
     <td><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/item-analysis?cid='.$course->id.'&aid='.$assessmentId.'&from=isolate');?>">
    <?php if ($n > 0) {
        echo round($tot / $n, 1);
    } else {
        echo '-';
    }
    if ($totalpossible > 0 && $n > 0) {
        $pct = round(100 * ($tot / $n) / $totalpossible, 1) . '%';
    } else {
        $pct = '-';
    }
    if ($ntime > 0) {
        $timeavg = round(($tottime / $ntime) / 60) . ' min';
        if ($tottimeontask > 0) {
            $timeavg .= ' (' . round($tottimeontask / $ntime) . ' min)';
        }
    } else {
        $timeavg = '-';
    }
    echo "</a></td><td>$pct</td><td></td><td>$timeavg</td><td></td></tr>";
    echo "</tbody></table>";
    if ($hassection) {
        echo "<script type='javascript'> initSortTable('myTable',Array('S','S','N'),true);</script>";
    } else {
        echo "<script type='javascript'> initSortTable('myTable',Array('S','N'),true);</script>";
    } ?>
     <p><?php AppUtility::t('Meanings')?>:  <i><?php AppUtility::t('italics')?></i>-<?php AppUtility::t('available to student, IP-In Progress (some questions unattempted), OT-overtime, PT-practice test, EC-extra credit, NC-no credit')?><br/>
     <sup><?php AppUtility::t('e')?></sup> <?php AppUtility::t('Has exception')?> <sup><?php AppUtility::t('LP')?></sup> <?php AppUtility::t('Used latepass')?>  </p><br>
     </div> </div>
<?php
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
