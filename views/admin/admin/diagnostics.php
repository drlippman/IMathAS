<?php
use \app\components\AppUtility;
?>
<?php 	if (isset($_GET['step']) && $_GET['step']==2) {  //STEP 2 DISPLAY
?>
<div id="headerdiagsetup" class="pagetitle"><h2>Diagnostic Setup</h2></div>
<h4>Second-level Selector - extra information</h4>
<form method=post action="diagnostics?step=3">

<input type=hidden name="sel1list" value="<?php echo $sel1list ?>"/>
<input type=hidden name="iplist" value="<?php echo $iplist ?>"/>
<input type=hidden name="pwlist" value="<?php echo $pwlist ?>"/>
<input type=hidden name="cid" value="<?php echo $params['cid'] ?>"/>
<input type=hidden name="term" value="<?php echo $params['term'] ?>"/>
<input type=hidden name="sel1name" value="<?php echo $params['sel'] ?>"/>
<input type=hidden name="diagname" value="<?php echo $params['diagname'] ?>"/>
<input type=hidden name="idprompt" value="<?php echo $params['idprompt'] ?>"/>
<input type=hidden name="entryformat" value="<?php echo $entryformat; ?>"/>
<input type=hidden name="public" value="<?php echo $public ?>"/>
<input type=hidden name="reentrytime" value="<?php echo $params['reentrytime'] ?>"/>
<input type=hidden name="id" value="<?php echo $page_updateId ?>" >
<p>Second-level selector name:
    <input type=text name=sel2name value="<?php echo $sel2name ?>"/>
    'Select your ______'</p>
<p>For each of the first-level selectors, select which assessment should be delivered,
    and provide options for the second-level selector</p>
<p>Alphabetize selectors on submit? <input type="checkbox" name="alpha" value="1" /></p>
<?php
foreach($sel1 as $k=>$s1) {
    ?>
    <div>
        <p><b><?php echo $s1 ?></b>.  Deliver assessment:
            <?php
                AppUtility::writeHtmlSelect ($page_selectName[$k],$page_selectValList[$k],$page_selectLabelList[$k],$page_selectedOption[$k]);
            ?>
            <br/>
            Force regen on reentry (if allowed)? <input type=checkbox name="reg<?php echo $k; ?>" value="1" <?php
             if (($forceregen & (1<<$k)) > 0) {echo 'checked="checked"';}?> />
            <?php
            if ($k==0 && count($sel1)>1) {
                echo '<br/>Use these second-level selectors for all first-level selectors?';
                echo '<input type=checkbox name="useoneforall" value="1" onclick="toggleonefor(this)" />';
            }
            ?>
        </p>

        <div class="sel2">Add selector value:
            <input type=text id="in<?php echo $k ?>"  onkeypress="return onenter(event,'in<?php echo $k ?>','out<?php echo $k ?>')"/>
            <input type="button" value="Add" onclick="additem('in<?php echo $k ?>','out<?php echo $k ?>')"/><br/>

            <table >
                <tbody id="out<?php echo $k ?>">

                <?php  
                if (isset($sel2[$s1])) {
                    for ($i=0;$i<count($sel2[$s1]);$i++) {
                        ?>
                        <tr id="trout<?php echo $k . "-" . $i ?>">
                            <td><input type=hidden id="out<?php echo $k . "-" . $i ?>" name="out<?php echo $k . "-" . $i ?>" value="<?php echo $sel2[$s1][$i] ?>">
                                <?php echo $sel2[$s1][$i] ?></td>
                            <td><a href='#' onclick="removeitem('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Remove</a>
                                <a href='#' onclick="moveitemup('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Move up</a>
                                <a href='#' onclick="moveitemdown('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Move down</a>
                            </td>
                        </tr>

                    <?php
                    }
                }
                ?>
                </tbody>
            </table>
        </div>

        <?php
        echo (isset($sel2[$s1]) && count($sel2[$s1])>0) ? "<script> cnt['out$k'] = ".count($sel2[$s1]).";</script>\n"  : "<script> cnt['out$k'] = 0;</script>\n";
        ?>
    </div>

<?php
}

echo '<input type=submit value="Continue">';
echo '<form>';

} elseif (isset($_GET['step']) && $_GET['step']==3) {  //STEP 3 DISPLAY
    echo $page_successMsg;
    echo $page_diagLink;
    echo $page_publicLink;
    echo "<a href=".AppUtility::getURLFromHome('admin', 'admin/index').">Return to Admin Page</a>\n";
} else {
//STEP 1 DISPLAY
?>
<form method=post action=diagnostics?step=2>

<?php echo (isset($params['id'])) ? "	<input type=hidden name=id value=\"{$params['id']}\"/>" : ""; ?>

	<p>Diagnostic Name:
        <input type=text size=50 name="diagname" value="<?php echo $diagname; ?>"/></p>

	<p>Term designator (e.g. F06):  <input type=radio name="termtype" value="mo" <?php if ($term=="*mo*") {echo 'checked="checked"';}?>>Use Month
        <input type=radio name="termtype" value="day" <?php if ($term=="*day*") {echo 'checked="checked"';}?>>Use Day
        <input type=radio name="termtype" value="cu" <?php if ($term!="*mo*" && $term!="*day*"  ) {echo 'checked="checked"';}?>>Use: <input type=text size=7 name="term" value="<?php if ($term!="*mo*" && $term!="*day*" ) {echo $term; }?>"/></p>

	<p>Linked with course:
        <?php
        AppUtility::writeHtmlSelect ("cid",$page_courseSelectList['val'],$page_courseSelectList['label'],$page_courseSelected); ?>
    </p>

	<p>Available? (Can be taken)?
        <input type=radio name="avail" value="1" <?php AppUtility::writeHtmlChecked(1,($public&1),0); ?> /> Yes
        <input type=radio name="avail" value="0" <?php AppUtility::writeHtmlChecked(1,($public&1),1); ?> /> No
    </p>
	<p>Include in public listing?
        <input type=radio name="public" value="1" <?php AppUtility::writeHtmlChecked(2,($public&2),0); ?> /> Yes
        <input type=radio name="public" value="0" <?php AppUtility::writeHtmlChecked(2,($public&2),1); ?> /> No
    </p>
	<p>Allow reentry (continuation of test at later date)?
        <input type=radio name="reentry" value="0" <?php AppUtility::writeHtmlChecked(4,($public&4),1); ?> /> No

        <input type=radio name="reentry" value="1" <?php AppUtility::writeHtmlChecked(4,($public&4),0); ?> /> Yes, within
        <input type="text" name="reentrytime" value="<?php echo $reentrytime; ?>" size="4" /> minutes (0 for no limit)
    </p>

	<p>Unique ID prompt: <input type=text size=60 name="idprompt" value="<?php echo $idprompt; ?>" /></p>

	<p>Attach first level selector to ID: <input type="checkbox" name="entrynotunique" value="1" <?php AppUtility::writeHtmlChecked($entrynotunique,true); ?> /></p>

	<p>ID entry format:
        <?php
        AppUtility::writeHtmlSelect("entrytype",$page_entryType['val'],$page_entryType['label'],$page_entryTypeSelected);
        ?>
    </p>
	<p>ID entry number of characters?:
        <?php
        AppUtility::writeHtmlSelect("entrydig",$page_entryNums['val'],$page_entryNums['label'],$page_entryNumsSelected);
        ?>
    </p>
	<p>
        Allow access without password from computer with these IP addresses.  Use * for wildcard, e.g. 134.39.*<br/>
        Enter IP address: <input type=text id="ipin" onkeypress="return onenter(event,'ipin','ipout')">
        <input type=button value="Add" onclick="additem('ipin','ipout')"/>

	<table>
        <tbody id="ipout">
        <?php
        if (trim($ips)!='') {

            $ips= explode(',',$ips);
            for ($i=0;$i<count($ips);$i++) {
                ?>
                <tr id="tripout-<?php echo $i ?>">
                    <td><input type=hidden id="ipout-<?php echo $i ?>" name="ipout-<?php echo $i ?>" value="<?php echo $ips[$i] ?>">
                        <?php echo $ips[$i] ?></td>
                    <td>
                        <a href='#' onclick="return removeitem('ipout-<?php echo $i ?>','ipout')">Remove</a>
                        <a href='#' onclick="return moveitemup('ipout-<?php echo $i ?>','ipout')">Move up</a>
                        <a href='#' onclick="return moveitemdown('ipout-<?php echo $i ?>','ipout')">Move down</a>
                    </td>
                </tr>
            <?php
            }
        }
        ?>
        </tbody>
    </table>

<?php
		if (is_array($ips)) {
            echo "<script> cnt['ipout'] = ".count($ips).";</script>";
        } else {
            echo "<script> cnt['ipout'] = 0;</script>";
        }
	?>
	</p>


	<p>From other computers, a password will be required to access the diagnostic.<br/>
        Enter Password:
        <input type=text id="pwin"  onkeypress="return onenter(event,'pwin','pwout')">
        <input type=button value="Add" onclick="additem('pwin','pwout')"/>

	<table>
        <tbody id="pwout">
        <?php
        $pws = explode(';',$pws);
        if (trim($pws[0])!='') {
            $pwsb= explode(',',$pws[0]);
            for ($i=0;$i<count($pwsb);$i++) {
                ?>
                <tr id="trpwout-<?php echo $i ?>">
                    <td>
                        <input type=hidden id="pwout-<?php echo $i ?>" name="pwout-<?php echo $i ?>" value="<?php echo $pwsb[$i] ?>">
                        <?php echo $pwsb[$i] ?>
                    </td>
                    <td>
                        <a href='#' onclick="return removeitem('pwout-<?php echo $i ?>','pwout')">Remove</a>
                        <a href='#' onclick="return moveitemup('pwout-<?php echo $i ?>','pwout')">Move up</a>
                        <a href='#' onclick="return moveitemdown('pwout-<?php echo $i ?>','pwout')">Move down</a>
                    </td>
                </tr>
            <?php
            }
        }
        ?>
        </tbody>
    </table>

<?php
		if (is_array($pwsb)) {
            echo "	<script> cnt['pwout'] = ".count($pwsb).";</script>";
        } else {
            echo "	<script> cnt['pwout'] = 0;</script>";
        }
?>
	</p>
	<p>Super passwords will override testing window limits.<br/>
        Enter Password:
        <input type=text id="pwsin"  onkeypress="return onenter(event,'pwsin','pwsout')">
        <input type=button value="Add" onclick="additem('pwsin','pwsout')"/>

	<table>
        <tbody id="pwsout">
        <?php
        if (count($pws)>1 && trim($pws[1])!='') {

            $pwss= explode(',',$pws[1]);
            for ($i=0;$i<count($pwss);$i++) {
                ?>
                <tr id="trpwsout-<?php echo $i ?>">
                    <td>
                        <input type=hidden id="pwsout-<?php echo $i ?>" name="pwsout-<?php echo $i ?>" value="<?php echo $pwss[$i] ?>">
                        <?php echo $pwss[$i] ?>
                    </td>
                    <td>
                        <a href='#' onclick="return removeitem('pwsout-<?php echo $i ?>','pwsout')">Remove</a>
                        <a href='#' onclick="return moveitemup('pwsout-<?php echo $i ?>','pwsout')">Move up</a>
                        <a href='#' onclick="return moveitemdown('pwsout-<?php echo $i ?>','pwsout')">Move down</a>
                    </td>
                </tr>
            <?php
            }
        }
        ?>
        </tbody>
    </table>

<?php
		if (is_array($pwss)) {
            echo "	<script> cnt['pwsout'] = ".count($pwss).";</script>";
        } else {
            echo "	<script> cnt['pwsout'] = 0;</script>";
        }
?>
	</p>

	<h4>First-level selector - selects assessment to be delivered</h4>
	<p>Selector name:  <input name="sel" type=text value="<?php echo $sel; ?>"/> "Please select your _______"</p>
	<p>Alphabetize selectors on submit? <input type="checkbox" name="alpha" value="1" /></p>
	<p>Enter new selector option:
        <input type=text id="sellist"  onkeypress="return onenter(event,'sellist','selout')">
        <input type=button value="Add" onclick="additem('sellist','selout')"/>


		<table>
            <tbody id="selout">
            <?php
            if (trim($sel1list)!='') {
                $sl= explode(',',$sel1list);
                for ($i=0;$i<count($sl);$i++) {
                    ?>
                    <tr id="trselout-<?php echo $i ?>">
                        <td>
                            <input type=hidden id="selout-<?php echo $i ?>" name="selout-<?php echo $i ?>" value="<?php echo $sl[$i]?>">
                            <?php echo $sl[$i]?>
                        </td>
                        <td>
                            <a href='#' onclick="return removeitem('selout-<?php echo $i ?>','selout')">Remove</a>
                            <a href='#' onclick="return moveitemup('selout-<?php echo $i ?>','selout')">Move up</a>
                            <a href='#' onclick="return moveitemdown('selout-<?php echo $i ?>','selout')">Move down</a>
                        </td>
                    </tr>
                <?php
                }
            }
            ?>
            </tbody>
        </table>

<?php
		if (is_array($sl)) {
            echo "<script> cnt['selout'] = ".count($sl).";</script>";
        } else {
            echo "<script> cnt['selout'] = 0;</script>";
        }
?>
	</p>

	<p><input type=submit value="Continue Setup"/></p>
	</form>
<?php
}
?>