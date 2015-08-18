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
                    echo "<a href='#'>Delete</a>";
                    echo '</td></tr>';
        }
        echo '</body></table>';
    }
    ?>
    <p><button type="button" class="btn btn-primary1 btn-color" onclick="window.location.href='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&addgrpset=ask');?>'">Add new set of groups</button></p>
    <?php } ?>
</div>