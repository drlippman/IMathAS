<?php
use app\components\AppUtility;
use kartik\time\TimePicker;
use app\components\AppConstant;
$this->title = AppUtility::t('Form',false);
if (isset($params['cid'])) {
?>
<div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
</div>
<?php } else { ?>
<div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
</div>
<?php } ?>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?> </div>
        </div>
    </div>
</div>
    <div class="tab-content shadowBox non-nav-tab-item ">
<?php
switch($action) {
    case "addnewcourse":
        echo '<br>';
        echo '<div class="col-lg-10"><h2>Your course has been created!</h2></div>';
        echo '<div class="col-lg-10">For students to enroll in this course, you will need to provide them two things</div><ol>';
        echo '<div class=col-lg-10><li>The course ID: <b>'.$cid.'</b></li></div>';
        if (trim($params['ekey'])=='') {
            echo '<div class="col-lg-10"><li>Tell them to leave the enrollment key blank, since you didn\'t specify one.  The enrollment key acts like a course ';
            echo 'password to prevent random strangers from enrolling in your course.  If you want to set an enrollment key, ';
            echo '<a href="forms.php?action=modify&id='.$cid.'">modify your course settings</a></li>';
        } else {
            echo '<div class=col-lg-10><li>The enrollment key: <b>'.$params['ekey'].'</b></li></div>';
        }
        echo '</ol></p><BR class=form><br>';
        echo '<div class="col-lg-10">If you forget these later, you can find them by viewing your course settings.</div><BR class=form><br>';
        echo '<div class=col-lg-10><a href='.AppUtility::getURLFromHome('instructor', 'instructor/index?cid='.$cid).'>Enter the Course</a></div>';
    break;
    case "delete":
        echo '<div id="headerforms" class="pagetitle col-lg-10"><h2>Delete Course</h2></div>';
        echo '<div>';
        echo "<div class='col-lg-10'>Are you sure you want to delete the course <b>$name</b>?</div><br>\n";
        echo "<div class='col-lg-10'><input type=button value=\"Delete\" onclick=\"window.location='actions?action=delete&id={$params['id']}'\">\n";
        echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></div>\n";
        echo "</div>";
        break;
    case "deladmin":
        echo "<p>Are you sure you want to delete this user?</p>\n";
        echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=deladmin&id={$_GET['id']}'\">\n";
        echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></p>\n";
        break;
    case "chgpwd":
        echo '<div id="headerforms" class="pagetitle"><h2>Change Your Password</h2></div>';
        echo "<form method=post action=\"actions.php?action=chgpwd\">\n";
        echo "<span class=form>Enter old password:</span>  <input class=form type=password name=oldpw size=40> <BR class=form>\n";
        echo "<span class=form>Enter new password:</span> <input class=form type=password name=newpw1 size=40> <BR class=form>\n";
        echo "<span class=form>Verify new password:</span>  <input class=form type=password name=newpw2 size=40> <BR class=form>\n";
        echo '<div class=submit><input type="submit" value="'._('Save').'"></div></form>';
        break;

    case "chgrights":
    case "newadmin":
        echo "<form method=post action=\"actions.php?action={$_GET['action']}";
        if ($_GET['action']=="chgrights") { echo "&id={$_GET['id']}"; }
        echo "\">\n";
        if ($_GET['action'] == "newadmin") {
            echo "<span class=form>New User username:</span>  <input class=form type=text size=40 name=adminname><BR class=form>\n";
            echo "<span class=form>First Name:</span> <input class=form type=text size=40 name=firstname><BR class=form>\n";
            echo "<span class=form>Last Name:</span> <input class=form type=text size=40 name=lastname><BR class=form>\n";
            echo "<span class=form>Email:</span> <input class=form type=text size=40 name=email><BR class=form>\n";
            echo '<span class="form">Password:</span> <input class="form" type="text" size="40" name="password"/><br class="form"/>';
            $oldgroup = 0;
            $oldrights = 10;
        } else {
            $query = "SELECT FirstName,LastName,rights,groupid FROM imas_users WHERE id='{$_GET['id']}'";
            $result = mysql_query($query) or die("Query failed : " . mysql_error());
            $line = mysql_fetch_array($result, MYSQL_ASSOC);
            echo "<h2>{$line['FirstName']} {$line['LastName']}</h2>\n";
            $oldgroup = $line['groupid'];
            $oldrights = $line['rights'];

        }
        echo "<BR><span class=form><img src=\"".AppUtility::getHomeURL()."img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=rights','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/> Set User rights to: </span> \n";
        echo "<span class=formright><input type=radio name=\"newrights\" value=\"5\" ";
        if ($oldrights == 5) {echo "CHECKED";}
        echo "> Guest User <BR>\n";
        echo "<input type=radio name=\"newrights\" value=\"10\" ";
        if ($oldrights == 10) {echo "CHECKED";}
        echo "> Student <BR>\n";
        //obscelete
        //echo "<input type=radio name=\"newrights\" value=\"15\" ";
        //if ($oldrights == 15) {echo "CHECKED";}
        //echo "> TA/Tutor/Proctor <BR>\n";
        echo "<input type=radio name=\"newrights\" value=\"20\" ";
        if ($oldrights == 20) {echo "CHECKED";}
        echo "> Teacher <BR>\n";
        echo "<input type=radio name=\"newrights\" value=\"40\" ";
        if ($oldrights == 40) {echo "CHECKED";}
        echo "> Limited Course Creator <BR>\n";
        echo "<input type=radio name=\"newrights\" value=\"60\" ";
        if ($oldrights == 60) {echo "CHECKED";}
        echo "> Diagnostic Creator <BR>\n";
        echo "<input type=radio name=\"newrights\" value=\"75\" ";
        if ($oldrights == 75) {echo "CHECKED";}
        echo "> Group Admin <BR>\n";
        if ($myrights==100) {
            echo "<input type=radio name=\"newrights\" value=\"100\" ";
            if ($oldrights == 100) {echo "CHECKED";}
            echo "> Full Admin </span><BR class=form>\n";
        }

        if ($myrights == 100) {
            echo "<span class=form>Assign to group: </span>";
            echo "<span class=formright><select name=\"group\" id=\"group\">";
            echo "<option value=0>Default</option>\n";
            $query = "SELECT id,name FROM imas_groups ORDER BY name";
            $result = mysql_query($query) or die("Query failed : " . mysql_error());
            while ($row = mysql_fetch_row($result)) {
                echo "<option value=\"{$row[0]}\" ";
                if ($oldgroup==$row[0]) {
                    echo "selected=1";
                }
                echo ">{$row[1]}</option>\n";
            }
            echo "</select><br class=form />\n";
        }

        echo "<div class=submit><input type=submit value=Save></div></form>\n";
        break;
    case "modify":
    case "addcourse":
//    if ($myRights < 40) {
//        echo "You don't have the authority for this action";
//        break;
//    }
        if (isset($params['cid'])) {
            $cid = $params['cid'];
        }
        echo "<form method=post action=\"actions?action={$params['action']}";
        if (isset($params['cid'])) {
            echo "&cid=$cid";
        }
        if ($params['action']=="modify")
        { echo "&id={$params['cid']}"; }
        echo "\"><input class='course-setting-submit-btn' type=submit value=Submit>";
        echo "<div class='col-md-12 padding-left-zero padding-top-ten padding-bottom-twenty-five'>
                    <div class='col-md-12 margin-top-fifteen'>
                        <div class=col-md-3>Course ID</div>
                        <div class=col-md-4>$courseid</div>
                    </div>";
        echo "<div class='col-md-12 margin-top-fifteen'>
                <div class='col-md-3 padding-top-five'>Enter Course name</div>
                <div class=col-md-4>
                    <input class='form-control' required='please fill out this field' type=text size=80 name=\"coursename\" value=\"$name\">
                </div>
              </div>";
        echo "<div class='col-md-12 margin-top-fifteen'>
                    <div class='col-md-3 padding-top-five'>Enter Enrollment key</div>
                    <div class=col-md-4>
                        <input class='form-control' type=text size=30 name=\"ekey\" value=\"$ekey\">
                    </div>
              </div>";
        echo '<div class="col-md-12 margin-top-fifteen">
                    <div class="col-md-3 padding-top-five">Available?</div>
                    <div class="col-md-4 padding-left-zero">';
                       echo '<div class="col-md-12">';
                                echo '<input type="checkbox" name="stuavail" value="1" ';
                                if (($avail&1)==0) {
                                    echo 'checked="checked"';
                                }
                                echo '/><span class="margin-left-five"> Available to students</span>
                            </div>

                        <div class="col-md-12 margin-top-five">
                            <input type="checkbox" name="teachavail" value="2" ';
                            if (($avail&2)==0) {
                                echo 'checked="checked"';}
                            echo '/><span class="margin-left-five"> Show on instructors\' home page</span>
                        </div>
                    </div>
              </div>';
        if ($params['action']=="modify") {
            echo '<div class="col-md-12 margin-top-fifteen">
                        <div class="col-md-3 padding-top-five">Lock for assessment</div>
                        <div class="col-md-4">
                            <select class="form-control" name="lockaid">';
                             echo '<option value="0" ';
                             if ($lockaid == 0) {
                                echo 'selected="1"';
                             }
                             echo '>No lock</option>';
                             if($assessment)
                             {
                                foreach($assessment as $key => $row)
                                {
                                    echo "<option value=\"{$row['id']}\" ";
                                    if ($lockaid==$row['id']) { echo 'selected="1"';}
                                    echo ">{$row['name']}</option>";
                                }
                             }
                            echo '</select>
                        </div>
            </div>';
        }
        if (!isset($CFG['CPS']['deftime']) || $CFG['CPS']['deftime'][1]==1) {
            echo "
            <div class='col-md-12 margin-top-twenty'>
                    <div class='col-md-3 select-text-margin'>
                            Default start/end time for new items
                    </div>
                    <div class=col-md-8>";
                            echo '<span class="floatleft non-bold select-text-margin">Start</span>';

                            echo '<div class ="floatleft width-fourty-per margin-left-fifteen margin-right-minus-eleven-per margin-left-fifteen time-input">';
                            echo TimePicker::widget([
                                'name' => 'defstime',
                                'value' => $defstimedisp,
                                'pluginOptions' => [
                                    'showSeconds' => false
                                ]
                            ]);
                            echo '</div>';
                    echo '<label class="floatleft non-bold select-text-margin">End</label>';
                                echo '<div class="width-fourty-per margin-left-fifteen floatleft">';
                                echo TimePicker::widget([
                                    'name' => 'deftime',
                                    'value' => $deftimedisp,
                                    'pluginOptions' => [
                                        'showSeconds' => false,
                                        'class' => 'time'
                                    ]
                                ]);
                                echo '</div>
                    </div>
            </div>';
        }

        if (!isset($CFG['CPS']['copyrights']) || $CFG['CPS']['copyrights'][1]==1) {
            echo "<div class='col-md-12 margin-top-fifteen'>
                    <div class=col-md-3>Allow other instructors to copy course items
                    </div>
                    <div class='col-md-6 padding-left-zero'>
                        <div class='col-md-12'>";
                        echo '<input type=radio name="copyrights" value="0" ';
                                if ($copyrights==0) { echo "checked=1";}
                        echo '/><span class="margin-left-five"> Require enrollment key from everyone</span>
                        </div>
                        <div class="col-md-12 margin-top-five">
                        <input type=radio name="copyrights" value="1" ';
                        if ($copyrights==1) { echo "checked=1";}
                        echo '/><span class="margin-left-five"> No key required for group members, require key from others</span>
                        </div>
                        <div class="col-md-12 margin-top-five">
                        <input type=radio name="copyrights" value="2" ';
                        if ($copyrights==2) { echo "checked=1";}
                        echo '/><span class="margin-left-five"> No key required from anyone</span>
                        </div>
                    </div>
                    </div>';
        }
        if (!isset($CFG['CPS']['msgset']) || $CFG['CPS']['msgset'][1]==1) {
            echo "
            <div class='col-md-12 margin-top-fifteen'>

                <div class=col-md-3>Message System</div>

                <div class='col-md-5 padding-left-zero'>";
                    echo '<div class="col-md-12">
                    <input type=radio name="msgset" value="0" ';
                    if ($msgset==0) { echo "checked=1";}
                    echo '/><span class="margin-left-five"> On for send and receive</span>
                    </div>
                    <div class="col-md-12 margin-top-five">
                    <input type=radio name="msgset" value="1" ';
                    if ($msgset==1) { echo "checked=1";}
                    echo '/> <span class="margin-left-five">On for receive, students can only send to instructor</span>
                    </div>
                    <div class="col-md-12 margin-top-five">
                    <input type=radio name="msgset" value="2" ';
                    if ($msgset==2) { echo "checked=1";}
                    echo '/> <span class="margin-left-five">On for receive, students can only send to students</span>
                    </div>
                    <div class="col-md-12 margin-top-five">
                    <input type=radio name="msgset" value="3" ';
                    if ($msgset==3) { echo "checked=1";}
                    echo '/> <span class="margin-left-five">On for receive, students cannot send</span>
                    </div>
                    <div class="col-md-12 margin-top-five">
                    <input type=radio name="msgset" value="4" ';
                    if ($msgset==4) { echo "checked=1";}
                    echo '/><span class="margin-left-five"> Off</span>
                    </div>
                    <div class="col-md-12 margin-top-five">
                    <input type=checkbox name="msgmonitor" value="1" ';
                    if ($msgmonitor==1) { echo "checked=1";}
                    echo '/> <span class="margin-left-five">Enable monitoring of student-to-student messages</span>
                    </div>';
                echo '</div>
            </div>';
        }
        if (!isset($CFG['CPS']['toolset']) || $CFG['CPS']['toolset'][1]==1) {
            echo "
            <div class='col-md-12 margin-top-fifteen'>
                <div class=col-md-3>Navigation Links for Students</div>

                <div class='col-md-4 padding-left-zero'>";
                echo '<div class="col-md-12">
                <input type="checkbox" name="toolset-cal" value="1" ';
                if (($toolset&1)==0) { echo 'checked="checked"';}
                echo '><span class="margin-left-five"> Calendar</span>
                </div>';
                echo '<div class="col-md-12 margin-top-five">
                <input type="checkbox" name="toolset-forum" value="2" ';
                if (($toolset&2)==0) { echo 'checked="checked"';}
                echo '><span class="margin-left-five"> Forum List</span>
                </div>';
                echo '
                </div>
            </div>';
        }

        if (!isset($CFG['CPS']['chatset']) || $CFG['CPS']['chatset'][1]==1) {
            if (isset($mathchaturl) && $mathchaturl!='') {
                echo '<div class=col-md-3>Enable live chat</div><div class=col-md-10>';
                echo '<input type=checkbox name="chatset" value="1" ';
                if ($chatset==1) {echo 'checked="checked"';};
                echo ' /></div><br class="form" /><br class="form" />';
            }
        }
        if (!isset($CFG['CPS']['deflatepass']) || $CFG['CPS']['deflatepass'][1]==1) {
            echo '
            <div class="col-md-12 margin-top-fifteen">
                <div class="col-md-3 padding-top-five">Auto-assign LatePasses on course enroll</div>
                <div class="col-md-5 display-flex">';
                    echo '<input class="width-seventy-eight-per form-control" type="text" size="3" name="deflatepass" value="'.$deflatepass.'"/>
                    <span class="margin-left-ten select-text-margin">LatePasses<span>
                </div>
            </div>';
        }
        if (isset($enablebasiclti) && $enablebasiclti==true && isset($params['cid'])) {
            echo '
            <div class="col-md-12 margin-top-fifteen">
            <div class="col-md-3">LTI access secret (max 10 chars; blank to not use)</div>';
            echo '

            <div class=col-md-6>
            <input class="form-control width-sixty-four-per display-inline-block" name="ltisecret" type="text" value="'.$ltisecret.'" maxlength="10"/> ';
            echo '<button class="margin-left-ten" type="button" onclick="document.getElementById(\'ltiurl\').style.display=\'\';this.parentNode.removeChild(this);">'._('Show LTI key and URL').'</button>';
            echo '<span id="ltiurl" style="display:none;">';
            if (isset($params['cid'])) {
                echo '<br/>URL: '.$urlmode.$_SERVER['HTTP_HOST'].AppUtility::getHomeURL().'bltilaunch.php<br/>';
                echo 'Key: placein_'.$params['cid'].'_0 (to allow students to login directly to '.$installname.') or<br/>';
                echo 'Key: placein_'.$params['cid'].'_1 (to only allow access through the LMS )';
            } else {
                echo 'Course ID not yet set.';
            }
            echo '</span>
            </div>
            </div>';
        }
        if ($myrights>=75) {
            echo '<div class=col-md-3>Mark course as template?</div>';
            echo '<div class=col-md-10><input type=checkbox name="isgrptemplate" value="2" ';
            if (($istemplate&2)==2) {echo 'checked="checked"';};
            echo ' /> Mark as group template course';
            if ($myrights==100) {
                echo '<br/><input type=checkbox name="istemplate" value="1" ';
                if (($istemplate&1)==1) {echo 'checked="checked"';};
                echo ' /> Mark as global template course<br/>';
                echo '<input type=checkbox name="isselfenroll" value="4" ';
                if (($istemplate&4)==4) {echo 'checked="checked"';};
                echo ' /> Mark as self-enroll course';
                if (isset($CFG['GEN']['guesttempaccts'])) {
                    echo '<br/><input type=checkbox name="isguest" value="8" ';
                    if (($istemplate&8)==8) {echo 'checked="checked"';};
                    echo ' /> Mark as guest-access course';
                }
            }
            echo '</div><br class="form" /><br class="form" />';
        }
        if (isset($CFG['CPS']['templateoncreate']) && $params['action']=='addcourse' ) {
            echo '<div class=col-md-3>Use content from a template course:</div>';
            echo '<div class=col-md-10><select name="usetemplate" onchange="templatepreviewupdate(this)">';
            echo '<option value="0" selected="selected">Start with blank course</option>';
            //$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$templateuser' ORDER BY ic.name";
            $globalcourse = array();
            $groupcourse = array();
            $query = "SELECT id,name,copyrights,istemplate FROM imas_courses WHERE (istemplate&1)=1 AND available<4 AND copyrights=2 ORDER BY name";
            $result = mysql_query($query) or die("Query failed : " . mysql_error());
            while ($row = mysql_fetch_row($result)) {
                $globalcourse[$row[0]] = $row[1];
            }
            $query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
            $query .= "iu.groupid='$groupid' AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
            $result = mysql_query($query) or die("Query failed : " . mysql_error());
            while ($row = mysql_fetch_row($result)) {
                $groupcourse[$row[0]] = $row[1];
            }
            if (count($groupcourse)>0) {
                echo '<optgroup label="Group Templates">';
                foreach ($groupcourse as $id=>$name) {
                    echo '<option value="'.$id.'">'.$name.'</option>';
                }
                echo '</optgroup>';
            }
            if (count($globalcourse)>0) {
                if (count($groupcourse)>0) {
                    echo '<optgroup label="System-wide Templates">';
                }
                foreach ($globalcourse as $id=>$name) {
                    echo '<option value="'.$id.'">'.$name.'</option>';
                }
                if (count($groupcourse)>0) {
                    echo '</optgroup>';
                }
            }

            echo '</select><span id="templatepreview"></span></div><br class="form" /><br class="form" />';
            echo '<script type="text/javascript"> function templatepreviewupdate(el) {';
            echo '  var outel = document.getElementById("templatepreview");';
            echo '  if (el.value>0) {';
            echo '  outel.innerHTML = "<a href=\"'.AppUtility::getHomeURL().'course/course.php?cid="+el.value+"\" target=\"preview\">Preview</a>";';
            echo '  } else {outel.innerHTML = "";}';
            echo '}</script>';
        } ?>
        <input type="hidden" name="picicons" value="<?php echo AppConstant::PIC_ICONS_VALUE;?>">
        <input type="hidden" name="topbar" value="<?php echo AppConstant::TOPBAR_VALUE;?>">
        <input type="hidden" name="chatset" value="<?php echo AppConstant::CHATSET_VALUE;?>">
        <input type="hidden" name="cploc" value="<?php echo AppConstant::CPLOC_VALUE?>">
        <input type="hidden" name="istemplate" value="0">
        <input type="hidden" name="toolset" value="0">
        <input type="hidden" name="copyrights" value="0">
        <input type="hidden" name="hideicons" value="<?php echo AppConstant::HIDE_ICONS_VALUE;?>">
        <input type="hidden" name="unenroll" value="<?php echo AppConstant::UNENROLL_VALUE;?>">
        <input type="hidden" name="showlatepass" value="<?php echo AppConstant::SHOWLATEPASS;?>">
        <input type="hidden" name="itemorder" value="<?php echo AppConstant::ITEM_ORDER;?>">
        <input type="hidden" name="deflatepass" value="0">
        <input type="hidden" name="avail" value="0">
        <input type="hidden" name="blockcnt" value="0">
        <?php echo "</div>";
        break;
    case "chgteachers":
        $query = "SELECT name FROM imas_courses WHERE id='{$_GET['id']}'";
        $result = mysql_query($query) or die("Query failed : " . mysql_error());
        $line = mysql_fetch_array($result, MYSQL_ASSOC);
        echo '<div id="headerforms" class="pagetitle">';
        echo "<h2>{$line['name']}</h2>\n";
        echo '</div>';

        echo "<h4>Current Teachers:</h4>\n";
        $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_teachers.id,imas_teachers.userid ";
        $query .= "FROM imas_users,imas_teachers WHERE imas_teachers.courseid='{$_GET['id']}' AND " ;
        $query .= "imas_teachers.userid=imas_users.id ORDER BY imas_users.LastName;";
        $result = mysql_query($query) or die("Query failed : " . mysql_error());
        $num = mysql_num_rows($result);
        echo '<form method="post" action="actions.php?action=remteacher&cid='.$_GET['id'].'&tot='.$num.'">';
        echo 'With Selected: <input type="submit" value="Remove as Teacher"/>';
        echo "<table cellpadding=5>\n";
        $onlyone = ($num==1);
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {

            if ($onlyone) {
                echo '<tr><td></td>';
            } else {
                echo '<tr><td><input type="checkbox" name="tid[]" value="'.$line['id'].'"/></td>';
            }

            echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
            if ($onlyone) {
                echo "<td></td></tr>";
            } else {
                echo "<td><A href=\"actions.php?action=remteacher&cid={$_GET['id']}&tid={$line['id']}\">Remove as Teacher</a></td></tr>\n";
            }
            $used[$line['userid']] = true;
        }
        echo "</table></form>\n";

        echo "<h4>Potential Teachers:</h4>\n";
        if ($myrights<100) {
            $query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) AND groupid='$groupid' ORDER BY LastName;";
        } else if ($myrights==100) {
            $query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) ORDER BY LastName;";
        }
        $result = mysql_query($query) or die("Query failed : " . mysql_error());
        echo '<form method="post" action="actions.php?action=addteacher&cid='.$_GET['id'].'">';
        echo 'With Selected: <input type="submit" value="Add as Teacher"/>';
        echo "<table cellpadding=5>\n";
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
            if (trim($line['LastName'])=='' && trim($line['FirstName'])=='') {continue;}
            if ($used[$line['id']]!=true) {


                echo '<tr><td><input type="checkbox" name="atid[]" value="'.$line['id'].'"/></td>';
                echo "<td>{$line['LastName']}, {$line['FirstName']} </td> ";
                echo "<td><a href=\"actions.php?action=addteacher&cid={$_GET['id']}&tid={$line['id']}\">Add as Teacher</a></td></tr>\n";
            }
        }
        echo "</table></form>\n";
        echo "<p><input type=button value=\"Done\" onclick=\"window.location='admin.php'\" /></p>\n";
        break;
    case "importmacros": ?>

         <form enctype="multipart/form-data" method=post action="actions?action=importmacros">
             <div class = "title-container">
                 <div class="row">
                     <div class="">
                         <button class="floatright margin-top-minus-six-per btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php AppUtility::t('Submit') ?></button>
                     </div>
                 </div>
             </div>
         <div class='col-md-12 padding-twenty'>
                <div class='col-md-12 text-gray-background padding-left-thirty padding-top-five'>
                    <h3><?php AppUtility::t('Install Macro File')?></h3>
                    <p>
                    <b><?php AppUtility::t('Warning')?></b>
                    <span class='margin-left-ten'><?php AppUtility::t('Macro Files have a large security risk')?>.  <b><?php AppUtility::t('Only install macro files from a trusted source')?></b></span>
                    </p>
                    <p class='margin-bottom-ten'>
                    <b><?php AppUtility::t('Warning')?></b>
                    <span class='margin-left-ten'> <?php AppUtility::t('Install will overwrite any existing macro file of the same name')?></span>
                    </p>
                <input type="hidden" name="MAX_FILE_SIZE" value="300000" />
             <div class='col-md-12 padding-left-zero'>
              <span class='floatleft'><?php AppUtility::t('Import file')?> </span>
              <span class='floatleft margin-left-ten'>
                   <input name="userfile" type="file" />
              </span>
              </div>

             </div></div></form><?php
        break;

    case "importqimages": ?>
        <form enctype="multipart/form-data" method=post action="actions?action=importqimages">
            <div class = "title-container">
                <div class="row">
                    <div class="">
                        <button class="floatright margin-top-minus-six-per btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Submit' ?></button>
                    </div>
                </div>
            </div>

        <div class='col-md-12 padding-twenty'>
                <div class='col-md-12 text-gray-background padding-left-thirty padding-top-five'>
        <h3><?php AppUtility::t('Install Question Images')?></h3>
              <p>
                    <b><?php AppUtility::t('Warning')?></b>
                    <span class='margin-left-ten'> <?php AppUtility::t('This has a large security risk')?>.  <b><?php AppUtility::t('Only install question images from a trusted source')?></b>, <?php AppUtility::t("and where you've verified the archive only contains images")?>.<span>
              </p>
         <p class='margin-bottom-ten'>
                    <b></b>
                    <span class='margin-left-ten'><?php AppUtility::t('Install will ignore files with the same filename as existing files')?> .</span>
              </p>
         <input type="hidden" name="MAX_FILE_SIZE\" value="5000000" />
         <span class='floatleft'><?php AppUtility::t('Import file')?> </span>
                <span class='floatleft margin-left-ten'>
                    <input name="userfile" type="file" />
                </span>
        </div></div></form>
         <?php break;
    case "importcoursefiles":?>
        <form enctype="multipart/form-data" method=post action="actions?action=importcoursefiles">
            <div class = "title-container">
                <div class="row">
                    <div class="">
                        <button class="floatright margin-top-minus-six-per btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php AppUtility::t('Submit') ?></button>
                    </div>
                </div>
            </div>
          <div class='col-md-12 padding-twenty'>
                <div class='col-md-12 text-gray-background padding-left-thirty padding-top-five'>
        <h3><?php AppUtility::t('Install Course files')?></h3>
            <p>
                <b><?php AppUtility::t('Warning')?></b>
               <span class='margin-left-ten'> <?php AppUtility::t('This has a large security risk')?>.  <b><?php AppUtility::t('Only install course files from a trusted source')?></b>, <?php AppUtility::t("and where you've verified the archive only contains regular files (no PHP files)")?>.</span>
            </p>
         <p class='margin-bottom-ten'>
                <b><?php AppUtility::t('Warning')?></b>
                <span class='margin-left-ten'><?php AppUtility::t('Install will ignore files with the same filename as existing files')?>.</span>
               </p>
         <input type="hidden" name="MAX_FILE_SIZE" value="10000000" />
         <span class='floatleft'>Import file </span>
                <span class='floatleft margin-left-ten'>
                    <input name="userfile" type="file" />
                </span>
        </div></div></form>  <?php
        break;

    case "transfer":
        if ($myRights < 40)
        {
            echo "You don't have the authority for this action"; break;
        }
        echo '<div id="headerforms" class="pagetitle">';
        echo '<div>';
        echo "<div class='col-lg-10'><h3>Transfer Course Ownership</h3></div>\n";
        echo '</div>';
        echo "<form method=post action=\"actions?action=transfer&id={$_GET['cid']}\">\n";
        echo "<div class='col-lg-3'>Transfer course ownership to</div>
         <div class='col-lg-4'><select name=newowner class='form-control'>\n";
        foreach($queryUser as $key => $row)
        {
            echo "<option value=\"$row[id]\">$row[LastName], $row[FirstName]</option>\n";
        }
        echo "</select></div>\n";
        echo "<div class='col-lg-10'><input type=submit value=\"Transfer\">\n";
        echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin'\"></div>\n";
        echo "</div>";
        echo "</form>\n";
        break;

    case "deloldusers": ?>
             <form method=post action="actions?action=deloldusers">
                 <div class = "title-container">
                     <div class="row">
                         <div class="">
                             <button class="floatright margin-top-minus-six-per btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php AppUtility::t('Delete') ?></button>
                         </div>
                     </div>
                 </div>
               <div class='col-md-12 padding-twenty'>
                <div class='col-md-12 padding-top-five text-gray-background padding-left-thirty'>
            <h3><?php AppUtility::t('Delete Old Users')?></h3>
            <div class='col-md-12 margin-top-ten padding-left-zero'>
                <span><?php AppUtility::t('Delete Users older than')?></span>
                         <span>
                            <input type=text class='margin-left-ten form-control display-inline-block width-five-per' name=months size=4 value="6"/>
                            <span class='margin-left-ten'><?php AppUtility::t('Months')?></span>
                        </span>
            </div>
            <div class='col-md-12 margin-top-fifteen padding-left-zero'>
                <span><?php AppUtility::t('Delete Who')?></span>
             <span>
                    <span class='margin-left-ten'><input type=radio name=who value="students" CHECKED>
                        <span class='margin-left-five'><?php AppUtility::t('Students')?></span>
                    </span>
                    <span class='margin-left-ten'>
                        <input type=radio name=who value="all">
                        <span class='margin-left-five'><?php AppUtility::t('Everyone but Admins')?></span>
                    </span>
                  </span>
                  </div>
            </div></div>
            </form> <?php
            break;
    case "listltidomaincred":  ?>
         <div class="col-md-12 modify-lti-domain-credential"><div id="headerforms" class="pagetitle">
         <h3><?php AppUtility::t('Modify LTI Domain Credentials')?></h3>
         </div>
         <table class='margin-top-fifteen table table-bordered table-striped table-hover data-table'><thead><tr><th><?php AppUtility::t('Domain')?></th><th><?php AppUtility::t('Key')?></th><th><?php AppUtility::t('Can create Instructors')?>?</th><th><?php AppUtility::t('Modify')?></th><th><?php AppUtility::t('Delete')?></th></tr></thead>
        <?php
        foreach ($users as $row) {
            echo "<tbody><tr><td>{$row['email']}</td><td>{$row['SID']}</td>";
            if ($row['rights']==76) { ?>
                 <td><?php AppUtility::t('Yes')?></td>
            <?php } else { ?>
                  <td><?php AppUtility::t('No')?></td>
            <?php } ?>
             <td><a href="forms?action=modltidomaincred&id=<?php echo $row['id']?>"><?php AppUtility::t('Modify')?></a></td>
            <?php if ($row['id']==0) {
                echo "<td></td>";
            } else { ?>
             <td>
                 <a href="javascript: deleteLtiUser(<?php echo $row['id']?>)">  <?php AppUtility::t('Delete')?></a>
             </td>
           <?php } ?>
             </tr>
            <?php } ?>
            </tbody></table>
         <form method=post action="actions?action=modltidomaincred&id=new">
        <div class='col-md-12 padding-left-zero'>
        <div class='col-md-12 padding-left-zero'><?php AppUtility::t('Add new LTI key/secret')?></div>
        <div class='margin-top-twenty col-md-12 padding-left-zero'>
            <span class='col-md-2 padding-left-zero'><?php AppUtility::t('Domain')?></span>
            <div class='col-md-4 padding-left-zero'>
                <input class='form-control' type=text name="ltidomain" size=20>
            </div>
        </div>
        <div class='margin-top-twenty col-md-12 padding-left-zero'>
            <span class='col-md-2 padding-left-zero'><?php AppUtility::t('Key')?></span>
            <div class='col-md-4 padding-left-zero'>
                <input class='form-control' type=text name="ltikey" size=20>
            </div>
        </div>
        <div class='margin-top-twenty col-md-12 padding-left-zero'>
            <span class='col-md-2 padding-left-zero'><?php AppUtility::t('Secret')?></span>
            <div class='col-md-4 padding-left-zero'>
                <input class='form-control' type=text name="ltisecret" size=20>
            </div>
        </div>
        <div class='margin-top-twenty col-md-12 padding-left-zero'>
            <span class='col-md-2 padding-left-zero'><?php AppUtility::t('Can create instructors')?></span>
            <div class='col-md-4 padding-left-zero'>
                <select class='form-control' name="createinstr">
                    <option value="11" selected="selected"><?php AppUtility::t('No')?></option>
                    <option value="76"><?php AppUtility::t('Yes, and creates'); echo ' '.$installname.' '; AppUtility::t('login');?></option>
                </select>
            </div>
        </div>
        <div class='margin-top-twenty col-md-12 padding-left-zero'>
            <span class='col-md-2 padding-left-zero'><?php AppUtility::t('Associate with group')?></span>
            <div class='col-md-4 padding-left-zero'>
                <select class='form-control' name='groupid'>
                    <option value='0'><?php AppUtility::t('Default')?></option>
        <?php
        foreach ($groupsName as $group )
        {
            echo '<option value="'.$group['id'].'">'.$group['name'].'</option>';
        }
        echo "</select>"; ?>
        </div>
        </div>
        <div class='margin-top-twenty col-md-12 padding-left-zero'>
            <input type=submit value="<?php AppUtility::t('Add LTI Credentials')?>">
        </div>
        </div>
        </div></form>
        <?php
        break;
    case "modltidomaincred":
