<?php
// User search and other utilities
// IMathAS (c) 2018 David Lippman

function searchForUser($searchterm, $limitToTeacher=true, $basicsort=false) {
    global $DBH;

    $words = array();
    $possible_users = array();
    $hasp1 = false;
    $searchterm = trim($searchterm);
    if (strpos($searchterm, ',') !== false) {
      // comma: only split on commas; spaces within names are kept
      $words = array();
      foreach (explode(',', $searchterm) as $part) {
        $part = trim(preg_replace('/\s+/', ' ', $part));
        if ($part !== '') {
          $words[] = $part;
        }
      }
      if (count($words) == 0) {
        $words = array('');
      }
    } else {
      $words = preg_split('/\s+/', $searchterm);
    }
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
      // build candidate [part1, part2] splits of the search term.
      // Names may contain spaces, so without a comma try every split point.
      $pairs = array();
      if (strpos($searchterm, ',') !== false) {
        $pairs[] = array($words[0], implode(' ', array_slice($words, 1)));
      } else {
        for ($k = 1; $k < count($words); $k++) {
          $pairs[] = array(implode(' ', array_slice($words, 0, $k)),
                           implode(' ', array_slice($words, $k)));
        }
      }
      $conds = array();
      $qarr = array();
      $matchterms = array();
      foreach ($pairs as $pair) {
        $t0 = (substr($pair[0],-1) == '%') ? 'LIKE ?' : '= ?';
        $t1 = (substr($pair[1],-1) == '%') ? 'LIKE ?' : '= ?';
        $conds[] = "(iu.LastName $t0 AND iu.FirstName $t1)";
        $conds[] = "(iu.LastName $t1 AND iu.FirstName $t0)";
        array_push($qarr, $pair[0], $pair[1], $pair[1], $pair[0]);
        $matchterms[] = strtolower($pair[0]);
        $matchterms[] = strtolower($pair[1]);
      }
      $query = "SELECT iu.id,LastName,iu.FirstName,iu.email,iu.SID,iu.rights,ig.name FROM imas_users AS iu LEFT JOIN imas_groups AS ig ON iu.groupid=ig.id ";
      $query .= "WHERE (" . implode(' OR ', $conds) . ")";
      if ($limitToTeacher) {
        $query .= " AND iu.rights>19";
      }
      $query .= " LIMIT 200";

      $stm = $DBH->prepare($query);
      $stm->execute($qarr);
      $possible_users = array();
      while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($row['rights']==11 || $row['rights']==76 || $row['rights']==77) {continue;} //skip LTI creds
        $row['priority'] = 0;
        if (in_array(strtolower($row['LastName']), $matchterms)) {
          $hasp1 = true;
          $row['priority'] += 1;
        }
        if (in_array(strtolower($row['FirstName']), $matchterms)) {
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
	require_once __DIR__ . '/../assess2/AssessClaim.php';
	AssessClaim::release();
	$_SESSION = array();
	if (isset($_COOKIE[session_name()])) {
		setsecurecookie(session_name(), '', time()-42000, true);
	}
	session_destroy();
}
