<?php
use app\components\AppUtility;
use app\components\AppConstant;
use app\controllers\AppController;
$this->title = 'Gradebook Export';
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Gradebook'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']); ?>
</div>
<div class="tab-content shadowBox">
    <?php
    if (!$isteacher)
    {
        echo "This page not available to students";
    }else
    {
        if (!isset($params['commentloc']))
        {
            if (isset($params['export']))
            {
                $para = 'export=' . $params['export'];
            } else if (isset($params['emailgb']))
            {
                $para = 'emailgb=' . $params['emailgb'];
            } ?>
            <form method=post action="gradebook-export?cid=<?php echo $course->id ?>&stu=<?php echo $stu ?>&gbmode=<?php echo $gbmode ?>&<?php echo $para ?>" class="nolimit">

               <?php if (isset($params['export'])) { ?>
                <div class="col-md-12 col-sm-12 gradebook-export-file-header">
                    <a class="padding-left-fifteen" href="<?php echo AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid=' . $course->id); ?> ">
                        Return to gradebook
                    </a>
                </div>
                <?php } ?>

                <div class="col-md-12 col-sm-12 gradebook-export-file-padding">
                <?php if ($params['emailgb'] == "ask") {
                    echo "
                    <div class='col-md-12 col-sm-12 padding-left-zero padding-bottom-twenty padding-top-five'>
                        <div class=\"col-md-3 col-sm-4 select-text-margin\">Email Gradebook To</div>
                        <div class=\"col-md-3 col-sm-3 padding-left-zero\">
                                <input class='form-control' type=text name=\"email\" size=\"30\"/>
                        </div>
                    </div>";
                } ?>
                <?php echo '
                        <div class="padding-left-zero col-sm-12 col-md-12">
                        <div class="col-md-3 col-sm-4">Locked students?</div>
                        <div class="col-md-3 col-sm-3 padding-left-three">
                            <span>
                                <input type="radio" name="locked" value="hide" checked="checked">
                                <span class="padding-left-five">Hide</span>
                            </span>
                            <span class="padding-left-twenty">
                                <input type="radio" name="locked" value="show" >
                                <span class="padding-left-five">Show</span>
                            </span>
                        </div>
                    </div>
                    <div class="padding-left-zero col-sm-12 col-md-12 padding-top-twenty">
                        <div class="col-md-3 col-sm-4">Separate header line for points possible?</div>
                        <div class="col-md-3 col-sm-3 padding-left-three">
                           <span>
                                <input type="radio" name="pointsln" value="0" checked="checked">
                                <span class="padding-left-five">No</span>
                            </span>
                            <span class="padding-left-thirty-one">
                                <input type="radio" name="pointsln" value="1">
                                <span class="padding-left-five">Yes</span>
                            </span>
                        </div>
                    </div>
                    <div class="padding-left-zero col-md-12 col-sm-12 padding-top-twenty">
                        <div class="col-md-3 col-sm-4 ">Assessment comments:</div>
                        <div class="col-md-4 col-sm-5 padding-left-three">
                            <span class="col-md-12 col-sm-12 padding-left-zero">
                                <input type="radio" name="commentloc" value="-1" checked="checked">
                                <span class="padding-left-five">Don\'t include comments</span>
                            </span>
                            <span class="col-md-12 col-sm-12 padding-left-zero padding-top-ten">
                                <input type="radio" name="commentloc" value="1">
                                <span class="padding-left-five">Separate set of columns at the end</span>
                            </span>
                            <span class="col-md-12 col-sm-12 padding-left-zero padding-top-ten">
                                <input type="radio" name="commentloc" value="0">
                                <span class="padding-left-five">After each score column</span>
                            </span>
                        </div>
                    </div>
                    <div class="padding-left-zero col-md-12 col-sm-12 padding-top-twenty">
                        <div class="col-md-3 col-sm-4">Include last login date?</div>
                        <div class="col-md-3 col-sm-3 padding-left-three">
                            <span class="">
                                <input type="radio" name="lastlogin" value="0" checked="checked">
                                <span class="padding-left-five">No</span>
                            </span>
                            <span class="padding-left-thirty-one">
                                <input type="radio" name="lastlogin" value="1" >
                                <span class="padding-left-five">Yes</span>
                            </span>
                        </div>
                    </div>
                    <div class="padding-left-zero col-md-12 col-sm-12 padding-top-twenty">
                        <div class="col-md-3 col-sm-4">Include total number of logins?</div>
                        <div class="col-md-3 col-sm-3 padding-left-three">
                            <span class="">
                                <input type="radio" name="logincnt" value="0" checked="checked">
                                <span class="padding-left-five">No</span>
                            </span>
                            <span class="padding-left-thirty-one">
                                <input type="radio" name="logincnt" value="1" >
                                <span class="padding-left-five">Yes</span>
                            </span>
                        </div>
                    </div>';
                    if (isset($params['export'])) { ?>
                    <div class="padding-left-zero col-md-offset-3 col-md-9 col-sm-9 col-sm-offset-4 padding-top-twenty">
                       <span class="">
                           <input type=submit name="submit" value="Download Gradebook as CSV" />
                       </span>
                       <span class="margin-left-fifteen">
                           <input type=submit name="submit" value="Download Gradebook for Excel" />
                       </span>
                    </div>

                     <?php echo '
                     <div class="col-md-12 col-sm-12 padding-top-twenty">
                        When you click the
                        <b>Download Gradebook</b>
                        button, your browser will probably ask if you want to save or
                        open the file.  Click
                        <b>Save</b> to save the file to your computer, or
                        <b>Open</b> to open the gradebook in Excel
                        or whatever program your computer has set to open .csv spreadsheet files
                     </div>

                    <div class="col-md-12 col-sm-12 padding-top-twenty">A CSV (comma separated values) file will just contain data, and can be opened in most spreadsheet programs</div>

                    <div class="col-md-12 col-sm-12 padding-top-twenty">Using the Download for Excel button will generate an HTML file that Excel can open, and will most likely preserve coloring and other formatting</div>
                ';
                } else {
                    echo '<div class="padding-left-zero col-md-offset-3 col-md-9 col-sm-9 col-sm-offset-4 padding-top-twenty padding-bottom-five">
                            <input type=submit value="Email Gradebook" />
                          </div>';
                }
                echo '</div></form>';
            } else
            {
                    if (isset($params['email']))
                    {
                        $params['emailgb'] = $params['email'];
                    }
                    global $commentloc, $pointsln, $lastlogin, $logincnt, $hidelocked;
                    $commentloc = $params['commentloc'];  //0: interleve, 1: at end
                    $pointsln = $params['pointsln']; //0: on main, 1: separate line
                    $lastlogin = $params['lastlogin']; //0: no, 1 yes
                    $logincnt = $params['logincnt']; //0: no, 1 yes
                    $catfilter = -1;
                    $secfilter = -1;
                    //Gbmode : Links NC Dates
                    $totonleft = floor($gbmode / 1000) % 10; //0 right, 1 left
                    $links = floor($gbmode / 100) % 10; //0: view/edit, 1 q breakdown
                    $hidenc = (floor($gbmode / 10) % 10) % 4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
                    $availshow = $gbmode % 10; //0: past, 1 past&cur, 2 all
                    if ($params['submit'] == "Download Gradebook for Excel")
                    {
                        header('Content-type: application/vnd.ms-excel');
                        header('Content-Disposition: attachment; filename="gradebook-' . $course->id . '.xls"');
                        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                        header('Pragma: public');
                        echo '<html><head>';
                        echo '<style type="text/css">';
                        echo '</style></head><body>';
                        gbinstrdisp($totalData, $studentData);
                        echo '</body></html>';
                        exit;
                    } else
                    {
                        $gb = gbinstrexport($totalData, $studentData);
                        if (isset($params['export']) && $params['export'] == "true")
                        {
                            header('Content-type: text/csv');
                            header("Content-Disposition: attachment; filename=\"gradebook-$course->id.csv\"");
                            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                            header('Pragma: public');
                            foreach ($gb as $gbline)
                            {
                                $line = '';
                                foreach ($gbline as $val)
                                {
                                    # remove any windows new lines, as they interfere with the parsing at the other end
                                    $val = str_replace("\r\n", "\n", $val);
                                    $val = str_replace("\n", " ", $val);
                                    $val = str_replace(array("<BR>", '<br>', '<br/>'), ' ', $val);
                                    $val = str_replace("&nbsp;", " ", $val);
                                    # if a deliminator char, a double quote char or a newline are in the field, add quotes
                                    if (preg_match("/[\,\"\n\r]/", $val)) {
                                        $val = '"' . str_replace('"', '""', $val) . '"';
                                    }
                                    $line .= $val . ',';
                                }
                                # strip the last deliminator
                                $line = substr($line, 0, -1);
                                $line .= "\n";
                                echo $line;
                            }
                            exit;
                        }
                        if (isset($params['emailgb']))
                        {
                            $line = '';
                            foreach ($gb as $gbline)
                            {
                                foreach ($gbline as $val)
                                {
                                    # remove any windows new lines, as they interfere with the parsing at the other end
                                    $val = str_replace("\r\n", "\n", $val);
                                    $val = str_replace("\n", " ", $val);
                                    $val = str_replace("<BR>", " ", $val);
                                    $val = str_replace("<br/>", " ", $val);
                                    $val = str_replace("&nbsp;", " ", $val);

                                    # if a deliminator char, a double quote char or a newline are in the field, add quotes
                                    if (preg_match("/[\,\"\n\r]/", $val))
                                    {
                                        $val = '"' . str_replace('"', '""', $val) . '"';
                                    }
                                    $line .= $val . ',';
                                }
                                # strip the last deliminator
                                $line = substr($line, 0, -1);
                                $line .= "\n";
                            }
                            $sendfrom = "imathas@yoursite.edu";
                            $boundary = '-----=' . md5(uniqid(rand()));
                            $message = "--" . $boundary . "\n";
                            $message .= "Content-Type: text/csv; name=\"Gradebook\"\n";
                            $message .= "Content-Transfer-Encoding: base64\n";
                            $message .= "Content-Disposition: attachment; filename=\"gradebook.csv\"\n\n";
                            $content_encode = chunk_split(base64_encode($line));
                            $message .= $content_encode . "\n";
                            $message .= "--" . $boundary . "--\n";
                            $headers = "From: $sendfrom\n";
                            $headers .= "MIME-Version: 1.0\n";
                            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";
                            if ($params['emailgb'] == "me")
                            {
                                $params['emailgb'] = $currentUser['email'];
                            }
                            if ($params['emailgb'] != '')
                            {
                                mail($params['emailgb'], "Gradebook for $course->name", $message, $headers);
                                echo "Gradebook Emailed.  <a href='" . AppUtility::getURLFromHome('gradebook', 'gradebook/gradebook?cid=' . $course->id) . "'>Return to Gradebook</a>";
                                exit;
                            }
                        }
                    }
            }
        }
        echo '</div>';
        function gbinstrexport($totalData, $studentData)
        {
            global $nopt, $cid, $gbmode, $stu, $commentloc, $pointsln, $lastlogin, $logincnt;
            $defaultValuesArray = $totalData['defaultValuesArray'];
            $hidenc = $defaultValuesArray['hidenc'];
            $availshow = $defaultValuesArray['availshow'];
            $catfilter = $defaultValuesArray['catfilter'];
            $secfilter = $defaultValuesArray['secfilter'];
            $totonleft = $defaultValuesArray['totonleft'];
            $isdiag = $defaultValuesArray['isDiagnostic'];
            $isteacher = $totalData['isTeacher'];
            $gbt = $totalData['gradebook'];
            $gbo = array();
            $n = 0;
            for ($i = 0; $i < count($gbt[0][0]); $i++) { //biographical headers
                $gbo[0][$n] = $gbt[0][0][$i];
                $n++;
            }
            if ($totonleft)
            {
                //total totals
                if ($catfilter < 0) {
                    if (isset($gbt[0][3][0])) { //using points based
                        $gbo[0][$n] = "Total: " . $gbt[0][3][$availshow] . " pts";
                        $n++;
                        $gbo[0][$n] = "%";
                        $n++;
                    } else {
                        $gbo[0][$n] = "Weighted Total %";
                        $n++;
                    }
                }
                if (count($gbt[0][2]) > 1 || $catfilter != -1) { //want to show cat headers?
                    for ($i = 0; $i < count($gbt[0][2]); $i++) { //category headers
                        if ($availshow < 2 && $gbt[0][2][$i][2] > 1) {
                            continue;
                        } else if ($availshow == 2 && $gbt[0][2][$i][2] == 3) {
                            continue;
                        }
                        if ($availshow < 3) {
                            if (isset($gbt[0][3][0])) { //using points based
                                $gbo[0][$n] = $gbt[0][2][$i][0] . ': ' . $gbt[0][2][$i][3 + $availshow] . ' pts';
                            } else {
                                $gbo[0][$n] = $gbt[0][2][$i][0] . ': ' . $gbt[0][2][$i][11] . '%';
                            }
                        } else if ($availshow == 3) {
                            $gbo[0][$n] = $gbt[0][2][$i][0];
                        }
                        $n++;
                    }
                }

            }
            if ($catfilter > -2) {
                for ($i = 0; $i < count($gbt[0][1]); $i++) { //assessment headers
                    if (!$isteacher && $gbt[0][1][$i][4] == 0) { //skip if hidden
                        continue;
                    }
                    if ($hidenc == 1 && $gbt[0][1][$i][4] == 0) { //skip NC
                        continue;
                    } else if ($hidenc == 2 && ($gbt[0][1][$i][4] == 0 || $gbt[0][1][$i][4] == 3)) {//skip all NC
                        continue;
                    }
                    if ($gbt[0][1][$i][3] > $availshow) {
                        continue;
                    }
                    //name and points
                    $gbo[0][$n] = $gbt[0][1][$i][0] . ': ';
                    if ($gbt[0][1][$i][4] == 0 || $gbt[0][1][$i][4] == 3) {
                        $gbo[0][$n] .= ' (Not Counted)';
                    } else {
                        $gbo[0][$n] .= $gbt[0][1][$i][2] . '&nbsp;pts';
                        if ($gbt[0][1][$i][4] == 2) {
                            $gbo[0][$n] .= ' (EC)';
                        }
                    }
                    if ($gbt[0][1][$i][5] == 1) {
                        $gbo[0][$n] .= ' (PT)';
                    }
                    $n++;
                    if ($commentloc == 0) {
                        $gbo[0][$n] = $gbt[0][1][$i][0] . ': Comments';
                        $n++;
                    }
                }
            }
            if (!$totonleft) {
                //total totals
                if (count($gbt[0][2]) > 1 || $catfilter != -1) { //want to show cat headers?
                    for ($i = 0; $i < count($gbt[0][2]); $i++) { //category headers
                        if ($availshow < 2 && $gbt[0][2][$i][2] > 1) {
                            continue;
                        } else if ($availshow == 2 && $gbt[0][2][$i][2] == 3) {
                            continue;
                        }
                        if ($availshow < 3) {
                            $gbo[0][$n] = $gbt[0][2][$i][0] . ': ' . $gbt[0][2][$i][3 + $availshow] . ' pts';
                        } else if ($availshow == 3) {
                            $gbo[0][$n] = $gbt[0][2][$i][0];
                        }
                        $n++;
                    }
                }
                if ($catfilter < 0) {
                    if (isset($gbt[0][3][0])) { //using points based
                        $gbo[0][$n] = "Total: " . $gbt[0][3][$availshow] . " pts";
                        $n++;
                        $gbo[0][$n] = "%";
                        $n++;
                    } else {
                        $gbo[0][$n] = "Weighted Total %";
                        $n++;
                    }
                }
            }
            $gbo[0][$n] = "Comment";
            $gbo[0][$n + 1] = "Instructor Note";
            $n += 2;
            if ($commentloc == 1) {
                if ($catfilter > -2) {
                    for ($i = 0; $i < count($gbt[0][1]); $i++) { //assessment comment headers
                        if (!$isteacher && $gbt[0][1][$i][4] == 0) { //skip if hidden
                            continue;
                        }
                        if ($hidenc == 1 && $gbt[0][1][$i][4] == 0) { //skip NC
                            continue;
                        } else if ($hidenc == 2 && ($gbt[0][1][$i][4] == 0 || $gbt[0][1][$i][4] == 3)) {//skip all NC
                            continue;
                        }
                        if ($gbt[0][1][$i][3] > $availshow) {
                            continue;
                        }
                        //name and points
                        $gbo[0][$n] = $gbt[0][1][$i][0] . ': Comments';
                        $n++;
                    }
                }
            }
            //get gb comments;
            $gbcomments = array();
            foreach ($studentData as $row) {
                $gbcomments[$row['userid']] = array($row['gbcomment'], $row['gbinstrcomment']);
            }
            //create student rows
            for ($i = 1; $i < count($gbt); $i++) {
                $n = 0;

                for ($j = 0; $j < count($gbt[0][0]); $j++) {
                    $gbo[$i][$n] = $gbt[$i][0][$j];
                    $n++;
                }
                if ($totonleft) {
                    //total totals
                    if ($catfilter < 0) {
                        if ($availshow == 3) {
                            if (isset($gbt[$i][3][8])) { //using points based
                                $gbo[$i][$n] = $gbt[$i][3][6] . '/' . $gbt[$i][3][7];
                                $n++;
                                $gbo[$i][$n] = $gbt[$i][3][8];
                                $n++;
                            } else {
                                $gbo[$i][$n] = $gbt[$i][3][6];
                                $n++;
                            }
                        } else {
                            if (isset($gbt[0][3][0])) { //using points based
                                $gbo[$i][$n] = $gbt[$i][3][$availshow];
                                $n++;
                                $gbo[$i][$n] = $gbt[$i][3][$availshow + 3];
                                $n++;
                            } else {
                                $gbo[$i][$n] = $gbt[$i][3][$availshow];
                                $n++;
                            }
                        }
                    }
                    //category totals
                    if (count($gbt[0][2]) > 1 || $catfilter != -1) { //want to show cat headers?
                        for ($j = 0; $j < count($gbt[0][2]); $j++) { //category headers
                            if ($availshow < 2 && $gbt[0][2][$j][2] > 1) {
                                continue;
                            } else if ($availshow == 2 && $gbt[0][2][$j][2] == 3) {
                                continue;
                            }
                            $gbo[$i][$n] = $gbt[$i][2][$j][$availshow];
                            $n++;
                        }
                    }
                }
                //assessment values
                if ($catfilter > -2) {
                    for ($j = 0; $j < count($gbt[0][1]); $j++) {
                        if (!$isteacher && $gbt[0][1][$j][4] == 0) { //skip if hidden
                            continue;
                        }
                        if ($hidenc == 1 && $gbt[0][1][$j][4] == 0) { //skip NC
                            continue;
                        } else if ($hidenc == 2 && ($gbt[0][1][$j][4] == 0 || $gbt[0][1][$j][4] == 3)) {//skip all NC
                            continue;
                        }
                        if ($gbt[0][1][$j][3] > $availshow) {
                            continue;
                        }
                        if ($gbt[0][1][$j][6] == 0) {//online
                            if (isset($gbt[$i][1][$j][0])) {
                                $gbo[$i][$n] = $gbt[$i][1][$j][0];
                                if ($gbt[$i][1][$j][3] == 1) {
                                    $gbo[$i][$n] .= ' (NC)';
                                } else if ($gbt[$i][1][$j][3] == 2) {
                                    $gbo[$i][$n] .= ' (IP)';
                                } else if ($gbt[$i][1][$j][3] == 3) {
                                    $gbo[$i][$n] .= ' (OT)';
                                } else if ($gbt[$i][1][$j][3] == 4) {
                                    $gbo[$i][$n] .= ' {PT)';
                                }

                            } else { //no score
                                $gbo[$i][$n] = '-';
                            }
                        } else if ($gbt[0][1][$j][6] == 1) { //offline
                            if (isset($gbt[$i][1][$j][0])) {
                                $gbo[$i][$n] = $gbt[$i][1][$j][0];
                                if ($gbt[$i][1][$j][3] == 1) {
                                    $gbo[$i][$n] .= ' (NC)';
                                }
                            } else {
                                $gbo[$i][$n] = '-';
                            }

                        } else if ($gbt[0][1][$j][6] == 2) { //discuss
                            if (isset($gbt[$i][1][$j][0])) {
                                $gbo[$i][$n] = $gbt[$i][1][$j][0];
                            } else {
                                $gbo[$i][$n] = '-';
                            }
                        }
                        $n++;
                        if ($commentloc == 0) {
                            if (isset($gbt[$i][1][$j][1])) {
                                $gbo[$i][$n] = $gbt[$i][1][$j][1];
                            } else {
                                $gbo[$i][$n] = '';
                            }
                            $n++;
                        }
                    }
                }
                if (!$totonleft) {
                    //category totals
                    if (count($gbt[0][2]) > 1 || $catfilter != -1) { //want to show cat headers?
                        for ($j = 0; $j < count($gbt[0][2]); $j++) { //category headers
                            if ($availshow < 2 && $gbt[0][2][$j][2] > 1) {
                                continue;
                            } else if ($availshow == 2 && $gbt[0][2][$j][2] == 3) {
                                continue;
                            }
                            $gbo[$i][$n] = $gbt[$i][2][$j][$availshow];
                            $n++;
                        }
                    }
                    //total totals
                    if ($catfilter < 0) {
                        if ($availshow == 3) {
                            if (isset($gbt[$i][3][8])) { //using points based
                                $gbo[$i][$n] = $gbt[$i][3][6] . '/' . $gbt[$i][3][7];
                                $n++;
                                $gbo[$i][$n] = $gbt[$i][3][8];
                                $n++;
                            } else {
                                $gbo[$i][$n] = $gbt[$i][3][6];
                                $n++;
                            }
                        } else {
                            if (isset($gbt[0][3][0])) { //using points based
                                $gbo[$i][$n] = $gbt[$i][3][$availshow];
                                $n++;
                                $gbo[$i][$n] = $gbt[$i][3][$availshow + 3];
                                $n++;
                            } else {
                                $gbo[$i][$n] = $gbt[$i][3][$availshow];
                                $n++;
                            }
                        }
                    }

                }
                if (isset($gbcomments[$gbt[$i][4][0]])) {
                    $gbo[$i][$n] = $gbcomments[$gbt[$i][4][0]][0];
                    $gbo[$i][$n + 1] = $gbcomments[$gbt[$i][4][0]][1];
                } else {
                    $gbo[$i][$n] = '';
                    $gbo[$i][$n + 1] = '';
                }
                $n += 2;
                if ($commentloc == 1) {
                    if ($catfilter > -2) {
                        for ($j = 0; $j < count($gbt[0][1]); $j++) {
                            if (!$isteacher && $gbt[0][1][$j][4] == 0) { //skip if hidden
                                continue;
                            }
                            if ($hidenc == 1 && $gbt[0][1][$j][4] == 0) { //skip NC
                                continue;
                            } else if ($hidenc == 2 && ($gbt[0][1][$j][4] == 0 || $gbt[0][1][$j][4] == 3)) {//skip all NC
                                continue;
                            }
                            if ($gbt[0][1][$j][3] > $availshow) {
                                continue;
                            }

                            if (isset($gbt[$i][1][$j][1])) {
                                $gbo[$i][$n] = $gbt[$i][1][$j][1];
                            } else {
                                $gbo[$i][$n] = '';
                            }
                            $n++;
                        }
                    }
                }
            }
            if ($pointsln == 1) {
                $ins = array();

                for ($i = 0; $i < count($gbo[0]); $i++) {
                    if (preg_match('/(-?[\d\.]+)(\s*|&nbsp;)pts.*/', $gbo[0][$i], $matches)) {
                        $ins[$i] = $matches[1];
                    } else {
                        $ins[$i] = '';
                    }
                }
                $ins[0] = "Points Possible";
                array_splice($gbo, 1, 0, array($ins));
            }
            return $gbo;
        }

        //HTML formatted, for Excel import?
        function gbinstrdisp($totalData, $studentData)
        {
            global $cid, $gbmode, $stu, $imasroot, $tutorsection, $commentloc, $pointsln, $logincnt;
            $defaultValuesArray = $totalData['defaultValuesArray'];
            $hidenc = $defaultValuesArray['hidenc'];
            $istutor = $totalData['isTutor'];
            $availshow = $defaultValuesArray['availshow'];
            $catfilter = $defaultValuesArray['catfilter'];
            $secfilter = $defaultValuesArray['secfilter'];
            $totonleft = $defaultValuesArray['totonleft'];
            $isdiag = $defaultValuesArray['isDiagnostic'];
            $isteacher = $totalData['isTeacher'];
            $gbt = $totalData['gradebook'];
            if ($availshow == 4) {
                $availshow = 1;
                $hidepast = true;
            }
            echo '<table class="gb" id="myTable"><thead><tr>';
            $n = 0;
            for ($i = 0; $i < count($gbt[0][0]); $i++) { //biographical headers
                //if ($i==1 && $gbt[0][0][1]!='ID') { continue;}
                echo '<th>' . $gbt[0][0][$i];
                if ($gbt[0][0][$i] == 'Name') {
                    if ($pointsln == 1) {
                        echo '<br/>';
                    } else {
                        echo '&nbsp;';
                    }
                    echo '<span class="small">N=' . (count($gbt) - 2) . '</span>';
                }
                echo '</th>';
                $n++;
            }
            if ($totonleft && !$hidepast) {
                //total totals
                if ($catfilter < 0) {
                    if (isset($gbt[0][3][0])) { //using points based
                        echo '<th><span class="cattothdr">Total';
                        if ($pointsln == 1) {
                            echo '<br/>';
                        } else {
                            echo '&nbsp;';
                        }
                        echo $gbt[0][3][$availshow] . '&nbsp;pts</span></th>';
                        echo '<th>%</th>';
                        $n += 2;
                    } else {
                        echo '<th><span class="cattothdr">Weighted Total %</span></th>';
                        $n++;
                    }
                }
                if (count($gbt[0][2]) > 1 || $catfilter != -1) { //want to show cat headers?
                    for ($i = 0; $i < count($gbt[0][2]); $i++) { //category headers
                        if (($availshow < 2 || $availshow == 3) && $gbt[0][2][$i][2] > 1) {
                            continue;
                        } else if ($availshow == 2 && $gbt[0][2][$i][2] == 3) {
                            continue;
                        }
                        echo '<th class="cat' . $gbt[0][2][$i][1] . '"><span class="cattothdr">';
                        echo $gbt[0][2][$i][0];
                        if ($pointsln == 1) {
                            echo '<br/>';
                        } else {
                            echo '&nbsp;';
                        }
                        if ($availshow < 3) {
                            if (isset($gbt[0][3][0])) { //using points based
                                echo $gbt[0][2][$i][3 + $availshow] . '&nbsp;', _('pts');
                            } else {
                                echo $gbt[0][2][$i][11] . '%';
                            }
                        } else {
                            if (isset($gbt[0][2][$i][11])) {
                                echo $gbt[0][2][$i][11] . '%';
                            }
                        }
                        echo '</span></th>';
                        $n++;
                    }
                }
            }
            if ($catfilter > -2) {
                for ($i = 0; $i < count($gbt[0][1]); $i++) { //assessment headers
                    if (!$isteacher && !$istutor && $gbt[0][1][$i][4] == 0) { //skip if hidden
                        continue;
                    }
                    if ($hidenc == 1 && $gbt[0][1][$i][4] == 0) { //skip NC
                        continue;
                    } else if ($hidenc == 2 && ($gbt[0][1][$i][4] == 0 || $gbt[0][1][$i][4] == 3)) {//skip all NC
                        continue;
                    }
                    if ($gbt[0][1][$i][3] > $availshow) {
                        continue;
                    }
                    if ($hidepast && $gbt[0][1][$i][3] == 0) {
                        continue;
                    }
                    //name and points
                    echo '<th class="cat' . $gbt[0][1][$i][1] . '">' . $gbt[0][1][$i][0];
                    if ($pointsln == 1) {
                        echo '<br/>';
                    } else {
                        echo '&nbsp;';
                    }
                    if ($gbt[0][1][$i][4] == 0 || $gbt[0][1][$i][4] == 3) {
                        echo $gbt[0][1][$i][2] . ' (Not Counted)';
                    } else {
                        echo $gbt[0][1][$i][2] . '&nbsp;pts';
                        if ($gbt[0][1][$i][4] == 2) {
                            echo ' (EC)';
                        }
                    }
                    if ($gbt[0][1][$i][5] == 1 && $gbt[0][1][$i][6] == 0) {
                        echo ' (PT)';
                    }

                    echo '</th>';
                    $n++;
                    if ($commentloc == 0) {
                        echo '<th>' . $gbt[0][1][$i][0] . ': Comments' . '</th>';
                        $n++;
                    }
                }
            }
            if (!$totonleft && !$hidepast) {
                if (count($gbt[0][2]) > 1 || $catfilter != -1) { //want to show cat headers?
                    for ($i = 0; $i < count($gbt[0][2]); $i++) { //category headers
                        if (($availshow < 2 || $availshow == 3) && $gbt[0][2][$i][2] > 1) {
                            continue;
                        } else if ($availshow == 2 && $gbt[0][2][$i][2] == 3) {
                            continue;
                        }
                        echo '<th class="cat' . $gbt[0][2][$i][1] . '"><span class="cattothdr">';
                        echo $gbt[0][2][$i][0];
                        if ($pointsln == 1) {
                            echo '<br/>';
                        } else {
                            echo '&nbsp;';
                        }
                        if ($availshow < 3) {
                            if (isset($gbt[0][3][0])) { //using points based
                                echo $gbt[0][2][$i][3 + $availshow] . '&nbsp;', _('pts');
                            } else {
                                echo $gbt[0][2][$i][11] . '%';
                            }
                        } else {
                            if (isset($gbt[0][2][$i][11])) {
                                echo $gbt[0][2][$i][11] . '%';
                            }
                        }
                        echo '</span></th>';
                        $n++;
                    }
                }
                //total totals
                if ($catfilter < 0) {
                    if (isset($gbt[0][3][0])) { //using points based
                        echo '<th><span class="cattothdr">Total';
                        if ($pointsln == 1) {
                            echo '<br/>';
                        } else {
                            echo '&nbsp;';
                        }
                        echo $gbt[0][3][$availshow] . '&nbsp;pts</span></th>';
                        echo '<th>%</th>';
                        $n += 2;
                    } else {
                        echo '<th><span class="cattothdr">Weighted Total %</span></th>';
                        $n++;
                    }
                }
            }
            echo '<th>Comment</th>';
            echo '<th>Instructor Note</th>';
            $n += 2;
            if ($commentloc == 1) {
                if ($catfilter > -2) {
                    for ($i = 0; $i < count($gbt[0][1]); $i++) { //assessment comment headers
                        if (!$isteacher && $gbt[0][1][$i][4] == 0) { //skip if hidden
                            continue;
                        }
                        if ($hidenc == 1 && $gbt[0][1][$i][4] == 0) { //skip NC
                            continue;
                        } else if ($hidenc == 2 && ($gbt[0][1][$i][4] == 0 || $gbt[0][1][$i][4] == 3)) {//skip all NC
                            continue;
                        }
                        if ($gbt[0][1][$i][3] > $availshow) {
                            continue;
                        }
                        //name and points
                        echo '<th>' . $gbt[0][1][$i][0] . ': Comments' . '</th>';
                        $n++;
                    }
                }
            }
            echo '</tr></thead><tbody>';
            //get gb comments;
            $gbcomments = array();
            foreach ($studentData as $row) {
                $gbcomments[$row['userid']] = array($row['gbcomment'], $row['gbinstrcomment']);
            }
            //create student rows
            for ($i = 1; $i < count($gbt); $i++) {
                if ($i % 2 != 0) {
                    echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
                } else {
                    echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\">";
                }
                echo '<td class="locked" scope="row">';
                echo $gbt[$i][0][0];
                for ($j = 1; $j < count($gbt[0][0]); $j++) {
                    echo '<td class="c">' . $gbt[$i][0][$j] . '</td>';
                }
                if ($totonleft && !$hidepast) {
                    //total totals
                    if ($catfilter < 0) {
                        if ($availshow == 3) {
                            if (isset($gbt[$i][3][8])) { //using points based
                                echo '<td class="c">' . $insdiv . $gbt[$i][3][6] . '/' . $gbt[$i][3][7] . $enddiv . '</td>';
                                echo '<td class="c">' . $insdiv . $gbt[$i][3][8] . '%' . $enddiv . '</td>';

                            } else {
                                echo '<td class="c">' . $insdiv . $gbt[$i][3][6] . '%' . $enddiv . '</td>';
                            }
                        } else {
                            if (isset($gbt[0][3][0])) { //using points based
                                echo '<td class="c">' . $gbt[$i][3][$availshow] . '</td>';
                                echo '<td class="c">' . $gbt[$i][3][$availshow + 3] . '%</td>';
                            } else {
                                echo '<td class="c">' . $gbt[$i][3][$availshow] . '%</td>';
                            }
                        }
                    }
                    //category totals
                    if (count($gbt[0][2]) > 1 || $catfilter != -1) { //want to show cat headers?
                        for ($j = 0; $j < count($gbt[0][2]); $j++) { //category headers
                            if (($availshow < 2 || $availshow == 3) && $gbt[0][2][$j][2] > 1) {
                                continue;
                            } else if ($availshow == 2 && $gbt[0][2][$j][2] == 3) {
                                continue;
                            }
                            if ($catfilter != -1 && $availshow < 3 && $gbt[0][2][$j][$availshow + 3] > 0) {
                                echo '<td class="c">' . $gbt[$i][2][$j][$availshow] . ' (' . round(100 * $gbt[$i][2][$j][$availshow] / $gbt[0][2][$j][$availshow + 3]) . '%)</td>';
                            } else {
                                echo '<td class="c">';
                                if ($availshow == 3) {
                                    echo $gbt[$i][2][$j][3] . ' of ' . $gbt[$i][2][$j][4];
                                } else {
                                    if (isset($gbt[$i][3][8])) { //using points based
                                        echo $gbt[$i][2][$j][$availshow];
                                    } else {
                                        if ($gbt[0][2][$j][3 + $availshow] > 0) {
                                            echo round(100 * $gbt[$i][2][$j][$availshow] / $gbt[0][2][$j][3 + $availshow], 1) . '%';
                                        } else {
                                            echo '0%';
                                        }
                                    }
                                }
                                echo '</td>';
                            }
                        }
                    }
                }
                //assessment values
                if ($catfilter > -2) {
                    for ($j = 0; $j < count($gbt[0][1]); $j++) {
                        if (!$isteacher && !$istutor && $gbt[0][1][$j][4] == 0) { //skip if hidden
                            continue;
                        }
                        if ($hidenc == 1 && $gbt[0][1][$j][4] == 0) { //skip NC
                            continue;
                        } else if ($hidenc == 2 && ($gbt[0][1][$j][4] == 0 || $gbt[0][1][$j][4] == 3)) {//skip all NC
                            continue;
                        }
                        if ($gbt[0][1][$j][3] > $availshow) {
                            continue;
                        }
                        if ($hidepast && $gbt[0][1][$j][3] == 0) {
                            continue;
                        }
                        echo '<td class="c">';
                        if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5] & (1 << $availshow)) && !$hidepast) {
                            echo '<span style="font-style:italic">';
                        }
                        if ($gbt[0][1][$j][6] == 0) {//online
                            if (isset($gbt[$i][1][$j][0])) {

                                echo $gbt[$i][1][$j][0];
                                if ($gbt[$i][1][$j][3] == 1) {
                                    echo ' (NC)';
                                } else if ($gbt[$i][1][$j][3] == 2) {
                                    echo ' (IP)';
                                } else if ($gbt[$i][1][$j][3] == 3) {
                                    echo ' (OT)';
                                } else if ($gbt[$i][1][$j][3] == 4) {
                                    echo ' (PT)';
                                }

                            } else { //no score
                                if ($gbt[$i][0][0] == 'Averages') {
                                    echo '-';
                                } else {
                                    echo '-';
                                }
                            }
                            if (isset($gbt[$i][1][$j][6])) {
                                if ($gbt[$i][1][$j][6] > 1) {
                                    if ($gbt[$i][1][$j][6] > 2) {
                                        echo '<sup>LP (' . ($gbt[$i][1][$j][6] - 1) . ')</sup>';
                                    } else {
                                        echo '<sup>LP</sup>';
                                    }
                                } else {
                                    echo '<sup>e</sup>';
                                }
                            }
                        } else if ($gbt[0][1][$j][6] == 1) { //offline

                            if (isset($gbt[$i][1][$j][0])) {
                                echo $gbt[$i][1][$j][0];
                                if ($gbt[$i][1][$j][3] == 1) {
                                    echo ' (NC)';
                                }
                            } else {
                                echo '-';
                            }

                            if ($gbt[$i][1][$j][1] == 1) {
                                echo '<sup>*</sup>';
                            }
                        } else if ($gbt[0][1][$j][6] == 2) { //discuss
                            if (isset($gbt[$i][1][$j][0])) {
                                echo $gbt[$i][1][$j][0];
                            } else {
                                echo '-';
                            }
                        }
                        if (isset($gbt[$i][1][$j][5]) && ($gbt[$i][1][$j][5] & (1 << $availshow)) && !$hidepast) {
                            echo '<sub>d</sub></span>';
                        }
                        echo '</td>';
                        if ($commentloc == 0) {
                            if (isset($gbt[$i][1][$j][1])) {
                                echo '<td>' . $gbt[$i][1][$j][1] . '</td>';
                            } else {
                                echo '<td></td>';
                            }
                            $n++;
                        }
                    }
                }
                if (!$totonleft && !$hidepast) {
                    //category totals
                    if (count($gbt[0][2]) > 1 || $catfilter != -1) { //want to show cat headers?
                        for ($j = 0; $j < count($gbt[0][2]); $j++) { //category headers
                            if (($availshow < 2 || $availshow == 3) && $gbt[0][2][$j][2] > 1) {
                                continue;
                            } else if ($availshow == 2 && $gbt[0][2][$j][2] == 3) {
                                continue;
                            }
                            if ($catfilter != -1 && $availshow < 3 && $gbt[0][2][$j][$availshow + 3] > 0) {
                                echo '<td class="c">' . $gbt[$i][2][$j][$availshow] . ' (' . round(100 * $gbt[$i][2][$j][$availshow] / $gbt[0][2][$j][$availshow + 3]) . '%)</td>';
                            } else {
                                echo '<td class="c">';
                                if ($availshow == 3) {
                                    echo $gbt[$i][2][$j][3] . ' of ' . $gbt[$i][2][$j][4];
                                } else {
                                    if (isset($gbt[$i][3][8])) { //using points based
                                        echo $gbt[$i][2][$j][$availshow];
                                    } else {
                                        if ($gbt[0][2][$j][3 + $availshow] > 0) {
                                            echo round(100 * $gbt[$i][2][$j][$availshow] / $gbt[0][2][$j][3 + $availshow], 1) . '%';
                                        } else {
                                            echo '0%';
                                        }
                                    }
                                }
                                echo '</td>';
                            }
                        }
                    }
                    //total totals
                    if ($catfilter < 0) {
                        if ($availshow == 3) {
                            if (isset($gbt[$i][3][8])) { //using points based
                                echo '<td class="c">' . $insdiv . $gbt[$i][3][6] . '/' . $gbt[$i][3][7] . $enddiv . '</td>';
                                echo '<td class="c">' . $insdiv . $gbt[$i][3][8] . '%' . $enddiv . '</td>';

                            } else {
                                echo '<td class="c">' . $insdiv . $gbt[$i][3][6] . '%' . $enddiv . '</td>';
                            }
                        } else {
                            if (isset($gbt[0][3][0])) { //using points based
                                echo '<td class="c">' . $gbt[$i][3][$availshow] . '</td>';
                                echo '<td class="c">' . $gbt[$i][3][$availshow + 3] . '%</td>';
                            } else {
                                echo '<td class="c">' . $gbt[$i][3][$availshow] . '%</td>';
                            }
                        }
                    }
                }
                if (isset($gbcomments[$gbt[$i][4][0]])) {
                    echo '<td>' . $gbcomments[$gbt[$i][4][0]][0] . '</td>';
                    echo '<td>' . $gbcomments[$gbt[$i][4][0]][1] . '</td>';
                } else {
                    echo '<td></td>';
                    echo '<td></td>';
                }
                $n += 2;
                if ($commentloc == 1) {
                    if ($catfilter > -2) {
                        for ($j = 0; $j < count($gbt[0][1]); $j++) {
                            if (!$isteacher && $gbt[0][1][$j][4] == 0) { //skip if hidden
                                continue;
                            }
                            if ($hidenc == 1 && $gbt[0][1][$j][4] == 0) { //skip NC
                                continue;
                            } else if ($hidenc == 2 && ($gbt[0][1][$j][4] == 0 || $gbt[0][1][$j][4] == 3)) {//skip all NC
                                continue;
                            }
                            if ($gbt[0][1][$j][3] > $availshow) {
                                continue;
                            }
                            if (isset($gbt[$i][1][$j][1])) {
                                echo '<td>' . $gbt[$i][1][$j][1] . '</td>';
                            } else {
                                echo '<td></td>';
                            }
                            $n++;
                        }
                    }
                }
                echo '</tr>';
            }
            echo "</tbody></table>";
        }

        ?>
