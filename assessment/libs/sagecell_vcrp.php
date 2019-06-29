<?php
//SageCell extension

global $allowedmacros;
array_push($allowedmacros,"sagecell", "sagecellButton" );

function sagecell($script="",$q=0,$essayPart="none",$id="sagecell") {
$cid='sagecell_vcrp_'.$id;
$fns="<script>function getSageOutput_$id (part=-1,format='any') {
    var ref=$q-1;
    if (part >= 0)
      ref=$q*1000+part;
    var iframe=$(\"#\"+'$cid').find('.sagecellframe')[0];
    var output=iframe.contentWindow.document.getElementsByClassName('sagecell_sessionOutput');
    if (output.length) {
      var sageOutput=output[0].textContent;
      if (format == 'list') {
        var start=sageOutput.indexOf('[');
        var end=sageOutput.lastIndexOf(']');
        if (start > -1 && end > -1)
          sageOutput=sageOutput.substring(start+1,end);
      }
      if (format == 'matrix') {
        sageOutput=sageOutput.replace(/ /g,',');
        sageOutput=sageOutput.replace(/\[/g,'(');
        sageOutput=sageOutput.replace(/\]/g,')');
        sageOutput=sageOutput.replace(/\)\s*\(/g,'),(');
        sageOutput='['+sageOutput+']';
      }
	    $('input[id=\'tc'+ref+'\'],input[id=\'qn'+ref+'\']').val(sageOutput);
    } else {
      alert('Please evaluate cell');
    }
  }
</script>";
if ($essayPart === 'none') {
  return "<div id='$cid' class='sagetransfer'><pre class='converttosagecell'>".$script."</pre>".$fns."</div>";
} else {
  $ansPart = ($essayPart === "essay" ? '[AB]' : '[AB'.strval($essayPart).']');

	return "<div id='$cid' class='sagetransfer'><div class='converttosagecell'>$ansPart<pre class='hidden'>$script</pre></div>$fns</div>";
}

}

function sagecellButton($label="Transfer",$part="none",$format='any',$id="sagecell") {
  switch (strval($part)) {
    case "none":
      $parts=-1;
      break;
    case "essay":
      $parts=-2;
      break;
    default:
      $parts=$part;
  }
  return "<input type='button' value='$label' onClick='getSageOutput_$id($parts,\"$format\")'>";
}

?>