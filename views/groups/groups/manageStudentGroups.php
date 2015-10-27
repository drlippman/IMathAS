<?php
use app\components\AppUtility;
use app\components\AppConstant;
if(isset($grpSetId))
{
    $this->title = $grpSetName;
}elseif($addGrpSet)
{
    $this->title = AppUtility::t('Add Group Set', false);
}elseif($addGrp)
{
    $this->title = AppUtility::t('Add Group ', false);
}elseif($renameGrp)
{
    $this->title = AppUtility::t('Rename Group', false);
}elseif($renameGrpSet)
{
    $this->title = AppUtility::t('Rename Group Set', false);
}
elseif($deleteGrpSet)
{
    $this->title = AppUtility::t('Delete Group Set', false);
}
elseif($deleteGrp)
{
    $this->title = AppUtility::t('Delete Group', false);
}
else
{
    $this->title = AppUtility::t('Manage Student Groups', false);
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php if(isset($grpSetId) || isset($addGrpSet) || isset($addGrp) || isset($renameGrp) || isset($renameGrpSet) || isset($deleteGrpSet)){?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Manage Student Groups', false)], 'link_url' => [AppUtility::getHomeURL()  . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)]]); ?>
    <?php }else{?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL()  . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id]]); ?>
    <?php }?>
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
<div style="padding: 20px">
    <div class="group-background-div" style="min-height: 380px;">
        <br>
        <div class="align-groups">
        <?php if(isset($addGrpSet)){?>
            <h4>Add new set of student groups</h4>
            <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&addgrpset=true')?>">
            <p>New group set name: <input class="form-control-grp" name="grpsetname" type="text" maxlength="60" size="40"/></p>
            <div class="margin-left-groups">
            <p><input type="submit" value="Create" />
            <input type=button value="Nevermind" class="#" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)?>'" /></p></div>
            </form>
        <?php }elseif(isset($renameGrpSet)){?>
             <h4>Rename student group set</h4>
             <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&renameGrpSet='.$renameGrpSet)?>">
             <p>New group set name: <input class="form-control-grp" name="grpsetname" type="text" value="<?php echo $grpSetName['name'];?>"  maxlength="60"/></p>
             <p><input style="margin-left: 20.2%" type="submit" value="Rename" />
             <input type=button value="Nevermind" class="#" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id)?>'" /></p>
             </form>
        <?php }elseif(isset($deleteGrpSet)){?>
        <?php }elseif(isset($renameGrp)){?>
            <h4>Rename student group </h4>
                <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&renameGrp='.$renameGrp)?>">
                <p>New group name:<input class="form-control-grp" name="grpname" type="text" value="<?php echo $currGrpName;?>"/></p>
                <p><input type="submit" value="Rename" />
                    <input type=button value="Nevermind" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId'.$grpSetId)?>'" /></p>
            </form>
        <?php }elseif(isset($deleteGrp)){?>
                <form id="deleteGrp" method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&deleteGrp='.$deleteGrp.'&confirm=true')?>" >
                <p><input type="submit" value="Yes, Delete">
                <input type=button value="Nevermind" class="" onClick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId)?>'" /></p>
                </form>
        <?php }elseif(isset($addGrp)){?>
            <h4>Add new student group</h4>
            <form method="post" action="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&addGrp=true')?>">
             <?php if (isset($stuList))
             {
                echo "<input type='hidden' name='stutoadd' value=$stuList />";
             }?>
                <p>New group name: <input class="form-control-grp" name="grpname" type="text" size="40" /></p>
                <div class="margin-left-groups-set">
                <p><input type="submit" value="Create" />
                <input type=button value='Nevermind' class='' onClick='window.location="<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId)?>"' /></p>
                </div>
                </form>
        <?php }elseif(isset($remove) && isset($grpId)){?>
        <?php }elseif(isset($removeAll)){?>
        <?php }elseif(isset($grpSetId)){?>
        <input type="hidden" id="grpSetId"  value="<?php echo $grpSetId;?>">
            <h3>Managing groups in set <?php echo $grpSetName?></h3>
            <div id="myTable">
                <p><button type="button" onclick="window.location='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&addGrp=true')?>'">Add New Group</button>
                    <?php if(array_sum($hasUserImg)){
                        echo ' <button type="button" onclick="rotatepics(this)" >'.'View Pictures'.'</button><br/>';
                    }?>
                </p>
                <?php if (count($page_Grp)==0)
                {
                    echo '<p>No student groups have been created yet</p>';
                }foreach($page_Grp as $grpId=>$grpName)
                {
                    echo "<br><b>Group: $grpName</b>&nbsp;&nbsp;";
                    echo "[<a href='manage-student-groups?cid=$course->id&grpSetId={$grpSetId}&renameGrp={$grpId}'>Rename</a>] ";
                    echo "[<a href='javascript:deleteGrp($course->id,$grpId,$grpSetId)'>Delete</a>]";
                    if (count($page_GrpMembers[$grpId]) > 0)
                    {
                        echo "[<a href='javascript:removeAllMember($course->id,$grpId,$grpSetId)'>Remove all members</a>]";
                    }
                    echo '<ul>';
                    if (count($page_GrpMembers[$grpId]) == 0)
                    {
                        echo '<br><li>No group members</li>';
                    }else
                    {
                        foreach($page_GrpMembers[$grpId] as $uid=>$name)
                        {
                            echo '<br><li>';
                            $imageUrl = $uid . '' . ".jpg";
                            if($hasUserImg[$uid] == AppConstant::NUMERIC_ONE)
                            {
                                if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true)
                                {
                                    echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\"  />";
                                }
                                else{?>
                                    <img class="circular-profile-image" id="img<?php echo $imgCount ?>"
                                         src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl ?>">
                               <?php }

                            }
                            echo "$name | <a href='javascript:remove($course->id,$grpId,$grpSetId,$uid)'>Remove from group</a></li>";
                        }
                    }
                    echo '</ul>';
                 }
                echo '<h3>Students not in a group yet</h3>';
                if (count($page_unGrpStu) > AppConstant::NUMERIC_ZERO)
                {?>
                    <form method='post' action='<?php echo AppUtility::getURLFromHome('groups','groups/manage-student-groups?cid='.$course->id.'&grpSetId='.$grpSetId.'&addstutogrp=true')?>'>
                   <div class="pull-left select-text-margin with-selected"><?php echo 'With selected, add to group';?></div>
                    <?php echo '<select class="form-control-1 " name="addtogrpid">';
                    echo "<option value='--new--'>New Group</option>";
                    foreach ($page_Grp as $grpId=>$grpName)
                    {
                        echo "<option value=$grpId>$grpName</option>";
                    }
                    echo '</select>';
                    echo '<ul class="nomark stu-list">';
                    foreach ($page_unGrpStu as $grpId=>$grpName)
                    {
                        echo "<li><input type='checkbox' style='text-align: center' name='stutoadd[]' value=$grpId />";
                        if($hasUserImg[$grpId] == AppConstant::NUMERIC_ONE)
                        {
                            $imageUrl = $grpId . '' . ".jpg";

                           if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true)
                            {
                                echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\"  />";
                            }
                            else
                            {?>
                                <img class="circular-profile-image" id="img" src="<?php echo AppUtility::getAssetURL() ?>Uploads/<?php echo $imageUrl ?>">
                      <?php }
                        }

                        echo "&nbsp;$grpName</li>";
                    }
                    echo '<br>&nbsp;<input type="submit" value="Add" class="add-grp-btn btn btn-primary"/>';
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
                        echo "<div class='col-sm-12'><td class='col-sm-8'><a href='manage-student-groups?cid=$course->id&grpSetId={$gs['id']}'>{$gs['name']}</a></td><td class='small col-sm-4'></div>";
                        echo "<a href='manage-student-groups?cid=$course->id&renameGrpSet={$gs['id']}'>Rename</a> | ";
                        echo "<a href='manage-student-groups?cid=$course->id&copyGrpSet={$gs['id']}'>Copy</a> | ";
                        $deleteGrpSet = $gs['id'];
                        $nameGrpSet = $gs['id'];
                    echo "<a href='javascript:deleteGrpSet($course->id,$deleteGrpSet,$nameGrpSet)'>Delete</a>";
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
</div>

    <script type="text/javascript">
        $(document).ready(function()
        {
                picshow(0);
        });
        var picsize = 0;
        function rotatepics(el)
        {
            picsize = (picsize+1)%3;
            if (picsize==0) {
                $(el).html("<?php echo AppUtility::t('View Pictures'); ?>");
            } else if (picsize==1) {
                $(el).html("<?php echo AppUtility::t('View Big Pictures'); ?>");
            } else {
                $(el).html("<?php echo AppUtility::t('Hide Pictures'); ?>");
            }
            picshow(picsize);
        }
        function picshow(size)
        {
            if (size == 0)
            {
                els = document.getElementById("myTable").getElementsByTagName("img");

                for (var i = 0; i < els.length; i++) {
                    els[i].style.display = "none";
                }
            } else
            {
                els = document.getElementById("myTable").getElementsByTagName("img");

                for (var i = 0; i < els.length; i++) {
                    els[i].style.display = "inline";
                    if (size == 2) {
                        els[i].style.width = "100px";
                        els[i].style.height = "100px"
                    }
                    if (size == 1) {
                        els[i].style.width = "50px";
                        els[i].style.height = "50px";
                    }
                }
            }
        }

        function deleteGrpSet(cid,deleteId,name)
        {
            jQuerySubmit('delete-grp-set-ajax', {cid: cid, deleteId: deleteId},'deleteResponseSuccess');
        }

        function deleteResponseSuccess(response)
        {
            response = JSON.parse(response);
            var deleteGrpName = response.data.deleteGrpName;
            var cid = response.data.cid;
            var used = response.data.used;
            var deleteGrpSet = response.data.deleteGrpSet;
            if(response.status == 0)
            {
                var message ='';
                message+='Are you SURE you want to delete the set of student groups <b>'+deleteGrpName+'</b> and all the groups contained within it?';
                if(used !='')
                {
                    message+='This set of groups is currently used in the assessments, wikis, and/or forums below.  These items will be set to non-group if this group set is deleted';
                    message+=''+used+'';
                }
                else
                {
                    message+='<p>This set of groups is not currently being used</p>';
                }
                var html = '<div><p>'+message+'</p></div>';
                $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                    modal: true, title: 'Delete student group set', zIndex: 10000, autoOpen: true,
                    width: 'auto', resizable: false,
                    closeText: "hide",
                    buttons:
                    {
                        "Nevermind": function ()
                        {
                            $(this).dialog('destroy').remove();
                            return false;
                        },
                        "Yes,Delete": function ()
                        {
                            window.location ="manage-student-groups?cid="+cid+"&deleteGrpSet="+deleteGrpSet+"&confirm=true";
                        }
                    },
                    close: function (event, ui) {
                        $(this).remove();
                    },
                    open: function(){
                        jQuery('.ui-widget-overlay').bind('click',function(){
                            jQuery('#dialog').dialog('close');
                        })
                    }
                });
            }
        }
        function deleteGrp(cid,deleteId,grpId)
        {

            jQuerySubmit('delete-grp-ajax', {cid: cid, deleteId: deleteId, grpId:grpId},'deleteGrpResponseSuccess');
        }

        function deleteGrpResponseSuccess(response)
        {
            response = JSON.parse(response);
            var currGrpNameToDlt = response.data.currGrpNameToDlt;
            var currGrpSetNameToDlt = response.data.currGrpSetNameToDlt;
            var cid = response.data.cid;
            var deleteGrp = response.data.deleteGrp;
            var grpSetId = response.data.grpSetId;
            if(response.status == 0)
            {
                var message ='';
                message+='Are you SURE you want to delete the student group<b>'+currGrpNameToDlt+'</b>?';
                message+='<p>Any wiki page content for this group will be deleted.</p>';
                message+='<span id="post-type-radio-list"><input type="radio" name="delpost" value="1" checked="checked"/> Delete group forum posts&nbsp;';
                message+='<input type="radio" name="delpost" value="0" /> Make group forum posts non-group-specific posts</span>';
                var html = '<div>'+message+'</div>';
                $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                    modal: true, title: 'Delete student group', zIndex: 10000, autoOpen: true,
                    width: 'auto', resizable: false,
                    closeText: "hide",
                    buttons:
                    {
                        "Nevermind": function ()
                        {
                            $(this).dialog('destroy').remove();
                            return false;
                        },
                        "Yes,Delete": function ()
                        {
                            var sel = $("#post-type-radio-list input[type='radio']:checked");
                            var selected = sel.val();
                            jQuerySubmit('delete-on-confirmation-ajax', {cid: cid, deleteGrp: deleteGrp, grpSetId: grpSetId,selected:selected},'responseSuccess');
                            $(this).dialog('destroy').remove();
                            return true;
                        }
                    },
                    close: function (event, ui) {
                        $(this).remove();
                    },
                    open: function(){
                        jQuery('.ui-widget-overlay').bind('click',function(){
                            jQuery('#dialog').dialog('close');
                        })
                    }
                });
            }

        }
        function responseSuccess(response)
        {
            response = JSON.parse(response);

            var cid = response.data.cid;
            var grpSetId = response.data.grpSetId;
            if(response.status == 0)
            {
                window.location ="manage-student-groups?cid="+cid+"&grpSetId="+grpSetId;
            }
        }

        function removeAllMember(cid,removeId,grpSetId)
        {
            jQuerySubmit('remove-all-ajax', {cid: cid, removeId: removeId,grpSetId:grpSetId},'removeResponseSuccess');
        }

        function removeResponseSuccess(response)
        {
            response = JSON.parse(response);
            var Stu_GrpName = response.data.Stu_GrpName;
            var cid = response.data.cid;
            var removeAll = response.data.removeAll;
            var grpSetId = response.data.grpSetId;
            if(response.status == 0)
            {
                var message ='';
                message+='Are you SURE you want to remove <b>ALL</b> members of the student group <b>'+Stu_GrpName+'</b>?';
                var html = '<div><p>'+message+'</p></div>';
                $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                    modal: true, title: 'Remove ALL group members', zIndex: 10000, autoOpen: true,
                    width: 'auto', resizable: false,
                    closeText: "hide",
                    buttons:
                    {
                        "Nevermind": function ()
                        {
                            $(this).dialog('destroy').remove();
                            return false;
                        },
                        "Yes, Remove": function ()
                        {
                            window.location ="manage-student-groups?cid="+cid+"&grpSetId="+grpSetId+"&removeall="+removeAll+"&confirm=true";
                        }
                    },
                    close: function (event, ui) {
                        $(this).remove();
                    },
                    open: function(){
                        jQuery('.ui-widget-overlay').bind('click',function(){
                            jQuery('#dialog').dialog('close');
                        })
                    }
                });
            }

        }
        function remove(cid,grpId,grpSetId,removeId)
        {
            jQuerySubmit('remove-ajax', {cid: cid, removeId: removeId,grpSetId:grpSetId,grpId:grpId},'removeResponseSuccessAjax');
        }

        function removeResponseSuccessAjax(response)
        {
            response = JSON.parse(response);
            var stuNameToBeRemoved = response.data.stuNameToBeRemoved;
            var cid = response.data.cid;
            var grpId = response.data.grpId;
            var remove = response.data.remove;
            var grpSetId = response.data.grpSetId;
            var Stu_GrpSetName = response.data.Stu_GrpSetName;
            var Stu_GrpName = response.data.Stu_GrpName;
            if(response.status == 0)
            {
                var message ='';
                message+='Are you SURE you want to remove <b>'+stuNameToBeRemoved+'</b> from the student group <b>'+Stu_GrpName+'</b>?';
                var html = '<div><p>'+message+'</p></div>';
                $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                    modal: true, title: 'Remove group member', zIndex: 10000, autoOpen: true,
                    width: 'auto', resizable: false,
                    closeText: "hide",
                    buttons:
                    {
                        "Nevermind": function ()
                        {
                            $(this).dialog('destroy').remove();
                            return false;
                        },
                        "Yes, Remove": function ()
                        {
                            window.location ="manage-student-groups?cid="+cid+"&grpSetId="+grpSetId+"&remove="+remove+"&grpId="+grpId+"&confirm=true";
                        }
                    },
                    close: function (event, ui) {
                        $(this).remove();
                    },
                    open: function(){
                        jQuery('.ui-widget-overlay').bind('click',function(){
                            jQuery('#dialog').dialog('close');
                        })
                    }
                });
            }

        }
    </script>