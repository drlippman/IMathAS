<?php
use yii\helpers\Html;
use app\components\AppUtility;
$this->title = 'Diagnostics';
$this->params['breadcrumbs'][] = $this->title;
?>
<?php if($params['id']) {?>
    <div class="item-detail-header">
        <?php echo $this->render("../itemHeader/_indexWithLeftContent", ['link_title' => ['Home'], 'link_url' => [AppUtility::getHomeURL() . 'site/index'], 'page_title' => $this->title]); ?>
    </div>
    <div class="title-container">
        <div class="row">
            <div class="pull-left page-heading">
                <div class="vertical-align title-page"><?php echo $this->title ?></div>
            </div>
        </div>
    </div>
<?php } ?>

<?php

if (!isset($params['id']))
{
    $displayDiagnostics;
    $nologo = true;
    $infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
    $placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/{$infopath}infopages.css\" type=\"text/css\">\n";
    $placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/js/jstz_min.js\" ></script>";
    $pagetitle = "Diagnostics";
    echo "<img class=\"floatleft\" src=\"$imasroot/img/ruler.jpg\"/>
		<div class=\"content\">
		<div id=\"headerdiagindex\" class=\"pagetitle\"><h2>", _('Available Diagnostics'), "</h2></div>
		<ul class=\"nomark\">";
    if (count($displayDiagnostics) == 0) {
        echo "<li>", _('No diagnostics are available through this page at this time'), "</li>";
    }
    foreach($displayDiagnostics as $key => $row) {
        echo "<li>" ?>
        <a href=<?php echo AppUtility::getURLFromHome('site', 'diagnostics?id='.$row['id'])?>><?php echo $row['name']?></a>
   <?php  echo "</li>"; }
    echo "</ul></div>";
    exit;
}?>

<?php
if(!($line['public']&1)) {
echo "<html><body>", _('This diagnostic is not currently available to be taken'), "</body></html>";
    exit;
} ?>

<?php if (file_exists((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'')."diag$diagid.php")) {
} else {
$nologo = true;
$infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/{$infopath}infopages.css\" type=\"text/css\">\n";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/js/jstz_min.js\" ></script>";
$pagetitle =$line['name'];
?>

<div class="tab-content shadowBox non-nav-tab-item">
    <br>
<form method=post action="diagnostics?id=<?php echo $diagid; ?>">
    <div class=col-md-2><?php echo $line['idprompt']; ?></div>
    <div class="col-md-6"><input class="form form-control-1" type=text  size=12 name=SID></div><BR class=form>
    <div class="col-md-2 select-text-margin"><?php echo _('Enter First Name'); ?></div>
    <div class="col-md-6"><input class="form form-control-1" type=text size=20 name=firstname></div><BR class=form>
    <div class="col-md-2 select-text-margin"><?php echo _('Enter Last Name'); ?></div> <div class="col-md-6"><input class="form form-control-1" type=text size=20 name=lastname></div><BR class=form>

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

    <div class="col-md-2 select-text-margin"><?php printf(_('Select your %s'), $line['sel1name']); ?></div>
    <div class=col-md-4>
    <select name="course" class="form-control" id="course" onchange="getteach()">
    <option value="-1"><?php printf(_('Select a %s'), $line['sel1name']); ?></option>
    <?php
    for ($i=0;$i<count($sel1);$i++) {
        echo "<option value=\"$i\">{$sel1[$i]}</option>\n";
    }
    ?>
</select></div><br class=form><br class=form>

    <div class="col-md-2 select-text-margin"><?php printf(_('Select your %s'), $line['sel2name']); ?></div>
    <div class=col-md-4>
        <select name="teachers" class="form-control" id="teachers">
            <option value="not selected"><?php printf(_('Select a %s first'), $line['sel1name']); ?></option>
        </select>
    </div><br class=form><br class=form>

    <?php
    if (!$noproctor) {
        echo "<b><div class='col-md-12'>", _('This test can only be accessed from this location with an access password'), "</div></b></br>\n";
        echo "<div class='col-md-2 select-text-margin'>", _('Access password'), "</div>  <div class='col-md-4'><input class='form form-control-1' type=password size=40 name=passwd></div><BR class=form>";
    }
    ?>
    <div class="header-btn col-sm-4 col-sm-offset-2 padding-top-fifteen padding-bottom-thirty">
        <button class="btn btn-primary page-settings padding-right-ten" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Access Diagnostic' ?></button>
        <a href="<?php echo AppUtility::getURLFromHome('site', 'diagnostics'); ?>"
           class="btn btn-primary page-settings padding-right-ten"><i class="fa fa-eye"></i>&nbsp;Diagnostics
        </a>
    </div>
    <input type="hidden" id="tzoffset" name="tzoffset" value="">
    <input type="hidden" id="tzname" name="tzname" value="">
    <script>
        var thedate = new Date();
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
        var tz = jstz.determine();
        document.getElementById("tzname").value = tz.name();
    </script>

    <input type=hidden name="mathdisp" id="mathdisp" value="2" />
    <input type=hidden name="graphdisp" id="graphdisp" value="2" />
    <?php
    $allowreentry = ($line['public']&4);
    $pws = explode(';',$line['pws']);
    if ($noproctor && count($pws)>1 && trim($pws[1])!='' && (!$allowreentry || $line['reentrytime']>0)) {
        echo "<p>", _('No access code is required for this diagnostic.  However, if your testing window has expired, a proctor can enter a password to allow reaccess to this test.'), "</br>\n";
        echo "<span class=form>", _('Override password'), ":</span>  <input class='form form-control-1' type=password size=40 name=passwd><BR class=form>";
    }
    ?>
</form>
<div id="bsetup" class="col-md-10">JavaScript is not enabled. JavaScript is required for <?php echo $installname; ?>. Please enable JavaScript and reload this page</div><br>
</div>
<script type="text/javascript">
    function determinesetup() {
        document.getElementById("submit").style.display = "block";
        if (MathJaxCompatible && !ASnoSVG) {
            document.getElementById("bsetup").innerHTML = "Browser setup OK";
        } else {
            document.getElementById("bsetup").innerHTML = "Using image-based display";
        }
        if (MathJaxCompatible) {
            document.getElementById("mathdisp").value = "1";
        }
        if (!ASnoSVG) {
            document.getElementById("graphdisp").value = "1";
        }
    }
    var existingonload = window.onload;
    if (existingonload) {
        window.onload = function() {existingonload(); determinesetup();}
    } else {
        window.onload = determinesetup;
    }
</script>
<?php
}
?>