<?php

if (isset($GLOBALS['CFG']['hooks']['lti'])) {
    require_once $CFG['hooks']['lti'];
}
/**
 * Implements IMSGlobal\LTI\Database interface
 */

/**
 * Database tables and model:
 * imas_lti_keys.  Stores tool and platform RSA keys
 *  key_set_url     The keyset url the key came from
 *  kid             The key ID
 *  alg             The signature algorithm
 *  publickey       The public key
 *  privatekey      The private key. We only have this for our tool's keys
 *
 * imas_ltinonces.  Stores a cache of nonces to prevent reuse
 *  id              An autoincrement local ID
 *  nonce           The nonce string
 *  time            The time the nonce was recorded. They are typically cleared after 90 min.
 *
 * imas_platforms.  Stores LMS registrations
 *  id              An autoincrement local ID
 *  issuer          The LMS's issuer name
 *  client_id       Client ID
 *  auth_login_url  The OpenID connect login URL
 *  auth_token_url  The authorization token URL for services like grade passback
 *  key_set_url     The LMS's keyset URL
 *
 * imas_lti_deployments.  Stores deployments of an LMS registration
 *  id              An autoincrement local ID
 *  platform        imas_lti_platforms.id
 *  deployment      A deployment identifier provided by the LMS
 *
 * imas_lti_groupassoc.  An association between deployments and our groups
 *  deploymentid    imas_lti_deployments.id
 *  groupid         Our group ID, imas_groups.id
 *
 * imas_lti_courses.  Stores course associations
 *  id              An autoincrement local ID
 *  org             Stored as 'LTI13-' . imas_lti_platforms.id
 *  contextid       A course identifier provided by the LMS
 *  courseid        Our local course ID, imas_courses.id
 *  copiedfrom      The imas_courses.id that was copied via the LTI interface
 *  contextlabel    The LMS's label identifier for the course
 *
 * imas_ltiusers.  Stores user associations
 *  id              An autoincrement local ID
 *  org             Stored as 'LTI13-' . imas_lti_platforms.id
 *  ltiuserid       A userid identifier provided by the LMS
 *  userid          Our local user ID, imas_users.id
 *
 * imas_placements. Stores individual link item associations
 *  id              An autoincrement local ID
 *  org             Stored as 'LTI13-' . imas_lti_platforms.id
 *  contextid       A course identifier provided by the LMS
 *  linkid          A link identifier provided by the LMS
 *  placementtype   A string identifier for the item type. 'assess' for assessments
 *  typeid          The table ID for the placementtype.  imas_assessments.id for assessments
 *
 * imas_lti_tokens.  A store of OAuth2 service tokens
 *  platformid      imas_lti_platforms.id
 *  scopes          md5 hash of the scopes the token was issued for
 *  token           The authorization token
 *  expires         A timestamp for when the token expires
 * 
 * imas_lti_lineitems.  A record of LMS lineitems 
 *  itemtype        The numeric identifier for the placementtype. 0 for assess
 *  typeid          The table ID for the placementtype.  imas_assessments.id for assessments
 *  lticourseid     imas_lti_courses.id the lineitem is associated with 
 *  lineitem        The lineitem URL
 * 
 * imas_ltiqueue.  A store of queued lti grade updates
 *  hash            A string of the form imas_assessments.id . '-' . imas_users.id
 *  sourcedid       A string, formed by implode(':|:', $values), where the values are:
 *    LTI 1.1:
 *      lti_lis_result_sourcedid
 *      lti_outcomeurl
 *      LTI consumer key
 *      LTI key lookup info: 'c' for course-level, 'u' for global
 *    LTI 1.3:
 *      'LTI1.3'                      the literal string as a version identifier
 *      imas_ltiusers.ltiuserid       The LMS's user identifier
 *      imas_lti_lineitems.lineitem   The lineitem URL
 *      imas_lti_platforms.id         The platform ID
 *
 */

define('TOOL_HOST', $GLOBALS['basesiteurl']);

use \IMSGlobal\LTI;

class Imathas_LTI_Database implements LTI\Database
{
    private $dbh;

    private $types_as_num = [
        'assess' => 0,
        'course' => 1,
    ];

    public function __construct(PDO $DBH)
    {
        $this->dbh = $DBH;
        if (function_exists('lti_get_types_as_num')) {
            $this->types_as_num = array_merge($this->types_as_num, lti_get_types_as_num());
        }
    }

    /**
     * Find a registration by issuer and client id
     * @param  string           $iss
     * @param  string           $client_id
     * @return LTI_Registration
     */
    public function find_registration_by_issuer(string $iss, ?string $client_id): ?LTI\LTI_Registration
    {
        if (empty($client_id)) {
            if (isset($_GET['u'])) {
                $stm = $this->dbh->prepare('SELECT * FROM imas_lti_platforms WHERE issuer=? AND uniqid=?');
                $stm->execute(array($iss, $_GET['u']));
            } else {
                $stm = $this->dbh->prepare('SELECT * FROM imas_lti_platforms WHERE issuer=?');
                $stm->execute(array($iss));
                if ($stm->rowCount() > 1) {
                    throw new OIDC_Exception("Multiple registrations found for this issuer. Platform must provide client_id on launch.", 1);
                }
            }
        } else {
            $stm = $this->dbh->prepare('SELECT * FROM imas_lti_platforms WHERE issuer=? AND client_id=?');
            $stm->execute(array($iss, $client_id));
        }
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if ($row === false || $row === null) {
            if (!empty($GLOBALS['CFG']['LTI']['autoreg']) && trim($client_id) != '') {
                if ($iss === 'https://canvas.instructure.com') {
                    $row = [
                       'issuer' => $iss,
                       'client_id' => trim($client_id),
                       'auth_login_url' => 'https://canvas.instructure.com/api/lti/authorize_redirect',
                       'auth_token_url' => 'https://canvas.instructure.com/login/oauth2/token',
                       'key_set_url' => 'https://canvas.instructure.com/api/lti/security/jwks'
                    ];
                } else if ($iss === 'https://canvas.beta.instructure.com') {
                    $row = [
                       'issuer' => $iss,
                       'client_id' => trim($client_id),
                       'auth_login_url' => 'https://canvas.beta.instructure.com/api/lti/authorize_redirect',
                       'auth_token_url' => 'https://canvas.beta.instructure.com/login/oauth2/token',
                       'key_set_url' => 'https://canvas.beta.instructure.com/api/lti/security/jwks'
                    ];
                } else if ($iss === 'https://canvas.test.instructure.com') {
                    $row = [
                       'issuer' => $iss,
                       'client_id' => trim($client_id),
                       'auth_login_url' => 'https://canvas.test.instructure.com/api/lti/authorize_redirect',
                       'auth_token_url' => 'https://canvas.test.instructure.com/login/oauth2/token',
                       'key_set_url' => 'https://canvas.test.instructure.com/api/lti/security/jwks'
                    ];
                }
                if (is_array($row)) { // set something above - create platform reg
                    $stm = $this->dbh->prepare("INSERT INTO imas_lti_platforms (issuer,client_id,auth_login_url,auth_token_url,key_set_url) VALUES (?,?,?,?,?)");
                    $stm->execute(array_values($row));
                    $row['id'] = $this->dbh->lastInsertId();
                } 
            }
            if ($row === false || $row === null) {
                return null;
            }
        }
        return LTI\LTI_Registration::new ()
            ->set_auth_login_url($row['auth_login_url'])
            ->set_auth_token_url($row['auth_token_url'])
            ->set_client_id($row['client_id'])
            ->set_key_set_url($row['key_set_url'])
            ->set_issuer($iss)
            ->set_id(intval($row['id']));
    }

