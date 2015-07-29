<?php
use app\components\AppUtility;
use app\components\HtmlUtility;
$this->title = 'Gradebook';
$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid=' .$course->id]];
$this->params['breadcrumbs'][] = $this->title;
//AppUtility::dump($gradebookData['tutorSection']);
?>
<h2>Gradebook</h2>
<input type="hidden" class="course-info" id="course-id" name="course-info" value="<?php echo $course->id; ?>"/>
<input type="hidden" class="user-info" name="user-info" value="<?php echo $user->id; ?>"/>
<input type="hidden" id="gradebook-id" name="gradebook-data" value=""/>
<div class="cpmid">
    Offline Grades: <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/add-grades?cid='.$course->id); ?>">Add</a>, <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/manage-offline-grades?cid='.$course->id); ?>">Manage</a> |
                <select id="exportsel" onchange="chgexport()"><option value="0">Export to...</option></select> |
                <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-settings?cid='.$course->id); ?>">GB Settings</a> | <a href="#">Averages</a> |
                <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-comments?cid='.$course->id."&stu=0"); ?>">Comments</a> | <input type="button" id="lockbtn" class="btn-primary"onclick="lockcol()" value="Lock headers"> |
    Color:      <select id="colorsel" onchange="updateColors(this)"><option value="0">None</option></select> | <a href="#" onclick="chgnewflag(); return false;">NewFlag</a><br><br>
    Category:   <select id="filtersel" onchange="chgfilter()"><option value="-1">All</option><option value="0">Default</option><option value="-2" selected="1">Category Totals</option></select> |
    Not Counted:<select id="toggle2" onchange="chgtoggle()"><option value="0">Show all</option><option value="1">Show stu view</option><option value="2" selected="selected">Hide all</option></select> |
    Show:       <select id="toggle3" onchange="chgtoggle()"><option value="0">Past due</option><option value="3">Past &amp; Attempted</option><option value="4">Available Only</option><option value="1">Past &amp; Available</option><option value="2" selected="selected">All</option></select> |
    Links:      <select id="toggle1" onchange="chgtoggle()"><option value="0">View/Edit</option><option value="1" selected="selected">Scores</option></select> |
    Pics:       <select id="toggle4" onchange="chgtoggle()"><option value="0" selected="selected">None</option><option value="1">Small</option><option value="2">Big</option></select>
</div>

<div class="button-container">
    <form>
        <span>Check: <a class="check-all" href="#">All</a>/<a class="uncheck-all" href="#">None</a> With Selected:</span>
    </form>
    <form>
        <span> <a class="btn btn-primary" id="unenroll-btn">Print Report</a></span>
    </form>
    <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/roster-email?cid='.$course->id.'&gradebook=1' ) ?>" method="post" id="roster-form">
        <input type="hidden" id="student-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-email" value="E-mail"></span>
    </form>
    <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/copy-student-email?cid='.$course->id.'&gradebook=1' ) ?>" method="post" id="roster-form">
        <input type="hidden" id="email-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-copy-emails" value="Copy E-mails"></span>
    </form>
    <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/roster-message?cid='.$course->id.'&gradebook=1' ) ?>" method="post" id="roster-form">
        <input type="hidden" id="message-id" name="student-data" value=""/>
        <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
        <span> <input type="submit" class="btn btn-primary" id="roster-message" value="Message"></span>
    </form>
    <span> <a class="btn btn-primary" id="unenroll-btn" onclick="studentUnenroll()">Unenroll</a></span>
    <span> <a class="btn btn-primary" id="lock-btn">Lock</a></span>
    <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/make-exception?cid='.$course->id.'&gradebook=1' ) ?>" name="teacherMakeException" id="make-student" method="post">
        <input type="hidden" id="exception-id" name="student-data" value=""/>
        <input type="hidden" id="section-name" name="section-data" value=""/>
        <span> <input type="submit" class="btn btn-primary" id="gradebook-makeExc" value="Make Exception"></span>
    </form>
</div><br/>

