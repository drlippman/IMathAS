<?php
use yii\helpers\Html;
use app\components\AppUtility;
$this->title = 'Diagnostics';
$this->params['breadcrumbs'][] = $this->title;
?>
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
<div class="tab-content shadowBox non-nav-tab-item col-md-12 col-sm-12 padding-top-bottom-two-em">
<?php
if (!isset($params['id']))
{
    $displayDiagnostics;
    $nologo = true;
    $infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
    $placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/{$infopath}infopages.css\" type=\"text/css\">\n";
    $pagetitle = "Diagnostics"; ?>
        <div class="col-md-3 col-sm-4 padding-top-two-em padding-left-two-em">
            <img class="floatleft width-ninety-per" src="<?php echo $imasroot ?>img/ruler.jpg" />
        </div>
		<div class="col-dm-6 col-sm-6 padding-top-two-em padding-left-zero">
            <div id="headerdiagindex" class="pagetitle">
                <h2><?php AppUtility::t('Available Diagnostics'); ?></h2>
            </div>
            <ul class="nomark">
                <?php if (count($displayDiagnostics) == 0) { ?>
                    <li><?php AppUtility::t('No diagnostics are available through this page at this time')?></li>
                <?php }
                foreach($displayDiagnostics as $key => $row) { ?>
                        <li>
                        <a href=<?php echo AppUtility::getURLFromHome('site', 'diagnostics?id='.$row['id'])?>><?php echo $row['name']?></a>
                        </li>
                <?php } ?>
            </ul>
        </div>
    </div>

<?php } else {?>

<?php
if(!($line['public']&1)) {
echo "<html><body>", _('This diagnostic is not currently available to be taken'), "</body></html>";
} else { ?>

<?php if (file_exists((isset($CFG['GEN']['diagincludepath'])?$CFG['GEN']['diagincludepath']:'')."diag$diagid.php")) {
} else {
$nologo = true;
$infopath = isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'';
$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/{$infopath}infopages.css\" type=\"text/css\">\n";
$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/js/jstz_min.js\" ></script>";
$pagetitle =$line['name'];
?>
<form method=post action="diagnostics?id=<?php echo $diagid; ?>">
    <div class="col-md-12 col-sm-12">
        <div class="col-md-3 col-sm-3 padding-right-zero select-text-margin">
            <?php echo $line['idprompt']; ?>
        </div>
        <div class="col-md-6 col-sm-6">
            <input class="form form-control-1" type=text  size=12 name=SID>
        </div>
    </div>
    <div class="col-md-12 col-sm-12">
        <div class="col-md-3 col-sm-3 select-text-margin">
            <?php Apputility::t(('Enter First Name')); ?>
        </div>
        <div class="col-md-6 col-sm-6">
            <input class="form form-control-1" type=text size=20 name=firstname>
        </div>
    </div>
    <div class="col-md-12 col-sm-12">
        <div class="col-md-3 col-sm-3 select-text-margin">
            <?php Apputility::t(('Enter Last Name')); ?>
        </div>
        <div class="col-md-6 col-sm-6">
            <input class="form form-control-1" type=text size=20 name=lastname>
        </div>
    </div>
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

    <div class="col-md-12 col-sm-12 padding-bottom-one-em">
        <div class="col-md-3 col-sm-3 select-text-margin">
            <?php printf(_('Select your %s'), $line['sel1name']); ?>
        </div>
        <div class="col-md-4 col-sm-4">
            <select name="course" class="form-control" id="course" onchange="getteach()">
                <option value="-1"><?php printf(_('Select a %s'), $line['sel1name']); ?></option>
                <?php
                for ($i=0;$i<count($sel1);$i++) {
                    echo "<option value=\"$i\">{$sel1[$i]}</option>\n";
                }
                ?>
            </select>
        </div>
    </div>
    <div class="col-md-12 col-sm-12 padding-bottom-one-em">
        <div class="col-md-3 col-sm-3 select-text-margin">
            <?php printf(_('Select your %s'), $line['sel2name']); ?>
        </div>
        <div class="col-md-4 col-sm-4">
            <select name="teachers" class="form-control" id="teachers">
                <option value="not selected"><?php printf(_('Select a %s first'), $line['sel1name']); ?></option>
            </select>
        </div>
    </div>

    <?php
    if (!$noproctor) { ?>
        <div class="col-md-9 col-md-offset-3 col-sm-9 col-sm-offset-3 padding-bottom-one-em">
        <b>
            <div class='col-md-12 padding-left-pt-five-em'>This test can only be accessed from this location with an access password
            </div>
        </b>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class='col-md-3 col-sm-3 select-text-margin'>
                Access password
            </div>
            <div class='col-md-4 col-sm-4'>
                <input class='form form-control-1' type=password size=40 name=passwd>
            </div>
        </div>
   <?php } ?>
    <div class="header-btn col-md-6 col-md-offset-3 col-sm-6 col-sm-offset-3 padding-top-fifteen padding-bottom-thirty">
        <div class="col-md-12 col-sm-12 padding-left-pt-five-em">
            <button class="btn btn-primary page-settings padding-right-ten" type="submit" value="Submit">
<!--                <i class="fa fa-share header-right-btn"></i>-->
                <?php AppUtility::t('Access Diagnostic'); ?>
            </button>
            <span class="padding-left-one-em">
                <a href="<?php echo AppUtility::getURLFromHome('site', 'diagnostics'); ?>"
                   class="btn btn-primary page-settings padding-right-ten"><i class="fa fa-eye"></i>
                    &nbsp;Diagnostics
                </a>
            </span>
        </div>
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
    <div id="bsetup" class="col-md-12 col-sm-12 padding-left-two-em">
        JavaScript is not enabled. JavaScript is required for <?php echo $installname; ?>. Please enable JavaScript and reload this page
    </div>
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
} } }

if (!preg_match($pattern, $params['SID']))
{
    echo $html;
}
?>