    /**
     * Find deployment.  If doesn't exist, creates it.
     * @param  int            $platform_id
     * @param  string         $deployment_id
     * @return LTI_Deployment
     */
    public function find_deployment(int $platform_id, string $deployment_id): LTI\LTI_Deployment
    {
        $stm = $this->dbh->prepare('SELECT * FROM imas_lti_deployments WHERE platform=? AND deployment=?');
        $stm->execute(array($platform_id, $deployment_id));
        if ($stm->rowCount() === 0) {
            // no existing deployment record, create one
            $stm = $this->dbh->prepare('INSERT INTO imas_lti_deployments (platform,deployment) VALUES (?,?)');
            $stm->execute(array($platform_id, $deployment_id));
        }
        return LTI\LTI_Deployment::new ()->set_deployment_id($deployment_id);
    }

    /**
     * Get an array of our public keys, for generating our jwks page
     * @return array
     */
    public function get_jwks_keys(): array
    {
        $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=?');
        $stm->execute(array(TOOL_HOST . '/lti/jwks.php'));
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get our private key
     * @return array
     */
    public function get_tool_private_key(): array
    {
        $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=? ORDER BY created_at DESC LIMIT 1');
        $stm->execute(array(TOOL_HOST . '/lti/jwks.php'));
        return $stm->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get key from the database
     * @param  string $keyseturl [description]
     * @param  string $kid       [description]
     * @return array|null with key_set_url,kid,alg,publickey,privatekey,created_at
     *                  or null if no key exists
     */
    public function get_key(string $keyseturl, string $kid): ?array
    {
        $stm = $this->dbh->prepare('SELECT * FROM imas_lti_keys WHERE key_set_url=? AND kid=?');
        $stm->execute(array($keyseturl, $kid));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        } else {
            return $row;
        }
    }

    /**
     * Delete a key from the database
     * @param string $keyseturl
     * @param string $kid
     */
    public function delete_key(string $keyseturl, string $kid): void
    {
        $stm = $this->dbh->prepare('DELETE FROM imas_lti_keys WHERE key_set_url=? AND kid=?');
        $stm->execute(array($keyseturl, $kid));
    }

    /**
     * Record keys from a JWKS for caching
     * @param string $keyseturl the keyset url we got these from
     * @param array  $keys      array of the keys
     */
    public function record_keys(string $keyseturl, array $keys): void
    {
        $stm = $this->dbh->prepare('INSERT IGNORE INTO imas_lti_keys (key_set_url,kid,alg,publickey) VALUES (?,?,?,?)');
        foreach ($keys as $kid => $keyinfo) {
            $stm->execute(array($keyseturl, $kid, $keyinfo['alg'], $keyinfo['pub']));
        }
    }

    /**
     * Get token from database.
     * If a failed token request has reached it's retry delay, this will return
     * (false, number of previous failures)
     *
     * If the previous request failed and it's before the retry delay, the token
     * will start with 'failed'
     *
     * @param  int    $platform_id
     * @param  string $scope   an md5 hash of the pipe-concatenated scopes
     * @return array  (token string | false, number of previous failures)
     */
    public function get_token(int $platform_id, string $scope): array
    {
        $stm = $this->dbh->prepare('SELECT * FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
        $stm->execute(array($platform_id, $scope));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if ($row === false || $row === null) {
            return array(false, 0);
        } else if ($row['expires'] < time()) {
            $stm = $this->dbh->prepare('DELETE FROM imas_lti_tokens WHERE platformid=? AND scopes=?');
            $stm->execute(array($platform_id, $scope));
            if (substr($row['token'], 0, 6) === 'failed') {
                return array(false, intval(substr($row['token'], 6)));
            } else {
                return array(false, 0);
            }
        } else {
            return array($row['token'], 0);
        }
    }

    /**
     * Record an access token
     * @param int    $platform_id
     * @param string $scope       an md5 hash of the pipe-concatenated scopes
     * @param array  $tokeninfo   token response, with 'access_token' and 'expires_in'
     */
    public function record_token(int $platform_id, string $scope, array $tokeninfo): void
    {
        $stm = $this->dbh->prepare('REPLACE INTO imas_lti_tokens (platformid, scopes, token, expires) VALUES (?,?,?,?)');
        $stm->execute(array($platform_id, $scope, $tokeninfo['access_token'], time() + $tokeninfo['expires_in'] - 1));
    }

    /**
     * Get local user id
     * @param  LTI_Message_Launch $launch
     * @return false|int local userid
     */
    public function get_local_userid(LTI\LTI_Message_Launch $launch)
    {
        $ltiuserid = $launch->get_platform_user_id();
        $platform_id = $launch->get_platform_id();

        $stm = $this->dbh->prepare('SELECT userid FROM imas_ltiusers WHERE ltiuserid=? AND org=?');
        $stm->execute(array($ltiuserid, 'LTI13-' . $platform_id));
        $userid = $stm->fetchColumn(0);
        if ($userid === false) {
            $contextid = $launch->get_platform_context_id();
            $migration_claim = $launch->get_migration_claim();
        }
        if ($userid === false && 
            !empty($migration_claim) && 
            $this->verify_migration_claim($migration_claim)
        ) {
            if (isset($migration_claim['user_id'])) {
                $oldltiuserid = $migration_claim['user_id'];
            } else {
                $oldltiuserid = $ltiuserid;
            }
            $oldkey = $migration_claim['oauth_consumer_key'];
            $stm = $this->dbh->prepare('SELECT userid FROM imas_ltiusers WHERE ltiuserid=? AND org LIKE ?');
            $stm->execute(array($oldltiuserid, $oldkey.':%'));
            $userid = $stm->fetchColumn(0);
            if ($userid !== false) {
                // found one; create a new ltiusers record using new ltiuserd/platformid
                $this->create_lti_user($userid, $ltiuserid, $platform_id);
            }
        } else if ($userid === false && $contextid != '') {
            // look to see if we already have a user record with the same context id
            // from an LTI 1.1 connection
            $groups = $this->get_groups($platform_id, $launch->get_deployment_id());
            if (count($groups)==0) {
                return false;
            }
            $groups = array_column($groups, 'id');
            // find old course connections using same contextid
            $query = 'SELECT ilc.org,iu.groupid FROM imas_lti_courses AS ilc 
                JOIN imas_courses AS ic ON ic.id=ilc.courseid 
                JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ilc.contextid=?';
            $stm = $this->dbh->prepare($query);
            $stm->execute(array($contextid));
            $qarr = array($ltiuserid);
            while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                if (in_array($row['groupid'], $groups)) { // check course is in right group
                    $key = explode(':', $row['org'])[0];
                    $qarr[] = $key.':%';
                }
            }
            if (count($qarr)==2) { // only use if one matching association
                $stm = $this->dbh->prepare('SELECT userid FROM imas_ltiusers WHERE ltiuserid=? AND org LIKE ?');
                $stm->execute($qarr);
                $userid = $stm->fetchColumn(0);
                if ($userid !== false) {
                    // found one; create a new ltiusers record using new ltiuserd/platformid
                    $this->create_lti_user($userid, $ltiuserid, $platform_id);
                }
            }
            
        }
        return $userid;
    }

    /**
     * Create a user account
     * @param  array $data assoc array of account data
     * @return int   new local userid
     */
    public function create_user_account(array $data): int
    {
        $query = "INSERT INTO imas_users (SID,password,rights,FirstName,LastName,email,msgnotify,groupid) VALUES ";
        $query .= '(:SID,:password,:rights,:FirstName,:LastName,:email,:msgnotify,:groupid)';
        $stm = $this->dbh->prepare($query);
        $stm->execute(array(':SID' => $data['SID'], ':password' => $data['pwhash'], ':rights' => $data['rights'],
            ':FirstName' => Sanitize::stripHtmlTags($data['firstname']),
            ':LastName' => Sanitize::stripHtmlTags($data['lastname']),
            ':email' => Sanitize::emailAddress($data['email']),
            ':msgnotify' => $data['msgnot'], ':groupid' => $data['groupid']));
        return $this->dbh->lastInsertId();
    }

    /**
     * Enroll a user as teacher or students in a course if needed
     * @param int             $userid
     * @param string          $role        standardized role, 'Learner' or 'Instructor'
     * @param LTI_Localcourse $localcourse
     * @param string          $section     a section identifer
     */
    public function enroll_if_needed(int $userid, string $role,
        LTI\LTI_Localcourse $localcourse, string $section = ''
    ): void {
        if ($role == 'Instructor') {
            $stm = $this->dbh->prepare('SELECT id FROM imas_teachers WHERE userid=? AND courseid=?');
            $stm->execute(array($userid, $localcourse->get_courseid()));
            if (!$stm->fetchColumn(0)) {
                $stm = $this->dbh->prepare('INSERT INTO imas_teachers (userid,courseid) VALUES (?,?)');
                $stm->execute(array($userid, $localcourse->get_courseid()));
            }
        } else {
            $stm = $this->dbh->prepare('SELECT id,lticourseid FROM imas_students WHERE userid=? AND courseid=?');
            $stm->execute(array($userid, $localcourse->get_courseid()));
            $row = $stm->fetch(PDO::FETCH_ASSOC);
            if ($row === false || $row === null) {
                $stm = $this->dbh->prepare("SELECT deflatepass FROM imas_courses WHERE id=:id");
                $stm->execute(array(':id'=>$localcourse->get_courseid()));
                $deflatepass = $stm->fetchColumn(0);

                $stm = $this->dbh->prepare('INSERT INTO imas_students (userid,courseid,section,latepass,lticourseid) VALUES (?,?,?,?,?)');
                $stm->execute(array($userid, $localcourse->get_courseid(), $section, $deflatepass, $localcourse->get_id()));
            } else if ($row['lticourseid'] !== $localcourse->get_id()) {
                $stm = $this->dbh->prepare('UPDATE imas_students SET lticourseid=? WHERE id=?');
                $stm->execute(array($localcourse->get_id(), $row['id']));
            }
        }
    }

    /**
     * Create lti user connection record
     * @param  int    $userid      local imas_users.id
     * @param  string $ltiuserid   user id provided by LMS
     * @param  int    $platform_id imas_lti_platforms.id
     * @return int    new imas_ltiusers.id
     */
    public function create_lti_user(int $userid, string $ltiuserid, int $platform_id): int
    {
        $stm = $this->dbh->prepare('INSERT INTO imas_ltiusers (userid,ltiuserid,org) VALUES (?,?,?)');
        $stm->execute(array($userid, $ltiuserid, 'LTI13-' . $platform_id));
        return $this->dbh->lastInsertId();
    }

    /**
     * Update the SID for a user
     * @param int    $userid
     * @param string $SID
     */
    public function set_user_SID(int $userid, string $SID): void
    {
        $stm = $this->dbh->prepare('UPDATE imas_users SET SID=? WHERE id=?');
        $stm->execute(array($SID, $userid));
    }

    /**
     * Get local user course id
     * @param  string $contextid
     * @param  LTI_Message_Launch $launch
     * @return null|LTI_Localcourse local course info
     */
    public function get_local_course(string $contextid, LTI\LTI_Message_Launch $launch): ?LTI\LTI_Localcourse
    {
        $platform_id = $launch->get_platform_id();

        $query = 'SELECT ilc.id,ilc.courseid,ilc.copiedfrom,ic.UIver,ic.dates_by_lti FROM
            imas_lti_courses AS ilc JOIN imas_courses AS ic ON ilc.courseid=ic.id
            WHERE ilc.contextid=? AND ilc.org=?';
        $stm = $this->dbh->prepare($query);
        $stm->execute(array($contextid, 'LTI13-' . $platform_id));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if ($row === false || $row === null) {
            $migration_claim = $launch->get_migration_claim();
            if (!empty($migration_claim) && $this->verify_migration_claim($migration_claim)) {
                if (isset($migration_claim['context_id'])) {
                    $oldcontextid = $migration_claim['context_id'];
                } else {
                    $oldcontextid = $contextid;
                }
                $oldkey = $migration_claim['oauth_consumer_key'];
                $query = 'SELECT courseid,copiedfrom,contextlabel FROM imas_lti_courses 
                    WHERE contextid=? AND org LIKE ?';
                $stm = $this->dbh->prepare($query);
                $stm->execute(array($oldcontextid, $oldkey.':%'));
                $row = $stm->fetch(PDO::FETCH_ASSOC);
                if ($row !== false) {
                    // found one; create a new lti_course record using new contextid/platformid
                    $newlticourseid = $this->add_lti_course($contextid, $platform_id, 
                        $row['courseid'], $row['contextlabel'], $row['copiedfrom']);
                    $localcourse = LTI\LTI_Localcourse::new()
                        ->set_courseid($row['courseid'])
                        ->set_copiedfrom($row['copiedfrom'])
                        ->set_id($newlticourseid);
                    return $localcourse;
                }
            } else if ($contextid != '') {
                // look to see if we already have a user record with the same context id
                // from an LTI 1.1 connection
                $groups = $this->get_groups($platform_id, $launch->get_deployment_id());
                if (count($groups)==0) {
                    return null;
                }
                $groups = array_column($groups, 'id');
                // find old course connections using same contextid
                $query = 'SELECT ilc.courseid,ilc.copiedfrom,ilc.contextlabel,iu.groupid FROM imas_lti_courses AS ilc 
                    JOIN imas_courses AS ic ON ic.id=ilc.courseid 
                    JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ilc.contextid=?';
                $stm = $this->dbh->prepare($query);
                $stm->execute(array($contextid));
                $foundrow = false;
                while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                    if (in_array($row['groupid'], $groups)) { // check course is in right group
                        if ($foundrow === false) {
                            $foundrow = $row;
                        } else { // more than one; bail
                            return null;
                        }
                    }
                }
                if ($foundrow !== false) {
                    // found one; create a new lti_course record using new contextid/platformid
                    $newlticourseid = $this->add_lti_course($contextid, $platform_id, 
                        $foundrow['courseid'], $foundrow['contextlabel'], $foundrow['copiedfrom']);
                    $localcourse = LTI\LTI_Localcourse::new()
                        ->set_courseid($foundrow['courseid'])
                        ->set_copiedfrom($foundrow['copiedfrom'])
                        ->set_id($newlticourseid);
                    return $localcourse;
                }
            }
            return null;
        } else {
            return LTI\LTI_Localcourse::new ($row);
        }
    }

