<?php
use \app\components\AppUtility;
use \app\components\AppConstant;

$this->title = 'Diagnostic Setup';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => ['Home', 'Admin'], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'admin/admin/index'], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item">
<?php    if (isset($_GET['step']) && $_GET['step'] == AppConstant::NUMERIC_TWO) {
?>
<div class="col-lg-10"><h4>Second-level Selector - extra information</h4></div>
<BR class=form>

<form method=post action="diagnostics?step=3"><BR class=form>

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
<input type=hidden name="id" value="<?php echo $page_updateId ?>">

<div class="col-lg-2">Second-level selector name</div>
<div class="col-lg-8"><input type=text class="form-control-1" name=sel2name value="<?php echo $sel2name ?>"/>
    'Select your ______'
</div>
<BR class=form><br>

<div class="col-lg-10">For each of the first-level selectors, select which assessment should be delivered,
    and provide options for the second-level selector
</div>
<BR class=form><br>

<div class="col-lg-2">Alphabetize selectors on submit?</div>
<div class="col-lg-6"><input type="checkbox" name="alpha" value="1"/></div>
<BR class=form><br>
<?php
foreach ($sel1 as $k => $s1) {
    ?>
    <div>
        <p><b><?php echo $s1 ?></b>. Deliver assessment:
            <?php
            AppUtility::writeHtmlSelect($page_selectName[$k], $page_selectValList[$k], $page_selectLabelList[$k], $page_selectedOption[$k]);
            ?>
            <br/>
            Force regen on reentry (if allowed)? <input type=checkbox name="reg<?php echo $k; ?>" value="1" <?php
            if (($forceregen & (AppConstant::NUMERIC_ONE << $k)) > AppConstant::NUMERIC_ZERO) {
                echo 'checked="checked"';
            }?> />
            <?php
            if ($k == AppConstant::NUMERIC_ZERO && count($sel1) > AppConstant::NUMERIC_ONE) {
                echo '<br/>Use these second-level selectors for all first-level selectors?';
                echo '<input type=checkbox name="useoneforall" value="1" onclick="toggleonefor(this)" />';
            }
            ?>
        </p>

        <div class="sel2">Add selector value:
            <input type=text id="in<?php echo $k ?>"
                   onkeypress="return onenter(event,'in<?php echo $k ?>','out<?php echo $k ?>')"/>
            <input type="button" value="Add" onclick="additem('in<?php echo $k ?>','out<?php echo $k ?>')"/><br/>

            <table>
                <tbody id="out<?php echo $k ?>">
                <?php
                if (isset($sel2[$s1])) {
                    for ($i = AppConstant::NUMERIC_ZERO; $i < count($sel2[$s1]); $i++) {
                        ?>
                        <tr id="trout<?php echo $k . "-" . $i ?>">
                            <td><input type=hidden id="out<?php echo $k . "-" . $i ?>"
                                       name="out<?php echo $k . "-" . $i ?>" value="<?php echo $sel2[$s1][$i] ?>">
                                <?php echo $sel2[$s1][$i] ?></td>
                            <td><a href='#'
                                   onclick="removeitem('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Remove</a>
                                <a href='#'
                                   onclick="moveitemup('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Move
                                    up</a>
                                <a href='#'
                                   onclick="moveitemdown('out<?php echo $k . "-" . $i ?>','out<?php echo $k ?>')">Move
                                    down</a>
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
        echo (isset($sel2[$s1]) && count($sel2[$s1]) > AppConstant::NUMERIC_ZERO) ? "<script> cnt['out$k'] = " . count($sel2[$s1]) . ";</script>\n" : "<script> cnt['out$k'] = 0;</script>\n";
        ?>
    </div>

<?php
} ?>

<div class="col-lg-2"> <?php echo '<input type=submit value="Continue"></div>';
echo '<form>';

} elseif (isset($_GET['step']) && $_GET['step'] == AppConstant::NUMERIC_THREE) {
    echo $page_successMsg;
    echo $page_diagLink;
    echo $page_publicLink;
    echo "<div class=col-lg-10><a href=" . AppUtility::getURLFromHome('admin', 'admin/index') . ">Return to Admin Page</a></div>\n";
} else {
    ?>
    <form method=post action=diagnostics?step=2><BR class=form>
    <?php echo (isset($params['id'])) ? "	<input type=hidden name=id value=\"{$params['id']}\"/>" : ""; ?>

    <div class="col-lg-2 padding-top-five">Diagnostic Name</div>
    <div class="col-lg-6"><input type=text size=60 name="diagname" class="form-control" maxlength="60"
                                 required="Please fill out this field" value="<?php echo $diagname; ?>"/></div>
    <BR class=form><br>

    <div class="col-lg-2">Term designator (e.g. F06)</div>
    <div class="col-lg-8">
        <input type=radio name="termtype" value="mo" <?php if ($term == "*mo*") {
            echo 'checked="checked"';
        } ?>><span class="padding-left-five padding-right">Use Month</span>
        <input type=radio name="termtype" value="day" <?php if ($term == "*day*") {
            echo 'checked="checked"';
        } ?>><span class="padding-left-five padding-right">Use Day</span>
        <input type=radio name="termtype" value="cu" <?php if ($term != "*mo*" && $term != "*day*") {
            echo 'checked="checked"';
        } ?>><span class="padding-left-five padding-right">Use</span>
        <input type=text size=7 name="term" class="form-control-1"
               value="<?php if ($term != "*mo*" && $term != "*day*") {
                   echo $term;
               } ?>"/>
    </div>
    <div class="col-lg-2">
    </div>
    <BR class=form><br>

    <div class="col-lg-2 padding-top-five">Linked with course</div>
    <div class="col-lg-4">
        <?php
        AppUtility::writeHtmlSelect("cid", $page_courseSelectList['val'], $page_courseSelectList['label'], $page_courseSelected); ?>
    </div>
    <BR class=form><br>

    <div class="col-lg-2">Available? (Can be taken)?</div>
    <div class="col-lg-6"><input type=radio name="avail"
                                 value="1" <?php AppUtility::writeHtmlChecked(AppConstant::NUMERIC_ONE, ($public & 1), AppConstant::NUMERIC_ZERO); ?> /><span
            class="padding-left-three padding-right"> Yes</span>
        <input type=radio name="avail"
               value="0" <?php AppUtility::writeHtmlChecked(AppConstant::NUMERIC_ONE, ($public & 1), AppConstant::NUMERIC_ONE); ?> /> <span
            class="padding-left-three padding-right">No</span>
    </div>
    <BR class=form><br>

    <div class="col-lg-2">Include in public listing?</div>
    <div class="col-lg-6"><input type=radio name="public"
                                 value="1" <?php AppUtility::writeHtmlChecked(AppConstant::NUMERIC_TWO, ($public & AppConstant::NUMERIC_TWO), AppConstant::NUMERIC_ZERO); ?> /> <span
            class="padding-left-three padding-right">Yes</span>
        <input type=radio name="public"
               value="0" <?php AppUtility::writeHtmlChecked(AppConstant::NUMERIC_TWO, ($public & AppConstant::NUMERIC_TWO), AppConstant::NUMERIC_ONE); ?> /><span
            class="padding-left-five padding-right">No</span>
    </div>
    <BR class=form><br>

    <div class="col-lg-2">Allow reentry (continuation of test at later date)?</div>
    <div class="col-lg-8"><input type=radio name="reentry"
                                 value="0" <?php AppUtility::writeHtmlChecked(AppConstant::NUMERIC_FOUR, ($public & 4), AppConstant::NUMERIC_ONE); ?> /><span
            class="padding-left-five padding-right">No</span>
        <span style="padding-left: 5px"><input type=radio name="reentry"
                                               value="1" <?php AppUtility::writeHtmlChecked(AppConstant::NUMERIC_FOUR, ($public & 4), AppConstant::NUMERIC_ZERO); ?> /> <span
                class="padding-left-three padding-right">Yes, within</span>
       <input type="text" class="form-control-1" name="reentrytime" value="<?php echo $reentrytime; ?>" size="4"/> minutes (0 for no limit)</span>
    </div>
    <BR class=form><br>

    <div class="col-lg-2 padding-top-five">Unique ID prompt</div>
    <div class="col-lg-6">
        <input type=text size=60 name="idprompt" class="form-control" value="<?php echo $idprompt; ?>"/></div>
    <BR class=form><br>

    <div class="col-lg-2">Attach first level selector to ID</div>
    <div class="col-lg-6"><input type="checkbox" name="entrynotunique"
                                 value="1" <?php AppUtility::writeHtmlChecked($entrynotunique, true); ?> /></div>
    <BR class=form><br>

    <div class="col-lg-2 padding-top-five">ID entry format</div>
    <div class="col-lg-4">   <?php
        AppUtility::writeHtmlSelect("entrytype", $page_entryType['val'], $page_entryType['label'], $page_entryTypeSelected);
        ?>
    </div>
    <BR class=form><br>

    <div class="col-lg-2">ID entry number of characters?</div>
    <div class="col-lg-4">    <?php
        AppUtility::writeHtmlSelect("entrydig", $page_entryNums['val'], $page_entryNums['label'], $page_entryNumsSelected);
        ?>
    </div>
    <BR class=form><br>

    <div class="col-lg-10">
        Allow access without password from computer with these IP addresses. Use * for wildcard, e.g. 134.39.*
    </div>
    <br/>

    <div class="col-lg-2 padding-top-five">Enter IP address</div>
    <div class="col-lg-8"><input type=text id="ipin" class="form-control-1"
                                 onkeypress="return onenter(event,'ipin','ipout')">
        <input type=button value="Add" onclick="additem('ipin','ipout')"/>
        <table>
            <tbody id="ipout">
            <?php
            if (trim($ips) != '') {

                $ips = explode(',', $ips);
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($ips); $i++) {
                    ?>
                    <tr id="tripout-<?php echo $i ?>">
                        <td><input type=hidden id="ipout-<?php echo $i ?>" name="ipout-<?php echo $i ?>"
                                   value="<?php echo $ips[$i] ?>">
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
            echo "<script> cnt['ipout'] = " . count($ips) . ";</script>";
        } else {
            echo "<script> cnt['ipout'] = 0;</script>";
        }
        ?>
    </div>
    <BR class=form><br>

    <div class="col-lg-10">From other computers, a password will be required to access the diagnostic.</div>
    <br/>

    <div class="col-lg-2 padding-top-five">Enter Password</div>
    <div class="col-lg-8"><input type=text id="pwin" class="form-control-1"
                                 onkeypress="return onenter(event,'pwin','pwout')">
        <input type=button value="Add" onclick="additem('pwin','pwout')"/>

        <table>
            <tbody id="pwout">
            <?php
            $pws = explode(';', $pws);
            if (trim($pws[0]) != '') {
                $pwsb = explode(',', $pws[0]);
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($pwsb); $i++) {
                    ?>
                    <tr id="trpwout-<?php echo $i ?>">
                        <td>
                            <input type=hidden id="pwout-<?php echo $i ?>" name="pwout-<?php echo $i ?>"
                                   value="<?php echo $pwsb[$i] ?>">
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
            echo "	<script> cnt['pwout'] = " . count($pwsb) . ";</script>";
        } else {
            echo "	<script> cnt['pwout'] = 0;</script>";
        }
        ?>
    </div>
    <BR class=form><br>

    <div class="col-lg-10">Super passwords will override testing window limits.</div>
    <br/>

    <div class="col-lg-2 padding-top-five">Enter Password</div>
    <div class="col-lg-8"><input type=text id="pwsin" class="form-control-1"
                                 onkeypress="return onenter(event,'pwsin','pwsout')">
        <input type=button value="Add" onclick="additem('pwsin','pwsout')"/>

        <table>
            <tbody id="pwsout">
            <?php
            if (count($pws) > AppConstant::NUMERIC_ONE && trim($pws[1]) != '') {

                $pwss = explode(',', $pws[1]);
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($pwss); $i++) {
                    ?>
                    <tr id="trpwsout-<?php echo $i ?>">
                        <td>
                            <input type=hidden id="pwsout-<?php echo $i ?>" name="pwsout-<?php echo $i ?>"
                                   value="<?php echo $pwss[$i] ?>">
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
            echo "	<script> cnt['pwsout'] = " . count($pwss) . ";</script>";
        } else {
            echo "	<script> cnt['pwsout'] = 0;</script>";
        }
        ?>
    </div>
    <BR class=form><br>

    <div class="col-lg-10"><h4>First-level selector - selects assessment to be delivered</h4></div>
    <BR class=form>

    <div class="col-lg-2">Selector name</div>
    <div class="col-lg-8"><input name="sel" class="form-control-1" type=text value="<?php echo $sel; ?>"/> "Please
        select your _______"
    </div>
    <BR class=form><br>

    <div class="col-lg-2">Alphabetize selectors on submit?</div>
    <div class="col-lg-6"><input type="checkbox" name="alpha" value="1"/></div>
    <BR class=form><br>

    <div class="col-lg-2">Enter new selector option</div>
    <div class="col-lg-8"><input type=text id="sellist" class="form-control-1"
                                 onkeypress="return onenter(event,'sellist','selout')">
        <input type=button value="Add" onclick="additem('sellist','selout')"/>
        <table>
            <tbody id="selout">
            <?php
            if (trim($sel1list) != '') {
                $sl = explode(',', $sel1list);
                for ($i = AppConstant::NUMERIC_ZERO; $i < count($sl); $i++) {
                    ?>
                    <tr id="trselout-<?php echo $i ?>">
                        <td>
                            <input type=hidden id="selout-<?php echo $i ?>" name="selout-<?php echo $i ?>"
                                   value="<?php echo $sl[$i] ?>">
                            <?php echo $sl[$i] ?>
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
            echo "<script> cnt['selout'] = " . count($sl) . ";</script>";
        } else {
            echo "<script> cnt['selout'] = 0;</script>";
        }
        ?>
    </div>
    <BR class=form><br>

    <div class="col-lg-2"><input type=submit value="Continue Setup"/></div>
    <BR class=form><br>
    </form>
<?php
}
?>
</div>