<div class="gradebook-div">

    <table id = "gradebook-table display-gradebook-table" class = "gradebook-table display-gradebook-table table table-bordered table-striped table-hover data-table">
        <thead>
        <?php

        if ($data['availShow'] == 4) {
            $data['availShow'] = 1;
            $hidepast = true;
        }
        if ($avgontop) {
            $avgrow = array_pop($gradebook);
            array_splice($gradebook, 1, 0,array($avgrow));
        }

        $sortarr = array();
        for ($i=0; $i < count($gradebook[0][0]); $i++) { //biographical headers
            if ($i==1) {
                echo '<th><div>&nbsp;</div></th>'; $sortarr[] = 'false';
            } //for pics
            if ($i==1 && $gradebook[0][0][1] != 'ID') {
                continue;
            }
            if ($gradebook[0][0][$i] == 'Section' || $gradebook[0][0][$i] == 'Code' || $gradebook[0][0][$i] == 'Last Login') {
                echo '<th class="nocolorize"><div>';
            } else {
                echo '<th><div>';
            }
            echo $gradebook[0][0][$i];
            if ($gradebook[0][0][$i]=='Section') {
                echo "<br/><select id='sec-filter-sel' class='form-control dropdown-auto'><option value='-1'";
                if($data['secFilter'] == -1){
                    echo "selected = 1";
                }
                echo ">".AppUtility::t('All', false)."</option>";
                foreach($data['sections'] as $section){
                    echo "<option value=".$section.">".$section."</option>";
                }
                echo "</select>";

            } else if($gradebook[0][0][$i] == 'Name'){
                echo '<br/><span class="small">N='.(count($gradebook)-2).'</span><br/>';
                echo "<select id=\"toggle5\" onchange=\"chgtoggle()\">";
                echo "<option value=0 "; HtmlUtility::writeHtmlSelected($hidelocked,0); echo ">", _('Show Locked'), "</option>";
                echo "<option value=2 "; HtmlUtility::writeHtmlSelected($hidelocked,2); echo ">", _('Hide Locked'), "</option>";
                echo "</select>";
            }
            echo '</div></th>';
            if ($gradebook[0][0][$i]=='Last Login') {
                $sortarr[] = "'D'";
            } else if ($i != 1) {
                $sortarr[] = "'S'";
            }
        }
        $n=0;
        //get collapsed gb cat info
        if (count($gradebook[0][2])>1) {

            $collapsegbcat = array();
            for ($i=0;$i<count($gradebook[0][2]);$i++) {

                if (isset($data['overrideCollapse'][$gradebook[0][2][$i][10]])) {
                    $collapsegbcat[$gradebook[0][2][$i][1]] = $data['overrideCollapse'][$gradebook[0][2][$i][10]];
                } else {
                    $collapsegbcat[$gradebook[0][2][$i][1]] = $gradebook[0][2][$i][12];
                }
            }
        }
        if ($data['totOnLeft'] && !$hidepast) {
            //total totals
            if ($data['catFilter'] < 0) {
                if (isset($gradebook[0][3][0])) { //using points based
                    echo '<th><div><span class="cattothdr">'.AppUtility::t('Total', false).'<br/>'.$gradebook[0][3][$data['availShow']].'&nbsp;'.AppUtility::t('pts', false).'</span></div></th>';
                    echo '<th><div>%</div></th>';
                    $n+=2;
                } else {
                    echo '<th><div><span class="cattothdr">'.AppUtility::t('Weighted Total %', false).'</span></div></th>';
                    $n++;
                }
            }
            if (count($gradebook[0][2])>1 || $data['catFilter'] != -1) { //want to show cat headers?
                for ($i=0;$i<count($gradebook[0][2]);$i++) { //category headers
                    if (($data['availShow']<2 || $data['availShow']==3) && $gradebook[0][2][$i][2]>1) {
                        continue;
                    } else if ($data['availShow'] == 2 && $gradebook[0][2][$i][2] == 3) {
                        continue;
                    }
                    echo '<th class="cat'.$gradebook[0][2][$i][1].'"><div><span class="cattothdr">';
                    if ($data['availShow']<3) {
                        echo $gradebook[0][2][$i][0].'<br/>';
                        if (isset($gradebook[0][3][0])) { //using points based
                            echo $gradebook[0][2][$i][3+$data['availShow']].'&nbsp;'.AppUtility::t('pts', false);
                        } else {
                            echo $gradebook[0][2][$i][11].'%';
                        }
                    } else if ($data['availShow'] == 3) { //past and attempted
                        echo $gradebook[0][2][$i][0];
                        if (isset($gradebook[0][2][$i][11])) {
                            echo '<br/>'.$gradebook[0][2][$i][11].'%';
                        }
                    }
                    if ($collapsegbcat[$gradebook[0][2][$i][1]]==0) {
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Collapse]', false)."</a>";
                    } else {
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Expand]', false)."</a>";
                    }
                    echo '</span></div></th>';
                    $n++;
                }
            }

        }
        if ($data['catFilter'] > -2) {
            for ($i = 0;$i < count($gradebook[0][1]); $i++) { //assessment headers
                if (!$data['isTeacher'] && !$data['isTutor'] && $gradebook[0][1][$i][4]==0) { //skip if hidden
                    continue;
                }
                if ($data['hideNC'] == 1 && $gradebook[0][1][$i][4]==0) { //skip NC
                    continue;
                } else if ($data['hideNC'] == 2 && ($gradebook[0][1][$i][4]==0 || $gradebook[0][1][$i][4]==3)) {//skip all NC
                    continue;
                }
                if ($gradebook[0][1][$i][3]>$data['availShow']) {
                    continue;
                }
                if ($hidepast && $gradebook[0][1][$i][3]==0) {
                    continue;
                }
                if ($collapsegbcat[$gradebook[0][1][$i][1]]==2) {
                    continue;
                }
                //name and points
                echo '<th class="cat'.$gradebook[0][1][$i][1].'"><div>'.$gradebook[0][1][$i][0].'<br/>';
                if ($gradebook[0][1][$i][4]==0 || $gradebook[0][1][$i][4]==3) {
                    echo $gradebook[0][1][$i][2].'&nbsp;'.AppUtility::t('pts', false).' '.AppUtility::t('(Not Counted)', false);
                } else {
                    echo $gradebook[0][1][$i][2].'&nbsp;'.AppUtility::t('pts', false);
                    if ($gradebook[0][1][$i][4]==2) {
                        echo ' (EC)';
                    }
                }
                if ($gradebook[0][1][$i][5]==1 && $gradebook[0][1][$i][6]==0) {
                    echo ' (PT)';
                }
                if ($data['includeDueDate'] && $gradebook[0][1][$i][11]<2000000000 && $gradebook[0][1][$i][11]>0) {
                    echo '<br/><span class="small">'.AppUtility::tzdate('n/j/y&\n\b\s\p;g:ia', $gradebook[0][1][$i][11]).'</span>';
                }
                //links
                if ($gradebook[0][1][$i][6]==0 ) { //online
                    if ($data['isTeacher']) {
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Settings]', false)."</a>";
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Isolate]', false)."</a>";
                        if ($gradebook[0][1][$i][10]==true) {
                            echo "<br/><a class=small href=\"#\">".AppUtility::t('[By Group]',false)."</a>";
                        }
                    } else {
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Isolate]',false)."</a>";
                    }
                } else if ($gradebook[0][1][$i][6]==1  && ($data['isTeacher'] || ($data['isTutor'] && $gradebook[0][1][$i][8]==1))) { //offline
                    if ($data['isTeacher']) {
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Settings]', false)."</a>";
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Isolate]',false)."</a>";
                    } else {
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Scores]',false)."</a>";
                    }
                } else if ($gradebook[0][1][$i][6]==2  && $data['isTeacher']) { //discussion
                    echo "<br/><a class=small href=\"#\">".AppUtility::t('[Settings]',false)."</a>";
                } else if ($gradebook[0][1][$i][6]==3  && $data['isTeacher']) { //exttool
                    echo "<br/><a class=small href=\"#\">".AppUtility::t('[Settings]',false)."</a>";
                    echo "<br/><a class=small href=\"#\">".AppUtility::t('[Isolate]',false)."</a>";
                }

                echo '</div></th>';
                $n++;
            }
        }
        if (!$data['totOnLeft'] && !$hidepast) {
            if (count($gradebook[0][2]) > 1 || $data['catFilter'] != -1) { //want to show cat headers?
                for ($i=0;$i<count($gradebook[0][2]);$i++) { //category headers
                    if (($data['availShow'] < 2 || $data['availShow'] == 3) && $gradebook[0][2][$i][2] > 1) {
                        continue;
                    } else if ($data['availShow'] == 2 && $gradebook[0][2][$i][2]==3) {
                        continue;
                    }
                    echo '<th class="cat'.$gradebook[0][2][$i][1].'"><div><span class="cattothdr">';
                    if ($data['availShow'] < 3) {
                        echo $gradebook[0][2][$i][0].'<br/>';
                        if (isset($gradebook[0][3][0])) { //using points based
                            echo $gradebook[0][2][$i][3+$data['availShow']].'&nbsp;'.AppUtility::t('pts', false);
                        } else {
                            echo $gradebook[0][2][$i][11].'%';
                        }
                    } else if ($data['availShow']==3) { //past and attempted
                        echo $gradebook[0][2][$i][0];
                    }
                    if ($collapsegbcat[$gradebook[0][2][$i][1]]==0) {
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Collapse]', false)."</a>";
                    } else {
                        echo "<br/><a class=small href=\"#\">".AppUtility::t('[Expand]', false)."</a>";
                    }
                    echo '</span></div></th>';
                    $n++;
                }
            }
            //total totals
            if ($data['catFilter'] < 0) {
                if (isset($gradebook[0][3][0])) { //using points based
                    echo '<th><div><span class="cattothdr">'.AppUtility::t('Total', false).'<br/>'.$gradebook[0][3][$data['availShow']].'&nbsp;'.AppUtility::t('pts', false).'</span></div></th>';
                    echo '<th><div>%</div></th>';
                    $n+=2;
                } else {
                    echo '<th><div><span class="cattothdr">'.AppUtility::t('Weighted Total %', false).'</span></div></th>';
                    $n++;
                }
            }
        }
        ?>