    /**
     * Get previous copiedfrom for a course when doing assoc with existing course
     * @param int $courseid   course ID
     * @param int $platform_id   platform id
     * @return int  previous copiedfrom, or 0 if none
     */
    public function get_previous_copiedfrom(int $courseid, int $platform_id): int 
    {
        $query = 'SELECT copiedfrom FROM imas_lti_courses WHERE courseid=? AND org=? AND copiedfrom>0';
        $stm = $this->dbh->prepare($query);
        $stm->execute(array($courseid, 'LTI13-' . $platform_id));
        $copiedfrom = $stm->fetchColumn(0);
        if ($copiedfrom !== false) {
            return $copiedfrom;
        }
        return 0;
    }

    /**
     * Add lti course association
     * @param  string  $contextid   LMS contextid
     * @param  int     $platform_id imas_lti_platforms.id
     * @param  int     $localcid    imas_courses.id
     * @param  string  $label       LMS provided course label
     * @param  integer $copiedfrom  local id course was copied from
     * @return int  new imas_lti_courses.id
     */
    public function add_lti_course(string $contextid, int $platform_id,
        int $localcid, string $label = '', int $copiedfrom = 0
    ): int {
        $stm = $this->dbh->prepare('INSERT INTO imas_lti_courses (contextid,org,courseid,contextlabel,copiedfrom) VALUES (?,?,?,?,?)');
        $stm->execute(array($contextid, 'LTI13-' . $platform_id, $localcid, $label, $copiedfrom));
        return $this->dbh->lastInsertId();
    }

