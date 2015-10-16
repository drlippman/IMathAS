<?php
use \app\components\AppUtility;
use \app\components\AppConstant;

if (isset($params['id'])) {
    $this->title = 'Edit Tool';
    $this->params['breadcrumbs'] = $this->title;
} else {
    $this->title = 'External Tools';
    $this->params['breadcrumbs'] = $this->title;
}
if (isset($params['id'])) {
    echo '<form method="post" action="external-tool?cid=' . $courseId . $ltfrom . '&amp;id=' . $params['id'] . '">';
}
?>
<div class="item-detail-header">
    <?php
    if ($isTeacher) {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id], 'page_title' => $this->title]);
        if (isset($params['ltfrom'])) {

            echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', $course->name, 'Modify Linked Text'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id, AppUtility::getHomeURL() . 'add-linked-text?cid=' . $course->id . '&amp;id=' . $params['ltfrom']], 'page_title' => $this->title]);
        }
    } else {
        if (isset($params['id'])) {
            echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin', 'External Tools'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index', AppUtility::getHomeURL() . 'admin/admin/external-tool?cid=' . $courseId . $ltfrom], 'page_title' => $this->title]);
        } else {
            echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]);
        }
    }
    ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>

    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <?php
    if (!(isset($teacherId)) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
        $err = AppConstant::NO_TEACHER_RIGHTS;
    } elseif (isset($params['cid']) && $params['cid'] == "admin" && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
        $err = AppConstant::REQUIRED_ADMIN_ACCESS;
    } elseif (!(isset($params['cid'])) && $myRights < AppConstant::GROUP_ADMIN_RIGHT) {
        $err = AppConstant::ACCESS_THROUGH_MENU;
    }

        if (isset($params['delete']))
        { ?>
            <input type="hidden" id="name-external-tool" value="<?php echo $extName;?> ">
         <?php
            echo '<br/>';
            echo '<div class="col-md-12">Are you SURE you want to delete the tool <b>'.$extName.'</b>?  Doing so will break ALL placements of this tool.</div><br/><br/>';
            echo '<form method="post" action="external-tool?cid='.$courseId.$ltfrom.'&amp;id='.$params['id'].'&amp;delete=true">';
            echo '<div class="col-md-2"><input type=submit value="Yes, I\'m Sure"></div>';
            echo '</form>';
        } else if (isset($params['id'])) {
?>
            <br/><div class="col-md-2"><?php AppUtility::t('Tool Name')?></div>
            <div class="col-md-10">
                <input class="form-control-1" required="Please fill out this field" maxlength="30" size="40" type="text" name="tname" value="<?php echo $name;?>" />
            </div>
            <br class="form" /><br/>

        <div class="col-md-2"><?php AppUtility::t('Launch URL') ?></div>
        <div class="col-md-10">
            <input type="url" class="form-control-1" size="40" name="url" pattern="https?://.+"
                   value="<?php echo $url; ?>"/>
        </div>
        <br class="form"/><br/>

        <div class="col-md-2"><?php AppUtility::t('Key') ?></div>
        <div class="col-md-10">
            <input type="text" class="form-control-1" size="40" maxlength="40" name="key" value="<?php echo $key; ?>"/>
        </div>
        <br class="form"/><br/>

        <div class="col-md-2"><?php AppUtility::t('Secret') ?></div>
        <div class="col-md-10">
            <input type="password" class="form-control-1" size="40" maxlength="40" name="secret"
                   value="<?php echo $secret; ?>"/>
        </div>
        <br class="form"/><br/>

        <div class="col-md-2"><?php AppUtility::t('Custom Parameters') ?></div>
        <div class="col-md-3">
            <textarea rows="2" cols="60" class="form-control" name="custom"><?php echo $custom; ?></textarea>
        </div>
        <br class="form"/><br/>

        <div class="col-md-2"><?php AppUtility::t('Privacy') ?></div>
        <div class="col-md-8">
            <input type="checkbox" name="privname"
                   value="1" <?php if (($privacy & AppConstant::NUMERIC_ONE) == AppConstant::NUMERIC_ONE) echo 'checked="checked"'; ?> /><span
                class="padding-left-five">Send name</span><br/>
            <input type="checkbox" name="privemail"
                   value="2" <?php if (($privacy & AppConstant::NUMERIC_TWO) == AppConstant::NUMERIC_TWO) echo 'checked="checked"'; ?> /><span
                class="padding-left-five">Send email</span>
        </div>

        <br class="form"/><br/>
        <?php
        if ($isAdmin) {
            echo '<div class="col-md-2">Scope of tool:</div>
            <div class="col-md-8">';
            echo '<input type="radio" name="scope" value="0" ' . (($grp == AppConstant::NUMERIC_ZERO) ? 'checked="checked"' : '') . '> System-wide<br/>';
            echo '<input type="radio" name="scope" value="1" ' . (($grp > AppConstant::NUMERIC_ZERO) ? 'checked="checked"' : '') . '> Group';
            echo '</div>
            <br class="form" /><br/>';
        } ?>
        <?php if (isset($params['id'])) { ?>
            <div class="header-btn col-sm-2 col-sm-offset-2 padding-bottom-thirty">
                <button class="btn btn-primary page-settings" type="submit" value="submit">
                    <i class="fa fa-share header-right-btn"></i> <?php AppUtility::t('Save'); ?> </button>
            </div>
        <?php } ?>
        </form>

    <?php } else {
        echo '<div class="col-md-12 padding-twenty">
            <div class="col-md-12 padding-top-twenty text-gray-background padding-left-thirty">';
        $str = "<p><b>";
        if ($isAdmin) {
            $str .= 'System and Group Tools';
        } else if ($isGrpAdmin) {
            $str .= 'Group Tools';
        } else {
            $str .= 'System and Group Tools';
        }
        $str .= '</b></p>';

        echo '<ul class="nomark margin-left-zero">';
        if (count($resultFirst) == AppConstant::NUMERIC_ZERO) {
            echo '<span class="col-md-12 padding-left-zero">'.AppUtility::t('No tools',false).'</span>';
        } else {
            foreach ($resultFirst as $key => $row) {
                echo '<li>' . $row['nm'];
                if ($isAdmin) {
                    if ($row['name'] == null) {
                        echo ' (System-wide)';
                    } else {
                        echo ' (for group ' . $row['name'] . ')';
                    }
                } ?>
                <input type="hidden" id="id" value="<?php echo $row['nm'] ?>">
                <?php
                echo ' <a href=' . AppUtility::getURLFromHome('admin', 'admin/external-tool?cid=' . $courseId . $ltfrom . '&amp;id=' . $row['id']) . '>Edit</a> ';
                $ExternalToolId = $row['id'];
                $cid = $courseId . $ltfrom;?>
                <input type="hidden" id="admin" value="<?php echo $cid ?>">
                | <a onclick=deleteExternalTool(<?php echo $ExternalToolId ?>) href='#'> <?php AppUtility::t('Delete');?> </a>
                <?php echo '</li>';
            }
        }
        echo '</ul>';
        echo '<p class="col-md-12 padding-left-zero"><a href="' . AppUtility::getURLFromHome('admin', 'admin/external-tool?cid=' . $courseId . '&amp;id=new') . '">'.AppUtility::t('Add a Tool',false).'</a></p>';
        echo '</div></div>';
    } ?>

</div>

<script>
    function deleteExternalTool(ExternalToolId) {
        var courseId = $('#admin').val();
        jQuerySubmit('delete-external-tool-ajax', {cid: courseId, ExternalToolId: ExternalToolId}, 'removeResponseSuccess');
    }

    function removeResponseSuccess(response) {
        response = JSON.parse(response);
        var name = response.data.nameOfExtTool;
        var cid = response.data.cid;
        var id = response.data.id;

        if (response.status == 0) {
            var message = '';
            message += 'Are you SURE you want to delete the tool <b>' + name + '</b>? <BR/>';
            message += 'Doing so will break ALL placements of this tool';
            var html = '<div><p>' + message + '</p></div>';
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Delete External Tool', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons: {
                    "Nevermind": function () {
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
                open: function () {
                    jQuery('.ui-widget-overlay').bind('click', function () {
                        jQuery('#dialog').dialog('close');
                    })
                }
            });
        }
    }

</script>