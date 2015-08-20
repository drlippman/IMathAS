<?php
use app\components\AppUtility;
use app\components\AppConstant;
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
        <?php }elseif(isset($renameGrp)){?>
            <h4>Rename student group</h4>
                <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&renameGrp='.$renameGrp)?>">
                <p>New group name:<input name="grpname" type="text" value="<?php echo $currGrpName;?>"/></p>
                <p><input type="submit" value="Rename" />
                    <input type=button value="Cancle" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId'.$grpSetId)?>'" /></p>
            </form>
        <?php }elseif(isset($deleteGrp)){?>
            <h4>Delete student group</h4>
                <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&deleteGrp='.$deleteGrp.'&confirm=true')?>" >
                <p>Are you SURE you want to delete the student group <b><?php echo $currGrpNameToDlt;?></b>?</p>
                <p>Any wiki page content for this group will be deleted.</p>
                <p><input type="radio" name="delpost" value="1" checked="checked" /> Delete group forum posts
                <input type="radio" name="delpost" value="0" /> Make group forum posts non-group-specific posts</p>
                <p><input type="submit" value="Yes, Delete">
                <input type=button value="Cancle" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId'.$grpSetId)?>'" /></p>
                </form>
        <?php }elseif(isset($grpSetId)){?>
            <h3>Managing groups in set <?php echo $grpSetName?></h3>
            <div id="myTable">
                <p><button type="button" onclick="">Add New Group</button>
                    <?php if(array_sum($hasUserImg)){
                        echo ' <button type="button" onclick="rotatepics(this)" >'.'View Pictures'.'</button><br/>';
                    }?>
                </p>
                <?php if (count($page_Grp)==0)
                {
                    echo '<p>No student groups have been created yet</p>';
                }foreach($page_Grp as $grpId=>$grpName)
                {
                    echo "<b>Group: $grpName</b> | ";
                    echo "<a href='manage-student-groups?cid=$course->id&grpSetId={$grpSetId}&renameGrp={$grpId}'>Rename</a> | ";
                    echo "<a href='manage-student-groups?cid=$course->id&grpSetId={$grpSetId}&deleteGrp={$grpId}'>Delete</a> | ";
                    echo "<a href='#'>Remove all members</a>";
                    echo '<ul>';
                    if (count($page_GrpMembers[$grpId])==0)
                    {
                        echo '<li>No group members</li>';
                    }else
                    {
                        foreach($page_GrpMembers[$grpId] as $uid=>$name)
                        {
                            echo '<li>';
                            if($hasUserImg[$uid] == AppConstant::NUMERIC_ONE)
                            {

                            }
                            echo "$name | <a href='#'>Remove from group</a></li>";
                        }
                    }
                    echo '</ul>';
                 }
                echo '<h3>Students not in a group yet</h3>';
                if (count($page_unGrpStu) > AppConstant::NUMERIC_ZERO)
                {
                    echo "<form method='post' action=''>";
                    echo 'With selected, add to group';
                    echo '<select name="addtogrpid">';
                    echo "<option value='--new--'>New Group</option>";
                    foreach ($page_Grp as $grpId=>$grpName)
                    {
                        echo "<option value='.$grpId'>$grpName</option>";
                    }
                    echo '</select>';
                    echo '&nbsp;<input type="submit" value="Add" class=" btn btn-primary"/>';
                    echo '<ul class="nomark">';
                    foreach ($page_unGrpStu as $grpId=>$grpName)
                    {
                        echo "<li><input type='checkbox' style='text-align: center' name='stutoadd[]' value='.$uid' />";
                        if($hasUserImg[$uid] == AppConstant::NUMERIC_ONE)
                        {

                        }

                        echo "&nbsp; $grpName</li>";
                    }
                    echo '</ul>';
                    echo '</form>';
                    echo '<p>&nbsp;</p>';
                }
                else
                {
                    echo '<p>None</p>';
                }
                echo '</div>';
                    ?>
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
                        echo "<td><a href='manage-student-groups?cid=$course->id&grpSetId={$gs['id']}'>{$gs['name']}</a></td><td class=small>";
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