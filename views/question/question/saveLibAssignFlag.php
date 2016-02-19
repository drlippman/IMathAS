<?php
use app\components\AppConstant;

if ($isChanged) {
    echo AppConstant::OK;
} else {
    echo AppConstant::ERROR;
}