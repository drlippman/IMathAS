<?php
use app\components\AppUtility;
use app\components\HtmlUtility;
if (($course['newflag']&1)==1) {
    $this->title = AppUtility::t('Gradebook'.' <span class="small" style="color: red;"> New </span>', false);
}else{
    $this->title = AppUtility::t('Gradebook', false);
}
$this->params['breadcrumbs'][] = $this->title;
?>
<input type="hidden" id="course-id" value="<?php echo $course->id ?>">
<input type="hidden" class="course-info" id="course-id" name="course-info" value="<?php echo $course->id; ?>"/>
<input type="hidden" class="user-info" name="user-info" value="<?php echo $user->id; ?>"/>
<input type="hidden" id="student-id"  value="<?php echo $data['defaultValuesArray']['studentId']; ?>"/>
<input type="hidden" id="gradebook-id" name="gradebook-data" value=""/>
<input type="hidden" id="showpics" name="user-info" value="<?php echo $data['defaultValuesArray']['showpics']; ?>"/>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class = "row">
        <div class = "pull-left page-heading">
            <div class = "vertical-align title-page"><?php echo $this->title ?></div>
        </div>
        <div class = "pull-left header-btn gradebook-header-links">
            <div class = "pull-right">
                <a href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gb-settings?cid=' . $course->id); ?>" class="btn btn-primary"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/courseSetting.png"></span> Gradebook Settings</a>
                <div class="btn-group">
                    <a class="btn btn-primary green-btn disable-btn" href="#"><span class="glyphicon glyphicon-export"></span> Export</a>
                    <a class="btn btn-primary dropdown-toggle green-carret" data-toggle="dropdown" href="#">
                        <span class="fa fa-caret-down"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="javascript: chgexport(1)"> File</a></li>
                        <li><a href="javascript: chgexport(2)"> My Email</a></li>
                        <li><a href="javascript: chgexport(3)"> Other Email</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']); ?>
