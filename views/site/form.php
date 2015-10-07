<?php
use app\components\AppConstant;
use app\components\AppUtility;
$imasroot = AppConstant::UPLOAD_DIRECTORY;
$pics = AppConstant::UPLOAD_DIRECTORY . $userId . '.jpg';

switch ($action) {
    case "newuser":
        break;

    case "chgpwd":
        break;

    case "chguserinfo":
        echo '<script type="text/javascript">function togglechgpw(val)
		{ if (val)
            {
            document.getElementById("pwinfo").style.display="";
            } else {
            document.getElementById("pwinfo").style.display="none";
            }
		} </script>';
        if ($gb == '') {
            echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Modify User Profile</div>\n";
        }
        echo '<div id="headerforms" class="pagetitle"><h2>User Profile</h2></div>';
        echo "<form enctype=\"multipart/form-data\" method=post action=\"action?action=chguserinfo$gb\">\n";
        echo '<fieldset id="userinfoprofile"><legend>Profile Settings</legend>';
        echo "<div class='col-lg-2'><label for=\"firstname\">Enter First Name:</label></div><div class='col-lg-8'><input class=form type=text size=20 id=firstname name=firstname value=\"{$line['FirstName']}\" /></div><br class=\"form\" />";
        echo "<div class=col-lg-2><label for=\"lastname\">Enter Last Name:</label></div> <div class='col-lg-6'><input class=form type=text size=20 id=lastname name=lastname value=\"{$line['LastName']}\"></div><BR class=form>\n";

        if ($myRights > AppConstant::STUDENT_RIGHT && $groupId > AppConstant::NUMERIC_ZERO) {
            echo '<div class="col-lg-2">'._('Group').':</div><div class="col-lg-6">'.$groupResult['name'].'</div><br class="form"/>';
        }
        echo '<div class="col-lg-2"><label for="dochgpw">Change Password?</label></div> <div class="col-lg-6"><input type="checkbox" name="dochgpw" onclick="togglechgpw(this.checked)" /></div><br class="form" />';
        echo '<div style="display:none" id="pwinfo">';
        echo "<div class='col-lg-2'><label for=\"oldpw\">Old password:</label></div> <div class='col-lg-6'><input class=form type=password id=oldpw name=oldpw size=40 /></div> <BR class=form>\n";
        echo "<div class='col-lg-2'><label for=\"newpw1\">Change password:</label></div> <div class='col-lg-6'> <input class=form type=password id=newpw1 name=newpw1 size=40></div> <BR class=form>\n";
        echo "<div class=col-lg-2><label for=\"newpw1\">Confirm password:</label></div> <div class='col-lg-6'> <input class=form type=password id=newpw2 name=newpw2 size=40></div> <BR class=form>\n";
        echo '</div>';
        echo "<div class=col-lg-2><label for=\"email\">E-mail address:</label></div> <div class='col-lg-6'> <input class=form type=text size=60 id=email name=email value=\"{$line['email']}\"></div><BR class=form>\n";
        echo "<div class=col-lg-2><label for=\"msgnot\">Notify me by email when I receive a new message:</label></div><div class=col-lg-6><input type=checkbox id=msgnot name=msgnot ";
        if ($line['msgnotify']==1) {echo "checked=1";}
        echo " /></div><BR class=form>\n";

        echo "<div class=col-lg-2><label for=\"stupic\">Picture:</label></div>";
        echo "<div class=\"col-lg-6\">";
        if ($line['hasuserimg'] == 1) {
            if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_$userid.jpg\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> Remove ";
            } else {
                $curdir = rtrim(dirname(__FILE__), '/\\');
                $galleryPath = "Uploads/";
                ?>

                <img src=<?php echo AppUtility::getHomeURL().AppConstant::UPLOAD_DIRECTORY.$userId.'.jpg'?>>
                <?php echo"<input type=\"checkbox\" name=\"removepic\" value=\"1\" /> Remove ";
            }
        } else {
            echo "No Pic ";
        }
        echo '<br/><input type="file" name="stupic"/></div><br class="form" />';
        echo '<div class="col-lg-2"><label for="perpage">Messages/Posts per page:</label></div>';
        echo '<div class="col-lg-6"><select name="perpage">';
        for ($i=10;$i<=100;$i+=10) {
            echo '<option value="'.$i.'" ';
            if ($i==$line['listperpage']) {echo 'selected="selected"';}
            echo '>'.$i.'</option>';
        }
        echo '</select></div><br class="form" />';

        $pagelayout = explode('|',$line['homelayout']);
        foreach($pagelayout as $k=>$v) {
            if ($v=='') {
                $pagelayout[$k] = array();
            } else {
                $pagelayout[$k] = explode(',',$v);
            }
        }
        $hpsets = '';
        if (!isset($CFG['GEN']['fixedhomelayout']) || !in_array(2,$CFG['GEN']['fixedhomelayout'])) {
            $hpsets .= '<input type="checkbox" name="homelayout10" ';
            if (in_array(10,$pagelayout[2])) {$hpsets .= 'checked="checked"';}
            $hpsets .=  ' /> New messages widget<br/>';

            $hpsets .= '<input type="checkbox" name="homelayout11" ';
            if (in_array(11,$pagelayout[2])) {$hpsets .= 'checked="checked"';}
            $hpsets .= ' /> New forum posts widget<br/>';
        }
        if (!isset($CFG['GEN']['fixedhomelayout']) || !in_array(3,$CFG['GEN']['fixedhomelayout'])) {

            $hpsets .= '<input type="checkbox" name="homelayout3-0" ';
            if (in_array(0,$pagelayout[3])) {$hpsets .= 'checked="checked"';}
            $hpsets .= ' /> New messages notes on course list<br/>';

            $hpsets .= '<input type="checkbox" name="homelayout3-1" ';
            if (in_array(1,$pagelayout[3])) {$hpsets .= 'checked="checked"';}
            $hpsets .= ' /> New posts notes on course list<br/>';
        }
        if ($hpsets != '') {
            echo '<div class="col-lg-2">Show on home page:</div><div class="col-lg-6">';
            echo $hpsets;
            echo '</div><br class="form" />';

        }
        if (isset($CFG['GEN']['translatewidgetID'])) {
            echo '<div class="col-lg-2">Attempt to translate pages into another language:</div>';
            echo '<div class="col-lg-6">';
            echo '<div id="google_translate_element"></div><script type="text/javascript">';
            echo ' function googleTranslateElementInit() {';
            echo '  new google.translate.TranslateElement({pageLanguage: "en", layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL}, "google_translate_element");';
            echo ' }</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>';
            echo '<br class="form"/>';
            unset($CFG['GEN']['translatewidgetID']);
        }
        echo '</fieldset>';

        if ($myRights > 19) {
            echo '<fieldset id="userinfoinstructor"><legend>Instructor Options</legend>';
            echo "<div class=col-lg-2><label for=\"qrd\">Make new questions private by default?<br/>(recommended for new users):</label></div><div class=col-lg-6><input type=checkbox id=qrd name=qrd ";
            if ($line['qrightsdef'] == 0) {
                echo "checked=1";
            }
            echo " /></div><BR class=form>\n";
            if ($line['deflib'] == 0) {
                $lName = "Unassigned";
            } else {
                $lName ;
            }

            echo "<script type=\"text/javascript\">";
            echo "var curlibs = '{$line['deflib']}';";
            echo "function libselect() {";
            echo "  window.open('$imasroot/course/libtree2.php?libtree=popup&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
            echo " }";
            echo "function setlib(libs) {";
            echo "  document.getElementById(\"libs\").value = libs;";
            echo "  curlibs = libs;";
            echo "}";
            echo "function setlibnames(libn) {";
            echo "  document.getElementById(\"libnames\").innerHTML = libn;";
            echo "}";
            echo "</script>";
            echo "<div class=col-lg-2>Default question library:</div><div class=col-lg-6> <span id=\"libnames\">$lName</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"{$line['deflib']}\">\n";
            echo " <input type=button value=\"Select Library\" onClick=\"libselect()\"></div><br class=form> ";

            echo "<div class=col-lg-2>Use default question library for all templated questions?</div>";
            echo "<div class=col-lg-6><input type=checkbox name=\"usedeflib\"";
            if ($line['usedeflib']==1) {echo "checked=1";}
            echo "> ";
            echo "</div><br class=form>";
            echo "<div class='col-lg-12'>Default question library is used for all local (assessment-only) copies of questions created when you ";
            echo "edit a question (that's not yours) in an assessment.  You can elect to have all templated questions ";
            echo "be assigned to this library.</div>";
            echo '</fieldset>';

        }
        if ($tzname != '') {

            echo '<fieldset><legend>Timezone</legend>';
            echo '<div class="col-lg-12">Due Dates and other times are being shown to you correct for the <b>'.$tzname.'</b> timezone.</div>';
            echo '<div class="col-lg-12">You may change the timezone the dates display based on if you would like. This change will only last until you close your browser or log out.</div>';
            echo '<div class="col-lg-12">Set timezone to: <select name="settimezone" id="settimezone">';
            $timezones = array('Etc/GMT+12', 'Pacific/Pago_Pago', 'America/Adak', 'Pacific/Honolulu', 'Pacific/Marquesas', 'Pacific/Gambier', 'America/Anchorage', 'America/Los_Angeles', 'Pacific/Pitcairn', 'America/Phoenix', 'America/Denver', 'America/Guatemala', 'America/Chicago', 'Pacific/Easter', 'America/Bogota', 'America/New_York', 'America/Caracas', 'America/Halifax', 'America/Santo_Domingo', 'America/Santiago', 'America/St_Johns', 'America/Godthab', 'America/Argentina/Buenos_Aires', 'America/Montevideo', 'Etc/GMT+2', 'Etc/GMT+2', 'Atlantic/Azores', 'Atlantic/Cape_Verde', 'Etc/UTC', 'Europe/London', 'Europe/Berlin', 'Africa/Lagos', 'Africa/Windhoek', 'Asia/Beirut', 'Africa/Johannesburg', 'Asia/Baghdad', 'Europe/Moscow', 'Asia/Tehran', 'Asia/Dubai', 'Asia/Baku', 'Asia/Kabul', 'Asia/Yekaterinburg', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Kathmandu', 'Asia/Dhaka', 'Asia/Omsk', 'Asia/Rangoon', 'Asia/Krasnoyarsk', 'Asia/Jakarta', 'Asia/Shanghai', 'Asia/Irkutsk', 'Australia/Eucla', 'Australia/Eucla', 'Asia/Yakutsk', 'Asia/Tokyo', 'Australia/Darwin', 'Australia/Adelaide', 'Australia/Brisbane', 'Asia/Vladivostok', 'Australia/Sydney', 'Australia/Lord_Howe', 'Asia/Kamchatka', 'Pacific/Noumea', 'Pacific/Norfolk', 'Pacific/Auckland', 'Pacific/Tarawa', 'Pacific/Chatham', 'Pacific/Tongatapu', 'Pacific/Apia', 'Pacific/Kiritimati');
            foreach ($timezones as $tz) {
                echo '<option value="'.$tz.'" '.($tz==$tzname?'selected':'').'>'.$tz.'</option>';
            }
            echo '</select></div>';
            echo '</fieldset>';


        }
        echo "<div class=submit><input type=submit value='Update Info'></div>\n";

        //echo '<p><a href="forms.php?action=googlegadget">Get Google Gadget</a> to monitor your messages and forum posts</p>';
        echo "</form>\n";
        break;
    case "forumwidgetsettings":
        echo '<div id="headerforms" class="pagetitle"><h2>Forum Widget Settings</h2></div>';
        echo '<p>The most recent 10 posts from each course show in the New Forum Posts widget.  Select the courses you want to show in the widget.</p>';
        echo "<form method=post action=\"action?action=forumwidgetsettings$gb\">\n";
        $allcourses = array();

        $result = $coursesTeaching;
        if (count($result) > 0) {
            echo '<p><b>Courses you\'re teaching:</b> Check: <a href="#" onclick="$(\'.teaching\').prop(\'checked\',true);return false;">All</a> <a href="#" onclick="$(\'.teaching\').prop(\'checked\',false);return false;">None</a>';
            foreach($result as $key => $row){
                $allcourses[] = $row['id'];
                echo '<br/><input type="checkbox" name="checked[]" class="teaching" value="'.$row['id'].'" ';
                if (!in_array($row['id'],$hideList)) {echo 'checked="checked"';}
                echo '/> '.$row['name'];
            }
            echo '</p>';
        }
        $result = $coursesTutoring;
        if (count($result) > 0) {
            echo '<p><b>Courses you\'re tutoring:</b> Check: <a href="#" onclick="$(\'.tutoring\').prop(\'checked\',true);return false;">All</a> <a href="#" onclick="$(\'.tutoring\').prop(\'checked\',false);return false;">None</a>';
            foreach($result as $key => $row) {
                $allcourses[] = $row['id'];
                echo '<br/><input type="checkbox" name="checked[]" class="tutoring" value="'.$row['id'].'" ';
                if (!in_array($row['id'],$hideList)) {echo 'checked="checked"';}
                echo '/> '.$row['name'];
            }
            echo '</p>';
        }
        $result = $coursesTaking;
        if (count($result) > 0) {
            echo '<p><b>Courses you\'re taking:</b> Check: <a href="#" onclick="$(\'.taking\').prop(\'checked\',true);return false;">All</a> <a href="#" onclick="$(\'.taking\').prop(\'checked\',false);return false;">None</a>';
            foreach($result as $key => $row){
                $allcourses[] = $row['id'];
                echo '<br/><input type="checkbox" name="checked[]" class="taking" value="'.$row['id'].'" ';
                if (!in_array($row['id'],$hideList)) {echo 'checked="checked"';}
                echo '/> '.$row['name'];
            }
            echo '</p>';
        }
        echo '<input type="hidden" name="allcourses" value="'.implode(',',$allcourses).'"/>';
        echo '<input type="submit" value="Save Changes"/>';
        echo '</form>';
        break;
}