</thead>
        <tbody>
        <?php
        for ($i=1;$i<count($gradebook);$i++) {
            if ($i==1) {$insdiv = "<div>";  $enddiv = "</div>";} else {$insdiv = ''; $enddiv = '';}
            if ($i%2!=0) {
                echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
            } else {
                echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
            }
            echo '<td class="locked" scope="row"><div class="trld">';
            if ($gradebook[$i][0][0]!="Averages" && $data['isTeacher']) {
                echo "<input type=\"checkbox\" name='checked[]' value='{$gradebook[$i][4][0]}' />&nbsp;";
            }
            echo "<a href=\"#\">";
            if ($gradebook[$i][4][1]>0) {
                echo '<span class="greystrike">'.$gradebook[$i][0][0].'</span>';
            } else {
                echo $gradebook[$i][0][0];
            }
            echo '</a>';
            if ($gradebook[$i][4][3]==1) {
                echo '<sup>*</sup>';
            }
            echo '</div></td>';
            if ($data['showPics'] == 1 && $gradebook[$i][4][2] == 1) {
                echo "<td>{$insdiv}<div class=\"trld\"><img src=\"#\"/></div></td>";
            } else if ($data['showPics'] == 2 && $gradebook[$i][4][2] == 1) {
                echo "<td>{$insdiv}<div class=\"trld\"><img src=\"#\"/></div></td>";
            } else {
                echo '<td>'.$insdiv.'<div class="trld">&nbsp;</div></td>';
            }
            for ($j=($gradebook[0][0][1] == 'ID' ? 1:2); $j < count($gradebook[0][0]); $j++) {
                echo '<td class="c">'.$insdiv.$gradebook[$i][0][$j].$enddiv .'</td>';
            }
            if ($data['totOnLeft'] && !$hidepast) {
                //total totals
                if ($data['catFilter'] < 0) {
                    if ($data['availShow'] == 3) {
                        if ($gradebook[$i][0][0] == 'Averages') {
                            if (isset($gradebook[$i][3][8])) { //using points based
                                echo '<td class="c">'.$insdiv.$gradebook[$i][3][6].'%'.$enddiv.'</td>';
                            }
                            echo '<td class="c">'.$insdiv.$gradebook[$i][3][6].'%'.$enddiv .'</td>';
                        } else {
                            if (isset($gradebook[$i][3][8])) { //using points based
                                echo '<td class="c">'.$insdiv.$gradebook[$i][3][6].'/'.$gradebook[$i][3][7].$enddiv.'</td>';
                                echo '<td class="c">'.$insdiv.$gradebook[$i][3][8] .'%'.$enddiv .'</td>';
                            } else {
                                echo '<td class="c">'.$insdiv.$gradebook[$i][3][6].'%'.$enddiv .'</td>';
                            }
                        }
                    } else {
                        if (isset($gradebook[0][3][0])) { //using points based
                            echo '<td class="c">'.$insdiv.$gradebook[$i][3][$data['availShow']].$enddiv .'</td>';
                            echo '<td class="c">'.$insdiv.$gradebook[$i][3][$data['availShow']+3] .'%'.$enddiv .'</td>';
                        } else {
                            echo '<td class="c">'.$insdiv.$gradebook[$i][3][$data['availShow']].'%'.$enddiv .'</td>';
                        }
                    }
                }
                //category totals
                if (count($gradebook[0][2])>1 || $data['catFilter'] != -1) { //want to show cat headers?
                    for ($j=0; $j<count($gradebook[0][2]); $j++) { //category headers
                        if (($data['availShow']<2 || $data['availShow'] == 3) && $gradebook[0][2][$j][2] > 1) {
                            continue;
                        } else if ($data['availShow'] == 2 && $gradebook[0][2][$j][2] == 3) {
                            continue;
                        }
                        if ($data['catFilter'] != -1 && $data['availShow'] < 3 && $gradebook[0][2][$j][$data['availShow'] + 3] > 0) {
                            echo '<td class="c">'.$insdiv;
                            if ($gradebook[$i][0][0] == 'Averages' && $data['availShow'] != 3) {
                                echo "<span onmouseover=\"tipshow(this,'".AppUtility::t('5-number summary:', false)." {$gradebook[0][2][$j][6+$data['availShow']]}')\" onmouseout=\"tipout()\" >";
                            }
                            echo $gradebook[$i][2][$j][$data['availShow']].' ('.round(100*$gradebook[$i][2][$j][$data['availShow']]/$gradebook[0][2][$j][$data['availShow']+3])  .'%)';

                            if ($gradebook[$i][0][0] == 'Averages' && $data['availShow'] != 3) {
                                echo '</span>';
                            }
                            echo $enddiv .'</td>';
                        } else {
                            echo '<td class="c">'.$insdiv;
                            if ($gradebook[$i][0][0]=='Averages') {
                                echo "<span onmouseover=\"tipshow(this,'".AppUtility::t('5-number summary:', false)." {$gradebook[0][2][$j][6+$data['availShow']]}')\" onmouseout=\"tipout()\" >";
                            }
                            if ($data['availShow'] == 3) {
                                if ($gradebook[$i][0][0]=='Averages') {
                                    echo $gradebook[$i][2][$j][3].'%';//echo '-';
                                } else {
                                    echo $gradebook[$i][2][$j][3].'/'.$gradebook[$i][2][$j][4];
                                }
                            } else {
                                if (isset($gradebook[$i][3][8])) { //using points based
                                    echo $gradebook[$i][2][$j][$data['availShow']];
                                } else {
                                    if ($gradebook[0][2][$j][3+$data['availShow']] > 0) {
                                        echo round(100*$gradebook[$i][2][$j][$data['availShow']]/$gradebook[0][2][$j][3+$data['availShow']],1).'%';
                                    } else {
                                        echo '0%';
                                    }
                                }
                            }
                            if ($gradebook[$i][0][0] == 'Averages') {
                                echo '</span>';
                            }
                            echo $enddiv .'</td>';
                        }

                    }
                }
            }
            //assessment values
            if ($data['catFilter'] > -2) {
                for ($j=0; $j < count($gradebook[0][1]); $j++) {
                    if (!$data['isTeacher'] && !$data['isTutor'] && $gradebook[0][1][$j][4] == 0) { //skip if hidden
                        continue;
                    }
                    if ($data['hideNC'] == 1 && $gradebook[0][1][$j][4] == 0) { //skip NC
                        continue;
                    } else if ($data['hideNC'] == 2 && ($gradebook[0][1][$j][4] == 0 || $gradebook[0][1][$j][4] == 3)) {//skip all NC
                        continue;
                    }
                    if ($gradebook[0][1][$j][3] > $data['availShow']) {
                        continue;
                    }
                    if ($hidepast && $gradebook[0][1][$j][3] == 0) {
                        continue;
                    }
                    if ($collapsegbcat[$gradebook[0][1][$j][1]] == 2) {
                        continue;
                    }

                    //if online, not average, and either score exists and active, or score doesn't exist and assess is current,
                    if ($gradebook[0][1][$j][6] == 0 && $gradebook[$i][1][$j][4] != 'average' && ((isset($gradebook[$i][1][$j][3]) && $gradebook[$i][1][$j][3]>9) || (!isset($gradebook[$i][1][$j][3]) && $gradebook[0][1][$j][3] == 1))) {
                        echo '<td class="c isact">'.$insdiv;
                    } else {
                        echo '<td class="c">'.$insdiv;
                    }
                    if (isset($gradebook[$i][1][$j][5]) && ($gradebook[$i][1][$j][5]&(1<<$data['availShow'])) && !$hidepast) {
                        echo '<span style="font-style:italic">';
                    }
                    if ($gradebook[0][1][$j][6] == 0) {//online
                        if (isset($gradebook[$i][1][$j][0])) {
                            if ($data['isTutor'] && $gradebook[$i][1][$j][4] == 'average') {
                            } else if ($gradebook[$i][1][$j][4] == 'average') {
                                echo "<a href=\"#\" ";
                                echo "onmouseover=\"tipshow(this,'".AppUtility::t('5-number summary:', false)." {$gradebook[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
                                echo ">";
                            } else {
                                echo "<a href=\"#\">";
                            }
                            if ($gradebook[$i][1][$j][3] > 9) {
                                $gradebook[$i][1][$j][3] -= 10;
                            }
                            echo $gradebook[$i][1][$j][0];
                            if ($gradebook[$i][1][$j][3] == 1) {
                                echo ' (NC)';
                            } else if ($gradebook[$i][1][$j][3] == 2) {
                                echo ' (IP)';
                            } else if ($gradebook[$i][1][$j][3] == 3) {
                                echo ' (OT)';
                            } else if ($gradebook[$i][1][$j][3] == 4) {
                                echo ' (PT)';
                            }
                            if ($data['isTutor'] && $gradebook[$i][1][$j][4] == 'average') {
                            } else {
                                echo '</a>';
                            }
                            if ($gradebook[$i][1][$j][1] == 1) {
                                echo '<sup>*</sup>';
                            }

                        } else { //no score
                            if ($gradebook[$i][0][0] == 'Averages') {
                                echo '-';
                            } else if ($data['isTeacher']) {
                                echo "<a href=\"#\">-</a>";
                            } else {
                                echo '-';
                            }
                        }
                        if (isset($gradebook[$i][1][$j][6]) ) {
                            if ($gradebook[$i][1][$j][6] > 1) {
                                if ($gradebook[$i][1][$j][6] > 2) {
                                    echo '<sup>LP ('.($gradebook[$i][1][$j][6]-1).')</sup>';
                                } else {
                                    echo '<sup>LP</sup>';
                                }
                            } else {
                                echo '<sup>e</sup>';
                            }
                        }
                    } else if ($gradebook[0][1][$j][6] == 1) { //offline
                        if ($data['isTeacher']) {
                            if ($gradebook[$i][0][0] == 'Averages') {
                                echo "<a href=\"#\" ";
                                echo "onmouseover=\"tipshow(this,'".AppUtility::t('5-number summary:', false)." {$gradebook[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
                                echo ">";
                            } else {
                                echo "<a href=\"#\">";
                            }
                        } else if ($data['isTutor'] && $gradebook[0][1][$j][8] == 1) {
                            if ($gradebook[$i][0][0] == 'Averages') {
                                echo "<a href=\"#\">";
                            } else {
                                echo "<a href=\"#\">";
                            }
                        }
                        if (isset($gradebook[$i][1][$j][0])) {
                            echo $gradebook[$i][1][$j][0];
                            if ($gradebook[$i][1][$j][3]==1) {
                                echo ' (NC)';
                            }
                        } else {
                            echo '-';
                        }
                        if ($data['isTeacher'] || ($data['isTutor'] && $gradebook[0][1][$j][8] == 1)) {
                            echo '</a>';
                        }
                        if ($gradebook[$i][1][$j][1] == 1) {
                            echo '<sup>*</sup>';
                        }
                    } else if ($gradebook[0][1][$j][6] == 2) { //discuss
                        if (isset($gradebook[$i][1][$j][0])) {
                            if ( $gradebook[$i][0][0] != 'Averages') {
                                echo "<a href=\"#\">";
                                echo $gradebook[$i][1][$j][0];
                                echo '</a>';
                            } else {
                                echo "<span onmouseover=\"tipshow(this,'".AppUtility::t('5-number summary:', false)." {$gradebook[0][1][$j][9]}')\" onmouseout=\"tipout()\"> ";
                                echo $gradebook[$i][1][$j][0];
                                echo '</span>';
                            }
                            if ($gradebook[$i][1][$j][1] == 1) {
                                echo '<sup>*</sup>';
                            }
                        } else {
                            if ($data['isTeacher'] && $gradebook[$i][0][0] != 'Averages') {
                                echo "<a href=\"#\">-</a>";
                            } else {
                                echo '-';
                            }
                        }

                    } else if ($gradebook[0][1][$j][6] == 3) { //exttool
                        if ($data['isTeacher']) {
                            if ($gradebook[$i][0][0] == 'Averages') {
                                echo "<a href=\"#\" ";
                                echo "onmouseover=\"tipshow(this,'".AppUtility::t('5-number summary:', false)." {$gradebook[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
                                echo ">";
                            } else {
                                echo "<a href=\"#\">";
                            }
                        } else if ($data['isTutor'] && $gradebook[0][1][$j][8] == 1) {
                            if ($gradebook[$i][0][0] == 'Averages') {
                                echo "<a href=\"#\">";
                            } else {
                                echo "<a href=\"#\">";
                            }
                        }
                        if (isset($gradebook[$i][1][$j][0])) {
                            echo $gradebook[$i][1][$j][0];
                            if ($gradebook[$i][1][$j][3] == 1) {
                                echo ' (NC)';
                            }
                        } else {
                            echo '-';
                        }
                        if ($data['isTeacher'] || ($data['isTutor'] && $gradebook[0][1][$j][8] == 1)) {
                            echo '</a>';
                        }
                        if ($gradebook[$i][1][$j][1] == 1) {
                            echo '<sup>*</sup>';
                        }
                    }
                    if (isset($gradebook[$i][1][$j][5]) && ($gradebook[$i][1][$j][5]&(1<<$data['availShow'])) && !$hidepast) {
                        echo '<sub>d</sub></span>';
                    }
                    echo $enddiv .'</td>';
                }
            }
            if (!$data['totOnLeft'] && !$hidepast) {
                //category totals
                if (count($gradebook[0][2])>1 || $data['catFilter'] != -1) { //want to show cat headers?
                    for ($j=0; $j<count($gradebook[0][2]); $j++) { //category headers
                        if (($data['availShow'] < 2 || $data['availShow'] == 3) && $gradebook[0][2][$j][2] > 1) {
                            continue;
                        } else if ($data['availShow'] == 2 && $gradebook[0][2][$j][2] == 3) {
                            continue;
                        }
                        if ($data['catFilter'] != -1 && $data['availShow'] < 3 && $gradebook[0][2][$j][$data['availShow']+3]>0) {
                            echo '<td class="c">'.$insdiv;
                            if ($gradebook[$i][0][0]=='Averages' && $data['availShow'] != 3) {
                                echo "<span onmouseover=\"tipshow(this,'".AppUtility::t('5-number summary:', false)." {$gradebook[0][2][$j][6+$data['availShow']]}')\" onmouseout=\"tipout()\" >";
                            }
                            echo $gradebook[$i][2][$j][$data['availShow']].' ('.round(100*$gradebook[$i][2][$j][$data['availShow']]/$gradebook[0][2][$j][$data['availShow']+3])  .'%)';

                            if ($gradebook[$i][0][0]=='Averages' && $data['availShow'] != 3) {
                                echo '</span>';
                            }
                            echo $enddiv .'</td>';
                        } else {
                            echo '<td class="c">'.$insdiv;
                            if ($gradebook[$i][0][0]=='Averages' && $data['availShow']<3) {
                                echo "<span onmouseover=\"tipshow(this,'".AppUtility::t('5-number summary:', false)." {$gradebook[0][2][$j][6+$data['availShow']]}')\" onmouseout=\"tipout()\" >";
                            }
                            if ($data['availShow'] == 3) {
                                if ($gradebook[$i][0][0] == 'Averages') {
                                    echo $gradebook[$i][2][$j][3].'%';
                                } else {
                                    echo $gradebook[$i][2][$j][3].'/'.$gradebook[$i][2][$j][4];
                                }
                            } else {
                                if (isset($gradebook[$i][3][8])) { //using points based
                                    echo $gradebook[$i][2][$j][$data['availShow']];
                                } else {
                                    if ($gradebook[0][2][$j][3+$data['availShow']]>0) {
                                        echo round(100*$gradebook[$i][2][$j][$data['availShow']]/$gradebook[0][2][$j][3+$data['availShow']],1).'%';
                                    } else {
                                        echo '0%';
                                    }
                                }
                            }
                            if ($gradebook[$i][0][0]=='Averages' && $data['availShow']<3) {
                                echo '</span>';
                            }
                            echo $enddiv .'</td>';
                        }
                    }
                }

                //total totals
                if ($data['catFilter']<0) {
                    if ($data['availShow']==3) {
                        if ($gradebook[$i][0][0]=='Averages') {
                            if (isset($gradebook[$i][3][8])) { //using points based
                                echo '<td class="c">'.$insdiv.$gradebook[$i][3][6].'%'.$enddiv .'</td>';
                            }
                            echo '<td class="c">'.$insdiv.$gradebook[$i][3][6].'%'.$enddiv .'</td>';
                        } else {
                            if (isset($gradebook[$i][3][8])) { //using points based
                                echo '<td class="c">'.$insdiv.$gradebook[$i][3][6].'/'.$gradebook[$i][3][7].$enddiv .'</td>';
                                echo '<td class="c">'.$insdiv.$gradebook[$i][3][8] .'%'.$enddiv .'</td>';

                            } else {
                                echo '<td class="c">'.$insdiv.$gradebook[$i][3][6].'%'.$enddiv .'</td>';
                            }
                        }
                    } else {
                        if (isset($gradebook[0][3][0])) { //using points based
                            echo '<td class="c">'.$insdiv.$gradebook[$i][3][$data['availShow']].$enddiv .'</td>';
                            echo '<td class="c">'.$insdiv.$gradebook[$i][3][$data['availShow']+3] .'%'.$enddiv .'</td>';
                        } else {
                            echo '<td class="c">'.$insdiv.$gradebook[$i][3][$data['availShow']].'%'.$enddiv .'</td>';
                        }
                    }
                }
            }
            echo '</tr>';


        }
        ?>
        </tbody>
    </table>

</div>

<p>Meanings:  IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit
<br>
<sup>*</sup>Has feedback,<sub> d</sub>Dropped score,<sup> e</sup>Has exception,<sup> LP</sup>Used latepass</p>