</div>
<?php echo $this->render("_toolbarGradebook", ['course' => $course,'data' => $data]); ?>
<div class="tab-content shadowBox"">
<div class="inner-content-gradebook">
<div class="button-container col-lg-12 padding-zero">

    <span class="col-lg-7 padding-zero pull-left">
 Meanings: IP-In Progress (some unattempted questions), OT-overtime, PT-practice test, EC-extra credit, NC-no credit
    <sup>*</sup>Has feedback,<sub> d</sub>Dropped score,<sup> e</sup>Has exception,<sup> LP</sup>Used latepass
        </span>
    <span class="inner-page-options col-lg-5 padding-zero pull-left">
        <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">
            <li class="dropdown">
                 <?php echo '<select  class="form-control export-to-height" id="colorsel" onchange="updateColors(this)">';
                echo '<option value="0">', _('Color'), '</option>';
                echo '<option value="0">', _('None'), '</option>';
                for ($j=50;$j<90;$j+=($j<70?10:5)) {
                    for ($k=$j+($j<70?10:5);$k<100;$k+=($k<70?10:5)) {
                        echo "<option value=\"$j:$k\" ";
                        if ("$j:$k"==$colorize) {
                            echo 'selected="selected" ';
                        }
                        echo ">$j/$k</option>";
                    }
                }
                echo '<option value="-1:-1" ';
                if ($colorize == "-1:-1") { echo 'selected="selected" ';}
                echo '>', _('Active'), '</option>';
                echo '</select>';?>
            </li>
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown"
                   href="#"><?php AppUtility::t('Category'); ?><span class="caret right-aligned"></span></a>
                <ul id="filtersel" class="dropdown-menu with-selected ">
 <li  value='1'><a  href="javascript: chgfilter(-1)" ><?php AppUtility::t('All') ?></a></li>
 <li  value='0'><a href="javascript: chgfilter(0)"  ><?php AppUtility::t('Default') ?> </a></li>
                    <?php foreach($data['gbCatsData'] as $category){ ?>
     <li><a href="javascript: chgfilter(<?php echo $category['id']?>)"><?php echo $category['name']?></a></li>
<?php } ?>
 <li><a href="javascript: chgfilter(-2)"><?php AppUtility::t('Category Totals')?></a></li>

                </ul>
            </li>
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown"
                   href="#"><?php AppUtility::t('With selected'); ?><span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu with-selected">
                    <li><a href="#">
                            <i class="fa fa-fw fa-print"></i>&nbsp;<?php AppUtility::t('Print Report'); ?>
                        </a>
                    </li>
                    <li>
                        <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/roster-email?cid=' . $course->id . '&gradebook=1') ?>" method="post" id="gradebook-email-form">
                            <input type="hidden" id="email" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a href="javascript: studentEmail()"><i class="fa fa-at fa-fw"></i>&nbsp;<?php AppUtility::t('Email'); ?></a>
                        </form>
                    </li>
                    <li>
                        <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/copy-student-email?cid=' . $course->id . '&gradebook=1') ?>" method="post"
                              id="copy-emails-form">
                            <input type="hidden" id="email-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: studentCopyEmail()"><i
                                    class="fa fa-clipboard fa-fw"></i>&nbsp;<?php AppUtility::t('Copy Emails'); ?></a>
                        </form>
                    </li>
                    <li>
                        <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/roster-message?cid=' . $course->id . '&gradebook=1') ?>" method="post"
                              id="gradebook-message-form">
                            <input type="hidden" id="message-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: studentMessage()"><i
                                    class="fa fa-envelope-o fa-fw"></i>&nbsp;<?php AppUtility::t('Message'); ?></a>
                        </form>
                    </li>
                    <li>
                        <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/unenroll?cid=' . $course->id . '&gradebook=1') ?>" id="un-enroll-form"
                              method="post">
                            <input type="hidden" id="checked-student" name="student-data" value=""/>
                            <a class="with-selected-list" href="javascript: studentUnEnroll()"><i
                                    class="fa fa-trash-o fa-fw"></i>&nbsp;<?php AppUtility::t('Unenroll'); ?></a>
                            </a>
                        </form>
                    </li>
                    <li><a id="lock-btn" href="#"><i class='fa fa-lock fa-fw'></i>&nbsp;<?php AppUtility::t('Lock'); ?>
                        </a></li>
                    <li>
                        <form action="<?php echo AppUtility::getURLFromHome('roster', 'roster/make-exception?cid=' . $course->id . '&gradebook=1') ?>" id="make-exception-form"
                              method="post">
                            <input type="hidden" id="exception-id" name="student-data" value=""/>
                            <input type="hidden" id="section-name" name="section-data" value=""/>
                            <a class="with-selected-list" href="javascript: teacherMakeException()"><i
                                    class='fa fa-plus-square fa-fw'></i>&nbsp;<?php AppUtility::t('Make Exception'); ?>
                            </a>
                        </form>
                    </li>

                </ul>
            </li>
        </ul>
    </span>
</div><br/>


<div class="gradebook-div">
    <table id="gradebook-table" class="gradebook-table table table-bordered table-striped table-hover data-table">
        <thead>
        <?php
