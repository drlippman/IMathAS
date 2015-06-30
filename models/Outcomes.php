<?php
/**
 * Created by PhpStorm.
 * User: tudip
 * Date: 29/6/15
 * Time: 5:35 PM
 */

namespace app\models;


use app\components\AppUtility;
use app\models\_base\BaseImasOutcomes;

class Outcomes extends BaseImasOutcomes {

    public  function SaveOutcomes($courseid,$outcome)
    {

            $this->name =$outcome;
            $this->courseid = $courseid;
            $this->save();

    }

} 