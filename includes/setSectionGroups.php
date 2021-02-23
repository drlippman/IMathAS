<?php 

function setSectionGroups($userid, $courseid, $section) {
    global $DBH;

    if ($section === null || trim($section) === '') {
        $section = _('(No section)');
    } else {
        $section = Sanitize::simpleString(trim($section));
    }
    $query = "SELECT igs.id AS igsid,ig.id,ig.name,im.userid FROM imas_stugroupset AS igs
        LEFT JOIN imas_stugroups AS ig ON igs.id=ig.groupsetid
        LEFT JOIN imas_stugroupmembers AS im ON ig.id=im.stugroupid AND im.userid=?
        WHERE igs.courseid=? AND igs.name='##autobysection##'";
    $stm = $DBH->prepare($query);
    $stm->execute(array($userid, $courseid));
    $added = false;
    $groupsetid = 0;
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $groupsetid = $row['igsid'];
        if ($row['userid'] == $userid && $row['name'] !== $section) {
            // delete
            $stm2 = $DBH->prepare("DELETE FROM imas_stugroupmembers WHERE stugroupid=? AND userid=?");
            $stm2->execute(array($row['id'], $userid));
        } else if ($row['userid'] === null && $row['name'] === $section) {
            // group exists; add
            $stm2 = $DBH->prepare("INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES (?,?)");
            $stm2->execute(array($row['id'], $userid));
            $added = true;
        } else if ($row['userid'] == $userid && $row['name'] === $section) {
            // already in group; do nothing 
            $added = true;
        }
    }
    if (!$added && $groupsetid > 0) {
        // not added, so group must not exist; create group and add user
        $stm2 = $DBH->prepare('INSERT INTO imas_stugroups (groupsetid,name) VALUES (?,?)');
        $stm2->execute(array($groupsetid, $section));
        $groupid = $DBH->lastInsertId();
        $stm2 = $DBH->prepare("INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES (?,?)");
        $stm2->execute(array($groupid, $userid));
    }
}

function createSectionGroupset($courseid) {
    global $DBH;

    // want to use by-section groups.  Double-check one doesn't exist already 
    $stm = $DBH->prepare('SELECT id FROM imas_stugroupset WHERE courseid=? AND name=?');
    $stm->execute(array($courseid, '##autobysection##'));
    $groupsetid = $stm->fetchColumn(0);
    if ($groupsetid !== false) {
        return $groupsetid;
    }
    // none found; create
    $stm = $DBH->prepare('INSERT INTO imas_stugroupset (courseid,name) VALUES (?,?)');
    $stm->execute(array($courseid, '##autobysection##'));
    $groupsetid = $DBH->lastInsertId();

    // now we need to create groups and add students
    $stm = $DBH->prepare('SELECT userid,section FROM imas_students WHERE courseid=? ORDER BY section');
    $stm->execute(array($courseid));
    $lastsection = null;
    $qarr = [];
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($row['section'] === null || trim($row['section']) === '') {
            $section = _('(No section)');
        } else {
            $section = Sanitize::simpleString(trim($row['section']));
        }
        if ($lastsection === null || $section != $lastsection) {
            // if we have students lined up, add them as members
            if (count($qarr) > 0) {
                $ph = Sanitize::generateQueryPlaceholdersGrouped($qarr, 2);
                $stm2 = $DBH->prepare("INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES $ph");
                $stm2->execute($qarr);
                $qarr = []; 
            }
            // now create a new group for this section
            $stm2 = $DBH->prepare('INSERT INTO imas_stugroups (groupsetid,name) VALUES (?,?)');
            $stm2->execute(array($groupsetid, $section));
            $groupid = $DBH->lastInsertId();
            
            $lastsection = $section;
        }
        // line up student for add
        array_push($qarr, $groupid, $row['userid']);
    }
    // if we have students lined up, add them as members
    if (count($qarr) > 0) {
        $ph = Sanitize::generateQueryPlaceholdersGrouped($qarr, 2);
        $stm2 = $DBH->prepare("INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES $ph");
        $stm2->execute($qarr);
    }

    return $groupsetid;
}

function sendMsgOnEnroll($msgset,$cid,$userid) {
    global $DBH;
    $msgOnEnroll = ((floor($msgset/5)&2) > 0);
    if ($msgOnEnroll) {
        $stm_nmsg = $DBH->prepare("INSERT INTO imas_msgs (courseid,title,message,msgto,msgfrom,senddate,deleted) VALUES (:cid,:title,:message,:msgto,:msgfrom,:senddate,1)");
        $stm = $DBH->prepare("SELECT userid FROM imas_teachers WHERE courseid=:cid");
        $stm->execute(array(':cid'=>$cid));
        while ($tuid = $stm->fetchColumn(0)) {
            $stm_nmsg->execute(array(':cid'=>$cid,':title'=>_('Automated new enrollment notice'),
                ':message'=>_('This is an automated system message letting you know this student just enrolled in your course'),
                ':msgto'=>$tuid, ':msgfrom'=>$userid, ':senddate'=>time()));
        }
    }
}