if ($data['availShow'] == 4) {
    $data['availShow'] = 1;
    $hidepast = true;
}
if ($avgontop) {
    $avgrow = array_pop($gradebook);
    array_splice($gradebook, 1, 0, array($avgrow));
}
$sortarr = array();
for ($i = 0; $i < count($gradebook[0][0]); $i++) { //biographical headers
    if ($i == 1) {
        echo '<th id="grade"><div>&nbsp;</div></th>';
        $sortarr[] = 'false';
    } //for pics
    if ($i == 1 && $gradebook[0][0][1] != 'ID') {
        continue;
    }
    if ($gradebook[0][0][$i] == 'Section' || $gradebook[0][0][$i] == 'Code' || $gradebook[0][0][$i] == 'Last Login') {
        echo '<th class="nocolorize"><div>';
    } else {
        echo '<th><div>';
    }
    echo $gradebook[0][0][$i];
    if ($gradebook[0][0][$i] == 'Section') {
        echo "<br/><select id='sec-filter-sel' class='form-control dropdown-auto'><option value='-1'";
        if ($data['secFilter'] == -1) {
            echo "selected = 1";
        }
        echo ">" . AppUtility::t('All', false) . "</option>";
        foreach ($data['sections'] as $section) {
            echo "<option value=" . $section . ">" . $section . "</option>";
        }
        echo "</select>";

    } else if ($gradebook[0][0][$i] == 'Name') {?>
        <div class="checkbox pull-left override-hidden">
            <label>
                <input type="checkbox" name="header-checked" value="">
                <span class="cr"><i class="cr-icon fa fa-check"></i></span>
            </label>
        </div>
        <?php echo '<br/><span class="small">N=' . (count($gradebook) - 2) . '</span><br/>';
    }
    echo '</div></th>';
    if ($gradebook[0][0][$i] == 'Last Login') {
        $sortarr[] = "'D'";
    } else if ($i != 1) {
        $sortarr[] = "'S'";
    }
}
$n = 0;
//get collapsed gb cat info
if (count($gradebook[0][2]) > 1) {

    $collapsegbcat = array();
    for ($i = 0; $i < count($gradebook[0][2]); $i++) {

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
            echo '<th><div><span class="cattothdr">' . AppUtility::t('Total', false) . '<br/>' . $gradebook[0][3][$data['availShow']] . '&nbsp;' . AppUtility::t('pts', false) . '</span></div></th>';
            echo '<th><div>%</div></th>';
            $n += 2;
        } else {
            echo '<th><div><span class="cattothdr">' . AppUtility::t('Weighted Total %', false) . '</span></div></th>';
            $n++;
        }
    }
    if (count($gradebook[0][2]) > 1 || $data['catFilter'] != -1) { //want to show cat headers?
        for ($i = 0; $i < count($gradebook[0][2]); $i++) { //category headers
            if (($data['availShow'] < 2 || $data['availShow'] == 3) && $gradebook[0][2][$i][2] > 1) {
                continue;
            } else if ($data['availShow'] == 2 && $gradebook[0][2][$i][2] == 3) {
                continue;
            }
            echo '<th class="cat' . $gradebook[0][2][$i][1] . '"><div><span class="cattothdr">';
            if ($data['availShow'] < 3) {
                echo $gradebook[0][2][$i][0] . '<br/>';
                if (isset($gradebook[0][3][0])) { //using points based
                    echo $gradebook[0][2][$i][3 + $data['availShow']] . '&nbsp;' . AppUtility::t('pts', false);
                } else {
                    echo $gradebook[0][2][$i][11] . '%';
                }
            } else if ($data['availShow'] == 3) { //past and attempted
                echo $gradebook[0][2][$i][0];
                if (isset($gradebook[0][2][$i][11])) {
                    echo '<br/>' . $gradebook[0][2][$i][11] . '%';
                }
            }
            if ($collapsegbcat[$gradebook[0][2][$i][1]] == 0) {
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Collapse]', false) . "</a>";
            } else {
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Expand]', false) . "</a>";
            }
            echo '</span></div></th>';
            $n++;
        }
    }

}
if ($data['catFilter'] > -2) {
    for ($i = 0; $i < count($gradebook[0][1]); $i++) { //assessment headers
        if (!$data['isTeacher'] && !$data['isTutor'] && $gradebook[0][1][$i][4] == 0) { //skip if hidden
            continue;
        }
        if ($data['hideNC'] == 1 && $gradebook[0][1][$i][4] == 0) { //skip NC
            continue;
        } else if ($data['hideNC'] == 2 && ($gradebook[0][1][$i][4] == 0 || $gradebook[0][1][$i][4] == 3)) {//skip all NC
            continue;
        }
        if ($gradebook[0][1][$i][3] > $data['availShow']) {
            continue;
        }
        if ($hidepast && $gradebook[0][1][$i][3] == 0) {
            continue;
        }
        if ($collapsegbcat[$gradebook[0][1][$i][1]] == 2) {
            continue;
        }
        //name and points
        echo '<th class="cat' . $gradebook[0][1][$i][1] . '"><div>' . $gradebook[0][1][$i][0] . '<br/>';
        if ($gradebook[0][1][$i][4] == 0 || $gradebook[0][1][$i][4] == 3) {
            echo $gradebook[0][1][$i][2] . '&nbsp;' . AppUtility::t('pts', false) . ' ' . AppUtility::t('(Not Counted)', false);
        } else {
            echo $gradebook[0][1][$i][2] . '&nbsp;' . AppUtility::t('pts', false);
            if ($gradebook[0][1][$i][4] == 2) {
                echo ' (EC)';
            }
        }
        if ($gradebook[0][1][$i][5] == 1 && $gradebook[0][1][$i][6] == 0) {
            echo ' (PT)';
        }
        if ($data['includeDueDate'] && $gradebook[0][1][$i][11] < 2000000000 && $gradebook[0][1][$i][11] > 0) {
            echo '<br/><span class="small">' . AppUtility::tzdate('n/j/y&\n\b\s\p;g:ia', $gradebook[0][1][$i][11]) . '</span>';
        }
        //links
        if ($gradebook[0][1][$i][6] == 0) { //online
            if ($data['isTeacher']) {
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Settings]', false) . "</a>";
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Isolate]', false) . "</a>";
                if ($gradebook[0][1][$i][10] == true) {
                    echo "<br/><a class=small href=\"#\">" . AppUtility::t('[By Group]', false) . "</a>";
                }
            } else {
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Isolate]', false) . "</a>";
            }
        } else if ($gradebook[0][1][$i][6] == 1 && ($data['isTeacher'] || ($data['isTutor'] && $gradebook[0][1][$i][8] == 1))) { //offline
            if ($data['isTeacher']) {
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Settings]', false) . "</a>";
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Isolate]', false) . "</a>";
            } else {
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Scores]', false) . "</a>";
            }
        } else if ($gradebook[0][1][$i][6] == 2 && $data['isTeacher']) { //discussion
            echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Settings]', false) . "</a>";
        } else if ($gradebook[0][1][$i][6] == 3 && $data['isTeacher']) { //exttool
            echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Settings]', false) . "</a>";
            echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Isolate]', false) . "</a>";
        }

        echo '</div></th>';
        $n++;
    }
}
if (!$data['totOnLeft'] && !$hidepast) {
    if (count($gradebook[0][2]) > 1 || $data['catFilter'] != -1) { //want to show cat headers?
        for ($i = 0; $i < count($gradebook[0][2]); $i++) { //category headers
            if (($data['availShow'] < 2 || $data['availShow'] == 3) && $gradebook[0][2][$i][2] > 1) {
                continue;
            } else if ($data['availShow'] == 2 && $gradebook[0][2][$i][2] == 3) {
                continue;
            }
            echo '<th class="cat' . $gradebook[0][2][$i][1] . '"><div><span class="cattothdr">';
            if ($data['availShow'] < 3) {
                echo $gradebook[0][2][$i][0] . '<br/>';
                if (isset($gradebook[0][3][0])) { //using points based
                    echo $gradebook[0][2][$i][3 + $data['availShow']] . '&nbsp;' . AppUtility::t('pts', false);
                } else {
                    echo $gradebook[0][2][$i][11] . '%';
                }
            } else if ($data['availShow'] == 3) { //past and attempted
                echo $gradebook[0][2][$i][0];
            }
            if ($collapsegbcat[$gradebook[0][2][$i][1]] == 0) {
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Collapse]', false) . "</a>";
            } else {
                echo "<br/><a class=small href=\"#\">" . AppUtility::t('[Expand]', false) . "</a>";
            }
            echo '</span></div></th>';
            $n++;
        }
    }
    //total totals
    if ($data['catFilter'] < 0) {
        if (isset($gradebook[0][3][0])) { //using points based
            echo '<th><div><span class="cattothdr">' . AppUtility::t('Total', false) . '<br/>' . $gradebook[0][3][$data['availShow']] . '&nbsp;' . AppUtility::t('pts', false) . '</span></div></th>';
            echo '<th><div>%</div></th>';
            $n += 2;
        } else {
            echo '<th><div><span class="cattothdr">' . AppUtility::t('Weighted Total %', false) . '</span></div></th>';
            $n++;
        }
    }
}
?>
        </thead>
        <tbody class='gradebook-table-body'>
        <?php
