<?php
use app\components\AppUtility;

$this->title = $pagetitle;
$this->params['breadcrumbs'][] = $this->title;
//if ($isadmin || $isgrpadmin) {
//    $curBreadcrumb .= " &gt; <a href=\"../admin/admin.php\">Admin</a> ";
//}
//if ($cid!=0) {
//    $curBreadcrumb .= " &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
//}
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
    <div class="col-lg-10">
        <?php
        if (isset($_POST['remove'])) {
            ?>
            <?php echo $hasChildWarning; ?>
            Are you SURE you want to delete these libraries?
            <form method=post action="managelibs.php?cid=<?php echo $cid ?>&confirmed=true">
                <p>
                    <input type=radio name="delq" value="no" CHECKED>
                    Move questions in library to Unassigned<br>
                    <input type=radio name="delq" value="yes" >
                    Also delete questions in library
                </p>
                <input type=hidden name=remove value="<?php echo $rlist ?>">
                <p>
                    <input type=submit value="Really Delete">
                    <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
                </p>
            </form>
        <?php
        } else if (isset($_POST['transfer'])) {
            ?>
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
            <form method=post action="managelibs.php?cid=<?php echo $cid ?>">
                <input type=hidden name=chgrights value="<?php echo $tlist ?>">
                <span class=form>Library use rights: </span>
		<span class=formright>
			<?php writeHtmlSelect ("newrights",$page_libRights['val'],$page_libRights['label'],$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</span><br class=form>
                <p>
                    <input type=submit value="Change Rights">
                    <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
                </p>
            </form>
        <?php
        }else if (isset($_POST['setparent'])) {
            ?>
            <form method=post action="managelibs.php?cid=<?php echo $cid ?>">
                <input type=hidden name=setparent value="<?php echo $tlist ?>">
                <span class=form>New Parent Library: </span>
		<span class=formright>
			<span id="libnames"></span>
			<input type=hidden name="libs" id="libs"  value="<?php echo $parent ?>">
			<input type=button value="Select Library" onClick="libselect()">
		</span><br class=form>

                <p>
                    <input type=submit value="Set Parent">
                    <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
                </p>
            </form>
        <?php
        }
        else if (isset($_GET['remove'])) {
        if ($libcnt>0) {
        ?>
        The library selected has children libraries.  A parent library cannot be removed until all
        children libraries are removed.
        <p><a href="managelibs.php?cid=<?php echo $cid ?>">Back to Library Manager</a>
            <?php
            } else {
            ?>

        <form method=post action="managelibs.php?cid=<?php echo $cid ?>&remove=<?php echo $_GET['remove'] ?>&confirmed=true">
            Are you SURE you want to delete this Library?
            <p>
                <input type=radio name="delq" value="no" CHECKED>Move questions in library to Unassigned<br>
                <input type=radio name="delq" value="yes" >Also delete questions in library
            </p>
            <p>
                <input type=submit value="Really Delete">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
            </p>
        </form>
        <?php
        }
        } else if (isset($_GET['transfer'])) {
//            print_r('inside'); die;
            ?>
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
        } else if (isset($_GET['modify'])) {
    ?>
    <form method=post action="manage-lib?cid=<?php echo $cid ?>&modify=<?php echo $_GET['modify'] ?>">
        <div class=col-lg-2><?php AppUtility::t('Library Name')?></div>
        <div class=col-lg-10><input type=text name="name" value="<?php echo $name ?>" size=20></div><br class=form><br>
        <div class=col-lg-2><?php AppUtility::t('Rights')?> </div>
		<div class=col-lg-10>
			<?php AppUtility::writeHtmlSelect ("rights",$page_libRightsVal,$page_libRightsLabel,$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</div><br class=form><br>

        <div class=col-lg-2><?php AppUtility::t('Sort order')?></div>
		<div class=col-lg-10>
			<input type="radio" name="sortorder" value="0" <?php AppUtility::writeHtmlChecked($sortorder,0); ?> />Creation date<br/>
			<input type="radio" name="sortorder" value="1" <?php AppUtility::writeHtmlChecked($sortorder,1); ?> />Alphabetical
		</div><br class=form><br>

        <div class=col-lg-2><?php AppUtility::t('Parent Library')?></div>
		<div class=col-lg-10>
			<span id="libnames"><?php echo $lnames ?></span>
			<input type=hidden name="libs" id="libs"  value="<?php echo $parent ?>">
			<input type=button value="Select Library" onClick="libselect()">
		</div><br class=form><br>
        <div class=submit>
            <input type=submit value="Save Changes">
            <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-lib?cid=<?php echo $cid ?>'">
        </div>
    </form>

    <i>Note</i>: Creating a library with rights less restrictive than the parent library will force
    the parent library to match the rights of the child library.
<?php
} else { //DEFAULT DISPLAY
        echo $page_AdminModeMsg;
  ?></div><BR class=form><br>
<form method=post action="manage-lib?cid=<?php echo $cid ?>">
    <input type=button value="Add New Library" onclick="window.location='manage-lib?modify=new&cid=<?php echo $cid ?>'">
</form>

<form id="qform" method=post action="manage-lib?cid=<?php echo $cid ?>">
		<div>
Check: <a href="#" onclick="return chkAllNone('qform','nchecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','nchecked[]',false)">None</a>
With Selected: <input type=submit name="transfer" value="Transfer" title="Transfer library ownership">
<input type=submit name="remove" value="Delete" title="Delete library">
<input type=submit name="setparent" value="Change Parent" title="Change the parent library">
<input type=submit name="chgrights" value="Change Rights" title="Change library use rights">
<?php echo $page_appliesToMsg ?>

</div>
<p>
    Root
<ul class=base>
    <?php
    $count = 0;
    if (isset($ltlibs[0])) {
        AppUtility::printlist(0,$names,$ltlibs,$count,$qcount,$cid,$rights,$sortorder,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin);
    }
    ?>
</ul>
</p>
<p>
    <b>Color Code</b><br/>
    <span class=r8>Open to all</span><br/>
    <span class=r4>Closed</span><br/>
    <span class=r5>Open to group, closed to others</span><br/>
    <span class=r2>Open to group, private to others</span><br/>
    <span class=r1>Closed to group, private to others</span><br/>
    <span class=r0>Private</span>
</p>

</form>
<?php
}?>
    </div>

<script>
    var curlibs = '<?php echo 0 ?>';
    function libselect() {
        window.open('../../question/question/library-tree?cid=<?php echo $cid ?>&libtree=popup&select=parent&selectrights=1&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
    }
    function setlib(libs) {
        document.getElementById("libs").value = libs;
        curlibs = libs;
    }
    function setlibnames(libn) {
        document.getElementById("libnames").innerHTML = libn;
    }
</script>

<?php } ?>