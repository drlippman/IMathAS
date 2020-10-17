<?php
namespace IMSGlobal\LTI;

class Cache {

    private $cache;
    private $dbh;

    function __construct() {
      // hacky, but oh well
      $this->dbh = $GLOBALS['DBH'];
    }

    public function get_launch_data($key) {
      if (isset($_SESSION['lticache'][$key])) {
        return $_SESSION['lticache'][$key];
      } else {
        return false;
      }
    }

    public function cache_launch_data($key, $jwt_body) {
      if (!isset($_SESSION['lticache'])) {
        $_SESSION['lticache'] = array();
      }
      $_SESSION['lticache'][$key] = $jwt_body;
      return $this;
    }

    public function cache_nonce($nonce) {
      $stm = $this->dbh->prepare('INSERT INTO imas_ltinonces (nonce,time) VALUES (?,?)');
      $stm->execute(array($nonce, time()));

      // delete old
      if (rand(1,100) == 1) { // don't need to run every time; run with 1% probability
        $old = time() - 5400; //old stuff - 90 minutes
        $stm = $this->dbh->prepare("DELETE FROM imas_ltinonces WHERE time<?");
        $stm->execute(array($old));
      }
      return $this;
    }

    public function check_nonce($nonce) {
      $stm = $this->dbh->prepare('SELECT id FROM imas_ltinonces WHERE nonce=?');
      $stm->execute(array($nonce));
      if ($stm->rowCount()>0) {
        return true;
      } else {
        return false;
      }
    }
}
?>
