<?php
/*
 * IMathAS: Single-session claim on an assessment attempt
 *
 * For timed or passworded assessments, the first session to start the
 * assessment "owns" it. Other sessions on the same account are refused until
 * the owner has gone quiet (no heartbeat) for stale_after seconds.
 */

class AssessClaim
{
    // seconds without a request before another session may take over
    const STALE_AFTER = 120;
    // heartbeat interval requested of the client
    const HEARTBEAT_INTERVAL = 30;
    // client covers the questions after this long without a successful heartbeat.
    // Must be well under STALE_AFTER.
    const OVERLAY_AFTER = 70;

    /**
     * Does a claim apply to this assessment?
     * @param  AssessInfo $assess_info
     * @return bool
     */
    public static function applies($assess_info) {
        global $CFG;
        if (!empty($CFG['assess2-noclaim'])) {
            return false;
        }
        return ($assess_info->getSetting('timelimit') > 0 || $assess_info->hasPassword());
    }

    /**
     * Try to claim or refresh the claim for the current session.
     * @param  int  $uid
     * @param  int  $aid
     * @param  bool $create  create a claim if there isn't one
     * @return string  'ok', 'takeover' (took over a stale claim), or 'in_use'
     */
    public static function claim($uid, $aid, $create) {
        global $DBH;
        $sid = session_id();
        $now = time();
        $cutoff = $now - self::STALE_AFTER;

        $stm = $DBH->prepare('SELECT sessionid,lastseen FROM imas_assess_claims WHERE userid=? AND assessmentid=?');
        $stm->execute([$uid, $aid]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            if (!$create) {
                return 'ok';
            }
            $stm = $DBH->prepare('INSERT IGNORE INTO imas_assess_claims (userid,assessmentid,sessionid,lastseen) VALUES (?,?,?,?)');
            $stm->execute([$uid, $aid, $sid, $now]);
            if ($stm->rowCount() > 0) {
                return 'ok';
            }
            // lost a race with another session; fall through to the update
            $row = ['sessionid' => null];
        }

        // atomic: only succeeds if we own it or it is stale
        $stm = $DBH->prepare('UPDATE imas_assess_claims SET sessionid=?,lastseen=? WHERE userid=? AND assessmentid=? AND (sessionid=? OR lastseen<?)');
        $stm->execute([$sid, $now, $uid, $aid, $sid, $cutoff]);
        if ($stm->rowCount() > 0) {
            return ($row['sessionid'] === $sid) ? 'ok' : 'takeover';
        }
        // zero rows: either we own it and lastseen was unchanged this second, or it is in use
        $stm = $DBH->prepare('SELECT sessionid FROM imas_assess_claims WHERE userid=? AND assessmentid=?');
        $stm->execute([$uid, $aid]);
        return ($stm->fetchColumn() === $sid) ? 'ok' : 'in_use';
    }

    /**
     * Record an event in imas_content_track for later review
     * @param  string $type  content_track type
     */
    public static function log($uid, $cid, $aid, $type) {
        global $DBH;
        $userIP = $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? $_SERVER['HTTP_CLIENT_IP']
            ?? '';
        $stm = $DBH->prepare('INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES (?,?,?,?,?,?)');
        $stm->execute([$uid, $cid, $type, $aid, time(),
            substr(base64_encode(hex2bin(md5($userIP))), -8, 6)]);
    }

    /**
     * Release claims held by the current session, on logout or leaving the
     * page. Only touches this session's own claims, so another session
     * can't clear someone else's.
     * @param  int|null $aid  limit to one assessment, or null for all
     */
    public static function release($aid = null) {
        global $DBH;
        $sid = session_id();
        if ($sid === '' || $sid === false) {
            return;
        }
        try {
            $query = 'DELETE FROM imas_assess_claims WHERE sessionid=?';
            $qarr = [$sid];
            if ($aid !== null) {
                $query .= ' AND assessmentid=?';
                $qarr[] = $aid;
            }
            $DBH->prepare($query)->execute($qarr);
        } catch (Exception $e) {
            // table may not exist yet; never break logout over this
        }
    }

    /**
     * Called from common_start for every assess2 endpoint other than startassess.
     * Only acts if a claim row already exists, so it's one cheap query otherwise.
     */
    public static function verify($uid, $aid) {
        if (self::claim($uid, $aid, false) === 'in_use') {
            echo '{"error": "attempt_in_use"}';
            exit;
        }
    }
}
