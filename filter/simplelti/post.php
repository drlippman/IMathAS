<?php

echo '
<html>
<head>
<script language="javascript">
    function go() {
        document.ltiLaunchForm.submit();
    }
</script>
</head>
<body onLoad="go()">
	<div>If you are not redirected in 15 seconds press Continue.</div>
	<form action="'.$_GET['url'].'" name="ltiLaunchForm" 
          method="post"> 
		
		 <input type="hidden" size="40" name="action" value="direct"/> 
		 <input type="hidden" size="40" name="sec_nonce" 
		 		value="'.$_GET['sec_nonce'].'"/> 
		 <input type="hidden" size="40" name="sec_created"  
             	value="'.$_GET['sec_created'].'"/> 
		 <input type="hidden" size="40" name="sec_digest"  
            	value="'.$_GET['sec_digest'].'"/>
		 
		 <input type="hidden" size="40" name="user_id" 
		 		value="'.$_GET['user_id'].'"/> 
		 <input type="hidden" size="40" name="user_role" 
		 		value="'.$_GET['user_role'].'"/> 
		 <input type="hidden" size="40" name="course_id" 
		 		value="'.$_GET['course_id'].'"/>  
		

		 <input type="submit" value="Continue">   
	</form>
</body>
<html>';
	
?>