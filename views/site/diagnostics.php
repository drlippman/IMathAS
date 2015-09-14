<?php
use yii\helpers\Html;
use app\components\AppUtility;
$this->title = 'Diagnostics';
$this->params['breadcrumbs'][] = $this->title;
?>
<?php

if (!isset($params['id']))
{
    $displayDiagnostics;
    $nologo = true;
    $infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
    $placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/{$infopath}infopages.css\" type=\"text/css\">\n";
    $placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
    $pagetitle = "Diagnostics";
//    require((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'../')."infoheader.php");
    echo "<img class=\"floatleft\" src=\"$imasroot/img/ruler.jpg\"/>
		<div class=\"content\">
		<div id=\"headerdiagindex\" class=\"pagetitle\"><h2>", _('Available Diagnostics'), "</h2></div>
		<ul class=\"nomark\">";
    if (count($displayDiagnostics) == 0) {
        echo "<li>", _('No diagnostics are available through this page at this time'), "</li>";
    }
    foreach($displayDiagnostics as $key => $row) {
        echo "<li>
        <a href=".AppUtility::getURLFromHome('site', 'diagnostics?id='.$row['id']).">".$row['name']."</a></li>";
    }
    echo "</ul></div>";
    exit;
}?>

<?php if (!($line['public']&1)) {
echo "<html><body>", _('This diagnostic is not currently available to be taken'), "</body></html>";
exit;
} ?>

<div class="tab-content shadowBox non-nav-tab-item">
    <br>
<form method=post action="diagnostics?id=<?php echo $diagid; ?>">
<!--    <span class=form>--><?php //echo $line['idprompt']; ?><!--</span> <input class=form type=text size=12 name=SID><BR class=form>-->
    <div class=col-lg-2><?php echo $line['idprompt']; ?></div>
    <div class="col-lg-6"><input class=form type=text size=12 name=SID></div><BR class=form>
<!--    <span class=form>--><?php //echo _('Enter First Name:'); ?><!--</span> <input class=form type=text size=20 name=firstname><BR class=form>-->
    <div class=col-lg-2><?php echo _('Enter First Name'); ?></div>
    <div class="col-lg-6"><input class=form type=text size=20 name=firstname></div><BR class=form>
<!--    <span class=form>--><?php //echo _('Enter Last Name:'); ?><!--</span> <input class=form type=text size=20 name=lastname><BR class=form>-->
    <div class=col-lg-2><?php echo _('Enter Last Name'); ?></div> <div class="col-lg-6"><input class=form type=text size=20 name=lastname></div><BR class=form>

    <script type="text/javascript">
        var teach = new Array();
        <?php
            $sel2 = explode(';',$line['sel2list']);
            foreach ($sel2 as $k=>$v) {
                echo "teach[$k] = new Array('".implode("','",explode('~',$sel2[$k]))."');\n";
            }
        ?>
        function getteach() {
            var classbox = document.getElementById("course");
            var cl = classbox.options[classbox.selectedIndex].value;
            var teachbox = document.getElementById("teachers");
            if (cl > -1) {
                var list = teach[cl];
                teachbox.options.length = 0;
                for(i=0;i<list.length;i++)
                {
                    teachbox.options[i] = new Option(list[i],list[i]);
                }
            }
        }
    </script>

<!--    <span class=form>--><?php //printf(_('Select your %s'), $line['sel1name']); ?><!--</span><span class=formright>-->
    <div class=col-lg-2><?php printf(_('Select your %s'), $line['sel1name']); ?></div>
    <div class=col-lg-4>
    <select name="course" class="form-control" id="course" onchange="getteach()">
    <option value="-1"><?php printf(_('Select a %s'), $line['sel1name']); ?></option>
    <?php
    for ($i=0;$i<count($sel1);$i++) {
        echo "<option value=\"$i\">{$sel1[$i]}</option>\n";
    }
    ?>
</select></div><br class=form><br class=form>

<!--    <span class=form>--><?php //printf(_('Select your %s'), $line['sel2name']); ?><!--</span><span class=formright>-->
    <div class=col-lg-2><?php printf(_('Select your %s'), $line['sel2name']); ?></div>
    <div class=col-lg-4>
        <select name="teachers" class="form-control" id="teachers">
            <option value="not selected"><?php printf(_('Select a %s first'), $line['sel1name']); ?></option>
        </select>
    </div><br class=form><br class=form>

    <?php
    if (!$noproctor) {
        echo "<b><div class='col-lg-12'>", _('This test can only be accessed from this location with an access password'), "</div></b></br>\n";
//        echo "<span class=form>", _('Access password:'), "</span>  <input class=form type=password size=40 name=passwd><BR class=form>";
        echo "<div class=col-lg-2>", _('Access password'), "</div>  <div class='col-lg-4'><input class=form type=password size=40 name=passwd></div><BR class=form>";
    }
    ?>
    <input type="hidden" id="tzoffset" name="tzoffset" value="">
    <input type="hidden" id="tzname" name="tzname" value="">
    <script>
        var thedate = new Date();
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
        var tz = jstz.determine();
        document.getElementById("tzname").value = tz.name();
    </script>
    <div id="submit" class="submit">
        <input type=submit value='<?php echo 'Access Diagnostic' ?>'></div>
    <input type=hidden name="mathdisp" id="mathdisp" value="2" />
    <input type=hidden name="graphdisp" id="graphdisp" value="2" />
    <?php
    $allowreentry = ($line['public']&4);
    $pws = explode(';',$line['pws']);
    if ($noproctor && count($pws)>1 && trim($pws[1])!='' && (!$allowreentry || $line['reentrytime']>0)) {
        echo "<p>", _('No access code is required for this diagnostic.  However, if your testing window has expired, a proctor can enter a password to allow reaccess to this test.'), "</br>\n";
        echo "<span class=form>", _('Override password'), ":</span>  <input class=form type=password size=40 name=passwd><BR class=form>";
    }
    ?>
</form>
</div>