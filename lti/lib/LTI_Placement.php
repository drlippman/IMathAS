<?php

/**
 * This class models placement records, representing a LTI resourcelink target
 *
 */
namespace IMSGlobal\LTI;

class LTI_Placement {

    private $placementtype;
    private $typeid;
    private $typenum;
    private $date_by_lti = null;
    private $startdate = null;
    private $enddate = null;

    public static function new() {
        return new LTI_Placement();
    }

    public function get_placementtype() {
        return $this->placementtype;
    }

    public function set_placementtype($placementtype) {
        $this->placementtype = $placementtype;
        return $this;
    }

    public function get_typeid() {
        return $this->typeid;
    }

    public function set_typeid($typeid) {
        $this->typeid = $typeid;
        return $this;
    }

    public function get_typenum() {
        return $this->typenum;
    }

    public function set_typenum($typenum) {
        $this->typenum = $typenum;
        return $this;
    }

    public function get_date_by_lti() {
        return $this->date_by_lti;
    }

    public function set_date_by_lti($date_by_lti) {
        $this->date_by_lti = $date_by_lti;
        return $this;
    }

    public function get_startdate() {
        return $this->startdate;
    }

    public function set_startdate($startdate) {
        $this->startdate = $startdate;
        return $this;
    }
    public function get_enddate() {
        return $this->enddate;
    }

    public function set_enddate($enddate) {
        $this->enddate = $enddate;
        return $this;
    }

}

?>