    /**
     * Get a course UIver
     * @param  int $courseid
     * @return int UIver
     */
    public function get_UIver(int $courseid): int
    {
        $stm = $this->dbh->prepare('SELECT UIver FROM imas_courses WHERE id=?');
        $stm->execute(array($courseid));
        return $stm->fetchColumn(0);
    }

    /**
     * Get groups associated with deployment
     * @param  int $platform_id  platform id
     * @param  string $deployment LMS provided deployment string
     * @return array  of groups, with indices id and name
     */
    public function get_groups(int $platform_id, string $deployment): array
    {
        $query = 'SELECT ig.id,ig.name FROM imas_groups AS ig
      JOIN imas_lti_groupassoc AS iga ON ig.id=iga.groupid
      JOIN imas_lti_deployments AS ild ON ild.id=iga.deploymentid
      WHERE ild.platform=? AND ild.deployment=?';
        $stm = $this->dbh->prepare($query);
        $stm->execute(array($platform_id, $deployment));
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Associate group with deployment
     * @param int    $platform_id imas_lti_platforms.id
     * @param string $deployment  LMS provided deployment string
     * @param int    $groupid     imas_groups.id
     * @return void
     */
    public function set_group_assoc(int $platform_id, string $deployment, int $groupid): void
    {
        $stm = $this->dbh->prepare('SELECT id FROM imas_lti_deployments WHERE platform=? AND deployment=?');
        $stm->execute(array($platform_id, $deployment));
        $internal_deployment_id = $stm->fetchColumn(0);
        $stm = $this->dbh->prepare('INSERT IGNORE INTO imas_lti_groupassoc (deploymentid,groupid) VALUES (?,?)');
        $stm->execute(array($internal_deployment_id, $groupid));
    }

    /**
     * Get course id from assessment id
     * @param  int $aid imas_assessments.id
     * @return int imas_courses.id or null if not found
     */
    public function get_course_from_aid(int $aid): ?int
    {
        $stm = $this->dbh->prepare('SELECT courseid FROM imas_assessments WHERE id=?');
        $stm->execute(array($aid));
        $cid = $stm->fetchColumn(0);
        if ($cid === false) { 
            return null;
        } else {
            return $cid;
        }
    }

    /**
     * Get all courses user is teacher for
     * 
     * @param int $userid 
     * @return array of id=>name
     */
    public function get_all_courses(int $userid): array 
    {
        $query = "SELECT DISTINCT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS imt ON ic.id=imt.courseid ";
        $query .= "AND imt.userid=? WHERE ic.available<4 ORDER BY ic.name";
        $stm = $this->dbh->prepare($query);
        $stm->execute([$userid]);
        $courses = [];
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $courses[$row[0]] = $row[1];
        }
        return $courses;
    }

