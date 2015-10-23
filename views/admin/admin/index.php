<?php

use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'OpenMath Administration';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home'], 'link_url' => [AppUtility::getHomeURL() . 'site/index'], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class=mainbody>
<div class="headerwrapper"></div>
<input type="hidden" id="showCid" value="<?php echo $showCid; ?>">

<div class="midwrapper">
<div class="tab-content shadowBox non-nav-tab-item">
    <br>

    <div class="col-md-12"><b><?php AppUtility::t('Hello'); ?><?php echo $userName ?></b></div>
    <div class="col-md-12 margin-left-five"><h3><?php AppUtility::t('Courses'); ?></h3></div>

    <div class='item margin-padding-admin-table padding-bottom margin-twenty'>

        <table class="display course-table table table-bordered table-striped table-hover data-table">
            <thead>
            <tr>
                <th style="max-width: 400px"><?php AppUtility::t('Name'); ?></th>
                <th STYLE="text-align: center"><?php AppUtility::t('Course ID'); ?></th>
                <th><?php AppUtility::t('Owner'); ?></th>
                <th STYLE="text-align: center"><?php AppUtility::t('Settings'); ?></th>

            </tr>
            </thead>
            <tbody>
            <?php
            $alt = AppConstant::NUMERIC_ZERO;
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($page_courseList); $i++) {

                if ($alt == AppConstant::NUMERIC_ZERO) {
                    echo "	<tr class=even>";
                    $alt = AppConstant::NUMERIC_ONE;
                } else {
                    echo "	<tr class=odd>";
                    $alt = AppConstant::NUMERIC_ZERO;
                }
                ?>
                <td>
                    <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course?cid=' . $page_courseList[$i]['id']) ?>">
                        <?php
                        if (($page_courseList[$i]['available'] & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ONE) {
                            echo '<i>';
                        }
                        if (($page_courseList[$i]['available'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO) {
                            echo '<span style="color:#aaf;">';
                        }
                        if (($page_courseList[$i]['available'] & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR) {
                            echo '<span style="color:#faa;text-decoration: line-through;">';
                        }

                        echo $page_courseList[$i]['name'];

                        if (($page_courseList[$i]['available'] & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ONE) {
                            echo '</i>';
                        }
                        if (($page_courseList[$i]['available'] & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO || ($page_courseList[$i]['available'] & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR) {
                            echo '</span>';
                        }
                        ?>
                    </a>
                </td>
                <td class=c><?php echo $page_courseList[$i]['id'] ?></td>
                <td><?php echo $page_courseList[$i]['LastName'] ?>, <?php echo $page_courseList[$i]['FirstName'] ?></td>
                <td style="text-align: center">
                    <div class='btn-group settings'>
                        <a class='btn btn-primary setting-btn'
                           href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=modify&cid=' . $page_courseList[$i]['id']); ?>">
                            <i class='fa fa-cog fa-fw'></i><?php AppUtility::t('Settings'); ?>
                        </a>
                        <a class='btn btn-primary dropdown-toggle' id='drop-down-id' data-toggle='dropdown' href='#'>
                            <span class='fa fa-caret-down'></span>
                        </a>
                        <ul class='dropdown-menu'>
                            <li>
                                <a href="<?php echo AppUtility::getURLFromHome('course', 'course/add-remove-course?cid=' . $page_courseList[$i]['id']); ?>">
                                    <i class="fa fa-pencil"></i>&nbsp;<?php AppUtility::t('Add/Remove'); ?></a>
                            </li>
                            <li>
                                <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=transfer&cid=' . $page_courseList[$i]['id']); ?>"><i
                                        class="fa fa-exchange"></i>&nbsp;<?php AppUtility::t('Transfer'); ?></a>
                            </li>
                            <li>
                                <?php $CourseID = $page_courseList[$i]['id']; ?>
                                <a href='javascript:deleteCourse(<?php echo $CourseID ?>)'><i class='fa fa-trash-o'></i>&nbsp;<?php AppUtility::t('Delete'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            <?php
            }
            ?>
            </tbody>
        </table>
        <div class="col-md-12 padding-left-zero">
        <div class="col-md-2 pull-left padding-left-zero display-inline-block">
            <a class="btn btn-primary margin-left-twenty"
               href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=addcourse') ?>"><?php AppUtility::t('Add New Course'); ?> </a>
        </div>
        <?php
        if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT) {
        if ($showcourses > AppConstant::NUMERIC_ZERO) {
            echo "<input type=button value=\"Show My Courses\" onclick=\"window.location='index?showcourses=0'\" />";
        }
        echo "<div class='col-md-2 display-inline-block padding-left-zero'>";
        echo "Show courses of</div>" ;
//        echo $a = substr($page_teacherSelectVal,0,4);
       echo "<div class='col-md-5 padding-left-zero display-inline-block'>";AppUtility::writeHtmlSelect("seluid", $page_teacherSelectVal, $page_teacherSelectLabel, $showcourses, "Select a user..", AppConstant::NUMERIC_ZERO, "onchange=\"showcourses()\"");?>
    </div>
        </div>
    <?php
    }
    ?>
</div>

<?php
if ($myRights < AppConstant::GROUP_ADMIN_RIGHT && isset($CFG['GEN']['allowteacherexport'])) {
    ?>
    <div class=cp>
        <a href="#"><?php AppUtility::t('Export Question Set'); ?></a><BR>
        <a href="#"><?php AppUtility::t('Export Libraries'); ?></a>
    </div>
<?php
} else if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT) {
    ?>
    <div class='cp item margin-left-twenty padding-bottom'>
        <span class=column>
            <a href="<?php echo AppUtility::getURLFromHome('question', 'question/manage-question-set?cid=admin') ?>"><?php AppUtility::t('Manage Question Set'); ?></a><BR>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/export-question-set?cid=admin') ?>"><?php AppUtility::t('Export Question Set'); ?></a><BR>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/import-question-set?cid=admin') ?>"><?php AppUtility::t('Import Question Set'); ?></a><BR>
        </span>
        <span class=column>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/manage-lib?cid=admin') ?>"><?php AppUtility::t('Manage Libraries'); ?></a><br>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/export-lib?cid=admin') ?>"><?php AppUtility::t('Export Libraries'); ?></a><BR>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/import-lib?cid=admin') ?>"><?php AppUtility::t('Import Libraries'); ?></a>
        </span>
        <?php
        if ($myRights == AppConstant::ADMIN_RIGHT) {
            ?>
            <span class=column>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=listgroups') ?>"><?php AppUtility::t('Edit Groups'); ?></a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=deloldusers') ?>"><?php AppUtility::t('Delete Old Users'); ?></a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('roster', 'roster/import-student?cid=admin') ?>"><?php AppUtility::t('Import Students from File'); ?></a>
        </span>
            <span class="column">
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=importmacros') ?>"><?php AppUtility::t('Install Macro File'); ?></a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=importqimages') ?>"><?php AppUtility::t('Install Question Images'); ?></a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=importcoursefiles') ?>"><?php AppUtility::t('Install Course Files'); ?></a><br/>
        </span>
            <span class="column">
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=listltidomaincred') ?>"><?php AppUtility::t('LTI Provider Creds'); ?></a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/external-tool?cid=admin') ?>"><?php AppUtility::t('External Tools'); ?></a><br/>
            <a href="<?php echo AppUtility::getURLFromHome('utilities', 'utilities/admin-utilities') ?>"><?php AppUtility::t('Admin Utilities'); ?></a><br/>
        </span>
        <?php
        }
        ?>
        <div class=clear></div>
    </div>
<?php
}
if ($myRights >= AppConstant::DIAGNOSTIC_CREATOR_RIGHT) {
    ?>
    <div class="col-md-12 margin-left-five"><h3><?php AppUtility::t('Diagnostics'); ?></h3></div>

    <div class='item margin-padding-admin-table padding-bottom'>
        <table class="display course-table table table-bordered table-striped table-hover data-table">
            <thead>
            <tr>
                <th><?php AppUtility::t('Name'); ?></th>
                <th STYLE="text-align: center"><?php AppUtility::t('Available'); ?></th>
                <th STYLE="text-align: center"><?php AppUtility::t('Public'); ?></th>
                <th style="text-align: center"><?php AppUtility::t('Modify'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            $alt = AppConstant::NUMERIC_ZERO;
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($page_diagnosticsId); $i++) {
                if ($alt == AppConstant::NUMERIC_ZERO) {
                    echo "	<tr class=even>";
                    $alt = AppConstant::NUMERIC_ONE;
                } else {
                    echo "	<tr class=odd>";
                    $alt = AppConstant::NUMERIC_ZERO;
                }
                ?>
                <td>
                    <a href="<?php echo AppUtility::getURLFromHome('site', 'diagnostics?id=' . $page_diagnosticsId[$i]) ?>"><?php echo $page_diagnosticsName[$i] ?></a>
                </td>
                <td class=c><?php echo $page_diagnosticsAvailable[$i] ?></td>
                <td class=c><?php echo $page_diagnosticsPublic[$i] ?></td>
                <td style="text-align: center">
                    <div class='btn-group settings'>
                        <a class='btn btn-primary setting-btn'
                           href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diagnostics?id=' . $page_diagnosticsId[$i]) ?>">
                            <i class="fa fa-pencil"></i>
                            <?php AppUtility::t('Modify'); ?>
                        </a>
                        <a class='btn btn-primary dropdown-toggle' id='drop-down-id' data-toggle='dropdown' href='#'>
                            <span class='fa fa-caret-down'></span>
                        </a>
                        <ul class='dropdown-menu'>
                            <li>
                                <?php $diagnoId = $page_diagnosticsId[$i]; ?>
                                <a href='javascript:deleteDiagnostics(<?php echo $diagnoId ?>)'>
                                    <i class='fa fa-trash-o'></i>&nbsp;<?php AppUtility::t('Remove'); ?></a>
                            </li>
                            <li>
                                <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diag-one-time?id=' . $page_diagnosticsId[$i]) ?>">
                                    <i class="fa fa-key"></i>&nbsp;<?php AppUtility::t('One-time Passwords'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            <?php
            }
            ?>
            </tbody>
        </table>

        <a class="btn btn-primary margin-left-twenty"
           href="<?php echo AppUtility::getURLFromHome('admin', 'admin/diagnostics') ?>">
            <?php AppUtility::t('Add New Diagnostic'); ?>
        </a>
    </div>
<?php
}
if ($myRights >= AppConstant::GROUP_ADMIN_RIGHT) {
?>
<div class="col-md-12 margin-left-five"><h3><?php echo $page_userBlockTitle ?></h3></div>

<div class='item margin-padding-admin-table padding-bottom'>
    <table class="display course-table table table-bordered table-striped table-hover data-table">
        <thead>
        <tr>
            <th><?php AppUtility::t('Name'); ?></th>
            <th><?php AppUtility::t('Username'); ?></th>
            <th><?php AppUtility::t('Email'); ?></th>
            <th><?php AppUtility::t('Rights'); ?></th>
            <th><?php AppUtility::t('Last Login'); ?></th>
            <th><?php AppUtility::t('Settings'); ?></th>
        </tr>
        </thead>
        <tbody class="">

        <?php
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($page_userDataId); $i++) {
            if ($alt == AppConstant::NUMERIC_ZERO) {
                echo "	<tr class=even>";
                $alt = AppConstant::NUMERIC_ONE;
            } else {
                echo "	<tr class=odd>";
                $alt = AppConstant::NUMERIC_ZERO;
            }
            ?>
            <td><?php echo $page_userDataLastName[$i] . ", " . $page_userDataFirstName[$i] ?></td>
            <td><?php echo $page_userDataSid[$i] ?></td>
            <td><?php echo $page_userDataEmail[$i] ?></td>
            <td><?php echo $page_userDataType[$i] ?></td>
            <td><?php echo $page_userDataLastAccess[$i] ?></td>

            <td class=c>
                <ul class="nav roster-menu-bar-nav sub-menu col-md-12">
                    <li class="dropdown">
                        <a class="dropdown-toggle grey-color-link" data-toggle="dropdown"
                           href="#"><?php AppUtility::t('Settings'); ?>
                            <span class="caret right-aligned"></span></a>
                        <ul class="dropdown-menu selected-options user-settings">
                            <li>
                                <a href="<?php echo AppUtility::getURLFromHome('admin', 'admin/forms?action=chgrights&id=' . $page_userDataId[$i]) ?>">
                                    <?php AppUtility::t('Change'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo AppUtility::getURLFromHome('site', 'change-password?id=' . $page_userDataId[$i]) ?>">
                                    <?php AppUtility::t('Reset'); ?>
                                </a>
                            </li>
                            <li>
                                <?php $userId = $page_userDataId[$i];?>
                                <a href='javascript:deleteAdmin(<?php echo $userId ?>)'><?php AppUtility::t('Delete')?></a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </td>
            </tr>
        <?php
        }
        ?>

        </tbody>
    </table>
<!--    <a class="btn btn-primary margin-left-twenty"-->
<!--       href="--><?php //echo AppUtility::getURLFromHome('admin', 'admin/add-new-user') ?><!--">-->
<!--        --><?php //AppUtility::t('Add New User'); ?>
<!--    </a>-->
    <input type=button value="Add New User" onclick="window.location='forms?action=newadmin'">
    <?php
    }
    if ($myRights == AppConstant::ADMIN_RIGHT) {
        writeHtmlSelect("selgrpid", $page_userSelectVal, $page_userSelectLabel, $showusers, null, null, "onchange=showgroupusers()");
    }
    ?>
    <?php
    function writeHtmlSelect($name, $valList, $labelList, $selectedVal = null, $defaultLabel = null, $defaultVal = null, $actions = null)
    {
        echo "<select class='form-control all-user-select user-select' name=\"$name\" id=\"$name\" ";
        echo (isset($actions)) ? $actions : "";
        echo ">\n";
        if (isset($defaultLabel) && isset($defaultVal)) {
            echo "<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
        }
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($valList); $i++) {
            if ((isset($selectedVal)) && ($valList[$i] == $selectedVal)) {
                echo "<option value=\"$valList[$i]\" selected>$labelList[$i]</option>\n";
            } else {
                echo "<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
            }
        }
        echo "</select>\n";
    }

    ?>

</div>
<div class="clear"></div>
</div>
</div>
</div>
