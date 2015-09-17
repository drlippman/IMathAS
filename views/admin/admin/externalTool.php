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
               <?php echo $extName = $nameOfExtTool['name'];?>

           </div>
            <input type="hidden" id="name-external-tool" value="<?php echo $extName;?> ">

         <?php
            //echo '<p>Are you SURE you want to delete the tool <b>'.$extName.'</b>?  Doing so will break ALL placements of this tool.</p>';
            echo '<form method="post" action="external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$params['id'].'&amp;delete=true">';
            echo '<input type=submit value="Yes, I\'m Sure">';
            echo '<input type=button value="Nevermind" class="secondarybtn" onclick="window.location=\'externaltools.php?cid='.$cid.'\'">';
            echo '</form>';

        } else if (isset($params['id'])) {

    echo '<form method="post" action="external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$params['id'].'">';
?>
            <div class="col-lg-2"><?php AppUtility::t('Tool Name')?></div>
            <div class="col-lg-10">
                <input class="input-item-title" size="40" type="text" name="tname" value="<?php echo $name;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Launch URL')?></div>
            <div class="col-lg-10">
                <input type="text" size="40" name="url" value="<?php echo $url;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Key')?></div>
            <div class="col-lg-10">
                <input type="text" size="40" name="key" value="<?php echo $key;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Secret')?></div>
            <div class="col-lg-10">
                <input type="password" size="40" name="secret" value="<?php echo $secret;?>" />
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Custom Parameters')?></div>
            <div class="col-lg-8">
                <textarea rows="2" cols="30" name="custom"><?php echo $custom;?></textarea>
            </div>
            <br class="form" /><br/>

            <div class="col-lg-2"><?php AppUtility::t('Privacy')?></div>
            <div class="col-lg-8">
                <input type="checkbox" name="privname" value="1" <?php if (($privacy&1)==1) echo 'checked="checked"';?> /><?php AppUtility::t('Tool Name')?> Send name<br/>
                <input type="checkbox" name="privemail" value="2" <?php if (($privacy&2)==2) echo 'checked="checked"';?> /><?php AppUtility::t('Tool Name')?>Send email
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
            echo '<div class="submit"><input type="submit" value="Save"></div>';
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
                    echo '| <a href='.AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$row['id'].'&amp;delete=ask').'>Delete</a>';
                   echo '</li>';
                }
            }
            echo '</ul>';
            echo '<p class="col-md-12 padding-left-zero"><a href="'.AppUtility::getURLFromHome('admin', 'admin/external-tool?cid='.$courseId. '&amp;id=new').'">Add a Tool</a></p>';
            echo '</div></div>';
        } ?>

</div>

<script>
    $('.confirmation-required').click(function(e){
        var nm = $('#id').val();
        var html = '<div>Are you SURE you want to delete the tool '+nm+'</div>';
        var cancelUrl = $(this).attr('href');
        e.preventDefault();
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function () {
                    window.location = cancelUrl;
                    $(this).dialog("close");
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    });

</script>