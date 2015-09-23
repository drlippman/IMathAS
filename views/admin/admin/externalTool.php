<?php
use \app\components\AppUtility;
if (isset($params['id']))
{
    $this->title = 'Edit Tool';
    $this->params['breadcrumbs'] = $this->title;
}
else{
    $this->title = 'External Tools';
    $this->params['breadcrumbs'] = $this->title;
}
if (isset($params['id'])) {
echo '<form method="post" action="external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$params['id'].'">';
}
?>
<div class="item-detail-header">
    <?php
    if($isTeacher){
        echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id], 'page_title' => $this->title]);
        if (isset($params['ltfrom'])) {

            echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name, 'Modify Linked Text'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id, AppUtility::getHomeURL().'add-linked-text?cid='.$course->id.'&amp;id='.$params['ltfrom']], 'page_title' => $this->title]);
        }
    } else {
        if (isset($params['id'])) {
            echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home','Admin', 'External Tools'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'admin/admin/index', AppUtility::getHomeURL().'admin/admin/external-tool?cid='.$courseId.$ltfrom], 'page_title' => $this->title]);
        } else{
            echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home','Admin'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'admin/admin/index'], 'page_title' => $this->title]);
        }
    }
    ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
        <?php if (isset($params['id'])) { ?>
        <div class="pull-left header-btn">
            <button class="btn btn-primary pull-right page-settings" type="submit" value="submit">
                <i class="fa fa-share header-right-btn"></i><?php echo 'Save'; ?></button>
    </div>
          <?php  } ?>

    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
<?php
    if (!(isset($teacherId)) && $myRights < 75) {
        $err = "You need to log in as a teacher to access this page";
    } elseif (isset($params['cid']) && $params['cid']=="admin" && $myRights < 75) {
        $err = "You need to log in as an admin to access this page";
    } elseif (!(isset($params['cid'])) && $myRights < 75) {
        $err = "Please access this page from the menu links only.";
    }

        if (isset($params['delete']))
        { ?>
           <div id="name-external-tool">
               <?php $extName = $nameOfExtTool;?>
           </div>
            <input type="hidden" id="name-external-tool" value="<?php echo $extName;?> ">
         <?php
            echo '<br/>';
            echo '<div class="col-lg-12">Are you SURE you want to delete the tool <b>'.$extName.'</b>?  Doing so will break ALL placements of this tool.</div><br/><br/>';
            echo '<form method="post" action="external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$params['id'].'&amp;delete=true">';
            echo '<div class="col-lg-2"><input type=submit value="Yes, I\'m Sure"></div>';
            echo '</form>';
        } else if (isset($params['id'])) {
?>
            <br/><div class="col-lg-2"><?php AppUtility::t('Tool Name')?></div>
            <div class="col-lg-10">
                <input class="form-control-1" required="Please fill out this field" maxlength="30" size="40" type="text" name="tname" value="<?php echo $name;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Launch URL')?></div>
            <div class="col-lg-10">
                <input type="url" class="form-control-1" size="40" name="url" pattern="https?://.+" value="<?php echo $url;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Key')?></div>
            <div class="col-lg-10">
                <input type="text" class="form-control-1" size="40" maxlength="40" name="key" value="<?php echo $key;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Secret')?></div>
            <div class="col-lg-10">
                <input type="password" class="form-control-1" size="40" maxlength="40" name="secret" value="<?php echo $secret;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Custom Parameters')?></div>
            <div class="col-lg-3">
                <textarea rows="2" cols="60" class="form-control" name="custom"><?php echo $custom;?></textarea>
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Privacy')?></div>
            <div class="col-lg-8">
                <input type="checkbox" name="privname" value="1" <?php if (($privacy&1)==1) echo 'checked="checked"';?> /><span class="padding-left-five">Send name</span><br/>
                <input type="checkbox" name="privemail" value="2" <?php if (($privacy&2)==2) echo 'checked="checked"';?> /><span class="padding-left-five">Send email</span>
            </div>
            <br class="form" /><br/>
            <?php
            if ($isAdmin) {
                echo '<div class="col-lg-2">Scope of tool:</div>
            <div class="col-lg-8">';
                echo '<input type="radio" name="scope" value="0" '. (($grp==0)?'checked="checked"':'') . '> System-wide<br/>';
                echo '<input type="radio" name="scope" value="1" '. (($grp>0)?'checked="checked"':'') . '> Group';
                echo '</div>
            <br class="form" /><br/>';
            }
            echo '</form>';

        } else {
            echo '<div class="col-md-12 padding-twenty">
            <div class="col-md-12 padding-top-twenty text-gray-background padding-left-thirty">';
            if ($isAdmin) {
                echo '<p><b>System and Group Tools</b></p>';
            } else if ($isGrpAdmin) {
                echo '<p><b>Group Tools</b></p>';
            } else {
                echo '<p><b>Course Tools</b></p>';
            }

            echo '<ul class="nomark margin-left-zero">';
            if (count($resultFirst) == 0) {
                echo '<span class="col-md-12 padding-left-zero">No tools</span>';
            } else {
                foreach($resultFirst as $key => $row)
                {
                    echo '<li>'.$row['nm'];
                    if ($isAdmin) {
                        if ($row['name'] == null) {
                            echo ' (System-wide)';
                        } else {
                            echo ' (for group '.$row['name'].')';
                        }
                    } ?>
                    <input type="hidden" id="id" value="<?php echo $row['nm']?>">
                 <?php
                    echo ' <a href='.AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$row['id']).'>Edit</a> ';
                    $ExternalToolId = $row['id'];
                    $cid = $courseId.$ltfrom;?>
                    <input type="hidden" id="admin" value="<?php echo $cid?>">
                   | <a onclick=deleteExternalTool(<?php echo $ExternalToolId?>) href='#'>Delete</a>
                  <?php echo '</li>';
                }
            }
            echo '</ul>';
            echo '<p class="col-md-12 padding-left-zero"><a href="'.AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId. '&amp;id=new').'">Add a Tool</a></p>';
            echo '</div></div>';
        } ?>

</div>

<script>
    function deleteExternalTool(ExternalToolId)
    {
        var courseId = $('#admin').val();
        jQuerySubmit('delete-external-tool-ajax', {cid: courseId,ExternalToolId:ExternalToolId},'removeResponseSuccess');
    }

    function removeResponseSuccess(response)
    {
        response = JSON.parse(response);
        var name = response.data.nameOfExtTool;
        var cid = response.data.cid;
        var id = response.data.id;

        if(response.status == 0)
        {
            var message ='';
            message+='Are you SURE you want to delete the tool <b>'+name+'</b>? <BR/>';
            message+='Doing so will break ALL placements of this tool';
            var html = '<div><p>'+message+'</p></div>';
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Delete External Tool', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons:
                {
                    "Nevermind": function ()
                    {
                        $(this).dialog('destroy').remove();
                        return false;
                    },
                    "Delete": function ()
                    {
                        window.location ="external-tool?cid="+cid+"&id="+id+"&delete=true";
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