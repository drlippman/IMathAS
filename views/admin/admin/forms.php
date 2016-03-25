<?php
use app\components\AppUtility;
use kartik\time\TimePicker;
use app\components\AppConstant;

$this->title = $pageTitle;
$imasroot = AppUtility::getHomeURL();

if (isset($params['cid'])) {
    ?>
    <div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
    </div>
<?php } elseif($this->title=="Course Settings"){?>

    <div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
    </div>
<?php } elseif($action == 'chgteachers'){?>
    <div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
    </div>
<?php } elseif($action == 'newadmin'){?>
    <div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
    </div>
<?php } elseif($action == 'enroll'){
    $pageTitle = "Enroll in a course";
    $this->title = $pageTitle;?>
    <div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home'], 'link_url' => [AppUtility::getHomeURL() . 'site/index'], 'page_title' => $this->title]); ?>
    </div>
<?php } else {?>

<div class="item-detail-header" xmlns="http://www.w3.org/1999/html">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin','Course Creation Confirmation'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
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
switch ($action) {
    case "addnewcourse":
        echo '<br>';
        echo '<div class="col-md-10 col-sm-10"><h2>' . AppUtility::t('Your course has been created!', false) . '</h2></div>';
        echo '<div class="col-md-10 col-sm-10">' . AppUtility::t('For students to enroll in this course, you will need to provide them two things', false) . '</div><ol>';
        echo '<div class="col-md-10 col-sm-10"><li>' . AppUtility::t('The course ID:', false) . '<b>' . $cid . '</b></li></div>';
        if (trim($params['ekey']) == '') {
            echo '<div class="col-md-10 col-sm-10"><li>' . AppUtility::t('Tell them to leave the enrollment key blank, since you didn\'t specify one.  The enrollment key acts like a course password to prevent random strangers from enrolling in your course.  If you want to set an enrollment key,', false);
            echo '<a href="forms?action=modify&id=' . $cid . '">' . AppUtility::t('modify your course settings', false) . '</a></li>';
        } else {
            echo '<div class="col-md-10 col-sm-10"><li>' . AppUtility::t('The enrollment key:', false) . '<b>' . $params['ekey'] . '</b></li></div>';
        }
        echo '</ol></p><BR class=form><br>';
        echo '<div class="col-md-10 col-sm-10">' . AppUtility::t('If you forget these later, you can find them by viewing your course settings.', false) . '</div><BR class=form><br>';
        echo '<div class="col-md-10 col-sm-10"><a href=' . AppUtility::getURLFromHome('course', 'course/course?cid=' . $cid) . '>' . AppUtility::t('Enter the Course', false) . '</a></div>';
        break;
    case "delete":
        echo '<div id="headerforms" class="pagetitle col-md-10 col-sm-10"><h2>' . AppUtility::t('Delete Course', false) . '</h2></div>';
        echo '<div>';
        echo "<div class='col-md-10 col-sm-10'>" . AppUtility::t('Are you sure you want to delete the course', false) . "<b>$name</b>?</div><br>\n";
        echo "<div class='col-md-10 col-sm-10'><input type=button value=\"Delete\" onclick=\"window.location='actions?action=delete&id={$params['id']}'\">\n";
        echo "</div>";
        break;
    case "deladmin":
        if($myRights < AppConstant::GROUP_ADMIN_RIGHT)
        {
            echo "You don't have authority to delete user";
        }
            echo "<p>Are you sure you want to delete this user?</p>\n";
        echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions?action=deladmin&id=$getId'\">\n";
        echo "<input type=button value=\"Cancel\" class=\"secondarybtn\" onclick=\"window.location='admin'\"></p>\n";
        break;
        break;
    case "chgpwd":
        break;

    case "chgrights":
    case "newadmin":
            echo "</br>";
            echo "<form method=post action=\"actions?action=$getAction";
            if ($getAction == "chgrights") { echo "&id=$getId"; }
            echo "\">";
            if ($getAction == "newadmin") {
                echo "<div class='col-md-2 col-sm-2'>New User username</div>
                <input class='form form-control-1' type=text size=40 name=adminname maxlength='30'><BR class=form>\n";
                echo "<div class='col-md-2 col-sm-2'>First Name</div>
                <input class='form form-control-1'  type=text size=40 name=firstname maxlength='30'><BR class=form>\n";
                echo "<div class='col-md-2 col-sm-2'>Last Name</div>
                <input class='form form-control-1'  type=text size=40 name=lastname maxlength='30'><BR class=form>\n";
                echo "<div class='col-md-2 col-sm-2'>Email</div> <input class='form form-control-1' type=text size=40 name=email><BR class=form>\n";
                echo '<div class="col-md-2 col-sm-2">Password</div> <input class="form form-control-1" type="password" size="40" name="password"/><br class="form"/>';
                $oldGroup = 0;
                $oldRights = 10;
            } else {
                echo "<div class='col-md-12 col-sm-12'><h2>{$line['FirstName']} {$line['LastName']}</h2></div>\n";
                $oldGroup = $line['groupid'];
                $oldRights = $line['rights'];

            }
            ?>
             <div class="col-md-2 col-sm-2 padding-left-zero"><a href="#" onclick="window.open('<?php echo AppUtility::getHomeURL().'docs/help.php?section=rights' ?>','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"><i class="fa fa-question fa-fw help-icon"></i></a>
                 <?php AppUtility::t('Set User rights to')?></div>

            <?php  echo "<div class='col-md-4 col-sm-4 padding-left-zero'><input type=radio name=\"newrights\" value=\"5\" ";
            if ($oldRights == 5) {echo "CHECKED";}
            echo "> Guest User <BR>\n";
            echo "<input type=radio name=\"newrights\" value=\"10\" ";
            if ($oldRights == 10) {echo "CHECKED";}
            echo "> Student <BR>\n";
            echo "<input type=radio name=\"newrights\" value=\"20\" ";
            if ($oldRights == 20) {echo "CHECKED";}
            echo "> Teacher <BR>\n";
            echo "<input type=radio name=\"newrights\" value=\"40\" ";
            if ($oldRights == 40) {echo "CHECKED";}
            echo "> Limited Course Creator <BR>\n";
            echo "<input type=radio name=\"newrights\" value=\"60\" ";
            if ($oldRights == 60) {echo "CHECKED";}
            echo "> Diagnostic Creator <BR>\n";
            echo "<input type=radio name=\"newrights\" value=\"75\" ";
            if ($oldRights == 75) {echo "CHECKED";}
            echo "> Group Admin <BR>\n";
            if ($myRights==100) {
                echo "<input type=radio name=\"newrights\" value=\"100\" ";
                if ($oldRights == 100) {echo "CHECKED";}
                echo "> Full Admin </div><BR class=form><BR/>\n";
            }

            if ($myRights == 100) {
                echo "<div class='col-md-2 col-sm-2'>Assign to group </div>";
                echo "<div class='col-md-6 col-sm-6 padding-left-zero'><select name=\"group\" class='form-control' id=\"group\" style='width: auto'>";
                echo "<option value=0>Default</option>\n";

                foreach($resultGroup as $key => $row) {
                    echo "<option value=\"{$row['id']}\" ";
                    if ($oldGroup==$row['id']) {
                        echo "selected=1";
                    }
                   $name = AppUtility::truncate($row['name'], 30);
                    echo ">{$name}</option>\n";
                }
                echo "</select></div><br class=form /><br/>";
            }
            ?>
            <div class="header-btn col-md-6 col-sm-6 padding-bottom-ten padding-left-zero">
               <br/> <button class="btn btn-primary" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Save' ?></button>
            </div><br class="form">

            <?php
            echo '</form><br/>';
    break;
    case "modify":
    case "addcourse":
        if ($myRights < AppConstant::TEACHER_RIGHT) {
            echo AppConstant::NO_ACCESS_RIGHTS;
            break;
        }
        if (isset($params['cid'])) {

            $cid = $params['cid'];
        }
        echo "<form method=post action=\"actions?action={$params['action']}";
        if (isset($params['cid'])) {
            echo "&cid=$cid";
        }
        if ($params['action'] == "modify") {
            echo "&id={$params['cid']}";
        }
        echo "\">";
        echo "<div class='col-md-12 col-sm-12 padding-left-zero padding-top-ten padding-bottom-twenty-five'>
                    <div class='col-md-12 col-sm-12 margin-top-fifteen'>
                        <div class='col-md-3 col-sm-3'>"; AppUtility::t('Course ID'); echo"</div>
                        <div class='col-md-4 col-sm-6'>$courseid</div>
                    </div>";
        echo "<div class='col-md-12 col-sm-12 margin-top-fifteen'>
                <div class='col-md-3 col-sm-3 padding-top-five'>" . AppUtility::t('Enter Course name', false) . "</div>
                <div class='col-md-4 col-sm-4'>
                    <input class='form-control' type=text size=80 name=\"coursename\" value=\"$name\">
                </div>
              </div>";
        echo "<div class='col-md-12 col-sm-12 margin-top-fifteen'>
                    <div class='col-md-3 col-sm-3 padding-top-five'>" . AppUtility::t('Enter Enrollment key', false) . "</div>
                    <div class='col-md-4 col-sm-4'>
                        <input class='form-control' required='please fill out this field' type=text size=30 name=\"ekey\" value=\"$ekey\">

                    </div>
              </div>";
        echo '<div class="col-md-12 col-sm-12 margin-top-fifteen">
                    <div class="col-md-3 col-sm-3 padding-top-five">' . AppUtility::t('Available?', false) . '</div>
                    <div class="col-md-5 col-sm-8 padding-left-zero">';
        echo '<div class="col-md-12 col-sm-12">';
        echo '<input type="checkbox" name="stuavail" value="1" ';
        if (($avail & 1) == AppConstant::NUMERIC_ZERO) {
            echo 'checked="checked"';
        }
        echo '/><span class="padding-left-ten">' . AppUtility::t('Available to students', false) . '</span>
                            </div>
                        <div class="col-md-12 col-sm-12 margin-top-five">
                            <input type="checkbox" name="teachavail" value="2" ';
        if (($avail & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_ZERO) {
            echo 'checked="checked"';
        }
        echo '/><span class="padding-left-ten">' . AppUtility::t('Show on instructor\'s home page', false) . '</span>
                        </div>
                    </div>
              </div>';
        if ($params['action'] == "modify") {
            echo '<div class="col-md-12 col-sm-12 margin-top-fifteen">
                        <div class="col-md-3 col-sm-3 padding-top-five">' . AppUtility::t('Lock for assessment', false) . '</div>
                        <div class="col-md-4 col-sm-4">
                            <select class="form-control" name="lockaid">';
            echo '<option value="0" ';
            if ($lockaid == AppConstant::NUMERIC_ZERO) {
                echo 'selected="1"';
            }
            echo '>No lock</option>';
            if ($assessment) {
                foreach ($assessment as $key => $row) {
                    $rowName = AppUtility::truncate($row['name'], 30);
                    echo "<option value=\"{$row['id']}\" ";
                    if ($lockaid == $row['id']) {
                        echo 'selected="1"';
                    }
                    echo ">{$rowName}</option>";
                }
            }
            echo '</select>
                        </div>
            </div>';
        }
        if (!isset($CFG['CPS']['deftime']) || $CFG['CPS']['deftime'][1] == AppConstant::NUMERIC_ONE) {
            echo "
            <div class='col-md-12 col-sm-12 margin-top-twenty'>
                    <div class='col-md-3 col-sm-3 select-text-margin'>" . AppUtility::t('Default start/end time for new items', false) . "
                    </div>
                    <div class='col-md-9 col-sm-9'>";
            echo '<span class="floatleft non-bold select-text-margin">' . AppUtility::t('Start', false) . '</span>';
            echo '<div class ="col-md-4 col-sm-5 time-input default-start-timepicker padding-right-zero">';
            echo TimePicker::widget([
                'name' => 'defstime',
                'value' => $defstimedisp,
                'pluginOptions' => [
                    'showSeconds' => false
                ]
            ]);
            echo '</div>';
            echo '<label class="floatleft non-bold select-text-margin padding-right-five">End</label>';
            echo '<div class="col-md-4 col-sm-5 default-end-timepicker padding-right-zero">';
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
        if (!($_POST['copyrights']) || $_POST['copyrights'][1] == AppConstant::NUMERIC_ONE) {
            echo "<div class='col-md-12 col-sm-12 margin-top-fifteen'>
                    <div class='col-md-3 col-sm-3'>" . AppUtility::t('Allow other instructors to copy course items', false) . "
                    </div>
                    <div class='col-md-8 col-sm-9 padding-left-zero'>
                        <div class='col-md-12 col-sm-12'>";
            echo '<input type=radio name="copyrights" value="0" ';
            if ($copyrights == AppConstant::NUMERIC_ZERO) {
                echo "checked=1";
            }
            echo '/><span class="padding-left-ten">' . AppUtility::t('Require enrollment key from everyone', false) . '</span>
                        </div>
                        <div class="col-md-12 col-sm-12 margin-top-five">
                        <input type=radio name="copyrights" value="1" ';
            if ($copyrights == AppConstant::NUMERIC_ONE) {
                echo "checked=1";
            }
            echo '/><span class="padding-left-ten">' . AppUtility::t('No key required for group members, require key from others', false) . '</span>
                        </div>
                        <div class="col-md-12 col-sm-12 margin-top-five">
                        <input type=radio name="copyrights" value="2" ';
            if ($copyrights == AppConstant::NUMERIC_TWO) {
                echo "checked=1";
            }
            echo '/><span class="padding-left-ten">' . AppUtility::t('No key required from anyone', false) . '</span>
                        </div>
                    </div>
                    </div>';
        }
        if (!isset($CFG['CPS']['msgset']) || $CFG['CPS']['msgset'][1] == AppConstant::NUMERIC_ONE) {
            echo "
            <div class='col-md-12 col-sm-12 margin-top-fifteen'>

                <div class='col-md-3 col-sm-3'>" . AppUtility::t('Message System', false) . "</div>

                <div class='col-md-8 col-sm-9 padding-left-zero'>";
            echo '<div class="col-md-12 col-sm-12">
                    <input type=radio name="msgset" value="0" ';
            if ($msgset == AppConstant::NUMERIC_ZERO) {
                echo "checked=1";
            }
            echo '/><span class="padding-left-ten">' . AppUtility::t('On for send and receive', false) . '</span>
                    </div>
                    <div class="col-md-12 col-sm-12 margin-top-five">
                    <input type=radio name="msgset" value="1" ';
            if ($msgset == AppConstant::NUMERIC_ONE) {
                echo "checked=1";
            }
            echo '/> <span class="margin-left-five">' . AppUtility::t('On for receive, students can only send to instructor', false) . '</span>
                    </div>
                    <div class="col-md-12 col-sm-12 margin-top-five">
                    <input type=radio name="msgset" value="2" ';
            if ($msgset == AppConstant::NUMERIC_TWO) {
                echo "checked=1";
            }
            echo '/> <span class="margin-left-five">' . AppUtility::t('On for receive, students can only send to students', false) . '</span>
                    </div>
                    <div class="col-md-12 col-sm-12 margin-top-five">
                    <input type=radio name="msgset" value="3" ';
            if ($msgset == AppConstant::NUMERIC_THREE) {
                echo "checked=1";
            }
            echo '/> <span class="margin-left-five">' . AppUtility::t('On for receive, students cannot send', false) . '</span>
                    </div>
                    <div class="col-md-12 col-sm-12 margin-top-five">
                    <input type=radio name="msgset" value="4" ';
            if ($msgset == AppConstant::NUMERIC_FOUR) {
                echo "checked=1";
            }
            echo '/><span class="margin-left-five"> Off</span>
                    </div>
                    <div class="col-md-12 col-sm-12 margin-top-five">
                    <input type=checkbox name="msgmonitor" value="1" ';
            if ($msgmonitor==1) {
                echo "checked=1";
            }
            echo '/> <span class="margin-left-five">' . AppUtility::t('Enable monitoring of student-to-student messages', false) . '</span>
                    </div>';
            echo '</div>
            </div>';
        }
        if (!isset($CFG['CPS']['toolset']) || $CFG['CPS']['toolset'][1] == AppConstant::NUMERIC_ONE) {
            echo "
            <div class='col-md-12 col-sm-12 margin-top-fifteen'>
                <div class='col-md-3 col-sm-3'>" . AppUtility::t('Navigation Links for Students', false) . "</div>

                <div class='col-md-4 col-sm-4 padding-left-zero'>";
            echo '<div class="col-md-12 col-sm-12">
                <input type="checkbox" name="toolset-cal" value="1" ';
            if (($toolset & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ZERO) {
                echo 'checked="checked"';
            }
            echo '><span class="padding-left-ten">' . AppUtility::t('Calendar', false) . '</span>
                </div>';
            echo '<div class="col-md-12 col-sm-12 margin-top-five">
                <input type="checkbox" name="toolset-forum" value="2" ';
            if (($toolset & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_ZERO) {
                echo 'checked="checked"';
            }
            echo '><span class="padding-left-ten">' . AppUtility::t('Forum List', false) . '</span>
                </div>';
            echo '
                </div>
            </div>';
        }

        if (!isset($CFG['CPS']['chatset']) || $CFG['CPS']['chatset'][1] == AppConstant::NUMERIC_ONE) {
            if (isset($mathchaturl) && $mathchaturl != '') {
                echo '<div class="col-md-3 col-sm-3">Enable live chat</div>
                <div class="col-md-10 col-sm-10">';
                echo '<input type=checkbox name="chatset" value="1" ';
                if ($chatset == AppConstant::NUMERIC_ONE) {
                    echo 'checked="checked"';
                };
                echo ' /></div><br class="form" /><br class="form" />';
            }
        }
        if (!isset($CFG['CPS']['deflatepass']) || $CFG['CPS']['deflatepass'][1] == AppConstant::NUMERIC_ONE) {
            echo '
            <div class="col-md-12 col-sm-12 margin-top-fifteen">
                <div class="col-md-3 col-sm-3 padding-top-five">' . AppUtility::t('Auto-assign LatePasses on course enroll', false) . '</div>
                <div class="col-md-9 col-sm-9 display-flex">';
            echo '<input class="width-seventy-eight-per form-control-1" type="text" size="3" name="deflatepass" value="' . $deflatepass . '"/>
                    <span class="margin-left-ten select-text-margin padding-left-zero">' . AppUtility::t('LatePasses', false) . '<span>
                </div>
            </div>';
        }
        if (isset($enablebasiclti) && $enablebasiclti == true && isset($params['cid'])) {
            echo '
            <div class="col-md-12 col-sm-12 margin-top-fifteen">
            <div class="col-md-3 col-sm-3">' . AppUtility::t('LTI access secret (max 10 chars; blank to not use)', false) . '</div>';
            echo '

            <div class="col-md-9 col-sm-9">
            <input class="form-control width-sixty-four-per display-inline-block" name="ltisecret" type="text" value="' . $ltisecret . '" maxlength="10"/> ';
            echo '<button class="margin-left-ten" type="button" onclick="document.getElementById(\'ltiurl\').style.display=\'\';this.parentNode.removeChild(this);">' . _('Show LTI key and URL') . '</button>';
            echo '<span id="ltiurl" style="display:none;">';
            $urlmode = \app\components\AppUtility::urlMode();
            if (isset($params['cid'])) {
                echo '<br/>URL: ' . $urlmode . $_SERVER['HTTP_HOST'] . AppUtility::getHomeURL() . 'bltilaunch.php<br/>';
                echo 'Key: placein_' . $params['cid'] . '_0 (to allow students to login directly to ' . $installname . ') or<br/>';
                echo 'Key: placein_' . $params['cid'] . '_1 (to only allow access through the LMS )';
            } else {
                echo AppUtility::t('Course ID not yet set.', false);
            }
            echo '</span>
            </div>
            </div>';
        }
        if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT) {
            echo '<div class="col-md-12 col-sm-12 margin-top-twenty">
            <div class="col-md-3 col-sm-3">' . AppUtility::t('Mark course as template?', false) . '</div>';
            echo '<div class="col-md-9 col-sm-9"><input type=checkbox name="isgrptemplate" value="2" ';
            if (($istemplate & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO) {
                echo 'checked="checked"';
            };
            echo ' /><span class="padding-left-ten">' . AppUtility::t('Mark as group template course', false);echo '</span>';
            if ($myRights == AppConstant::ADMIN_RIGHT) {
                echo '<div class="margin-top-ten"><input type=checkbox name="istemplate" value="1" ';
                if (($istemplate & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ONE) {
                    echo 'checked="checked"';
                };
                echo ' /><span class="padding-left-ten">' . AppUtility::t('Mark as global template course', false) . '</span></div>';
                echo '<div class="margin-top-ten"><input type=checkbox name="isselfenroll" value="4" ';
                if (($istemplate & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR) {
                    echo 'checked="checked"';
                };
                echo ' /><span class="padding-left-ten">'. AppUtility::t('Mark as self-enroll course', false); echo '</span></div>';
                if (isset($CFG['GEN']['guesttempaccts'])) {
                    echo '<input type=checkbox name="isguest" value="8" ';
                    if (($istemplate & AppConstant::NUMERIC_EIGHT) == AppConstant::NUMERIC_EIGHT) {
                        echo 'checked="checked"';
                    };
                    echo ' />'. AppUtility::t('Mark as guest-access course', false);
                }
            }
            echo '</div></div>';
        }
        ?>
        <input type="hidden" name="picicons" value="<?php echo AppConstant::PIC_ICONS_VALUE; ?>">
        <input type="hidden" name="topbar" value="<?php echo AppConstant::TOPBAR_VALUE; ?>">
        <input type="hidden" name="chatset" value="<?php echo AppConstant::CHATSET_VALUE; ?>">
        <input type="hidden" name="cploc" value="<?php echo AppConstant::CPLOC_VALUE ?>">
        <input type="hidden" name="istemplate" value="0">
        <input type="hidden" name="toolset" value="0">
        <input type="hidden" name="copyrights" value="0">
        <input type="hidden" name="hideicons" value="<?php echo AppConstant::HIDE_ICONS_VALUE; ?>">
        <input type="hidden" name="unenroll" value="<?php echo AppConstant::UNENROLL_VALUE; ?>">
        <input type="hidden" name="showlatepass" value="<?php echo AppConstant::SHOWLATEPASS; ?>">
        <input type="hidden" name="itemorder" value="<?php echo AppConstant::ITEM_ORDER; ?>">
        <input type="hidden" name="deflatepass" value="0">
        <input type="hidden" name="avail" value="0">
        <input type="hidden" name="blockcnt" value="0">

    <div class="col-md-2 col-sm-2 col-sm-offset-3 padding-top-twenty padding-left-twenty-three">
        <button type='submit' value='Submit'>
            <i class='fa fa-share'></i>
            <?php AppUtility::t('Submit') ?>
        </button>
    </div>
        <?php echo "</div>";
        break;
    case "chgteachers":
        echo '<div id="" class="col-md-12 col-sm-12">';



        echo "<h4>Current Teachers:</h4>\n";
        $num = count($currentTeacher);

        echo '<form method="post" action="actions?action=remteacher&cid='.$_GET['id'].'&tot='.$num.'">';
        echo 'With Selected: <input type="submit" value="Remove as Teacher"/>';
        echo "<table cellpadding=5>\n";
        $onlyOne = ($num == 1);

            	foreach($currentTeacher as $key=> $line) {

                    if ($onlyOne) {
                        echo '<tr><td></td>';
                    } else {
                        echo '<tr><td><input type="checkbox" name="tid[]" value="'.$line['id'].'"/></td>';
                    }

                    echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
                    if ($onlyOne) {
                        echo "<td></td></tr>";
                    } else {
                        echo "<td><A href=\"actions?action=remteacher&cid={$_GET['id']}&tid={$line['id']}\">Remove as Teacher</a></td></tr>\n";
                    }
                    $used[$line['userid']] = true;
                }
        echo "</table></form><br/>";

        echo "<h4>Potential Teachers:</h4>\n";
        echo '<form method="post" action="actions?action=addteacher&cid='.$_GET['id'].'">';
        echo 'With Selected: <input type="submit" value="Add as Teacher"/>';
        echo "<table cellpadding=5>\n";
        foreach($potentialUserLessThanAdmin as $line) {
            if (trim($line['LastName'])=='' && trim($line['FirstName'])=='') {continue;}
            if ($used[$line['id']]!=true) {
                echo '<tr><td><input type="checkbox" name="atid[]" value="'.$line['id'].'"/></td>';
                echo "<td>{$line['LastName']}, {$line['FirstName']} </td> ";
                echo "<td><a href=\"actions?action=addteacher&cid={$_GET['id']}&tid={$line['id']}\">Add as Teacher</a></td></tr>\n";
            }
        }
        echo "</table></form><br/>";
        echo "<p><input type=button value=\"Done\" onclick=\"window.location='index'\" /></p>\n";
        echo '</div>';
        break;

    case "importmacros":
        ?>
        <form enctype="multipart/form-data" method=post action="actions?action=importmacros">
        <div class="title-container">
            <div class="row">

            </div>
        </div>
        <div class='col-md-12 col-sm-12 padding-twenty'>
            <div class='col-md-12 col-sm-12 text-gray-background padding-left-thirty padding-top-five'>
                <h3><?php AppUtility::t('Install Macro File') ?></h3>

                <p>
                    <b><?php AppUtility::t('Warning') ?></b>
                    <span class='margin-left-ten'><?php AppUtility::t('Macro Files have a large security risk') ?>
                        .  <b><?php AppUtility::t('Only install macro files from a trusted source') ?></b></span>
                </p>

                <p class='margin-bottom-ten'>
                    <b><?php AppUtility::t('Warning') ?></b>
                    <span
                        class='margin-left-ten'> <?php AppUtility::t('Install will overwrite any existing macro file of the same name') ?></span>
                </p>
                <input type="hidden" name="MAX_FILE_SIZE" value="300000"/>

                <div class='col-md-12 col-sm-12 padding-left-zero'>
                    <span class='floatleft'><?php AppUtility::t('Import file') ?> </span>
              <span class='floatleft margin-left-ten'>
                   <input name="userfile" type="file"/>
              </span>
                </div>
                <div class="col-md-6 col-sm-6 padding-left-zero padding-top-twenty">
                    <button class="btn btn-primary page-settings" type="submit" value="Submit">
                        <i class="fa fa-share header-right-btn"></i>
                        <?php AppUtility::t('Submit') ?>
                    </button>
                </div>
            </div>
        </div></form><?php
        break;
    case "importqimages":
        ?>
        <form enctype="multipart/form-data" method=post action="actions?action=importqimages">
            <div class='col-md-12 col-sm-12 padding-twenty'>
                <div class='col-md-12 col-sm-12 text-gray-background padding-left-thirty padding-top-five'>
                    <h3><?php AppUtility::t('Install Question Images') ?></h3>

                    <p>
                        <b><?php AppUtility::t('Warning') ?></b>
                    <span class='margin-left-ten'> <?php AppUtility::t('This has a large security risk') ?>
                        .  <b><?php AppUtility::t('Only install question images from a trusted source') ?></b>, <?php AppUtility::t("and where you've verified the archive only contains images") ?>
                        .<span>
                    </p>

                    <p class='margin-bottom-ten'>
                        <b></b>
                        <span>
                            <?php AppUtility::t('Install will ignore files with the same filename as existing files.') ?>
                        </span>
                    </p>
                    <input type="hidden" name="MAX_FILE_SIZE\" value="5000000"/>
                    <span class='floatleft'><?php AppUtility::t('Import file') ?> </span>
                <span class='floatleft margin-left-ten'>
                    <input name="userfile" type="file"/>
                </span>
                <div class="col-md-12 col-sm-12 padding-left-zero padding-top-twenty">
                    <button class="btn btn-primary page-settings" type="submit" value="Submit">
                        <i class="fa fa-share header-right-btn"></i>
                        <?php echo 'Submit' ?>
                    </button>
                </div>
                </div>
            </div>
        </form>
        <?php break;
    case "importcoursefiles":
        ?>
        <form enctype="multipart/form-data" method=post action="actions?action=importcoursefiles">

            <div class='col-md-12 col-sm-12 padding-twenty'>
                <div class='col-md-12 col-sm-12 text-gray-background padding-left-thirty padding-top-five'>
                    <h3><?php AppUtility::t('Install Course files') ?></h3>

                    <p>
                        <b><?php AppUtility::t('Warning') ?></b>
                        <span class='margin-left-ten'> <?php AppUtility::t('This has a large security risk') ?>
                            .  <b><?php AppUtility::t('Only install course files from a trusted source') ?></b>, <?php AppUtility::t("and where you've verified the archive only contains regular files (no PHP files)") ?>
                            .</span>
                    </p>

                    <p class='margin-bottom-ten'>
                        <b><?php AppUtility::t('Warning') ?></b>
                        <span
                            class='margin-left-ten'><?php AppUtility::t('Install will ignore files with the same filename as existing files') ?>
                            .</span>
                    </p>
                    <input type="hidden" name="MAX_FILE_SIZE" value="10000000"/>
                    <span class='floatleft'><?php AppUtility::t('Import file') ?> </span>
                <span class='floatleft margin-left-ten'>
                    <input name="userfile" type="file"/>
                </span>
                <div class="col-md-12 col-sm-12 padding-left-zero padding-top-twenty">
                    <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php AppUtility::t('Submit') ?></button>
                </div>
                </div>
            </div>
        </form>  <?php
        break;
    case "transfer":
        if ($myRights < AppConstant::LIMITED_COURSE_CREATOR_RIGHT) {
            echo AppConstant::NO_ACCESS_RIGHTS;
            break;
        }
        echo '<div id="headerforms" class="pagetitle">';
        echo '<div>';
        echo "<div class='col-md-10 col-sm-10'><h3>" . AppUtility::t('Transfer Course Ownership', false) . "</h3></div>\n";
        echo '</div>';
        echo "<form method=post action=\"actions?action=transfer&id={$params['cid']}\">\n";
        echo "<div class='col-md-3 col-sm-4'>" . AppUtility::t('Transfer course ownership to', false) . "</div>
         <div class='col-md-4 col-sm-4'><select name=newowner class='form-control'>\n";
        foreach ($queryUser as $key => $row) {
            echo "<option value=\"$row[id]\">$row[LastName], $row[FirstName]</option>\n";
        }
        echo "</select></div>\n";
        echo "<div class='col-md-10 col-sm-10'><input type=submit value=\"Transfer\">\n";
        echo "<input type=button value=\"Cancel\" class=\"secondarybtn\" onclick=\"window.location='index'\"></div>\n";
        echo "</div>";
        echo "</form>\n";
        break;

    case "deloldusers":
        ?>
        <form method=post action="actions?action=deloldusers">
            <div class="title-container">
                <div class="row">
                    <div class="">
                    </div>
                </div>
            </div>
            <div class='col-md-12 col-sm-12 padding-twenty'>
                <div class='col-md-12 col-sm-12 padding-top-five text-gray-background padding-left-thirty'>
                    <h3><?php AppUtility::t('Delete Old Users') ?></h3>

                    <div class='col-md-12 col-sm-12 margin-top-ten padding-left-zero'>
                        <span><?php AppUtility::t('Delete Users older than') ?></span>
                         <span>
                            <input type=text class='margin-left-ten form-control display-inline-block width-five-per'
                                   name=months size=4 value="6"/>
                            <span class='margin-left-ten'><?php AppUtility::t('Months') ?></span>
                        </span>
                    </div>
                    <div class='col-md-12 col-sm-12 margin-top-fifteen padding-left-zero'>
                        <span><?php AppUtility::t('Delete Who') ?></span>
             <span>
                    <span class='margin-left-ten'><input type=radio name=who value="students" CHECKED>
                        <span class='margin-left-five'><?php AppUtility::t('Students') ?></span>
                    </span>
                    <span class='margin-left-ten'>
                        <input type=radio name=who value="all">
                        <span class='margin-left-five'><?php AppUtility::t('Everyone but Admins') ?></span>
                    </span>
                  </span>
                    </div>
                    <div class="col-md-6 col-sm-6 padding-left-zero padding-top-twenty">
                        <button class="btn btn-primary page-settings" type="submit" value="Submit">
                            <i class="fa fa-share header-right-btn"></i>
                            <?php AppUtility::t('Delete') ?>
                        </button>
                    </div>
                </div>
            </div>
        </form> <?php
        break;
    case "listltidomaincred":
        ?>
        <div class="col-md-12 col-sm-12 modify-lti-domain-credential">
            <form method=post action="actions?action=modltidomaincred&id=new">
                <div id="headerforms" class="pagetitle">
                    <h3>
                        <?php AppUtility::t('Modify LTI Domain Credentials') ?>
                    </h3>
                </div>
                <table class='margin-top-fifteen table table-bordered table-striped table-hover data-table'>
                    <thead>
                    <tr>
                        <th class='word-break-break-all'><?php AppUtility::t('Domain') ?></th>
                        <th><?php AppUtility::t('Key') ?></th>
                        <th><?php AppUtility::t('Can create Instructors') ?>?</th>
                        <th><?php AppUtility::t('Modify') ?></th>
                        <th><?php AppUtility::t('Delete') ?></th>
                    </tr>
                    </thead>
                    <?php
                    foreach ($users as $row) {
                        echo "<tbody>
                    <tr>
                        <td class='word-break-break-all'>{$row['email']}</td>
                        <td class='word-break-break-all'>{$row['SID']}</td>";
                        if ($row['rights'] == AppConstant::SEVENTY_SIX) {
                            ?>
                            <td><?php AppUtility::t('Yes') ?></td>
                        <?php } else { ?>
                            <td><?php AppUtility::t('No') ?></td>
                        <?php } ?>
                        <td>
                            <a href="forms?action=modltidomaincred&id=<?php echo $row['id'] ?>"><?php AppUtility::t('Modify') ?></a>
                        </td>
                        <?php if ($row['id'] == AppConstant::NUMERIC_ZERO) {
                            echo "<td></td>";
                        } else {
                            ?>
                            <td>
                                <a href="javascript: deleteLtiUser(<?php echo $row['id'] ?>)">  <?php AppUtility::t('Delete') ?></a>
                            </td>
                        <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div class='col-md-12 col-sm-12 padding-left-zero'>
                    <div class='col-md-12 col-sm-12 padding-left-zero'>
                        <?php AppUtility::t('Add new LTI key/secret') ?>
                    </div>
                    <div class='margin-top-twenty col-md-12 col-sm-12 padding-left-zero'>
                <span class='col-md-2 col-sm-2 padding-left-zero'>
                    <?php AppUtility::t('Domain') ?>
                </span>
                        <div class='col-md-6 col-sm-6 padding-left-zero'>
                            <input class='form-control' type=text name="ltidomain" id="domain-char-limitation" size=40 maxlength="40">
                        </div>
                    </div>
                    <div class='margin-top-twenty col-md-12 col-sm-12 padding-left-zero'>
                <span class='col-md-2 col-sm-2 padding-left-zero'>
                    <?php AppUtility::t('Key') ?>
                </span>

                        <div class='col-md-6 col-sm-6 padding-left-zero'>
                            <input class='form-control' type=text name="ltikey" size=20 maxlength="40">
                        </div>
                    </div>
                    <div class='margin-top-twenty col-md-12 col-sm-12 padding-left-zero'>
                <span class='col-md-2 col-sm-2 padding-left-zero'>
                    <?php AppUtility::t('Secret') ?>
                </span>

                        <div class='col-md-6 col-sm-6 padding-left-zero'>
                            <input class='form-control' type=text name="ltisecret" size=20 >
                        </div>
                    </div>
                    <div class='margin-top-twenty col-md-12 col-sm-12 padding-left-zero'>
                <span class='col-md-2 col-sm-2 padding-left-zero'>
                    <?php AppUtility::t('Can create instructors') ?>
                </span>

                        <div class='col-md-6 col-sm-6 padding-left-zero'>
                            <select class='form-control' name="createinstr">
                                <option value="11" selected="selected"><?php AppUtility::t('No') ?></option>
                                <option value="76"><?php AppUtility::t('Yes, and creates');
                                    echo ' ' . $installname . ' ';
                                    AppUtility::t('login'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class='margin-top-twenty col-md-12 col-sm-12 padding-left-zero'>
                        <span class='col-md-2 col-sm-2 padding-left-zero'><?php AppUtility::t('Associate with group') ?></span>

                        <div class='col-md-6 col-sm-6 padding-left-zero' >
                            <select class='form-control' name='groupid'>
                                <option value='0'><?php AppUtility::t('Default') ?></option>
                                <div style="max-width: 300px">
                                <?php
                                foreach ($groupsName as $group) {
                                    echo '<option value="' . $group['id'] . '" >' . AppUtility::truncate($group['name'], 50) . '</option>';
                                }
                                echo '</div>';
                                echo "</select>"; ?>
                        </div>
                    </div>
                <div class="col-md-2 col-sm-2 padding-top-twenty padding-left-three col-sm-offset-2">
                    <input class="margin-left-minus-five"  id="lti-btn"  type=submit value="<?php AppUtility::t('Add LTI Credentials') ?>">
                </div>
                </div>
        </div>
        </form>
        <?php
        break;
    case "modltidomaincred":
        if ($myRights < AppConstant::ADMIN_RIGHT) {
            $this->setWarningFlash(AppConstant::NO_ACCESS_RIGHTS);
            return $this->redirect('forms?action = listltidomaincred');
        } ?>
        <div class="col-md-12 col-sm-12 modify-lti-domain-group-padding">
            <form method=post action="actions?action=modltidomaincred&id=<?php echo $user['id'] ?>">
                <div id="headerforms" class="pagetitle">
                    <h3>
                        <?php AppUtility::t('Modify LTI Domain Credentials') ?>
                    </h3>
                </div>
                <span class="col-md-3 col-sm-4 padding-left-zero margin-top-ten">
                    <?php AppUtility::t('Modify LTI key/secret') ?>
                </span>

                <div class="col-md-12 col-sm-12 padding-left-zero margin-top-fifteen">
                    <span class="col-md-2 col-sm-2 padding-left-zero select-text-margin">
                        <?php AppUtility::t('Domain') ?>
                    </span>
                    <span class="col-md-4 col-sm-4 padding-left-zero">
                        <input class="form-control" type=text name="ltidomain" id="domain-char-limitation" value="<?php echo $user['email']; ?>" size="40" maxlength="40">
                    </span>
                </div>
                <div class="col-md-12 col-sm-12 padding-left-zero margin-top-twenty">
                    <span class="col-md-2 col-sm-2 padding-left-zero select-text-margin">
                        <?php AppUtility::t('Key') ?>
                    </span>
                    <span class="col-md-4 col-sm-4 padding-left-zero">
                        <input class="form-control" type=text name="ltikey" value="<?php echo $user['SID'] ?>" size=40 maxlength="40">
                    </span>
                </div>
                <div class="col-md-12 col-sm-12 padding-left-zero margin-top-twenty">
                    <span class="col-md-2 col-sm-2 padding-left-zero select-text-margin">
                        <?php AppUtility::t('Secret') ?>
                    </span>
                    <span class="col-md-4 col-sm-4 padding-left-zero">
                        <input class="form-control" type=text name="ltisecret" value="<?php echo $user['password'] ?>"
                               size=20>
                    </span>
                </div>
                <div class="col-md-12 col-sm-12 padding-left-zero margin-top-twenty">
                    <span class="col-md-2 col-sm-2 padding-left-zero select-text-margin">
                        <?php AppUtility::t('Can create instructors') ?>
                    </span>
                    <span class="col-md-4 col-sm-4 padding-left-zero">
                        <select class="form-control" name="createinstr">
                            <option value="11"
                                <?php if ($user['rights'] == AppConstant::NUMERIC_ELEVEN) {
                                    echo 'selected="selected"';
                                } ?> >
                                <?php AppUtility::t('No') ?>
                            </option>
                            <option value="76" <?php
                            if ($user['rights'] == AppConstant::SEVENTY_SIX) {
                                echo 'selected="selected"';
                            } ?> >
                                <?php AppUtility::t('Yes') ?>
                            </option>
                        </select>
                    </span>
                </div>
                <div class="col-md-12 col-sm-12 padding-left-zero margin-top-twenty">
                    <span class="col-md-2 col-sm-2 padding-left-zero select-text-margin">
                        <?php AppUtility::t('Associate with group') ?>
                    </span>
                    <span class="col-md-4 col-sm-4 padding-left-zero">
                        <select class="form-control" name="groupid">
                            <option value="0">
                                <?php AppUtility::t('Default') ?>
                            </option>
                            <?php
                            foreach ($groupsName as $group) {
                                echo '<option value="' . $group['id'] . '"';
                                if ($group['id'] == $user['groupid']) {
                                    echo ' selected="selected"';
                                }
                                $groupName = AppUtility::truncate($group['name'],30);
                                echo '>' . $groupName. '</option>';
                            }
                            echo '</select>';
                            ?>
                     </span>
                </div>
                <div class="col-sm-2 col-sm-offset-2 padding-top-thirty padding-left-zero">
                    <input type=submit class="margin-left-minus-two" value="<?php AppUtility::t('Update LTI Credentials') ?>">
                </div>
            </form>
        </div>
        <?php break;
    case "listgroups":
        ?>
        <div class="col-md-12 col-sm-12 modify-group-padding">
            <form class="modify-group-table" method=post action="<?php echo AppUtility::getURLFromHome('admin', 'admin/actions?action=addgroup'); ?>">

                <div id="headerforms" class="pagetitle">
                    <h3 class="padding-left-thirteen padding-bottom-ten">
                        <?php AppUtility::t('Modify Groups') ?>
                    </h3>
                </div>
                <table class='margin-top-twenty table table-bordered table-striped table-hover data-table'>
                    <thead>
                    <tr>
                        <th><?php AppUtility::t('Group Name') ?></th>
                        <th><?php AppUtility::t('Modify') ?></th>
                        <th><?php AppUtility::t('Delete') ?></th>
                    </tr>
                    </thead>
                    <?php foreach ($groupsName as $row) {
                        echo "<tbody>
                    <tr>
                        <td class='word-break-all-width'>{$row['name']}</td>"; ?>
                        <td>
                            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=modgroup&id=' . $row['id']); ?>"><?php AppUtility::t('Modify') ?></a>
                        </td>
                        <?php if ($row['id'] == AppConstant::NUMERIC_ZERO) {
                            echo "<td></td>";
                        } else {
                            ?>
                            <td>
                                <a href="javascript: deleteGroup(<?php echo $row['id'] ?>)">  <?php AppUtility::t('Delete') ?></a>
                            </td>
                        <?php
                        }
                        echo "</tr>";
                    }?>
                </table>
                <?php  echo "<div class='col-md-12 col-sm-12 padding-left-zero padding-top-ten'>
                        <span class='select-text-margin col-md-2 col-sm-3 padding-left-zero'>" . AppUtility::t('Add new group', false) . "</span>
                        <span class='col-md-3 col-sm-3 padding-left-zero'><input class='display-inline-block form-control subject' type=text name=gpname id=gpname size=50></span>
                        </div>";
                ?>
                </tbody>
            <div class="col-md-12 col-sm-12 padding-top-twenty padding-bottom-twenty padding-left-zero">
                <button type=submit value="<?php AppUtility::t('Add Group') ?>">
                    <i class="fa fa-share header-right-btn"></i>
                    <?php AppUtility::t('Add Group') ?>
                </button>
            </div>
            </form>
        </div>
        <?php break;
    case "modgroup":
        ?>
        <div class="col-md-12 col-sm-12 rename-group-padding">
            <?php $gpname = $groupsName['name']; ?>
            <form method=post action=actions?action=modgroup&id=<?php echo $groupsName['id'] ?>>
                <div id="headerforms" class="pagetitle padding-left-zero">
                    <h2>
                        <?php AppUtility::t('Rename Instructor Group') ?>
                    </h2>
                </div>
                <?php AppUtility::t('Group name:', false) ?> <input
                    class="form-control width-thirty-three-per margin-top-thirty subject" type=text size=50 name=gpname
                    id=gpname value="<?php echo $gpname ?>">
                <button class="margin-top-twenty" type=submit value="<?php AppUtility::t('Update Group') ?>">
                    <i class="fa fa-share header-right-btn"></i>
                    <?php AppUtility::t('Update Group') ?>
                </button>
            </form>
        </div>
        <?php break;
    case "removediag":
        echo '<div class=""><br>';
        echo "<div class='col-md-10 col-sm-10'>" . AppUtility::t('Are you sure you want to delete this diagnostic?  This does not delete the connected course and does not remove students or their scores.', false) . "</div><br>\n";
        echo "<br> <div class='col-md-6 col-sm-6 padding-left-zero'><div class='col-md-2 col-sm-2'><input type=button value=\"Delete\" onclick=\"window.location='actions?action=removediag&id={$params['id']}'\"></div>\n";
        echo "<div class='col-md-2 col-sm-2 padding-left-zero'><input type=button value=\"Cancel\" class=\"secondarybtn\" onclick=\"window.location='index'\"></div></div>\n";
        echo '</div>';
        break;
}
?>
</div>
