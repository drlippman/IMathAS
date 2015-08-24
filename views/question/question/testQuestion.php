<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
use app\components\displayq2;

$this->title = AppUtility::t('Add Question', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<link href='<?php echo AppUtility::getHomeURL() ?>css/fullcalendar.print.css' rel='stylesheet' media='print' />
<!--Get current time-->
<input type="hidden" class="" value="<?php echo $courseId = $course->id?>">
<?php $imasroot = AppUtility::getHomeURL();?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $courseId]]); ?>
</div>
<!--Course name-->

<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php AppUtility::t('Add/Remove Questions') ?></div>
        </div>
        <div class="pull-left header-btn">
            <a href="<?php echo AppUtility::getURLFromHome('course', 'course/course-setting?cid='.$course->id); ?>"
               class="btn btn-primary pull-right page-settings"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/courseSetting.png">&nbsp;Course Setting</a>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => 'roster']); ?>
</div>
<div class="tab-content shadowBox">

    <br>
    <br>
    <?php

    $sessiondata['coursetheme'] = $coursetheme;
    $flexwidth = true; //tells header to use non _fw stylesheet
    $placeinhead = '';
    if ($showtips==2) {
        $placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqntips.js?v=012810\"></script>";
    }

    if ($eqnhelper==1 || $eqnhelper==2) {
        $placeinhead .= '<script type="text/javascript">var eetype='.$eqnhelper.'</script>';
        $placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqnhelper.js?v=030112\"></script>";
        $placeinhead .= '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';

    } else if ($eqnhelper==3 || $eqnhelper==4) {
        $placeinhead .= "<link rel=\"stylesheet\" href=\"$imasroot/assessment/mathquill.css?v=030212\" type=\"text/css\" />";
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')!==false) {
            $placeinhead .= '';
        }
        $placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/mathquill_min.js?v=030112\"></script>";
        $placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/mathquilled.js?v=030112\"></script>";
        $placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/AMtoMQ.js?v=030112\"></script>";
        $placeinhead .= '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';

    }
    $useeqnhelper = $eqnhelper;


    if ($overwriteBody==1) {
        echo $body;
    } else { //DISPLAY BLOCK HERE
        $useeditor = 1;
        $brokenurl = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/savebrokenqflag.php?qsetid=".$params['qsetid'].'&flag=';
        ?>
        <script type="text/javascript">
            var BrokenFlagsaveurl = '<?php echo $brokenurl;?>';
            function submitBrokenFlag(tagged) {
                url = BrokenFlagsaveurl + tagged;
                if (window.XMLHttpRequest) {
                    req = new XMLHttpRequest();
                } else if (window.ActiveXObject) {
                    req = new ActiveXObject("Microsoft.XMLHTTP");
                }
                if (typeof req != 'undefined') {
                    req.onreadystatechange = function() {submitBrokenFlagDone(tagged);};
                    req.open("GET", url, true);
                    req.send("");
                }
            }

            function submitBrokenFlagDone(tagged) {
                if (req.readyState == 4) { // only if req is "loaded"
                    if (req.status == 200) { // only if "OK"
                        if (req.responseText=='OK') {
                            toggleBrokenFlagmsg(tagged);
                        } else {
                            alert(req.responseText);
                            alert("Oops, error toggling the flag");
                        }
                    } else {
                        alert(" Couldn't save changes:\n"+ req.status + "\n" +req.statusText);
                    }
                }
            }
            function toggleBrokenFlagmsg(tagged) {
                document.getElementById("brokenmsgbad").style.display = (tagged==1)?"block":"none";
                document.getElementById("brokenmsgok").style.display = (tagged==1)?"none":"block";
                if (tagged==1) {alert("Make sure you also contact the question author or support so they know why you marked the question as broken");}
            }
        </script>
        <?php
        if (isset($params['formn']) && isset($params['loc'])) {
            echo '<p>';
            echo "<script type=\"text/javascript\">";
            echo "var numchked = -1;";
            echo "if (window.opener && !window.opener.closed) {";
            echo $page_onlyChkMsg;
            echo "  if (prevnext[0][1]>0){
				  document.write('<a href=\"testquestion.php?cid={$params['cid']}$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[0][0]+'&qsetid='+prevnext[0][1]+'\">Prev</a> ');
			  } else {
				  document.write('Prev ');
			  }
			  if (prevnext[1][1]>0){
				  document.write('<a href=\"testquestion.php?cid={$params['cid']}$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[1][0]+'&qsetid='+prevnext[1][1]+'\">Next</a> ');
			  } else {
				  document.write('Next ');
			  }
			  if (prevnext[2]!=null) {
			  	document.write(' <span id=\"numchked\">'+prevnext[2]+'</span> checked');
				numchked = prevnext[2];
			  }
			  if (prevnext[3]!=null) {
			  	document.write(' '+prevnext[3]+' remaining');
			  }
			}
			</script>";
            echo '</p>';
        }

        if (isset($params['checked'])) {
            echo "<p><input type=\"checkbox\" name=\"usecheck\" id=\"usecheck\" value=\"Mark Question for Use\" onclick=\"parentcbox.checked=this.checked;togglechk(this.checked)\" ";
            echo "/> Mark Question for Use</p>";
            echo "
		  <script type=\"text/javascript\">
		  var parentcbox = opener.document.getElementById(\"{$params['loc']}\");
		  document.getElementById(\"usecheck\").checked = parentcbox.checked;
		  function togglechk(ischk) {
			  if (numchked!=-1) {
				if (ischk) {
					numchked++;
				} else {
					numchked--;
				}
				document.getElementById(\"numchked\").innerHTML = numchked;
			  }
		  }
		  </script>";
        }

        echo $page_scoreMsg;
        echo '<script type="text/javascript"> function whiteout() { e=document.getElementsByTagName("div");';
        echo 'for (i=0;i<e.length;i++) { if (e[i].className=="question") {e[i].style.backgroundColor="#fff";}}}</script>';
        echo "<form method=post enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit()\">\n";
        echo "<input type=hidden name=seed value=\"$seed\">\n";
        echo "<input type=hidden name=attempt value=\"$attempt\">\n";

        if (isset($rawscores)) {
            if (strpos($rawscores,'~')!==false) {
                $colors = explode('~',$rawscores);
            } else {
                $colors = array($rawscores); //scorestocolors($rawscores,1,0,false);
            }
        } else {
            $colors = array();
        }
        displayq2::displayq(0,$params['qsetid'],$seed,true,true,$attempt,false,false,false,$colors);
        echo "<input type=submit value=\"Submit\"><input type=submit name=\"regen\" value=\"Submit and Regen\">\n";
        echo "<input type=button value=\"White Background\" onClick=\"whiteout()\"/>";
        echo "<input type=button value=\"Show HTML\" onClick=\"document.getElementById('qhtml').style.display='';\"/>";
        echo "</form>\n";

        echo '<code id="qhtml" style="display:none">';
        $message = displayq2::displayq(0,$params['qsetid'],$seed,false,false,0,true);
        $message = printfilter(forcefiltergraph($message));
        $message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
        $message = str_replacE('`','\`',$message);
        echo htmlentities($message);
        echo '</code>';

        if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
            echo "<p>Question id: {$params['qsetid']}.  <a href=\"$imasroot/msgs/msglist.php?add=new&cid={$CFG['GEN']['sendquestionproblemsthroughcourse']}&to={$line['ownerid']}&title=Problem%20with%20question%20id%20{$params['qsetid']}\" target=\"_blank\">Message owner</a> to report problems</p>";
        } else {
            echo "<p>Question id: {$params['qsetid']}.  <a href=\"mailto:{$line['email']}?subject=Problem%20with%20question%20id%20{$params['qsetid']}\">E-mail owner</a> to report problems</p>";
        }
        echo "<p>Description: {$line['description']}</p><p>Author: {$line['author']}</p>";
        echo "<p>Last Modified: $lastmod</p>";
        if ($line['deleted']==1) {
            echo '<p style="color:red;">This question has been marked for deletion.  This might indicate there is an error in the question. ';
            echo 'It is recommended you discontinue use of this question when possible</p>';
        }
        if ($line['replaceby']>0) {
            echo '<p style="color:red;">This message has been marked as deprecated, and it is recommended you use question ID '.$line['replaceby'].' instead.  You can find this question ';
            echo 'by searching all libraries with the ID number as the search term</p>';
        }

        echo '<p id="brokenmsgbad" style="color:red;display:'.(($line['broken']==1)?"block":"none").'">This message has been marked as broken.  This indicates ';
        echo 'there might be an error with this question.  Use with caution.  <a href="#" onclick="submitBrokenFlag(0);return false;">Unmark as broken</a></p>';
        echo '<p id="brokenmsgok" style="display:'.(($line['broken']==0)?"block":"none").'"><a href="#" onclick="submitBrokenFlag(1);return false;">Mark as broken</a> if there appears to be an error with the question.</p>';

        echo '<p>'._('License').': ';
        $license = array('Copyrighted','IMathAS Community License','Public Domain','Creative Commons Attribution-NonCommercial-ShareAlike','Creative Commons Attribution-ShareAlike');
        echo $license[$line['license']];
        if ($line['otherattribution']!='') {
            echo '<br/>Other Attribution: '.$line['otherattribution'];
        }
        echo '</p>';

        echo '<p>Question is in these libraries:';
        echo '<ul>';
        foreach ($resultLibNames as $row) {
            echo '<li>'.$row['name'];
            if ($myRights==100) {
                echo ' ('.$row['LastName'].', '.$row['FirstName'].')';
            }
            echo '</li>';
        }
        echo '</ul></p>';

        if ($line['ancestors']!='') {
            echo "<p>Derived from: {$line['ancestors']}";
            if ($line['ancestorauthors']!='') {
                echo '<br/>Created by: '.$line['ancestorauthors'];
            }
            echo "</p>";
        } else if ($line['ancestorauthors']!='') {
            echo '<p>Derived from work by: '.$line['ancestorauthors'].'</p>';
        }
        if ($myRights==100) {
            echo '<p>UniqueID: '.$line['uniqueid'].'</p>';
        }
    }

    ?>

    <br>
    </div>

