<?php

use \yii\base\Component;
use app\models\Links;
use \app\models\ExternalTools;
use \app\models\User;
use app\components\AppUtility;

require_once("../filter/basiclti/blti_util");
class Post extends Component
{
    public static function externalTool($linkId, $courseId, $groupId, $userId){

        $imasRoot = AppUtility::getHomeURL();
        $linkData = Links::getPoints($linkId);
        $text = $linkData['text'];
        $title = $linkData['title'];
        $points = $linkData['points'];
        $toolParts = explode('~~',substr($text,8));
        $tool = $toolParts[0];
        $linkCustom = $toolParts[1];

        if (isset($toolParts[2]) && $toolParts[2]!="") {
            $toolCustomUrl = $toolParts[2];
        } else {
            $toolCustomUrl = '';
        }
        if (isset($toolParts[3])) {
            $gbCat = $toolParts[3];
            $cntingb = $toolParts[4];
            $tutoredit = $toolParts[5];
            $gradesecret = $toolParts[6];
        }
        $tool = intval($tool);

        $externalToolData = ExternalTools::getToolData($tool, $courseId, $groupId);
        if(count($externalToolData) == 0)
        {
            return array('status'=> false, 'message'=>"Invalid tool.");
        }

        $params = array();

        if (trim($externalToolData['custom'])!='') {
            $toolcustarr = explode('&',$externalToolData['custom']);
            foreach ($toolcustarr as $custbit) {
                $pt = explode('=',$custbit);
                if (count($pt) == 2 && trim($pt[0]) != '' && trim($pt[1]) != '') {
                    $pt[0] = map_keyname($pt[0]);
                    $params['custom_'.$pt[0]] = str_replace(array('$courseId','$userId','$linkId'),array($courseId,$userId,intval($_GET['id'])),$pt[1]);
                }
            }
        }

        if (trim($linkCustom)!='') {
            $toolcustarr = explode('&',$linkCustom);
            foreach ($toolcustarr as $custbit) {
                $pt = explode('=',$custbit);
                if (count($pt)==2 && trim($pt[0])!='' && trim($pt[1])!='') {
                    $pt[0] = map_keyname($pt[0]);
                    $params['custom_'.$pt[0]] = str_replace(array('$courseId','$userId','$linkId'),array($courseId,$userId,intval($_GET['id'])),$pt[1]);
                }
            }
        }

        $userData = User::getById($userId);
        $firstName = $userData['FirstName'];
        $lastName = $userData['LastName'];
        $email = $userData['email'];

        $params['user_id'] = $userId;
        if (($externalToolData['privacy']&1)==1) {
            $params['lis_person_name_full'] = "$firstName $lastName";
            $params['lis_person_name_family'] = $lastName;
            $params['lis_person_name_given'] = $firstName;
        }

        if (($externalToolData['privacy']&2) == 2) {
            $params['lis_person_contact_email_primary'] = $email;
        }
        if (isset($teacherid)) {
            $params['roles'] = 'Instructor';
        } else {
            $params['roles'] = 'Learner';
        }

        $params['context_id'] = $courseId;
//        $params['context_title'] = trim($courseName);
//        $params['context_label'] = trim($courseName);
        $params['context_type'] = 'CourseSection';
        $params['resource_link_id'] = $courseId.'-'.$_GET['id'];

        if ($points>0 && isset($studentid) && !isset($sessiondata['stuview'])) {
            $sig = sha1($gradesecret.'::'.$params['resource_link_id'].'::'.$userId);
            $params['lis_result_sourcedid'] = $sig.'::'.$params['resource_link_id'].'::'.$userId;
            $params['lis_outcome_service_url'] = $imasRoot . '/admin/ltioutcomeservice.php';
        }

        $params['resource_link_title'] = $title;
        $params['tool_consumer_info_product_family_code'] = 'IMathAS';
        $params['tool_consumer_info_version'] = 'LTI 1.0';
        if (isset($CFG['GEN']['locale'])) {
            $params['launch_presentation_locale'] = $CFG['GEN']['locale'];
        } else {
            $params['launch_presentation_locale'] = 'en-US';
        }
        if ($_GET['target']=='new') {
            $params['launch_presentation_document_target'] = 'window';
        } else {
            $params['launch_presentation_document_target'] = 'iframe';
            $params['launch_presentation_height'] = '500';
            $params['launch_presentation_width'] = '600';
        }

        $params['launch_presentation_return_url'] = $imasRoot . '/course/course?cid=' . $courseId;
        if (isset($CFG['GEN']['LTIorgid'])) {
            $org_id = $CFG['GEN']['LTIorgid'];
        } else {
            $org_id = $_SERVER['HTTP_HOST'];
        }

        $org_desc = 'IMathAS';

        if ($toolCustomUrl!='') {
            $externalToolData['url'] = $toolCustomUrl;
        }

        if ($externalToolData['url']=='') {
            echo '<html><body>This tool does not have a default launch URL.  Custom launch URL is required.</body></html>';
            exit;
        }

        try {
            $params = signParameters($params, $externalToolData['url'], "POST", $externalToolData['ltikey'], $externalToolData['secret'], null, $org_id, $org_desc);
            $content = postLaunchHTML($params, $externalToolData['url'],isset($params['custom_debug']));
            print($content);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}