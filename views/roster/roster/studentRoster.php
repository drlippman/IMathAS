<?php
use app\components\AppUtility;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = AppUtility::t('Roster', false);
   // encode title. id called specifically have been deemed safe and left alone
$this->params['breadcrumbs'][] = Html::encode($this->title);
$urlmode = AppUtility::urlMode();
?>
<input type="hidden" id="course-id" value="<?php echo $course->id ?>">
<input type="hidden" id="image-id" value="<?php echo $isImageColumnPresent ?>">
<div class="item-detail-header">
   <!-- html encoding -->
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), Html::encode($course->name)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]); ?>
</div>
<div class = "title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
   <!-- html encoding -->
            <div class="vertical-align title-page"><?php echo Html::encode($this->title) ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'roster']); ?>
</div>
<div class="tab-content shadowBox">

<?php echo $this->render("_toolbarRoster", ['course' => $course]); ?>
<div class="roster-upper-content col-md-12 col-sm-12 padding-right-thirty">
    <div class="page-title col-md-7 col-sm-6 pull-left">
    </div>
    <div class="with-selected col-md-2 col-sm-3 pull-left">
        <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown" href="#"><i class="fa fa-user ic"></i>&nbsp;<?php AppUtility::t('Pictures'); ?>
                    <span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu selected-options">
                    <li>
                        <a href="student-roster?cid=<?php echo $course->id ?>&showpic=1"><?php AppUtility::t('Show'); ?></a>
                    </li>
                    <li>
                        <a href="student-roster?cid=<?php echo $course->id ?>&showpic=0"><?php AppUtility::t('Hide'); ?></a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
    <div class="with-selected col-md-3 col-sm-3 pull-left padding-left-right-zero">
        <ul class="nav nav-tabs nav-justified roster-menu-bar-nav sub-menu">
            <li class="dropdown">
                <a class="dropdown-toggle grey-color-link" data-toggle="dropdown"
                   href="#"><?php AppUtility::t('With selected'); ?><span class="caret right-aligned"></span></a>
                <ul class="dropdown-menu with-selected">
                    <li><a class="non-locked" href="#"><i
                                class="fa fa-unlock-alt fa-fw"></i>&nbsp;<?php AppUtility::t('Select non-locked'); ?>
                        </a>
                    </li>
                    <li>
                        <form action="roster-email?cid=<?php echo $course->id ?>" method="post" id="roster-email-form">
                            <input type="hidden" id="student-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: studentEmail()"><i
                                    class="fa fa-at fa-fw"></i>&nbsp;<?php AppUtility::t('Email'); ?></a>
                        </form>
                    </li>
                    <li>
                        <form action="roster-message?cid=<?php echo $course->id ?>" method="post"
                              id="roster-message-form">
                            <input type="hidden" id="message-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: studentMessage()"><i
                                    class="fa fa-envelope-o fa-fw"></i>&nbsp;<?php AppUtility::t('Message'); ?></a>
                        </form>
                    </li>
                    <li>
                        <form action="unenroll?cid=<?php echo $course->id ?>" id="un-enroll-form"
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
                        <form action="make-exception?cid=<?php echo $course->id ?>" id="make-exception-form"
                              method="post">
                            <input type="hidden" id="exception-id" name="student-data" value=""/>
                            <input type="hidden" id="section-name" name="section-data" value=""/>
                            <a class="with-selected-list" href="javascript: teacherMakeException()"><i
                                    class='fa fa-plus-square fa-fw'></i>&nbsp;<?php AppUtility::t('Make Exception'); ?>
                            </a>
                        </form>
                    </li>
                    <li>
                        <form action="copy-student-email?cid=<?php echo $course->id ?>" method="post"
                              id="copy-emails-form">
                            <input type="hidden" id="email-id" name="student-data" value=""/>
                            <input type="hidden" id="course-id" name="course-id" value="<?php echo $course->id; ?>"/>
                            <a class="with-selected-list" href="javascript: copyStudentsEmail()"><i
                                    class="fa fa-clipboard fa-fw"></i>&nbsp;<?php AppUtility::t('Copy Emails'); ?></a>
                        </form>
                    </li>
                </ul>
            </li>

        </ul>
    </div>
</div>

<div class="roster-table col-sm-12">
    <table class="student-data-table table table-striped table-hover data-table dataTable no-footer">
        <thead>
        <tr>
            <th class="width-five-per">
                <div class="checkbox override-hidden">
                    <label>
                        <input type="checkbox" name="header-checked" value="">
                        <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                    </label>
                </div>
            </th>
            <?php if ($isImageColumnPresent == 1) {
                ?>
                <th class="width-eight-per"><?php AppUtility::t('Picture') ?></th>
            <?php } ?>
            <?php echo $hasSectionRowHeader; ?>
            <?php echo $hasCodeRowHeader; ?>
            <th class="width-ten-per"><?php AppUtility::t('Last') ?></th>
            <th class="width-ten-per"><?php AppUtility::t('First') ?></th>
            <th class="width-fifteen-per"><?php AppUtility::t('Email') ?></th>
            <th class="width-ten-per"><?php AppUtility::t('UserName') ?></th>
            <th class="width-ten-per"><?php AppUtility::t('Last Access') ?></th>
            <th class="width-fifteen-per"></th>
        </tr>
        </thead>
        <tbody id="student-information-table">
        <?php
        $alt = 0;
        $numstu = 0;
