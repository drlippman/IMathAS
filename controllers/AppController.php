<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 13/4/15
 * Time: 8:32 PM
 */

namespace app\controllers;

use app\components\AppConstant;
use yii\web\Controller;


class AppController extends Controller
{

    function getBodyParams()
    {
        return $_POST;
//        return \Yii::$app->request->getBodyParams();
    }

    function setSuccessFlash($message)
    {
        $this->_setFlash('success', $message);
    }

    function setErrorFlash($message)
    {
        $this->_setFlash('danger', $message);
    }

    function setWarningFlash($message)
    {
        $this->_setFlash('warning', $message);
    }

    function unauthorizedAccessHandler()
    {
        if (!$this->isGuestUser()) {
            return $this->goHome();
            exit;
        }
    }

    function isGuestUser(){
        return \Yii::$app->user->isGuest;
    }

    function getUserTimezone(){
        return AppConstant::DEFAULT_TIME_ZONE;
    }

    function includeCSS($cssFileArray){
        for($i=0; $i<count($cssFileArray); $i++){
            $this->getView()->registerCssFile("../" . $cssFileArray[$i]);
        }
    }

    function includeJS($jsFileArray){
        for($i=0; $i<count($jsFileArray); $i++){
            $this->getView()->registerJsFile("../" . $jsFileArray[$i]);
        }
    }

    function getAuthenticatedUser(){
        return \Yii::$app->user->identity;
    }

    private function _setFlash($type, $message)
    {
        \Yii::$app->session->setFlash($type, $message);
    }

}