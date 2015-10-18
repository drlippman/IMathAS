<?php
use app\components\AppUtility;
use app\components\AssessmentUtility;
$this->title = AppUtility::t('Diagnostic Grade Book', false); ?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class = "row">
        <div class = "pull-left page-heading">
            <div class = "vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']); ?>
</div>

    <div class="tab-content shadowBox">
        <div class="offline-grade-header">  <a class="margin-left-thirty" href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/gradebook?cid='.$course->id);?>">View regular gradebook</a> </div>
        <div class="inner-content-gradebook">
         <?php
//DISPLAY
         ?>        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
 <form method=post action="gradebook?cid=<?php echo $course->id?>">
      <div class="col-md-12">
        <div class="pull-left padding-left-zero select-text-margin col-md-3">
          <span class="col-md-12">  <?php echo "Meanings:   NC-no credit"; ?> </span>
        </div>
        <div class="col-md-9 pull-right">
            <span class="col-md-5">
                   Students starting in:
                                     <select id="timetoggle" class="form-control-gradebook" onchange="chgtimefilter()">
                    <option value="1" <?php AssessmentUtility::writeHtmlSelected($timefilter,1); ?> >last 1 hour</option>
             <option value="2"  <?php  AssessmentUtility::writeHtmlSelected($timefilter,2); ?> >last 2 hours</option>
             <option value="4"  <?php AssessmentUtility::writeHtmlSelected($timefilter,4); ?>>last 4 hours</option>
             <option value="24"  <?php AssessmentUtility::writeHtmlSelected($timefilter,24); ?>>last day</option>
             <option value="168"  <?php AssessmentUtility::writeHtmlSelected($timefilter,168); ?>>last week</option>
             <option value="720"  <?php AssessmentUtility::writeHtmlSelected($timefilter,720); ?>>last month</option>
             <option value="8760" <?php  AssessmentUtility::writeHtmlSelected($timefilter,8760); ?>>last year</option>
             </select>

           </span>
            <span class=" pull-right">
             Last name: <input type=text id="lnfilter" value="<?php echo $lnfilter ?>" />
             <input type=button value="Filter by name" onclick="chglnfilter()" />
        </span>
        </div>
      </div> <br> <br>
<?php $gbt = gbinstrdisp($gradebookData,$studentsDistinctSection,$course); ?>

 </form>
 </div>
 </div>