if(!empty($resultDefaultUserList)) {
        foreach($resultDefaultUserList as $line) {

            if ($line['section']==null) {
                $line['section'] = '';
            }
            $numstu++;
            if ($line['locked']>0) {
                $lastaccess = "Is locked out";
            } else {
                $lastaccess = ($line['lastaccess']>0) ? AppUtility::tzdate("n/j/y g:ia",$line['lastaccess']) : "never";
            }

            $hasSectionData = ($hassection) ? "<td class='word-break-break-all'>{$line['section']}</td>" : "";
            $hasCodeData = ($hascode) ? "<td class='word-break-break-all'>{$line['code']}</td>" : "";
            if ($alt==0) {echo "			<tr class='word-break-break-all even'>"; $alt=1;} else {echo "			<tr class=odd>"; $alt=0;}
            ?>
				<td class="sorting_1 word-break-break-all">
                    <div class='checkbox override-hidden'>
                        <label>
                             <input type='checkbox' name='student-information-check' value="<?php echo $line['userid']?>" >
                             <span class='cr'><i class='cr-icon fa fa-check'></i></span>
                        </label>
                    </div>
                </td>
				 <?php if ($isImageColumnPresent == 1) { ?>
				<td class="word-break-break-all">
            <?php
            if ($line['hasuserimg']==1)
            {
                if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                    echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$line['userid']}.jpg\" style=\"display:none;\"  />";
                } else { ?>
                     <img class="circular-image profile-pic" src="<?php echo AppUtility::getHomeURL().'Uploads/'.$line['userid'].'.jpg';?>" onclick='rotatepics()'>
                <?php }
            }else{ ?>

                <img  class='circular-image profile-pic' src='<?php echo AppUtility::getHomeURL().'Uploads/dummy_profile.jpg'?>' onclick='rotatepics()'>
            <?php }
            ?>
				</td>
				<?php }
            echo $hasSectionData;
            echo $hasCodeData;
            // html encoding in variables user specified
            if ($line['locked']>0) {
                echo '<td class="word-break-break-all"><span class="greystrike">'. Html::encode($line['LastName']) .'</span></td>';
                echo '<td class="word-break-break-all"><span class="greystrike">'. Html::encode($line['FirstName']) .'</span></td>';
            } else {
                echo '<td class="word-break-break-all">'. Html::encode($line['LastName']) .'</td><td class="word-break-break-all">'. Html::encode($line['FirstName']).'</td>';
            }
            ?>
				<td class="word-break-break-all"><a href="mailto:<?php echo Html::encode($line['email']) ?>"><?php echo Html::encode($line['email']) ?></a></td>
				<td class="word-break-break-all"><?php echo $line['SID'] ?></td>
				<td class="word-break-break-all"><a href="login-log?cid=<?php echo $course->id ?>&uid=<?php echo $line['userid'] ?>" class="lal"><?php echo $lastaccess ?></a></td>
            <td class="word-break-break-all">
            <div class='btn-group settings width-eighty-per col-sm-12 col-md-12 padding-left-zero padding-right-zero'>
                 <a class='btn btn-primary dropdown-toggle width-hundread-per col-sm-12 col-md-12' data-toggle='dropdown' href='#'>
                     <span class=' col-sm-10 col-md-10 padding-left-zero'><i class='fa fa-cog fa-fw'></i> Settings</span><span class=' padding-top-five fa fa-caret-down'></span>
                     </a>
                 <ul class='dropdown-menu roster-table roster-table-dropdown' style="width: 170px;">
                     <li><a href="<?php echo AppUtility::getURLFromHome('gradebook','gradebook/grade-book-student-detail?from=listusers&cid='.$course->id.'&studentId='.$line['userid'])?>">
                        <img class='small-icon' src="<?php echo AppUtility::getHomeURL().'img/gradebook.png'; ?>">&nbsp;Grades</a></li>
                     <li><a class ='roster-make-exception' href='<?php echo AppUtility::getURLFromHome('roster','roster/make-exception?cid='.$course->id.'&student-data='.$line['userid'].'&section-data='.$line['section'] )?>'>
                     <i class='fa fa-plus-square fa-fw'></i>&nbsp;Exception</a></li>
                     <li><a href='<?php echo AppUtility::getURLFromHome('roster','roster/change-student-information?cid='.$course->id.'&uid='.$line['userid'])?>'>
                     <i class='fa fa-pencil fa-fw'></i>&nbsp;Change Information</a></li>
                    <?php if ($line['locked'] == 0) {?>
                     <li>
                         <a  href='javascript: lockUnlockStudent(false,<?php echo $line['userid'];?>)'>
                             <i class='fa fa-lock fa-fw'></i>&nbsp;Lock
                         </a>
                     </li>
                    <?php } else { ?>
                     <li>
                         <a href='javascript: lockUnlockStudent(true,<?php echo $line['userid'];?>)'>
                             <i class='fa fa-unlock'></i>&nbsp;Unlock
                         </a>
                     </li>
                    <?php } ?>
                     </ul>
            </div>
            </td>
			</tr>
<?php
        }
        }
        ?>

        </tbody>
    </table>
</div>

<script>
    $(document).ready(function ()
    {
          studentData = <?php echo json_encode($resultDefaultUserList ); ?>;

    });
    </script>
