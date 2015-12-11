<?php
use app\components\AppUtility;

if ($scoredView) {
    $placeinhead = '<script type="text/javascript">
			$(function() {
				$(\'input[value="Preview"]\').click().hide();
			});
			</script>';
}
echo "<style type=\"text/css\" media=\"print\">.hideonprint {display:none;} p.tips {display: none;}\n input.btn {display: none;}\n textarea {display: none;}\n input.sabtn {display: none;} .question, .review {background-color:#fff;}</style>\n";
echo "<style type=\"text/css\">p.tips {	display: none;}\n </style>\n";
echo '<script type="text/javascript">function rendersa() { ';
echo '  el = document.getElementsByTagName("span"); ';
echo '   for (var i=0;i<el.length;i++) {';
echo '     if (el[i].className=="hidden") { ';
echo '         el[i].className = "shown";';
//echo '		 AMprocessNode(el)';
echo '     }';
echo '    }';
echo '} </script>';
?>
<div class="tab-content shadowBox course-page-setting padding-right-twenty">
    <?php echo $temp;
    ?>
    </div>

