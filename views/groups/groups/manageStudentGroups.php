<?php
use app\components\AppUtility;
$this->title = AppUtility::t('Manage Student Groups', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">
    <br>
    <div class="group-background">
        <div class="align-groups">
        <?php if(isset($addGrpSet)){?>
            <h4>Add new set of student groups</h4>
            <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&addgrpset=true')?>">
            <p>New group set name: <input name="grpsetname" type="text" /></p>
            <p><input type="submit" value="Create" />
            <input type=button value="Cancle" class="#" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)?>'" /></p>
            </form>
        <?php }elseif(isset($renameGrpSet)){?>
             <h4>Rename student group set</h4>
             <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&renameGrpSet='.$renameGrpSet)?>">
             <p>New group set name: <input name="grpsetname" type="text" value="<?php echo $grpSetName['name'];?>"/></p>
             <p><input type="submit" value="Rename" />
             <input type=button value="Cancle" class="#" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)?>'" /></p>
             </form>
        <?php }elseif(isset($deleteGrpSet)){?>
                <h4>Delete student group set</h4>
                <p>Are you SURE you want to delete the set of student groups <b><?php echo $deleteGrpName ;?></b> and all the groups contained within it?
                <?php if ($used != '')
                {
                        echo '<p>This set of groups is currently used in the assessments, wikis, and/or forums below.  These items will be set to non-group if this group set is deleted</p><p>';
                        echo "$used</p>";
                 } else
                 {
                        echo '<p>This set of groups is not currently being used</p>';
                 }?>
                 <p>
                    <input type=button value="Yes, Delete" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&deleteGrpSet='.$deleteGrpSet.'&confirm=true')?>'" />
                    <input type=button value="Nevermind" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)?>'" />
                </p>
         <?php }else{?>
        <h4>Student Group Sets</h4>
        <?php
        if (count($page_groupSets)==0)
        {
            echo '<p>No existing sets of groups</p>';
        } else
        {
            echo '<p>Select a set of groups to modify the groups in that set</p>';
            echo '<table><tbody><tr>';
                foreach ($page_groupSets as $gs)
                {
                        echo "<td><a href='#'>{$gs['name']}</a></td><td class=small>";
                        echo "<a href='manage-student-groups?cid=$course->id&renameGrpSet={$gs['id']}'>Rename</a> | ";
                        echo "<a href='manage-student-groups?cid=$course->id&copyGrpSet={$gs['id']}'>Copy</a> | ";
                        echo "<a href='manage-student-groups?cid=$course->id&deleteGrpSet={$gs['id']}'>Delete</a>";
                        echo '</td></tr>';
            }
            echo '</body></table>';
        }
        ?>
        <p class="hide-hover"><input type="button" class="btn btn-primary1 btn-color" value="Add new set of groups" onclick="window.location.href='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&addgrpset=ask');?>'"></p>
        <?php } ?>
        </div>
    </div>
</div>