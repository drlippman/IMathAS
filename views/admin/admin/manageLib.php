<?php
echo $page_AdminModeMsg;
?>
<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
    <input type=button value="Add New Library" onclick="window.location='managelibs.php?modify=new&cid=<?php echo $cid ?>'">
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
        printlist(0);
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