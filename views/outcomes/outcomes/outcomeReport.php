<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Outcome Report';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Course Outcomes', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'outcomes/outcomes/add-outcomes?cid=' . $course->id]]); ?>
</div>
<div class = "title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page">
                <?php echo $this->title ?>
            </div>
        </div>
    </div>
</div>
<?php  $typeSel = 'Show for scores '.'<select class="form-control display-inline-block width-fourty-per" id="typeSel" onchange="chgtype()">';
    $typeSel .= '<option value="0" '.($type== 0?'selected="selected"':'').'>'.'Past Due scores'.'</option>';
    $typeSel.= '<option value="1" '.($type== 1?'selected="selected"':'').'>'.'Past Due and Attempted scores'.'</option>';
    $typeSel.= '</select>';?>

<input type="hidden" id="course-id" value="<?php echo $courseId?>">
<div class="tab-content shadowBox padding-two-em">
<?php
    if($report == AppConstant::NUMERIC_ZERO)
    {
        if($headerData && $outc)
        {
            echo '<div class="cpmid">'.$typeSel.'</div>';
            echo '<table id="myTable" class="table table-bordered table-striped table-hover data-table"><thead><th>'.$finalData[0][0][0] .'</th>';
             $arr = '"S"';
            foreach($outc as $data)
            {
            ?>
            <?php
                if(isset($type))
                {
                    $url =  AppUtility::getURLFromHome("outcomes","outcomes/outcome-report?cid=".$courseId."&report=1".'&selectedOutcome='.$data.'&type='.$type);
                }else
                {
                    $url =  AppUtility::getURLFromHome("outcomes","outcomes/outcome-report?cid=".$courseId."&report=1".'&selectedOutcome='.$data);
                 }
                ?>
                <th><?php echo $headerData[$data]?><br/><a class="small" href="<?php echo $url?>">[Details]</a></th>
            <?php  $arr .= ',"N"';
            }
            echo '</tr><tbody>';
            for($i=1;$i< count($finalData);$i++)
            {
                echo'<tr class="'.($i%2==0?'even':'odd').'">';
                if(isset($type)){
                    $url =  AppUtility::getURLFromHome("outcomes","outcomes/outcome-report?cid=".$courseId."&report=2".'&stud='.$finalData[$i][0][1].'&type='.$type);
                }else{
                    $url =  AppUtility::getURLFromHome("outcomes","outcomes/outcome-report?cid=".$courseId."&report=2".'&stud='.$finalData[$i][0][1]);
                }
                echo'<td><a href=" '.$url.'">'.$finalData[$i][0][0].'</a></td>';
                foreach($outc as $data)
                {
                    if(isset($finalData[$i][3][$type]) && isset($finalData[$i][3][$type][$data]))
                    {
                        echo '<td>'.round(100*$finalData[$i][3][$type][$data],1).'%</td>';
                    }
                    else
                    {
                        echo '<td>-</td>';
                    }
                }
                echo '</tr>';

            }
            echo '</tbody></table>';
//            echo "<script>initSortTable('myTable',Array($arr),true,false);</script>\n";
            echo '<p>'._('Note:  The outcome performance in each gradebook category is weighted based on gradebook weights to produce these overview scores').'</p>';

        }
    }else if($report == AppConstant::NUMERIC_ONE)
    {

        echo '<div class="cpmid">'.$typeSel.'</div>';
        echo '<table id="myTable" class="table table-bordered table-striped table-hover data-table"><thead><th>'."Name".'</th><th>'."Total".'</th>';
        $arr = '"S","N"';
        $catsToList = array();
        $itemsToList = array();
        for ($i=1;$i<count($finalData);$i++)
        {
            for ($j=0;$j<count($finalData[$i][1]);$j++){
                if(isset($itemsToList[$j])){continue;}
                if ($type==0 && $finalData[0][1][$j][2]==1) {continue;}
                if (isset($finalData[$i][1][$j][1][$selectedOutcome]))
                {
                    $itemsToList[$j] = 1;
                }
            }
            for ($j=0;$j<count($finalData[$i][2]);$j++)
            {
                if (isset($finalData[$i][2][$j][2*$type+1][$selectedOutcome]) && $finalData[$i][2][$j][2*$type+1][$selectedOutcome]>0)
                {
                    $catsToList[$j] = 1;
                }
            }

        }
        $catsToList = array_keys($catsToList);
        $itemsToList = array_keys($itemsToList);
        foreach ($catsToList as $cat)
        {
            echo '<th class="cat'.$finalData[0][2][$cat][1].'"><span class="cattothdr">'.$finalData[0][2][$cat][0].'</span></th>';
            $arr .= ',"N"';
        }
        foreach ($itemsToList as $col)
        {
            echo '<th class="cat'.$finalData[0][1][$col][1].'">'.$finalData[0][1][$col][0].'</th>';
            $arr .= ',"N"';
        }
        echo '</tr></thead><tbody>';
        for ($i=1;$i<count($finalData);$i++)
        {
            echo '<tr class="'.($i%2==0?'even':'odd').'">';
            echo '<td>'.$finalData[$i][0][0].'</td>';

            if (isset($finalData[$i][3][$type]) && isset($finalData[$i][3][$type][$selectedOutcome]))
            {
                echo '<td>'.round(100*$finalData[$i][3][$type][$selectedOutcome],1).'%</td>';
            } else
            {
                echo '<td>-</td>';
            }
            foreach ($catsToList as $col)
            {
                if (isset($finalData[$i][2][$col]) && isset($finalData[$i][2][$col][2*$type][$selectedOutcome]) && $finalData[$i][2][$col][2*$type+1][$selectedOutcome]>0) {
                    echo '<td>'.round(100*$finalData[$i][2][$col][2*$type][$selectedOutcome]/$finalData[$i][2][$col][2*$type+1][$selectedOutcome],1).'%</td>';
                } else
                {
                    echo '<td>-</td>';
                }
            }
            foreach ($itemsToList as $col) {
                if (isset($finalData[$i][1][$col]) && isset($finalData[$i][1][$col][0][$selectedOutcome])) {
                    echo '<td>'.round(100*$finalData[$i][1][$col][0][$selectedOutcome]/$finalData[$i][1][$col][1][$selectedOutcome],1).'%</td>';
                } else {
                    echo '<td>-</td>';
                }
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
//        echo "<script>initSortTable('myTable',Array($arr),true,false);</script>\n";
    }else if($report == AppConstant::NUMERIC_TWO)
    {
        echo '<div class="cpmid">'.$typeSel.'</div>';
        echo '<table id="myTable" class="table table-bordered table-striped table-hover data-table"><thead><tr><th>'.'Outcome'.'</th><th>'.'ToTal'.'</th>';
        $n = AppConstant::NUMERIC_TWO;
        for ($i=0;$i<count($finalData[0][2]);$i++) {
            echo '<th class="cat'.$finalData[0][2][$i][1].'"><span class="cattothdr">'.$finalData[0][2][$i][0].'</span></th>';
            $n++;
        }
        echo '</tr></thead><tbody>';
        $cnt = AppConstant::NUMERIC_ZERO;
        $print = new AppUtility();
        $print->printOutcomes($outcomesData,AppConstant::NUMERIC_ZERO,$finalData,$cnt,$n,$type,$headerData);
        echo '</table>';
    }?>
</div>
<script>
    function chgtype()
   {
        var courseId = $('#course-id').val();
        var type = document.getElementById("typeSel").value;
        window.location = "outcome-report?cid="+courseId+"&type="+type;
    }
</script>