    /**
     * Get courses we might want to copy or associate with
     *
     * @param  array $target    should have 'refcid' and 'refaid' defined
     * @param  int   $lastcopied last copied course id
     * @param  int   $userid     imas_users.id
     * @return array [courses to copy, courses to associate, sourceUIver]
     */
    public function get_potential_courses(array $target, int $lastcopied, int $userid): array
    {
        // see if we're allowed to do copies of copies
        $stm = $this->dbh->prepare('SELECT jsondata,UIver FROM imas_courses WHERE id=:sourcecid');
        $stm->execute(array(':sourcecid' => $target['refcid']));
        list($sourcejsondata, $sourceUIver) = $stm->fetch(PDO::FETCH_NUM);
        $sourcejsondata = json_decode($sourcejsondata, true);
        $blockLTICopyOfCopies = ($sourcejsondata !== null && !empty($sourcejsondata['blockLTICopyOfCopies']));

        $othercourses = array();

        if ($target['type'] == 'aid') {
            // look for other courses we could associate with
            $query = "SELECT DISTINCT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS imt ON ic.id=imt.courseid ";
            $query .= "AND imt.userid=:userid JOIN imas_assessments AS ia ON ic.id=ia.courseid ";
            $query .= "WHERE ic.available<4 AND ic.ancestors REGEXP :cregex AND ia.ancestors REGEXP :aregex ORDER BY ic.name";
            $stm = $this->dbh->prepare($query);
            $stm->execute(array(
                ':userid' => $userid,
                ':cregex' => '[[:<:]]' . $target['refcid'] . '[[:>:]]',
                ':aregex' => '[[:<:]]' . $target['refaid'] . '[[:>:]]'));
            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                $othercourses[$row[0]] = $row[1];
            }
        } else if (function_exists('lti_get_othercourses')) {
            $othercourses = lti_get_othercourses($target, $userid);
        }

        if ($blockLTICopyOfCopies) {
            $copycourses = array();
        } else {
            $copycourses = $othercourses;
            if ($lastcopied > 0 && !isset($copycourses[$lastcopied])) {
                // have a last copied courseid, but wasn't in the list above,
                // possible if user isn't owner. We'll add as an option anyway.
                $stm = $this->dbh->prepare('SELECT DISTINCT id,name FROM imas_courses WHERE id=?');
                $stm->execute(array($lastcopied));
                while ($row = $stm->fetch(PDO::FETCH_NUM)) {
                    $copycourses[$row[0]] = $row[1];
                }
            }
        }

