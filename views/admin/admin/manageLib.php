<?php
use app\components\AppUtility;
use \app\components\AppConstant;

$this->title = $pagetitle;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin', 'Manage Library'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index', AppUtility::getHomeURL() . 'admin/admin/manage-lib?cid='.$cid], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
    <BR class=form><br>
<?php if ($overwriteBody == 1) {
    echo $body;
} else {
    ?>
    <div class="col-md-10 col-sm-12">
        <?php
        if (isset($_POST['remove'])) {
            ?>
            <?php echo $hasChildWarning; ?>
           <?php AppUtility::t('Are you SURE you want to delete these libraries?')?>
            <form method=post action="manage-lib?cid=<?php echo $cid ?>&confirmed=true">
                <p>
                    <input type=radio name="delq" value="no" CHECKED>
                    Move questions in library to Unassigned<br>
                    <input type=radio name="delq" value="yes" >
                    Also delete questions in library
                </p>
                <input type=hidden name=remove value="<?php echo $rlist ?>">
                <p>
                    <input type=submit value="Really Delete">
                    <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-lib?cid=<?php echo $cid ?>'">
                </p>
            </form>
        <?php
        } else if (isset($_POST['transfer'])) {
            ?>
            <input type="hidden" class="transfer-post" value="<?php echo $tlist ?>" name="transfer">
            <form method=post action="manage-lib?cid=<?php echo $cid ?>">
                <input type=hidden name=transfer value="<?php echo $tlist ?>">
                Transfer library ownership to:
                <?php AppUtility::writeHtmlSelect ("newowner",$page_newOwnerListVal,$page_newOwnerListLabel,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
                <p>
                    <input type=submit value="Transfer">
                    <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-lib?cid=<?php echo $cid ?>'">
                </p>
            </form>
        <?php
        } else if (isset($_POST['chgrights'])) {
            ?>
            <form method=post action="manage-lib?cid=<?php echo $cid ?>">
                <input type=hidden name=chgrights value="<?php echo $tlist ?>">
                <div class="col-md-10 col-sm-12 padding-left-zero"><div class="col-md-3 padding-left-zero"><?php AppUtility::t('Library use rights')?> </div>
		<div class="col-md-4 col-sm-4 padding-left-zero">
			<?php AppUtility::writeHtmlSelect ("newrights",$page_libRightsVal,$page_libRightsLabel,$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</div></div><br class=form><br/>
                <div class="col-md-10 col-sm-12 padding-left-zero">
                    <div class="col-md-2 col-sm-2 padding-left-zero"><input type=submit value="Change Rights"></div>
                    <div class="col-md-3 col-sm-3 padding-left-zero"><input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'"></div>
                </div>
            </form>
        <?php
        }else if (isset($_POST['setparent'])) {
            ?>
            <form method=post action="manage-lib?cid=<?php echo $cid ?>">
                <input type=hidden name=setparent value="<?php echo $tlist ?>">
                <div class="col-md-10 col-sm-12 padding-left-zero"><div class="col-md-3 col-sm-4 padding-left-zero"><?php AppUtility::t('New Parent Library')?></div>
                    <div class="col-md-3 col-sm-4 padding-left-zero">
                        <span id="libnames"></span>
                        <input type=hidden name="libs" id="libs"  value="<?php echo $parent ?>">
                        <input type=button value="Select Library" onClick="libselect()">
                    </div></div><br class=form>
                <p>
                    <input type=submit value="Set Parent">
                    <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-lib?cid=<?php echo $cid ?>'">
                </p>
            </form>
        <?php
        }
        else if (isset($remove)) {
        if ($libcnt>0) {
        ?>
        <?php AppUtility::t('The library selected has children libraries.  A parent library cannot be removed until all
        children libraries are removed.')?>
        <p><a href="manage-lib?cid=<?php echo $cid ?>"><?php AppUtility::t('Back to Library Manager')?></a>
            <?php
            } else {
            ?>
        <form method=post action="manage-lib?cid=<?php echo $cid ?>&remove=<?php echo $remove ?>&confirmed=true">
            <?php AppUtility::t('Are you SURE you want to delete this Library?')?>
            <p>
                <input type=radio name="delq" value="no" CHECKED><?php AppUtility::t('Move questions in library to Unassigned')?><br>
                <input type=radio name="delq" value="yes" ><?php AppUtility::t('Also delete questions in library')?>
            </p>
            <p>
                <input type=submit value="Really Delete">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-lib?cid=<?php echo $cid ?>'">
            </p>
        </form>
        <?php
        }
        } else if (isset($transfer))
        {
            ?>
        <form method=post action="manage-lib?cid=<?php echo $cid ?>&transfer=<?php echo $transfer ?>">
            <input type=hidden name=transfer value="<?php echo $tlist ?>">
            Transfer library ownership to:
            <?php AppUtility::writeHtmlSelect ("newowner",$page_newOwnerListVal,$page_newOwnerListLabel,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
            <p>
                <input type=submit value="Transfer">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-lib?cid=<?php echo $cid ?>'">
            </p>
        </form>
        <?php
        } else if (isset($_GET['modify'])) {
    ?>
    <form method=post action="manage-lib?cid=<?php echo $cid ?>&modify=<?php echo $_GET['modify'] ?>">
        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Library Name')?></div>
        <div class="col-md-10 col-sm-12"><input type=text class="form-control-1" name="name" maxlength="60" required="Please fill out this field." value="<?php echo $name ?>" size=20></div><br class=form><br>
        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Rights')?> </div>
		<div class="col-md-4 col-sm-4">
			<?php AppUtility::writeHtmlSelect ("rights",$page_libRightsVal,$page_libRightsLabel,$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</div><br class=form><br>

        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Sort order')?></div>
		<div class="col-md-10 col-sm-12">
			<input type="radio" name="sortorder" value="0" <?php AppUtility::writeHtmlChecked($sortorder,0); ?> /><span class="padding-left-five"><?php AppUtility::t('Creation date')?></span><br/>
			<input type="radio" name="sortorder" value="1" <?php AppUtility::writeHtmlChecked($sortorder,1); ?> /><span class="padding-left-five"><?php AppUtility::t('Alphabetical')?></span>
		</div><br class=form><br>

        <div class="col-md-2 col-sm-2"><?php AppUtility::t('Parent Library')?></div>
		<div class="col-md-10 col-sm-12">
			<span id="libnames"><?php echo $lnames ?></span>
			<input type=hidden name="libs" id="libs"  value="<?php echo $parent ?>">
			<input type=button value="Select Library" onClick="libselect()">
		</div><br class=form><br>
        <div class="submit col-md-10 col-sm-12">
           <div class="col-md-2 col-sm-2 padding-left-zero"> <input type=submit value="Save Changes"></div>
           <div class="col-md-6 col-sm-6"><input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-lib?cid=<?php echo $cid ?>'"></div>
        </div><br/>
    </form><br/>

    <i>Note</i>: <?php AppUtility::t('Creating a library with rights less restrictive than the parent library will force
    the parent library to match the rights of the child library.')?>
<?php
} else { //DEFAULT DISPLAY
        echo $page_AdminModeMsg;
  ?></div><BR class=form><br>
<form method=post action="manage-lib?cid=<?php echo $cid ?>">
    <div class="col-md-12 col-sm-12"><input type=button value="Add New Library" onclick="window.location='manage-lib?modify=new&cid=<?php echo $cid ?>'"></div>
</form>

<form id="qform" method=post action="manage-lib?cid=<?php echo $cid ?>">
		<div>
<div class="col-md-12 col-sm-12"><?php AppUtility::t('Check')?> <a href="#" onclick="return chkAllNone('qform','nchecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','nchecked[]',false)">None</a>
With Selected: <input type=submit name="transfer" value="Transfer" title="Transfer library ownership">
<input type=submit name="remove" value="Delete" title="Delete library">
<input type=submit name="setparent" value="Change Parent" title="Change the parent library">
<input type=submit name="chgrights" value="Change Rights" title="Change library use rights">
<?php echo $page_appliesToMsg ?>

</div>
            </div>
<div class="col-md-12 col-sm-12">
    Root
<ul class=base>
    <?php
    $count = 0;
    if (isset($ltlibs[0])) {
        $newPrintlist = new AppUtility();
        $newPrintlist->printlist(0,$names,$ltlibs,$count,$qcount,$cid,$rights,$sortorder,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin);
    }
    ?>
</ul>
</div>
<div class="col-md-12 col-sm-12">
    <b><?php AppUtility::t('Color Code')?></b><br/>
    <span class=r8><?php AppUtility::t('Open to all')?></span><br/>
    <span class=r4><?php AppUtility::t('Closed')?></span><br/>
    <span class=r5><?php AppUtility::t('Open to group, closed to others')?></span><br/>
    <span class=r2><?php AppUtility::t('Open to group, private to others')?></span><br/>
    <span class=r1><?php AppUtility::t('Closed to group, private to others')?></span><br/>
    <span class=r0><?php AppUtility::t('Private')?></span>
</div>

</form><BR class="form"><BR class="form">
<?php
}?>
    </div>

<script>
    var curlibs = '<?php echo $parent1 ?>';
    function libselect()
    {
        window.open('../../question/question/library-tree?cid=<?php echo $cid ?>&libtree=popup&select=parent&selectrights=1&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
    }
    function setlib(libs) {
        document.getElementById("libs").value = libs;
        curlibs = libs;
    }
    function setlibnames(libn) {
        document.getElementById("libnames").innerHTML = libn;
    }
    function deleteSelected()
    {
        var courseId = $('#admin').val();
        var html ='<p><input type=radio name="delq" value="no" CHECKED>Move questions in library to Unassigned<br><input type=radio name="delq" value="yes" >Also delete questions in library</p>' ;
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
                    $(this).dialog("close");
                    window.location = "manage-lib?cid=admin&confirmed=true";
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
</script>

<?php } ?>