for ($i = 1; $i < count($gradebook); $i++) {
    if ($i == 1) {
        $insdiv = "<div>";
        $enddiv = "</div>";
    } else {
        $insdiv = '';
        $enddiv = '';
    }
    if ($i % 2 != 0) {
        echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
    } else {
        echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
    }
    echo '<td class="locked" scope="row"><div class="trld">';
    if ($gradebook[$i][0][0] != "Averages" && $data['isTeacher']) { ?>
        <div class="checkbox override-hidden">
            <label>
                <input type="checkbox" name='checked' value='<?php echo $gradebook[$i][4][0] ?>'/>
                <span class="cr"><i class="cr-icon fa fa-check"></i></span>
            </label>
        </div>
    <?php
    } ?>
    <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?cid='.$course->id.'&studentId='.$gradebook[$i][4][0])?>" >

    <?php
    if ($gradebook[$i][4][1] > 0) {
        echo '<span class="greystrike">' . $gradebook[$i][0][0] . '</span>';
    } else {
        echo $gradebook[$i][0][0];
    }
    echo '</a>';
    if ($gradebook[$i][4][3] == 1) {
        echo '<sup>*</sup>';
    }
    echo '</div></td>';
    if ($data['showPics'] == 1 && $gradebook[$i][4][2] == 1) {
        echo "<td>{$insdiv}<div class=\"trld\"><img src=\"#\"/></div></td>";
    } else if ($data['showPics'] == 2 && $gradebook[$i][4][2] == 1) {
        echo "<td>{$insdiv}<div class=\"trld\"><img src=\"#\"/></div></td>";
    } else {
        echo '<td>' . $insdiv . '<div class="trld">&nbsp;</div></td>';
    }
    for ($j = ($gradebook[0][0][1] == 'ID' ? 1 : 2); $j < count($gradebook[0][0]); $j++) {
        echo '<td class="c">' . $insdiv . $gradebook[$i][0][$j] . $enddiv . '</td>';
    }
    if ($data['totOnLeft'] && !$hidepast) {
        //total totals
        if ($data['catFilter'] < 0) {
            if ($data['availShow'] == 3) {
                if ($gradebook[$i][0][0] == 'Averages') {
                    if (isset($gradebook[$i][3][8])) { //using points based
                        echo '<td class="c">' . $insdiv . $gradebook[$i][3][6] . '%' . $enddiv . '</td>';
                    }
                    echo '<td class="c">' . $insdiv . $gradebook[$i][3][6] . '%' . $enddiv . '</td>';
                } else {
                    if (isset($gradebook[$i][3][8])) { //using points based
                        echo '<td class="c">' . $insdiv . $gradebook[$i][3][6] . '/' . $gradebook[$i][3][7] . $enddiv . '</td>';
                        echo '<td class="c">' . $insdiv . $gradebook[$i][3][8] . '%' . $enddiv . '</td>';
                    } else {
                        echo '<td class="c">' . $insdiv . $gradebook[$i][3][6] . '%' . $enddiv . '</td>';
                    }
                }
            } else {
                if (isset($gradebook[0][3][0])) { //using points based
                    echo '<td class="c">' . $insdiv . $gradebook[$i][3][$data['availShow']] . $enddiv . '</td>';
                    echo '<td class="c">' . $insdiv . $gradebook[$i][3][$data['availShow'] + 3] . '%' . $enddiv . '</td>';
                } else {
                    echo '<td class="c">' . $insdiv . $gradebook[$i][3][$data['availShow']] . '%' . $enddiv . '</td>';
                }
            }
        }
        //category totals
        if (count($gradebook[0][2]) > 1 || $data['catFilter'] != -1) { //want to show cat headers?
            for ($j = 0; $j < count($gradebook[0][2]); $j++) { //category headers
                if (($data['availShow'] < 2 || $data['availShow'] == 3) && $gradebook[0][2][$j][2] > 1) {
                    continue;
                } else if ($data['availShow'] == 2 && $gradebook[0][2][$j][2] == 3) {
                    continue;
                }
                if ($data['catFilter'] != -1 && $data['availShow'] < 3 && $gradebook[0][2][$j][$data['availShow'] + 3] > 0) {
                    echo '<td class="c">' . $insdiv;
                    if ($gradebook[$i][0][0] == 'Averages' && $data['availShow'] != 3) {
                        echo "<span onmouseover=\"tipshow(this,'" . AppUtility::t('5-number summary:', false) . " {$gradebook[0][2][$j][6+$data['availShow']]}')\" onmouseout=\"tipout()\" >";
                    }
                    echo $gradebook[$i][2][$j][$data['availShow']] . ' (' . round(100 * $gradebook[$i][2][$j][$data['availShow']] / $gradebook[0][2][$j][$data['availShow'] + 3]) . '%)';

                    if ($gradebook[$i][0][0] == 'Averages' && $data['availShow'] != 3) {
                        echo '</span>';
                    }
                    echo $enddiv . '</td>';
                } else {
                    echo '<td class="c">' . $insdiv;
                    if ($gradebook[$i][0][0] == 'Averages') {
                        echo "<span onmouseover=\"tipshow(this,'" . AppUtility::t('5-number summary:', false) . " {$gradebook[0][2][$j][6+$data['availShow']]}')\" onmouseout=\"tipout()\" >";
                    }
                    if ($data['availShow'] == 3) {
                        if ($gradebook[$i][0][0] == 'Averages') {
                            echo $gradebook[$i][2][$j][3] . '%';//echo '-';
                        } else {
                            echo $gradebook[$i][2][$j][3] . '/' . $gradebook[$i][2][$j][4];
                        }
                    } else {
                        if (isset($gradebook[$i][3][8])) { //using points based
                            echo $gradebook[$i][2][$j][$data['availShow']];
                        } else {
                            if ($gradebook[0][2][$j][3 + $data['availShow']] > 0) {
                                echo round(100 * $gradebook[$i][2][$j][$data['availShow']] / $gradebook[0][2][$j][3 + $data['availShow']], 1) . '%';
                            } else {
                                echo '0%';
                            }
                        }
                    }
                    if ($gradebook[$i][0][0] == 'Averages') {
                        echo '</span>';
                    }
                    echo $enddiv . '</td>';
                }

            }
        }
    }
    //assessment values
    if ($data['catFilter'] > -2) {
        for ($j = 0; $j < count($gradebook[0][1]); $j++) {
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
            if ($gradebook[0][1][$j][6] == 0 && $gradebook[$i][1][$j][4] != 'average' && ((isset($gradebook[$i][1][$j][3]) && $gradebook[$i][1][$j][3] > 9) || (!isset($gradebook[$i][1][$j][3]) && $gradebook[0][1][$j][3] == 1))) {
                echo '<td class="c isact">' . $insdiv;
            } else {
                echo '<td class="c">' . $insdiv;
            }
            if (isset($gradebook[$i][1][$j][5]) && ($gradebook[$i][1][$j][5] & (1 << $data['availShow'])) && !$hidepast) {
                echo '<span style="font-style:italic">';
            }
            if ($gradebook[0][1][$j][6] == 0) {//online
                if (isset($gradebook[$i][1][$j][0])) {
                    if ($data['isTutor'] && $gradebook[$i][1][$j][4] == 'average') {
                    } else if ($gradebook[$i][1][$j][4] == 'average') {
                        echo "<a href=\"#\" ";
                        echo "onmouseover=\"tipshow(this,'" . AppUtility::t('5-number summary:', false) . " {$gradebook[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
                        echo ">";
                    } else {   ?>
                        <a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/gradebook-view-assessment-details?stu='.$data['defaultValuesArray']['studentId'].'&cid='.$course->id.'&asid='.$gradebook[$i][1][$j][4].'&uid='.$gradebook[$i][4][0])?>">
                    <?php }
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
                if (isset($gradebook[$i][1][$j][6])) {
                    if ($gradebook[$i][1][$j][6] > 1) {
                        if ($gradebook[$i][1][$j][6] > 2) {
                            echo '<sup>LP (' . ($gradebook[$i][1][$j][6] - 1) . ')</sup>';
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
                        echo "onmouseover=\"tipshow(this,'" . AppUtility::t('5-number summary:', false) . " {$gradebook[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
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
            } else if ($gradebook[0][1][$j][6] == 2) { //discuss
                if (isset($gradebook[$i][1][$j][0])) {
                    if ($gradebook[$i][0][0] != 'Averages') {
                        echo "<a href=\"#\">";
                        echo $gradebook[$i][1][$j][0];
                        echo '</a>';
                    } else {
                        echo "<span onmouseover=\"tipshow(this,'" . AppUtility::t('5-number summary:', false) . " {$gradebook[0][1][$j][9]}')\" onmouseout=\"tipout()\"> ";
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
                        echo "onmouseover=\"tipshow(this,'" . AppUtility::t('5-number summary:', false) . " {$gradebook[0][1][$j][9]}')\" onmouseout=\"tipout()\" ";
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
            if (isset($gradebook[$i][1][$j][5]) && ($gradebook[$i][1][$j][5] & (1 << $data['availShow'])) && !$hidepast) {
                echo '<sub>d</sub></span>';
            }
            echo $enddiv . '</td>';
        }
    }
    if (!$data['totOnLeft'] && !$hidepast) {
        //category totals
        if (count($gradebook[0][2]) > 1 || $data['catFilter'] != -1) { //want to show cat headers?
            for ($j = 0; $j < count($gradebook[0][2]); $j++) { //category headers
                if (($data['availShow'] < 2 || $data['availShow'] == 3) && $gradebook[0][2][$j][2] > 1) {
                    continue;
                } else if ($data['availShow'] == 2 && $gradebook[0][2][$j][2] == 3) {
                    continue;
                }
                if ($data['catFilter'] != -1 && $data['availShow'] < 3 && $gradebook[0][2][$j][$data['availShow'] + 3] > 0) {
                    echo '<td class="c">' . $insdiv;
                    if ($gradebook[$i][0][0] == 'Averages' && $data['availShow'] != 3) {
                        echo "<span onmouseover=\"tipshow(this,'" . AppUtility::t('5-number summary:', false) . " {$gradebook[0][2][$j][6+$data['availShow']]}')\" onmouseout=\"tipout()\" >";
                    }
                    echo $gradebook[$i][2][$j][$data['availShow']] . ' (' . round(100 * $gradebook[$i][2][$j][$data['availShow']] / $gradebook[0][2][$j][$data['availShow'] + 3]) . '%)';

                    if ($gradebook[$i][0][0] == 'Averages' && $data['availShow'] != 3) {
                        echo '</span>';
                    }
                    echo $enddiv . '</td>';
                } else {
                    echo '<td class="c">' . $insdiv;
                    if ($gradebook[$i][0][0] == 'Averages' && $data['availShow'] < 3) {
                        echo "<span onmouseover=\"tipshow(this,'" . AppUtility::t('5-number summary:', false) . " {$gradebook[0][2][$j][6+$data['availShow']]}')\" onmouseout=\"tipout()\" >";
                    }
                    if ($data['availShow'] == 3) {
                        if ($gradebook[$i][0][0] == 'Averages') {
                            echo $gradebook[$i][2][$j][3] . '%';
                        } else {
                            echo $gradebook[$i][2][$j][3] . '/' . $gradebook[$i][2][$j][4];
                        }
                    } else {
                        if (isset($gradebook[$i][3][8])) { //using points based
                            echo $gradebook[$i][2][$j][$data['availShow']];
                        } else {
                            if ($gradebook[0][2][$j][3 + $data['availShow']] > 0) {
                                echo round(100 * $gradebook[$i][2][$j][$data['availShow']] / $gradebook[0][2][$j][3 + $data['availShow']], 1) . '%';
                            } else {
                                echo '0%';
                            }
                        }
                    }
                    if ($gradebook[$i][0][0] == 'Averages' && $data['availShow'] < 3) {
                        echo '</span>';
                    }
                    echo $enddiv . '</td>';
                }
            }
        }

        //total totals
        if ($data['catFilter'] < 0) {
            if ($data['availShow'] == 3) {
                if ($gradebook[$i][0][0] == 'Averages') {
                    if (isset($gradebook[$i][3][8])) { //using points based
                        echo '<td class="c">' . $insdiv . $gradebook[$i][3][6] . '%' . $enddiv . '</td>';
                    }
                    echo '<td class="c">' . $insdiv . $gradebook[$i][3][6] . '%' . $enddiv . '</td>';
                } else {
                    if (isset($gradebook[$i][3][8])) { //using points based
                        echo '<td class="c">' . $insdiv . $gradebook[$i][3][6] . '/' . $gradebook[$i][3][7] . $enddiv . '</td>';
                        echo '<td class="c">' . $insdiv . $gradebook[$i][3][8] . '%' . $enddiv . '</td>';

                    } else {
                        echo '<td class="c">' . $insdiv . $gradebook[$i][3][6] . '%' . $enddiv . '</td>';
                    }
                }
            } else {
                if (isset($gradebook[0][3][0])) { //using points based
                    echo '<td class="c">' . $insdiv . $gradebook[$i][3][$data['availShow']] . $enddiv . '</td>';
                    echo '<td class="c">' . $insdiv . $gradebook[$i][3][$data['availShow'] + 3] . '%' . $enddiv . '</td>';
                } else {
                    echo '<td class="c">' . $insdiv . $gradebook[$i][3][$data['availShow']] . '%' . $enddiv . '</td>';
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
</div>
</div>
 <!-- jQuery --><!--
<script type="text/javascript" charset="utf8" src="http://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.9.0.min.js"></script-->>
<!-- DataTables -->
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charser="utf8" src="//cdn.datatables.net/fixedcolumns/3.0.3/js/dataTables.fixedColumns.min.js"></script>

