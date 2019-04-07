<?php
//SageCell extension

global $allowedmacros;
array_push($allowedmacros,"sagecell", "sagecellTransfer");

function sagecell($script) {
	return "<pre class='converttosagecell'>".$script."</pre>";
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
      alert('Please evaluate cell first');
    }
  }
</script>";
$btn="<input type='button' value='$label' onClick='getSageOutput_$ref(this)'>";

return "<div class='sagetransfer'>".sagecell($script).$fns.$btn."</div>";
}
?>