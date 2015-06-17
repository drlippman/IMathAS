<?php

namespace app\controllers\instructor;

use app\components\AppConstant;
use app\components\AppUtility;
use app\models\Course;
use app\models\Message;
use Yii;
use app\controllers\AppController;


class InstructorController extends AppController
{

    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $this->guestUserHandler();
        $user = $this->getAuthenticatedUser();
        $cid = $this->getParamVal('cid');
        $course = Course::getById($cid);
        $message = Message::getByCourseIdAndUserId($cid, $user->id);
        $isreadArray = array(0, 4, 8, 12);
        $msgList = array();
        if($message){
            foreach($message as $singleMessage){
                if(in_array($singleMessage->isread, $isreadArray))
                array_push($msgList,$singleMessage);
            }
        }
        $this->includeJS(['../js/course.js']);
        $this->includeCSS(['../css/_leftSide.css']);
        return $this->render('index', ['course' => $course, 'messageList' => $msgList]);
    }
}