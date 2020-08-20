<?php

/**
 * This class models local course data
 *
 */
namespace IMSGlobal\LTI;

class LTI_Localcourse {

    private $id; // imas_lti_courses.id
    private $courseid;  //imas_courses.id
    private $copiedfrom = 0;
    private $UIver = null;
    private $dates_by_lti = 0;
    private $allow_direct_login = false;

    public function __construct(array $values=array()) {
        foreach ($values as $k=>$v) {
            if (property_exists($this, $k)) {
            $this->$k = $v;
            }
        }
    }

    public static function new(array $values=array()) {
        return new LTI_Localcourse($values);
    }

    public function get_id() {
        return $this->id;
    }

    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function get_courseid() {
        return $this->courseid;
    }

    public function set_courseid($courseid) {
        $this->courseid = intval($courseid);
        return $this;
    }

    public function get_copiedfrom() {
        return $this->copiedfrom;
    }

    public function set_copiedfrom($copiedfrom) {
        $this->copiedfrom = intval($copiedfrom);
        return $this;
    }

    public function get_UIver() {
        return $this->UIver;
    }

    public function set_UIver($UIver) {
        $this->UIver = $UIver;
        return $this;
    }

    public function get_dates_by_lti() {
        return $this->dates_by_lti;
    }

    public function set_dates_by_lti($dates_by_lti) {
        $this->dates_by_lti = $dates_by_lti;
        return $this;
    }
    public function get_allow_direct_login() {
        return $this->allow_direct_login;
    }

    public function set_allow_direct_login($allow_direct_login) {
        $this->allow_direct_login = $allow_direct_login;
        return $this;
    }

}