<?php
function gbinstrdisp($gradebookData,$studentsDistinctSection,$course) {
    $isteacher = $gradebookData['isTeacher'];
    $catfilter = $gradebookData['catFilter'];
    $tutorsection = $gradebookData['tutorSection'];
    $secfilter = $gradebookData['secFilter'];
    $istutor = $gradebookData['isTutor'];
    $isdiag = $gradebookData['isDiagnostic'];
    $stu = $gradebookData['defaultValuesArray']['studentId'];
    $hidenc = 1;
    $cid = $course->id;
    $gbt = $gradebookData['gradebook'];
    echo "<div id=\"tbl-container\">";
    echo "<table class=myTable table table-bordered table-striped table-hover data-table id=myTable><thead><tr>";
    $n=0;
    for ($i=0;$i<count($gbt[0][0]);$i++) { //biographical headers
        if ($i==1 && $gbt[0][0][1]!='ID') { continue;}
        echo '<th>'.$gbt[0][0][$i];
        if (($gbt[0][0][$i]=='Section' || ($isdiag && $i==4)) && (!$istutor || $tutorsection=='')) { ?>
             <br/><select style="color: #000000" id="secfiltersel" onchange="chgsecfilter()"><option value="-1"
            <?php if ($secfilter==-1) {echo  'selected=1';}
            echo  '>All</option>';
            foreach($studentsDistinctSection as $row){
                if ($row['section']=='') { continue;}
                echo  "<option value=\"{$row['section']}\" ";
                if ($row['section']==$secfilter) {
                    echo  'selected=1';
                }
                echo  ">{$row['section']}</option>";
            }
            echo  "</select>";

        } else if ($gbt[0][0][$i]=='Name') {
            echo '<br/><span class="small">N='.(count($gbt)-2).'</span>';
        }
        echo '</th>';
        $n++;
    }
    for ($i=0;$i<count($gbt[0][1]);$i++) { //assessment headers
        if (!$isteacher && $gbt[0][1][$i][4]==0) { //skip if hidden
            continue;
        }
        if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
            continue;
        } else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
            continue;
        }
        //name and points
        echo '<th class="cat'.$gbt[0][1][$i][1].'">'.$gbt[0][1][$i][0].'<br/>';
        if ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3) {
            echo $gbt[0][1][$i][2].' (Not Counted)';
        } else {
            echo $gbt[0][1][$i][2].'&nbsp;pts';
            if ($gbt[0][1][$i][4]==2) {
                echo ' (EC)';
            }
        }
        if ($gbt[0][1][$i][5]==1) {
            echo ' (PT)';
        }
        //links
        if ($isteacher) {
            if ($gbt[0][1][$i][6]==0) { //online ?>
                 <br><a class=small href="<?php echo AppUtility::getURLFromHome('assessment','assessment/add-assessment?id='.$gbt[0][1][$i][7].'&cid='.$cid.'&from=gb');?> ">[Settings]</a>
                 <br><a class=small href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/isolate-assessment-grade?cid='.$cid.'&aid='.$gbt[0][1][$i][7]);?> ">[Isolate]</a>
    <?php   } else if ($gbt[0][1][$i][6]==1) { //offline ?>
                 <br><a class=small href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-grades?stu='.$stu.'&cid='.$cid.'&grades=all&gbitem='.$gbt[0][1][$i][7]);?> ">[Settings]</a>
                 <br><a class=small href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-grades?stu='.$stu.'&cid='.$cid.'&grades=all&gbitem='.$gbt[0][1][$i][7].'&isolate=true');?> ">[Isolate]</a>
          <?php  } else if ($gbt[0][1][$i][6]==2) { //discussion ?>
                 <br><a class=small href="<?php echo AppUtility::getURLFromHome('forum','forum/addforum?id='.$gbt[0][1][$i][7].'&cid='.$cid.'&from=gb');?>">[Settings]</a>";
           <?php  }
        }
        echo '</th>';
        $n++;
    }

    echo '</tr></thead><tbody>';
    //create student rows
    for ($i=1;$i<count($gbt)-1;$i++) {
        if ($i%2!=0) {
            echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">";
        } else {
            echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">";
        }
        echo '<td class="locked" scope="row">'; ?>
         <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$cid.'&studentId='.$gbt[$i][4][0]);?> ">
        <?php echo $gbt[$i][0][0];
        echo '</a></td>';
        for ($j=($gbt[0][0][1]=='ID'?1:2);$j<count($gbt[0][0]);$j++) {
            echo '<td class="c">'.$gbt[$i][0][$j].'</td>';
        }
        //assessment values
        for ($j=0;$j<count($gbt[0][1]);$j++) {
            if ($gbt[0][1][$j][4]==0) { //skip if hidden
                continue;
            }
            if ($hidenc==1 && $gbt[0][1][$j][4]==0) { //skip NC
                continue;
            } else if ($hidenc==2 && ($gbt[0][1][$j][4]==0 || $gbt[0][1][$j][4]==3)) {//skip all NC
                continue;
            }
            echo '<td class="c">';
            if (isset($gbt[$i][1][$j][5])) {
                echo '<span style="font-style:italic">';
            }
            if ($gbt[0][1][$j][6]==0) {//online
                if (isset($gbt[$i][1][$j][0])) {
                    if ($gbt[$i][1][$j][4]=='average') { ?>
                         <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/itemanalysis?stu='.$stu.'&cid='.$cid.'&asid='.$gbt[$i][1][$j][4].'&aid='.$gbt[0][1][$j][7]);?>">
                  <?php  } else { ?>
                         <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/gradebook-view-assessment-details?stu='.$stu.'&cid='.$cid.'&asid='.$gbt[$i][1][$j][4].'&uid='.$gbt[$i][4][0]);?> ">
                    <?php }
                    echo $gbt[$i][1][$j][0];
                    if ($gbt[$i][1][$j][3]==1) {
                        echo ' (NC)';
                    }
                    /*else if ($gbt[$i][1][$j][3]==2) {
                        echo ' (IP)';
                    } else if ($gbt[$i][1][$j][3]==3) {
                        echo ' (OT)';
                    } else if ($gbt[$i][1][$j][3]==4) {
                        echo ' (PT)';
                    } */
                    echo '</a>';
                    if ($gbt[$i][1][$j][1]==1) {
                        echo '<sup>*</sup>';
                    }
                } else { //no score
                    if ($gbt[$i][0][0]=='Averages') {
                        echo '-';
                    } else { ?>
                             <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/gradebook-view-assessment-details?stu='.$stu.'&cid='.$cid.'&asid=new&aid='.$gbt[0][1][$j][7].'&uid='.$gbt[$i][4][0]);?> ">-</a>
                   <?php }
                }
            } else if ($gbt[0][1][$j][6]==1) { //offline
                if ($isteacher) {
                    if ($gbt[$i][0][0]=='Averages') { ?>
                        <br><a class=small href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-grades?stu='.$stu.'&cid='.$cid.'&grades=all&gbitem='.$gbt[0][1][$j][7]);?>">
                    <?php } else { ?>
                        <br><a class=small href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/add-grades?stu='.$stu.'&cid='.$cid.'&grades='.$gbt[$i][4][0].'&gbitem='.$gbt[0][1][$j][7])?>">
                    <?php }
                }
                if (isset($gbt[$i][1][$j][0])) {
                    echo $gbt[$i][1][$j][0];
                    if ($gbt[$i][1][$j][3]==1) {
                        echo ' (NC)';
                    }
                } else {
                    echo '-';
                }
                if ($isteacher) {
                    echo '</a>';
                }
                if ($gbt[$i][1][$j][1]==1) {
                    echo '<sup>*</sup>';
                }
            } else if ($gbt[0][1][$j][6]==2) { //discuss
                if (isset($gbt[$i][1][$j][0])) {
                    echo $gbt[$i][1][$j][0];
                } else {
                    echo '-';
                }
            }
            if (isset($gbt[$i][1][$j][5])) {
                echo '<sub>d</sub></span>';
            }
            echo '</td>';
        }

    }
    echo "</tbody></table>";
    if ($n>0) {
        $sarr = array_fill(0,$n-1,"'N'");
    } else {
        $sarr = array();
    }
    array_unshift($sarr,"'S'");

    $sarr = implode(",",$sarr);
    if (count($gbt)<500) {
        echo "<script type='javascript'>initSortTable('myTable',Array($sarr),true,false);</script>\n";
    }
} ?>
    <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charser="utf8" src="//cdn.datatables.net/fixedcolumns/3.0.3/js/dataTables.fixedColumns.min.js"></script>
    <script>
    $(document).ready(function () {
        var table = $('.myTable').DataTable( {
            scrollY: "300px",
            scrollX: true,
            scrollCollapse: true,
            "paginate": false,
            "ordering":false,
            paging: false
        });
        new $.fn.dataTable.FixedColumns(table);
    });

</script>