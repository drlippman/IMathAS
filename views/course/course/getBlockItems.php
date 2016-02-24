<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 24/2/16
 * Time: 7:45 PM
 */
if (count($items) > 0) {

}else if ($teacherId) {
    echo $courseData->generateAddItem($folder,'b',1);
}
if (($isTutor) && ($sessionData['ltiitemtype']) && $sessionData['ltiitemtype']==3)
{
    echo '<script type="text/javascript">$(".instrdates").hide();</script>';
}