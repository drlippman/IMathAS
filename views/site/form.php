<?php
use app\components\AppConstant;
use app\components\AppUtility;
if ($gb == '') {
$this->title = $pageTitle;
}
$imasroot = AppConstant::UPLOAD_DIRECTORY;
$pics = AppConstant::UPLOAD_DIRECTORY . $userId . '.jpg';
$emailErr = "";
$urlmode = AppUtility::urlMode();
?>
<div class="item-detail-header">
    <?php echo $this->render("../itemHeader/_indexWithLeftContent",['link_title'=>['Home'], 'link_url' => [AppUtility::getHomeURL().'site/index'], 'page_title' => $this->title]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="col-md-12 col-sm-12 tab-content shadowBox non-nav-tab-item">
<?php

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
		} </script>'; ?>

        <form enctype="multipart/form-data" method=post action="action?action=chguserinfo <?php echo $gb ?>" >
        <div class="col-md-4 col-sm-4 font-size-twenty-one padding-top-one-em"><?php AppUtility::t('Profile Settings'); ?></div>

        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
            <div class="col-md-2 col-sm-3 padding-top-pt-five-em">
                <label for="firstname"><?php AppUtility::t('Enter First Name'); ?></label>
            </div>
            <div class="col-md-8 col-sm-8">
                <input class='form form-control-1' required="" type=text size=20 id="firstname" name=firstname  value="<?php echo $line['FirstName'] ?>" />
            </div>
        </div>

        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
            <div class="col-md-2 col-sm-3 padding-top-pt-five-em">
                <label for="lastname"><?php AppUtility::t('Enter Last Name'); ?></label>
            </div>
            <div class="col-md-6 col-sm-6">
            <input class='form form-control'required="" type=text size=20 id=lastname name=lastname value="<?php echo $line['LastName'] ?>" >
            </div>
        </div>

        <?php if ($myRights > AppConstant::STUDENT_RIGHT && $groupId > AppConstant::NUMERIC_ZERO) { ?>
            <div class="col-md-12 col-sm-12 padding-left-zero padding-one-em">
                <div class="col-md-2 col-sm-3 padding-top-pt-five-em"><?php AppUtility::t(Group); ?></div>
                <div class="col-md-6 col-sm-7"><?php echo $groupResult['name'] ?></div>
            </div>
        <?php } ?>

        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
            <div class="col-md-2 col-sm-3 padding-top-pt-five-em padding-right-zero">
                <label for="dochgpw"><?php AppUtility::t('Change Password?'); ?></label>
            </div>
            <div class="col-md-2 col-sm-2 col-xs-2">
                <input type="checkbox" name="dochgpw" onclick="togglechgpw(this.checked)" />
            </div>
        </div>

        <div style="display:none" id="pwinfo">
            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
                <div class="col-md-2 col-sm-3">
                    <label for="oldpw"><?php AppUtility::t('Old password'); ?></label>
                </div>
                <div class="col-md-6 col-sm-6">
                    <input class="form form-control" type=password id=oldpw name=oldpw size=40 />
                </div>
            </div>
            <div class='col-md-12 col-sm-12 padding-left-zero padding-top-one-em'>
                <div class="col-md-2 col-sm-3">
                    <label for="newpw1"><?php AppUtility::t('Change password'); ?></label>
                </div>
                <div class="col-md-6 col-sm-6">
                    <input class="form form-control" type=password id=newpw1 name=newpw1 size=40>
                </div>
            </div>
            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
                <div class="col-md-2 col-sm-3">
                    <label for="newpw1"><?php AppUtility::t('Confirm password'); ?></label>
                </div>
                <div class="col-md-6 col-sm-6">
                        <input class="form form-control" type=password id=newpw2 name=newpw2 size=40>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-sm-12 col-xs-12 padding-left-zero padding-top-one-em">
            <div class="col-md-2 col-sm-3 col-xs-3 padding-top-pt-five-em">
                <label for="email"><?php AppUtility::t('E-mail address'); ?></label>
            </div>
            <div class="col-md-6 col-sm-6 col-xs-6">
                <input class="form form-control" type=email size=60 id="email" name=email value="<?php echo $line['email'] ?>" >
            </div>
        </div>

        <div class="col-md-12 col-sm-12 col-xs-12 padding-left-zero padding-top-one-em">
            <div class="col-md-offset-2 col-sm-offset-3 col-md-10 col-sm-9">
                <input type=checkbox id=msgnot name=msgnot <?php if ($line['msgnotify']==1) {echo "checked=1";} ?> />
                <label for="msgnot"><?php AppUtility::t('Notify me by email when I receive a new message') ?></label>
            </div>
        </div>

        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
            <div class="col-md-2 col-sm-3">
                <label for="stupic"><?php AppUtility::t('Picture'); ?></label>
            </div>
            <div class="col-md-8 col-sm-8 padding-left-zero">
               <div class="col-md-4 col-sm-5 padding-bottom-one-em">
                   <?php if ($line['hasuserimg'] == 1) {
                        if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                            echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_$userid.jpg\"/>
                            <input type=\"checkbox\" name=\"removepic\" value=\"1\" />
                            <span>".AppUtility::t('Remove')."</span>";
                        } else {
                            $curdir = rtrim(dirname(__FILE__), '/\\');
                            $galleryPath = "Uploads/";
                            ?>
                            <img src=<?php echo AppUtility::getHomeURL().AppConstant::UPLOAD_DIRECTORY.$userId.'.jpg'?>>
                            <?php echo"<input type=\"checkbox\" name=\"removepic\" value=\"1\" />
                            <span>".AppUtility::t('Remove',false)."</span>";
                        }
                    } else {
                        echo "No Pic ";
                    } ?>
               </div>
               <div class="col-md-12 col-sm-12">
                   <input type="file" name="stupic">
               </div>
            </div>
        </div>

        <div class="col-md-12 col-sm-12 padding-left-zero padding-top-two-em">
            <div class="col-md-2 col-sm-3">
                <label for="perpage"><?php AppUtility::t('Messages/Posts per page'); ?></label>
            </div>
            <div class="col-md-2 col-sm-2">
                <select class="form-control" name="perpage">
                    <?php for ($i=10;$i<=100;$i+=10) {
                        echo '<option value="'.$i.'" ';
                        if ($i==$line['listperpage']) {echo 'selected="selected"';}
                        echo '>'.$i.'</option>';
                    } ?>
                </select>
            </div>
        </div>
        <?php $pagelayout = explode('|',$line['homelayout']);
        foreach($pagelayout as $k=>$v) {
            if ($v=='') {
                $pagelayout[$k] = array();
            } else {
                $pagelayout[$k] = explode(',',$v);
            }
        }
        $hpsets = '';
        if (!isset($CFG['GEN']['fixedhomelayout']) || !in_array(2,$CFG['GEN']['fixedhomelayout'])) {
            $hpsets .= '<div class="col-md-10 col-sm-10">
                <input type="checkbox" name="homelayout10" ';
                if (in_array(10,$pagelayout[2])) {$hpsets .= 'checked="checked"';}
                $hpsets .=  ' />
                <span>'.AppUtility::t('New messages widget',false).'</span>
            </div>';
            $hpsets .= '<div class="col-md-10 col-sm-10">
            <input type="checkbox" name="homelayout11" ';
            if (in_array(11,$pagelayout[2])) {$hpsets .= 'checked="checked"';}
            $hpsets .= ' />
            <span>'.AppUtility::t('New forum posts widget',false).'</span>
            </div>';
        }

        if ($hpsets != '') { ?>
            <div class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em">
                <div class="col-md-2 col-sm-3"><?php AppUtility::t('Show on home page') ?></div>
                <div class="col-md-8 col-sm-8 padding-left-zero">
                    <?php echo $hpsets; ?>
                </div>
            </div>
        <?php }

        if (isset($CFG['GEN']['translatewidgetID'])) {
            echo '<div class="col-md-2">'.AppUtility::t('Attempt to translate pages into another language').'</div>';
            echo '<div class="col-md-6">';
            echo '<div id="google_translate_element"></div><script type="text/javascript">';
            echo ' function googleTranslateElementInit() {';
            echo '  new google.translate.TranslateElement({pageLanguage: "en", layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL}, "google_translate_element");';
            echo ' }</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>';
            echo '<br class="form"/>';
            unset($CFG['GEN']['translatewidgetID']);
        }

        if ($myRights > 19) { ?>
        <div class="col-md-4 col-sm-4 font-size-twenty-one padding-top-two-em"><?php AppUtility::t('Instructor Options') ?></div>
            <div class="col-md-offset-2 col-sm-offset-3 col-md-10 col-sm-9 padding-top-one-em">
                <input class="floatleft" type="checkbox" id="qrd" name="qrd" <?php if ($line['qrightsdef'] == 0) { echo "checked=1"; } ?> />
                <div class="col-md-11 col-sm-11 padding-right-zero">
                    <label for="qrd"><?php AppUtility::t('Make new questions private by default?(recommended for new users)') ?></label>
                </div>
            </div>

        <?php if ($line['deflib'] == 0) {
                $lName = "Unassigned";
            } else {
                $lName;
            }

            echo "<script type=\"text/javascript\">";
            echo "var curlibs = '{$line['deflib']}';";
            echo "function libselect() {";
            echo "  window.open('$imasroot/course/libtree2.php?libtree=popup&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
            echo " }";
            echo "function setlib(libs) {";
            echo "console.log(libs);";
            echo "  document.getElementById(\"libs\").value = libs;";
            echo "  curlibs = libs;";
            echo "}";
            echo "function setlibnames(libn) {";
            echo "  document.getElementById(\"libnames\").innerHTML = libn;";
            echo "}";
            echo "</script>"; ?>

            <div class='col-sm-12 col-md-12 padding-left-zero padding-top-one-em'>
                <div class='col-md-2 col-sm-3 padding-top-pt-five-em padding-right-zero'>Default question library</div>
                <div class='col-md-9 col-sm-8'>
                    <span class='col-md-2 col-sm-3 padding-top-pt-five-em padding-left-zero' id="libnames"> <?php echo $lName ?> </span>
                    <input type=hidden name="libs" id="libs"  value="<?php echo $line['deflib']; ?>" >
                    <div class='col-md-2 col-sm-4 padding-left-zero'>
                        <input type=button value="Select Library" onClick="libselect()">
                    </div>
                </div>
            </div>

            <div class='col-md-offset-2 col-sm-offset-3 col-md-10 col-sm-9 padding-left-zero padding-top-one-em'>
                <div class="col-md-12 col-sm-12">
                    <div class='floatleft'>
                            <input type=checkbox name="usedeflib" <?php if ($line['usedeflib']==1) {echo "checked=1";} ?> >
                    </div>
                    <div class='col-md-8 col-sm-10'>Use default question library for all templated questions?</div>
                </div>
            </div>
            <div class='col-md-offset-2 col-md-10 col-sm-offset-3 col-sm-9 padding-top-one-em'>
                Default question library is used for all local (assessment-only) copies of questions created when you
                edit a question (that's not yours) in an assessment.  You can elect to have all templated questions
                be assigned to this library.
            </div>

        <?php } if ($tzname != '') { ?>
            <div class="col-md-4 col-sm-4 font-size-twenty-one padding-top-one-em">Timezone</div>
            <div class="col-md-offset-2 col-md-10 col-sm-9 col-sm-offset-3 padding-top-one-em">
                <?php AppUtility::t('Due Dates and other times are being shown to you correct for the'); ?>
                <b> <?php echo $tzname ?> </b><?php AppUtility::t('timezone.'); ?>
            </div>
            <div class="col-md-offset-2 col-md-10 col-sm-9 col-sm-offset-3 padding-top-one-em">
                You may change the timezone the dates display based on if you would like. This change will only last until you close your browser or log out.
            </div>
            <div class="col-md-12 col-sm-12 padding-top-two-em">
                <span class="col-md-2 col-sm-3 padding-left-zero">Set timezone to</span>
                <span class="col-md-2 col-sm-3 padding-left-five">
                    <select class="form-control margin-left-zero" name="settimezone" id="settimezone">
                    <?php $timezones = array('Etc/GMT+12', 'Pacific/Pago_Pago', 'America/Adak', 'Pacific/Honolulu', 'Pacific/Marquesas', 'Pacific/Gambier', 'America/Anchorage', 'America/Los_Angeles', 'Pacific/Pitcairn', 'America/Phoenix', 'America/Denver', 'America/Guatemala', 'America/Chicago', 'Pacific/Easter', 'America/Bogota', 'America/New_York', 'America/Caracas', 'America/Halifax', 'America/Santo_Domingo', 'America/Santiago', 'America/St_Johns', 'America/Godthab', 'America/Argentina/Buenos_Aires', 'America/Montevideo', 'Etc/GMT+2', 'Etc/GMT+2', 'Atlantic/Azores', 'Atlantic/Cape_Verde', 'Etc/UTC', 'Europe/London', 'Europe/Berlin', 'Africa/Lagos', 'Africa/Windhoek', 'Asia/Beirut', 'Africa/Johannesburg', 'Asia/Baghdad', 'Europe/Moscow', 'Asia/Tehran', 'Asia/Dubai', 'Asia/Baku', 'Asia/Kabul', 'Asia/Yekaterinburg', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Kathmandu', 'Asia/Dhaka', 'Asia/Omsk', 'Asia/Rangoon', 'Asia/Krasnoyarsk', 'Asia/Jakarta', 'Asia/Shanghai', 'Asia/Irkutsk', 'Australia/Eucla', 'Australia/Eucla', 'Asia/Yakutsk', 'Asia/Tokyo', 'Australia/Darwin', 'Australia/Adelaide', 'Australia/Brisbane', 'Asia/Vladivostok', 'Australia/Sydney', 'Australia/Lord_Howe', 'Asia/Kamchatka', 'Pacific/Noumea', 'Pacific/Norfolk', 'Pacific/Auckland', 'Pacific/Tarawa', 'Pacific/Chatham', 'Pacific/Tongatapu', 'Pacific/Apia', 'Pacific/Kiritimati');
                    foreach ($timezones as $tz) {
                        echo '<option value="'.$tz.'" '.($tz==$tzname?'selected':'').'>'.$tz.'</option>';
                    } ?>
                   </select>
               </span>
            </div>
        <?php } ?>
        <div class="col-md-offset-2 col-sm-offset-3 col-md-2 col-sm-2 padding-top-one-em padding-bottom-two-em">
            <input type=submit value='Update Info'>
        </div>
<!--        <p>-->
<!--            <a href="forms.php?action=googlegadget">Get Google Gadget</a> to monitor your messages and forum posts-->
<!--        </p>-->
        </form>
    <?php break;

    case "forumwidgetsettings":
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
}?>

<?php        $result = $coursesTutoring;
        if (count($result) > 0) {
            echo '<p><b>Courses you\'re tutoring:Check:</b> <a href="#" onclick="$(\'.tutoring\').prop(\'checked\',true);return false;">All</a> <a href="#" onclick="$(\'.tutoring\').prop(\'checked\',false);return false;">None</a>';

                        foreach($result as $key => $row) {
                            $allcourses[] = $row['id'];
                            echo '<br/><input type="checkbox" name="checked[]" class="teaching" value="'.$row['id'].'" ';
                            if (!in_array($row['id'],$hideList)) {echo 'checked="checked"';}
                            echo '/> '.$row['name'];
                         } ?>

<?php
            echo '</p>';
        } ?>

     <?php   $result = $coursesTaking;
        if (count($result) > 0) {
            echo '<p><b>Courses you\'re taking:</b> Check: <a href="#" onclick="$(\'.taking\').prop(\'checked\',true);return false;">All</a> <a href="#" onclick="$(\'.taking\').prop(\'checked\',false);return false;">None</a>';

            foreach($result as $key => $row) {
                $allcourses[] = $row['id'];
                echo '<br/><input type="checkbox" name="checked[]" class="teaching" value="'.$row['id'].'" ';
                if (!in_array($row['id'],$hideList)) {echo 'checked="checked"';}
                echo '/> '.$row['name'];
            }
        }
     ?>

        <?php echo '<input type="hidden" name="allcourses" value="'.implode(',',$allcourses).'"/>'; ?>
        <div class="header-btn floatleft padding-bottom-one-em">
            <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Save Changes' ?></button>
        </div>
       <?php echo '</form>';
        break;
?>

    </div>
    <?php } ?>

