<?php
//SageCell extension

global $allowedmacros;
array_push($allowedmacros,"sagecell", "sagecellEssay", "sagecellTransfer", "sagecellTransferMulti" ,"sagecellEssayTransfer","sagecellEssayTransferMulti" );

function sagecell($script) {
	return "<pre class='converttosagecell'>".$script."</pre>";
}

function sagecellEssay($script,$essayPart=-1) {
  $ansPart = $essayPart < 0 ? '[AB]' : '[AB'.$essayPart.']';
	return '<div class="converttosagecell">'."$ansPart".'<pre class="hidden">'.$script.'</pre></div>';
}

function sagecellTransfer($script,$q,$label="Transfer",$part=-1) {
if ($part < 0) {
	$ref=$q-1;
} else {
	$ref=$q*1000+$part;
}
$fns="<script>function getSageOutput_$ref (el) {
    var iframe=$(el).closest('.sagetransfer').find('.sagecellframe')[0];
    var output=iframe.contentWindow.document.getElementsByClassName('sagecell_sessionOutput');
    if (output.length) {
	$('input[id=\'tc$ref\'],input[id=\'qn$ref\'').val(output[0].textContent);
    } else {
      alert('Please evaluate cell');
    }
  }
</script>";
$btn="<input type='button' value='$label' onClick='getSageOutput_$ref(this)'>";

return "<div class='sagetransfer'>".sagecell($script).$fns.$btn."</div>";
}

function sagecellTransferMulti($script,$q,$labels,$parts) {
$fns=[];
$btns=[];
for ($i=0;$i<count($parts);$i++) {
  $ref=$q*1000+$parts[$i];
  $fns[]="function getSageOutput_$ref (el) {
    var iframe=$(el).closest('.sagetransfer').find('.sagecellframe')[0];
    var output=iframe.contentWindow.document.getElementsByClassName('sagecell_sessionOutput');
    if (output.length) {
	$('input[id=\'tc$ref\'],input[id=\'qn$ref\'').val(output[0].textContent);
    } else {
      alert('Please evaluate cell');
    }
  }";
  $label="Use Output as Answer to Part ".$parts[$i];
  if ($labels[$i]) {
    $label=$labels[$i];
  }
  $btns[]="<input type='button' value='$label' onClick='getSageOutput_$ref(this)'>";
}
$f=implode("\n",$fns);
$b=implode("\n",$btns);
return "<div class='sagetransfer'>".sagecell($script)."<script>".$f."</script>\n".$b."</div>";
}

function sagecellEssayTransfer($script,$q,$essayPart,$part,$label="Transfer") {
  $ref=$q*1000+$part;
  $fns="<script>function getSageOutput_$ref (el) {
    var iframe=$(el).closest('.sagetransfer').find('.sagecellframe')[0];
    var output=iframe.contentWindow.document.getElementsByClassName('sagecell_sessionOutput');
    if (output.length) {
	$('input[id=\'tc$ref\'],input[id=\'qn$ref\'').val(output[0].textContent);
    } else {
      alert('Please evaluate cell first');
    }
  }
</script>";
$btn="<input type='button' value='$label' onClick='getSageOutput_$ref(this)'>";

return "<div class='sagetransfer'>".sagecellEssay($script,$essayPart).$fns.$btn."</div>";
}

function sagecellEssayTransferMulti($script,$q,$essayPart, $parts,$labels) {
$fns=[];
$btns=[];
for ($i=0;$i<count($parts);$i++) {
  $ref=$q*1000+$parts[$i];
  $fns[]="function getSageOutput_$ref (el) {
    var iframe=$(el).closest('.sagetransfer').find('.sagecellframe')[0];
    var output=iframe.contentWindow.document.getElementsByClassName('sagecell_sessionOutput');
    if (output.length) {
	$('input[id=\'tc$ref\'],input[id=\'qn$ref\'').val(output[0].textContent);
    } else {
      alert('Please evaluate cell');
    }
  }";
  $label="Use Output as Answer to Part ".$parts[$i];
  if ($labels[$i]) {
    $label=$labels[$i];
  }
  $btns[]="<input type='button' value='$label' onClick='getSageOutput_$ref(this)'>";
}
$f=implode("\n",$fns);
$b=implode("\n",$btns);
return "<div class='sagetransfer'>".sagecellEssay($script,$essayPart)."<script>".$f."</script>\n".$b."</div>";
}

?>