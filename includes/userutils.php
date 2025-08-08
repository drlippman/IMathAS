<?php
// User search and other utilities
// IMathAS (c) 2018 David Lippman

function searchForUser($searchterm, $limitToTeacher=true, $basicsort=false) {
    global $DBH;

    $words = array();
    $possible_users = array();
    $hasp1 = false;
    $words = preg_split('/\s+/', str_replace(',',' ',trim($searchterm)));
    if (count($words)==1 && strpos($words[0],'@')!==false) {
      $query = "SELECT iu.id,LastName,iu.FirstName,iu.email,iu.SID,iu.rights,ig.name FROM imas_users AS iu LEFT JOIN imas_groups AS ig ON iu.groupid=ig.id ";
      $query .= "WHERE (iu.email=? OR iu.SID=?)";
      if ($limitToTeacher) {
        $query .= " AND iu.rights>19";
      }
      $query .= " LIMIT 200";
      $stm = $DBH->prepare($query);
      $stm->execute(array($words[0], $words[0]));
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($row['rights']==11 || $row['rights']==76 || $row['rights']==77) {continue;} //skip LTI creds
        if ($row['name']==null) {$row['name'] = _('Default');}
        $row['priority'] = 0;
        $possible_users[] = $row;
      }
    } else if (count($words)==1) {
      if (substr($words[0],-1) == '%') {
        $wd0type = 'LIKE ?';
      } else {
        $wd0type = '= ?';
      }
      $query = "SELECT iu.id,LastName,iu.FirstName,iu.email,iu.SID,iu.rights,ig.name FROM imas_users AS iu LEFT JOIN imas_groups AS ig ON iu.groupid=ig.id ";
      $query .= "WHERE (iu.LastName $wd0type OR iu.FirstName $wd0type OR iu.SID $wd0type)";
      if ($limitToTeacher) {
        $query .= " AND iu.rights>19";
      }
      $query .= " LIMIT 200";
      $stm = $DBH->prepare($query);
      $stm->execute(array($words[0], $words[0], $words[0]));
      $words[0] = strtolower($words[0]);
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($row['rights']==11 || $row['rights']==76 || $row['rights']==77) {continue;} //skip LTI creds
        if (strtolower($row['SID'])==$words[0] || strtolower($row['LastName'])==$words[0]) {
          $row['priority'] = 1;
          $hasp1 = true;
        } else {
          $row['priority'] = 0;
        }
        if ($row['name']==null) {$row['name'] = _('Default');}
        $possible_users[] = $row;
      }
    } else if (count($words)>1) {
      if (substr($words[0],-1) == '%') {
        $wd0type = 'LIKE ?';
      } else {
        $wd0type = '= ?';
      }
      if (substr($words[1],-1) == '%') {
        $wd1type = 'LIKE ?';
      } else {
        $wd1type = '= ?';
      }
      $query = "SELECT iu.id,LastName,iu.FirstName,iu.email,iu.SID,iu.rights,ig.name FROM imas_users AS iu LEFT JOIN imas_groups AS ig ON iu.groupid=ig.id ";
      $query .= "WHERE ((iu.LastName $wd0type AND iu.FirstName $wd1type) OR (iu.LastName $wd1type AND iu.FirstName $wd0type))";
      if ($limitToTeacher) {
        $query .= " AND iu.rights>19";
      }
      $query .= " LIMIT 200";

      $stm = $DBH->prepare($query);
      $stm->execute(array($words[0], $words[1], $words[1], $words[0]));
      $possible_users = array();
      $words[0] = strtolower($words[0]);
      $words[1] = strtolower($words[1]);
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($row['rights']==11 || $row['rights']==76 || $row['rights']==77) {continue;} //skip LTI creds
        $row['priority'] = 0;
        if (strtolower($row['LastName'])==$words[0] || strtolower($row['LastName'])==$words[1]) {
          $hasp1 = true;
          $row['priority'] += 1;
        }
        if (strtolower($row['FirstName'])==$words[0] || strtolower($row['FirstName'])==$words[1]) {
          $hasp1 = true;
          $row['priority'] += 1;
        }
        if ($row['name']==null) {$row['name'] = _('Default');}
        $possible_users[] = $row;
      }
    }
    usort($possible_users, function($a,$b) use ($basicsort) {
      if ($a['priority']!=$b['priority'] && !$basicsort) {
        return $b['priority']-$a['priority'];
      } else if ($a['LastName']!=$b['LastName']) {
        return strcasecmp($a['LastName'],$b['LastName']);
      } else {
        return strcasecmp($a['FirstName'],$b['FirstName']);
      }
    });
    return $possible_users;
}

function logout() {
	$_SESSION = array();
	if (isset($_COOKIE[session_name()])) {
		setcookie(session_name(), '', time()-42000, '/', '', false, true);
	}
	session_destroy();
}
