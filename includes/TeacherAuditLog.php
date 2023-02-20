<?php
/**
 * Repo iMathAS: TeacherAuditLog
 */

class TeacherAuditLog
{
    const STUDENTS = [10];
    const TEACHERS = [20,40,75,100];
    const ACTIONS = [
        "Assessment Settings Change",
        "Mass Assessment Settings Change",
        "Mass Date Change",
        "Question Settings Change",
        "Clear Attempts",
        "Clear Scores",
        "Delete Item",
        "Unenroll",
        "Change Grades",
        "Course Settings Change",
        "Inlinetext Settings Change",
        "Link Settings Change",
        "Forum Settings Change",
        "Mass Forum Settings Change",
        "Block Settings Change",
        "Mass Block Settings Change",
        "Wiki Settings Change",
        "Drill Settings Change",
        "Gradebook Settings Change",
        "Roster Action",
        "Exception Change",
        "Delete Post",
        "Offline Grade Settings Change",
        "Change Offline Grades",
        "Change Forum Grades",
        "Change External Tool Grades"
    ];

    public static function addTracking($courseid, $action, $itemid = null, $metadata = array(), ?PDO $dbhOverride = null)
    {
        $dbh = is_null($dbhOverride) ? $GLOBALS['DBH'] : $dbhOverride;
        if (!in_array($action, self::ACTIONS)) {
            //log exception
            return false;
        }
        //always include calling file as source to metadata
        $metadata = ['source'=>parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)]+$metadata;

        $query = "INSERT INTO imas_teacher_audit_log (userid,courseid,action,itemid,metadata) VALUES "
            . "(:userid, :courseid, :action, :itemid, :metadata)";
        $stm = $GLOBALS['DBH']->prepare($query);
        return $stm->execute(array(
            ':userid'=>$GLOBALS['userid'],
            ':courseid'=>$courseid,
            ':action'=>$action,
            ':itemid'=>$itemid,
            ':metadata' => json_encode($metadata)
        ));
    }
    public static function findActionsByCourse($cid, ?PDO $dbhOverride = null): array
    {
        $dbh = is_null($dbhOverride) ? $GLOBALS['DBH'] : $dbhOverride;
        $query = "SELECT id, userid, courseid, action, itemid, metadata, UNIX_TIMESTAMP(created_at) AS created_at FROM imas_teacher_audit_log "
            . "WHERE courseid=? ORDER BY created_at DESC";
        $stm = $GLOBALS['DBH']->prepare($query);
        $stm->execute([$cid]);
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }
    public static function findCourseItemAction($cid, $itemid, $action)
    {
        $query = "SELECT id, userid, courseid, action, itemid, metadata, UNIX_TIMESTAMP(created_at) AS created_at FROM imas_teacher_audit_log "
            . "WHERE courseid=? AND itemid=? AND action=? ORDER BY created_at DESC";
        $stm = $GLOBALS['DBH']->prepare($query);
        $stm->execute([
            $cid,
            $itemid,
            $action
        ]);
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }
    public static function findCourseAction($cid, $action, ?PDO $dbhOverride = null): array
    {
        $dbh = is_null($dbhOverride) ? $GLOBALS['DBH'] : $dbhOverride;
        $query = "SELECT id, userid, courseid, action, itemid, metadata, UNIX_TIMESTAMP(created_at) AS created_at FROM imas_teacher_audit_log "
            . "WHERE courseid=? AND action=? ORDER BY created_at DESC";
        $stm = $GLOBALS['DBH']->prepare($query);
        $stm->execute([
            $cid,
            $action
        ]);
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }
    public static function countActionsByCourse(array $cid, array $actions, ?PDO $dbhOverride = null): array
    {
        $dbh = is_null($dbhOverride) ? $GLOBALS['DBH'] : $dbhOverride;

        $ph1 = \Sanitize::generateQueryPlaceholders($cid);
        $ph2 = \Sanitize::generateQueryPlaceholders($actions);
        $query = "SELECT courseid, action, UNIX_TIMESTAMP(created_at) AS created_at, count(action) as itemcount FROM imas_teacher_audit_log "
            . "WHERE courseid in ($ph1) AND action in ($ph2) GROUP BY courseid, action ORDER BY created_at DESC";
        $stm = $dbh->prepare($query);
        $stm->execute(array_merge($cid,$actions));

        $courses = array();
        while ($row = $stm->fetch(\PDO::FETCH_ASSOC)) {
            $courses[$row['courseid']]['courseid'] = $row['courseid'];
            if ($row['created_at'] > $courses[$row['courseid']]['lastactivity']) {
                $courses[$row['courseid']]['lastactivity'] = $row['created_at'];
            }
            $action = substr($row['action'], strpos($row['action'], " ") + 1);
            $courses[$row['courseid']][$action] = $row['itemcount'];
        }
        return $courses;
    }
    public static function countActionsByTeacher(
        array $actions,
        DateTime $startTimestamp,
        DateTime $endTimestamp,
        ?array $teacher = null,
        ?PDO $dbhOverride = null
    ): array
    {
        $dbh = is_null($dbhOverride) ? $GLOBALS['DBH'] : $dbhOverride;

        $ph = \Sanitize::generateQueryPlaceholders($actions);
        $query = "SELECT g.name, u.FirstName, u.LastName, l.userid, l.action, count(l.action) as itemcount
            FROM imas_teacher_audit_log as l JOIN imas_users as u ON l.userid = u.id
            LEFT JOIN imas_groups AS g ON u.groupid=g.id
            WHERE l.action in ($ph) AND l.created_at >= ? AND l.created_at <= ?
            GROUP BY l.userid, l.action";
        $stm = $dbh->prepare($query);
        $params = array_merge($actions, [$startTimestamp->format("Y-m-d H:i:s"),$endTimestamp->format("Y-m-d H:i:s")]);

        $stm = $dbh->prepare($query);
        $stm->execute($params);

        $teachers = array();
        while ($row = $stm->fetch(\PDO::FETCH_ASSOC)) {
            $teachers[$row['userid']]['userid'] = $row['userid'];
            $teachers[$row['userid']]['firstName'] = $row['FirstName'];
            $teachers[$row['userid']]['lastName'] = $row['LastName'];
            $teachers[$row['userid']]['group'] = $row['name'];
            $action = substr($row['action'], strpos($row['action'], " ") + 1);
            $teachers[$row['userid']][$action] = $row['itemcount'];
        }
        return $teachers;

    }
}