        // get origin course
        // TODO: if the $lastcopied isn't owned by us, should we still offer it as
        // an option to be copied?
        $stm = $this->dbh->prepare('SELECT DISTINCT id,name,ownerid FROM imas_courses WHERE id=?');
        $stm->execute(array($target['refcid']));
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $copycourses[$row[0]] = $row[1];
            // if it's user's course, also include in assoc list
            if ($row[2] == $userid) {
                $othercourses[$row[0]] = $row[1];
            }
        }
        return array($copycourses, $othercourses, $sourceUIver);
    }

    /**
     * Make sure use is a teacher on course to associate
     * @param  int  $destcid imas_courses.id
     * @param  int  $userid
     * @return bool if user is a teacher
     */
    public function ok_to_associate(int $destcid, int $userid): bool
    {
        $stm = $this->dbh->prepare('SELECT it.id FROM imas_courses AS ic JOIN
      imas_teachers AS it ON it.courseid=ic.id WHERE ic.id=? AND it.userid=?');
        $stm->execute(array($destcid, $userid));
        return ($stm->fetchColumn(0) !== false);
    }

    /**
     * Get placement from data, if exists
     * @param  string $resource_link_id LMS provided resource link id
     * @param  string $contextid        LMS provided contextid
     * @param  int    $platform_id      imas_lti_platforms.id
     * @return null|LTI_Placement
     */
    public function get_link_assoc(string $resource_link_id, string $contextid, int $platform_id): ?LTI\LTI_Placement
    {
        $query = "SELECT ip.typeid,ip.placementtype,ia.date_by_lti,ia.enddate,ia.startdate
      FROM imas_lti_placements AS ip LEFT JOIN imas_assessments AS ia
      ON ip.typeid=ia.id AND ip.placementtype='assess'
      WHERE linkid=? AND contextid=? AND org=?";
        $stm = $this->dbh->prepare($query);
        $stm->execute(array($resource_link_id, $contextid, 'LTI13-' . $platform_id));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            return LTI\LTI_Placement::new ()
                ->set_typeid($row['typeid'])
                ->set_placementtype($row['placementtype'])
                ->set_typenum($this->types_as_num[$row['placementtype']])
                ->set_date_by_lti($row['date_by_lti'])
                ->set_startdate($row['startdate'])
                ->set_enddate($row['enddate']);
        }
        return null;
    }

    /**
     * Get placement from lineitem, if exists
     * @param  string $lineitem     LMS provided lineitem string
     * @param  int $lticourseid     imas_lticourses.id
     * @return null|LTI_Placement
     */
    public function get_link_assoc_by_lineitem(string $lineitem, int $lticourseid): ?LTI\LTI_Placement
    {
        $query = 'SELECT itemtype,typeid FROM imas_lti_lineitems WHERE
        lticourseid=? AND lineitem=?';
        $stm = $this->dbh->prepare($query);
        $stm->execute(array($lticourseid, $lineitem));
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        $placementtype = array_search($row['itemtype'], $this->types_as_num);
        return LTI\LTI_Placement::new ()
            ->set_typeid($row['typeid'])
            ->set_placementtype($placementtype)
            ->set_typenum($row['itemtype']);
    }

    /**
     * Create a placement record
     * @param  int           $typeid           imas_(itemtype).id
     * @param  string        $placementtype    'assess' or otherwise
     * @param  string        $resource_link_id LMS provided resource link id
     * @param  string        $contextid        LMS provided contextid
     * @param  int           $platform_id      imas_lti_platforms.id
     * @return LTI_Placement
     */
    public function make_link_assoc(int $typeid, string $placementtype,
        string $resource_link_id, string $contextid, int $platform_id
    ): LTI\LTI_Placement{
        $query = 'INSERT INTO imas_lti_placements (typeid,placementtype,linkid,contextid,org) VALUES (?,?,?,?,?)';
        $stm = $this->dbh->prepare($query);
        $stm->execute(array($typeid, $placementtype, $resource_link_id, $contextid, 'LTI13-' . $platform_id));
        $typenum = $this->types_as_num[$placementtype];
        return LTI\LTI_Placement::new ()
            ->set_typeid($typeid)
            ->set_placementtype($placementtype)
            ->set_typenum($typenum);
    }

    /**
     * Get date info for an assessment
     * @param  int   $aid  imas_assessments.id
     * @return array
     */
    public function get_dates_by_aid(int $aid): array
    {
        $stm = $this->dbh->prepare('SELECT date_by_lti,startdate,enddate FROM imas_assessments WHERE id=?');
        $stm->execute(array($aid));
        return $stm->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get assessment info, including name, ptsposs, submitby, and dates
     * @param  int   $aid imas_assessments.id
     * @return array
     */
    public function get_assess_info(int $aid): array
    {
        $stm = $this->dbh->prepare('SELECT name,ptsposs,startdate,enddate,date_by_lti,submitby FROM imas_assessments WHERE id=?');
        $stm->execute(array($aid));
        return $stm->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Set assessment dates when setting dates by lti
     * @param int $aid       imas_assessments.id
     * @param int $enddate   new enddate
     * @param int $datebylti new value for date_by_lti
     * @return void
     */
    public function set_assessment_dates(int $aid, int $enddate, int $datebylti): void
    {
        $stm = $this->dbh->prepare("UPDATE imas_assessments SET startdate=:startdate,enddate=:enddate,date_by_lti=:datebylti WHERE id=:id");
        $stm->execute(array(':startdate' => min(time(), $enddate),
            ':enddate' => $enddate, ':datebylti' => $datebylti, ':id' => $aid));
    }

    /**
     * Sets or updates a duedate exception
     * @param int           $userid
     * @param LTI_Placement $link
     * @param int           $lms_duedate  the new duedate provided by the LMS
     * @return void
     */
    public function set_or_update_duedate_exception(int $userid, LTI\LTI_Placement $link, int $lms_duedate): void
    {
        $now = time();
        $aid = $link->get_typeid();
        $stm = $this->dbh->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
        $stm->execute(array(':userid' => $userid, ':assessmentid' => $aid));
        $exceptionrow = $stm->fetch(PDO::FETCH_NUM);
        $useexception = false;
        if ($exceptionrow != null) {
            //have exception.  Update using lti_duedate if needed
            if ($link->get_date_by_lti() > 0 && $lms_duedate != $exceptionrow[1]) {
                //if new due date is later, or no latepass used, then update
                if ($exceptionrow[2] == 0 || $lms_duedate > $exceptionrow[1]) {
                    $stm = $this->dbh->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,is_lti=1,islatepass=0 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
                    $stm->execute(array(':startdate' => min($now, $lms_duedate, $exceptionrow[0]),
                        ':enddate' => $lms_duedate, ':userid' => $userid, ':assessmentid' => $aid));
                }
            }
        } else if ($link->get_date_by_lti() == 3 &&
            ($link->get_enddate() != $lms_duedate || $now < $link->get_startdate())
        ) {
            //default dates already set by LTI, and users's date doesn't match - create new exception
            //also create if it's before the default assessment startdate - since they could access via LMS, it should be available.
            $stm = $this->dbh->prepare("INSERT INTO imas_exceptions (startdate,enddate,islatepass,is_lti,userid,assessmentid,itemtype) VALUES (?,?,?,?,?,?,'A')");
            $stm->execute(array(min($now, $lms_duedate), $lms_duedate, 0, 1, $userid, $aid));
        }
    }

    /**
     * Find assessment in $destcid that has $aidtolookfor as an immediate ancestor
     * @param  int    $aidtolookfor
     * @param  int    $destcid
     * @return int|false   imas_assessments.id if found
     */
    public function find_aid_by_immediate_ancestor(int $aidtolookfor, int $destcid)
    {
        $anregex = '^([0-9]+:)?' . $aidtolookfor . '[[:>:]]';
        $stm = $this->dbh->prepare("SELECT id FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
        $stm->execute(array(':ancestors' => $anregex, ':destcid' => $destcid));
        return $stm->fetchColumn(0);
    }

    /**
     * Find assessment in $destcid that has $aidtolookfor as an ancestor
     * We don't know sourcecid, so need to look for closest ancestor in the bunch
     * @param  int    $aidtolookfor
     * @param  int    $destcid
     * @return int|false   imas_assessments.id if found
     */
    public function find_aid_by_loose_ancestor(int $aidtolookfor, int $destcid)
    {
        $anregex = '[[:<:]]' . $aidtolookfor . '[[:>:]]';
        $stm = $this->dbh->prepare("SELECT id,name,ancestors FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
        $stm->execute(array(':ancestors' => $anregex, ':destcid' => $destcid));
        $res = $stm->fetchAll(PDO::FETCH_ASSOC);
        if (count($res) == 1) { //only one result - we found it
            return $res[0]['id'];
        }
        if (count($res) > 1) { //multiple results - look for one with assessement closest to start
            foreach ($res as $k => $row) {
                $res[$k]['loc'] = strpos($row['ancestors'], (string) $aidtolookfor);
            }
            //pick the one with the assessment closest to the start
            usort($res, function ($a, $b) {return $a['loc'] - $b['loc'];});
            return $res[0]['id'];
        }
        return false;
    }

    /**
     * Attempts to find an appropriate assessment in $destcid which was copied
     * from course $copiedfrom that might be a copy
     * of $sourceaid in course $aidsourcecid.
     *
     * @param  int    $sourceaid
     * @param  int    $aidsourcecid
     * @param  int    $copiedfrom
     * @param  int    $destcid
     * @return int|false  imas_assessments.id if found
     */
    public function find_aid_by_ancestor_walkback(int $sourceaid, int $aidsourcecid,
        int $copiedfrom, int $destcid
    ) {
        $stm = $this->dbh->prepare("SELECT ancestors FROM imas_courses WHERE id=?");
        $stm->execute(array($destcid));
        $ancestors = explode(',', $stm->fetchColumn(0));
        $ciddepth = array_search($aidsourcecid, $ancestors); //so if we're looking for 23, "20,24,23,26" would give 2 here.
        if ($ciddepth !== false) {
            // first approach: look for aidsourcecid:sourcecid in ancestry of current course
            // then we'll walk back through the ancestors and make sure the course
            // history path matches.
            // This approach will work as long as there's a newer-format ancestry record
            $anregex = '[[:<:]]' . $aidsourcecid . ':' . $sourceaid . '[[:>:]]';
            $stm = $this->dbh->prepare("SELECT id,ancestors FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
            $stm->execute(array(':ancestors' => $anregex, ':destcid' => $destcid));
            while ($res = $stm->fetch(PDO::FETCH_ASSOC)) {
                $aidanc = explode(',', $res['ancestors']);
                $isok = true;
                for ($i = 0; $i <= $ciddepth; $i++) {
                    if (!isset($aidanc[$i])) {
                        $isok = false;
                        break;
                    }
                    $ancparts = explode(':', $aidanc[$i]);
                    if ($ancparts[0] != $ancestors[$i]) {
                        $isok = false;
                        break; // not the same ancestry path
                    }
                }
                if ($isok) { // found it!
                    return $res['id'];
                }
            }

            // last approach didn't work, so maybe it was an older-format ancestry.
            // We'll try a heavier-duty walkback that calls the database for each walkback
            array_unshift($ancestors, $destcid); //add current course to front
            $foundsubaid = true;
            $aidtolookfor = $sourceaid;
            for ($i = $ciddepth; $i >= 0; $i--) { //starts one course back from aidsourcecid because of the unshift
                $stm = $this->dbh->prepare("SELECT id FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:cid");
                $stm->execute(array(':ancestors' => '^([0-9]+:)?' . $aidtolookfor . '[[:>:]]', ':cid' => $ancestors[$i]));
                if ($stm->rowCount() > 0) {
                    $aidtolookfor = $stm->fetchColumn(0);
                } else {
                    $foundsubaid = false;
                    break;
                }
            }
            if ($foundsubaid) { // tracked it back all the way
                return $aidtolookfor;
            }

            // ok, still didn't work, so assessment wasn't copied through the whole
            // history.  So let's see if we have a copy in our course with the assessment
            // anywhere in the ancestry.
            $anregex = '[[:<:]]' . $sourceaid . '[[:>:]]';
            $stm = $this->dbh->prepare("SELECT id,name,ancestors FROM imas_assessments WHERE ancestors REGEXP :ancestors AND courseid=:destcid");
            $stm->execute(array(':ancestors' => $anregex, ':destcid' => $destcid));
            $res = $stm->fetchAll(PDO::FETCH_ASSOC);
            if (count($res) == 1) { //only one result - we found it
                return $res[0]['id'];
            }
            $stm = $this->dbh->prepare("SELECT name FROM imas_assessments WHERE id=?");
            $stm->execute(array($sourceaid));
            $aidsourcename = $stm->fetchColumn(0);
            if (count($res) > 1) { //multiple results - look for the identical name
                foreach ($res as $k => $row) {
                    $res[$k]['loc'] = strpos($row['ancestors'], (string) $aidtolookfor);
                    if ($row['name'] == $aidsourcename) {
                        return $row['id'];
                    }
                }
                //no name match. pick the one with the assessment closest to the start
                usort($res, function ($a, $b) {return $a['loc'] - $b['loc'];});
                return $res[0]['id'];
            }

            // still haven't found it, so nothing in our current course has the
            // desired assessment as an ancestor.  Try finding something just with
            // the right name maybe?
            $stm = $this->dbh->prepare("SELECT id FROM imas_assessments WHERE name=:name AND courseid=:courseid");
            $stm->execute(array(':name' => $aidsourcename, ':courseid' => $destcid));
            if ($stm->rowCount() > 0) {
                return $stm->fetchColumn(0);
            }
        }
        return false;
    }

    /**
     * Records lineitem in database if set, or cretes a new one if possible
     *
     * TODO: figure out what to do in case of failure or inability to create lineitem
     *
     * @param LTI_Message_Launch $launch
     * @param LTI_Placement      $link
     * @param array              $info    name, ptsposs, and optionally date_by_lti, enddate, startdate
     * @param LTI_Localcourse    $localcourse
     */
    public function set_or_create_lineitem(LTI\LTI_Message_Launch $launch, LTI\LTI_Placement $link,
        array $info, LTI\LTI_Localcourse $localcourse
    ): bool {
        $platform_id = $launch->get_platform_id();
        $itemtype = $link->get_typenum();
        $typeid = $link->get_typeid();
        if ($launch->can_set_grades()) { // no need to proceed if we can't send back grades
            $lineitemstr = $launch->get_lineitem();
            $lineitem = LTI\LTI_Lineitem::new ()
                ->set_resource_id($itemtype . '-' . $typeid);
                
            if ($link->get_placementtype() == 'assess' ||
                (function_exists('lti_is_reviewable') && lti_is_reviewable($link))
            ) {
                $submission_review = LTI\LTI_Grade_Submission_Review::new ()
                    ->set_reviewable_status(["Submitted"]);
                $lineitem->set_submission_review($submission_review);
            }
            if ($lineitemstr === false && $launch->can_create_lineitem()) {
                // there wasn't a lineitem in the launch, so find or create one
                $ags = $launch->get_ags();

                // set score maximum and label of new lineitem
                $lineitem->set_score_maximum($info['ptsposs'])
                    ->set_label($info['name']); 

                if ($link->get_placementtype() == 'assess') {
                    // TODO: figure this out.  Ideally we should link the lineitem to
                    // the resource_link.id, but Canvas doesn't seem to like this ?
                    // Perhaps it doesn't recognize the link as owned by the tool.
                    // $lineitem->set_resource_link_id($launch->get_resource_link()['id']);
                }
                if (empty($info['date_by_lti']) && !empty($info['startdate'])) {
                    $lineitem->set_start_date_time(date(DATE_ATOM, $info['startdate']));
                }
                if (empty($info['date_by_lti']) && !empty($info['enddate']) && $info['enddate'] < 2000000000) {
                    $lineitem->set_end_date_time(date(DATE_ATOM, $info['enddate']));
                }
                $newlineitem = $ags->find_or_create_lineitem($lineitem);
                if ($newlineitem === false) {
                    // TODO: handle lineitem creation failure somehow.
                    // What are we going to do in this case?
                    return false;
                }
                $lineitemstr = $newlineitem->get_id();
            } else if ($launch->can_create_lineitem() &&
                !empty($lineitem->get_submission_review())
            ) {
                // lineitem already defined. Update it with submissionReview info
                $ags = $launch->get_ags();
                $lineitem->set_id($lineitemstr);
                $ags->update_lineitem($lineitem);
            }
            if (!empty($lineitemstr)) {
                $query = 'INSERT INTO imas_lti_lineitems (itemtype,typeid,lticourseid,lineitem) 
                    VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE lineitem=VALUES(lineitem)';
                $stm = $this->dbh->prepare($query);
                $stm->execute(array($itemtype, $typeid, $localcourse->get_id(), $lineitemstr));
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sets or updates a lineitem, but only if a lineitem was included in the
     * launch. Does not attempt to create a line item
     * @param LTI_Message_Launch $launch
     * @param LTI_Placement      $link
     * @param LTI_Localcourse    $localcourse
     */
    public function set_or_update_lineitem(LTI\LTI_Message_Launch $launch, LTI\LTI_Placement $link,
        LTI\LTI_Localcourse $localcourse
    ): void {
        $itemtype = $link->get_typenum();
        $typeid = $link->get_typeid();
        if ($launch->can_set_grades()) {
            // no need to proceed if we can't send back grades
            $lineitemstr = $launch->get_lineitem();
            if ($lineitemstr !== false) {
                $query = 'INSERT INTO imas_lti_lineitems (itemtype,typeid,lticourseid,lineitem)
          VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE lineitem=VALUES(lineitem)';
                $stm = $this->dbh->prepare($query);
                $stm->execute(array($itemtype, $typeid, $localcourse->get_id(), $lineitemstr));
            }
        }
    }

    /**
     * Determine if there is a lineitem stored in the database already
     * @param   LTI_Placement      $link
     * @param   LTI_Localcourse    $localcourse
     * @return bool
     */
    public function has_lineitem(LTI\LTI_Placement $link, LTI\LTI_Localcourse $localcourse): bool
    {
        $itemtype = $link->get_typenum();
        $typeid = $link->get_typeid();
        $stm = $this->dbh->prepare('SELECT lineitem FROM imas_lti_lineitems WHERE itemtype=? AND typeid=? AND lticourseid=?');
        $stm->execute(array($itemtype, $typeid, $localcourse->get_id()));
        return ($stm->fetchColumn(0) !== false);
    }

    /**
     * Get all assessments in course
     * @param  int   $cid [description]
     * @return array of courses with id,name
     */
    public function get_assessments(int $cid): array
    {
        $stm = $this->dbh->prepare('SELECT id,name FROM imas_assessments WHERE courseid=? ORDER BY name');
        $stm->execute(array($cid));
        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get old assessment_sessions.id
     * @param int $uid  target userid
     * @param int $aid  assessment id
     * @return int asid
     */
    public function get_old_asid($uid, $aid)
    {
        $stm = $this->dbh->prepare('SELECT id FROM imas_assessment_sessions WHERE userid=? AND assessmentid=?');
        $stm->execute(array($uid, $aid));
        return $stm->fetchColumn(0);
    }

    /**
     * Get new assessment grades
     */
    public function get_assess_grades($courseid, $aid, $platform_id, $isquiz, $includeempty) 
    {
        $query = 'SELECT istu.userid,ilu.ltiuserid,iar.score,iar.status,istu.lticourseid FROM 
            imas_students AS istu 
            JOIN imas_ltiusers AS ilu ON istu.userid=ilu.userid AND ilu.org=?
            LEFT JOIN imas_assessment_records AS iar 
              ON istu.userid=iar.userid AND iar.assessmentid=?
            WHERE istu.courseid=?';
        $stm = $this->dbh->prepare($query);
        $stm->execute(array('LTI13-'.$platform_id, $aid, $courseid));
        $out = [];
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            if (!$includeempty && 
                ($row['score'] === null || ($isquiz && ($row['status']&64)==0))
            ) {
                continue; // no record or quiz with no submission
            } 
            if ($row['score'] === null) {
                $row['score'] = 0;
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Create and enroll users in a course based on roster data
     * @param  array $data        array('members'=>, 'context'=>)
     * @param  LTI_Localcourse $localcourse
     * @param  int $platform_id
     * @return array (count of added stu, array of unmatched stus)
     */
    public function update_roster(array $data, LTI\LTI_Localcourse $localcourse,
        int $platform_id
    ): array{
        $section = '';
        if (!empty($data['context']['label'])) {
            $section = $data['context']['label'];
        }

        $stm = $this->dbh->prepare("SELECT deflatepass FROM imas_courses WHERE id=:id");
        $stm->execute(array(':id'=>$localcourse->get_courseid()));
        $deflatepass = $stm->fetchColumn(0);

        $query = 'SELECT iu.FirstName,iu.LastName,ilu.ltiuserid,istu.id FROM
            imas_users AS iu JOIN imas_ltiusers AS ilu ON ilu.userid=iu.id AND ilu.org=?
            JOIN imas_students AS istu ON istu.userid=iu.id WHERE istu.courseid=?';
        $stm = $this->dbh->prepare($query);
        $stm->execute(array('LTI13-' . $platform_id, $localcourse->get_courseid()));
        $current = array();
        $enrollcnt = 0;
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $current[$row['ltiuserid']] = $row;
        }
        if (!is_array($data['members'])) {
            $data['members'] = array();
        }
        foreach ($data['members'] as $member) {
            if (standardize_role($member['roles']) !== 'Learner') {
                // only handling students
                continue;
            }
            if (!isset($current[$member['user_id']])) {
                // not enrolled in course
                // See if user_id exists already
                $stm = $this->dbh->prepare('SELECT userid FROM imas_ltiusers WHERE ltiuserid=? AND org=?');
                $stm->execute(array($member['user_id'], 'LTI13-' . $platform_id));
                $localuserid = $stm->fetchColumn(0);
                if ($localuserid === false) {
                    // No existing user, create user if we have enough info
                    if (!empty($member['given_name']) && !empty($member['family_name'])) {
                        // create user account
                        $localuserid = $this->create_user_account([
                            'SID' => uniqid(), // temporary
                            'pwhash' => 'pass',
                            'rights' => 10,
                            'firstname' => $member['given_name'],
                            'lastname' => $member['family_name'],
                            'email' => '',
                            'msgnot' => 0,
                            'groupid' => 0,
                        ]);
                        $num = $this->create_lti_user($localuserid, $member['user_id'], $platform_id);
                        $this->set_user_SID($localuserid, 'lti-' . $num);
                    } else {
                        // skip this user
                        continue;
                    }
                }
                // enroll in course
                $stm = $this->dbh->prepare('INSERT INTO imas_students (userid,courseid,section,latepass,lticourseid) VALUES (?,?,?,?,?)');
                $stm->execute(array($localuserid, $localcourse->get_courseid(), $section, $deflatepass, $localcourse->get_id()));

                $enrollcnt++;
            } else {
                // found them - remove from current list
                unset($current[$member['user_id']]);
            }
        }
        return array($enrollcnt, $current);
    }

    /**
     * Mark the students indicated as locked from the course
     * @param  array $stuids   array of imas_students.id
     * @param  int  $courseid  the course id
     * @return void
     */
    public function lock_stus(array $stuids, int $courseid): void
    {
        if (count($stuids) > 0) {
            $ph = Sanitize::generateQueryPlaceholders($stuids);
            $query = "UPDATE imas_students SET locked=? WHERE id IN ($ph) AND courseid=?";
            $stm = $this->dbh->prepare($query);
            $stm->execute(array_merge(array(time()), $stuids, array($courseid)));
        }
    }

    private function verify_migration_claim($claim) {
        $key = $claim['oauth_consumer_key'];
        $query = "SELECT password FROM imas_users WHERE SID=? 
            AND (rights=11 OR rights=76 OR rights=77)";
        $stm = $this->dbh->prepare($query);
        $stm->execute(array($key));
        $secret = $stm->fetchColumn(0);
        if ($secret === false) {
            return false;
        }
        
        $sig = base64_encode(hash_hmac('sha256', $claim['signing_string'], $secret));
        return ($sig == $claim['oauth_consumer_key_sign']);
    }

}