//        if ($myrights<100) { echo "not allowed"; exit;} ?>
         <div id="headerforms" class="pagetitle">
         <h3><?php AppUtility::t('Modify LTI Domain Credentials')?></h3>
         </div>
        <form method=post action="actions?action=modltidomaincred&id=<?php echo $user['id']?>">
        <?php AppUtility::t('Modify LTI key/secret')?> <br/>
        <?php AppUtility::t('Domain')?> <input type=text name="ltidomain" value="<?php echo $user['email'];?>" size=20><br/>
        <?php AppUtility::t('Key')?><input type=text name="ltikey" value="<?php echo $user['SID'] ?>" size=20><br/>
        <?php AppUtility::t('Secret')?><input type=text name="ltisecret"  value="<?php echo $user['password']?>"size=20><br/>
        <?php AppUtility::t('Can create instructors')?>
         <select name="createinstr"><option value="11"
        <?php if ($user['rights']==11)
        {
            echo 'selected="selected"';
        }
         ?> > <?php AppUtility::t('No')?></option>
              <option value="76" <?php
        if ($user['rights']==76) {echo 'selected="selected"';} ?>
          ><?php AppUtility::t('Yes')?></option></select><br/>
        <?php AppUtility::t('Associate with group')?> <select name="groupid"><option value="0"><?php AppUtility::t('Default')?></option>
                <?php
        foreach ($groupsName as $group ) {
            echo '<option value="'.$group['id'].'"';
            if ($group['id']==$user['groupid']) { echo ' selected="selected"';}
            echo '>'.$group['name'].'</option>';
        }
        echo '</select><br/>'; ?>
         <input type=submit value="<?php AppUtility::t('Update LTI Credentials')?>">
         </form> <?php
        break;
    case "listgroups":?>
         <div class="col-md-12 modify-group-padding">
          <div id="headerforms" class="pagetitle">
         <h3><?php AppUtility::t('Modify Groups')?></h3>
         </div>
         <table class='margin-top-twenty table table-bordered table-striped table-hover data-table'><thead><tr><th><?php AppUtility::t('Group Name')?></th><th><?php AppUtility::t('Modify')?></th><th><?php AppUtility::t('Delete')?></th></tr></thead>
         <?php foreach($groupsName as $row)
         {
            echo "<tbody><tr><td>{$row['name']}</td>"; ?>
            <td><a href="<?php echo AppUtility::getURLFromHome('admin','admin/forms?action=modgroup&id='.$row['id']);?>"><?php AppUtility::t('Modify')?></a></td>
            <?php if ($row['id'] == AppConstant::NUMERIC_ZERO)
            {
               echo "<td></td>";
            } else { ?>
                  <td> <a href="#" onclick="deleteGroup(<?php echo $row['id'] ?>)"><?php AppUtility::t('Delete')?></a> </td>
            <?php }
            echo "</tr>";
        }
        echo "</table>"; ?>
        <form method=post action="<?php echo AppUtility::getURLFromHome('admin','admin/actions?action=addgroup');?>">
        <?php  echo "<div class='col-md-12 margin-top-twenty padding-left-zero'>
                        <span class='floatleft select-text-margin'>Add new group</span>
                        <input class='width-thirty-per floatleft margin-left-thirty form-control' type=text name=gpname id=gpname size=50>
                        </div>"; ?>
        <div class='margin-top-twenty col-md-2 padding-left-zero'> <input type=submit value="<?php AppUtility::t('Add Group')?>"></div>
        </tbody></form></div><?php
        break;
    case "modgroup": ?>
         <div id="headerforms" class="pagetitle"><h2><?php AppUtility::t('Rename Instructor Group')?></h2></div>
         <?php $gpname = $groupsName['name']; ?>
         <form method=post action=actions?action=modgroup&id=<?php echo $groupsName['id']?>>
         <?php AppUtility::t('Group name:',false)?> <input type=text size=50 name=gpname id=gpname value="<?php echo $gpname ?>"><br/>
         <input type=submit value="<?php AppUtility::t('Update Group')?>">
         </form> <?php
         break;
    case "removediag":
        if ($myRights < 60)
        {
            echo "You don't have the authority for this action";
            break;
        }
        echo '<div class=""><br>';
        echo "<div class='col-lg-10'>Are you sure you want to delete this diagnostic?  This does not delete the connected course and does not remove students or their scores.</div><br>\n";
        echo "<br> <div class='col-lg-6 padding-left-zero'><div class='col-lg-2'><input type=button value=\"Delete\" onclick=\"window.location='actions?action=removediag&id={$params['id']}'\"></div>\n";
        echo "<div class='col-lg-2 padding-left-zero'><input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='index'\"></div></div>\n";
        echo '</div>';
        break;
}
?>
     </div>
