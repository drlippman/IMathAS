<?php
foreach (glob(__DIR__ . '/../lti/php-jwt/*.php') as $filename) {
    require_once $filename;
}
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

class GoogleOAuthManager {
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    // how long a Google identity waiting to be connected to an account stays in the session
    const PENDING_TTL = 1800;

    public function __construct($clientId, $clientSecret, $redirectUri) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }

    /**
     * Build the Google authorization URL and stash CSRF state/return info in session.
     * Only used for the login-page "Sign in with Google" flow - accounts can only be
     * connected by signing in with an existing username/password, never directly from
     * the profile page, to avoid an account-takeover route that skips password entry.
     */
    public function getAuthorizationUrl($returnTo) {
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_oauth_returnto'] = $returnTo;

        $params = array(
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        );
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function exchangeCodeForToken($code) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        )));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        //curl_close($ch);
        if ($resp === false) {
            throw new Exception('Unable to contact Google to exchange the authorization code');
        }
        $data = json_decode($resp, true);
        if (empty($data['id_token'])) {
            throw new Exception('Google did not return an ID token');
        }
        return $data;
    }

    /**
     * Verify a Google ID token's signature (via Google's published JWKS,
     * reusing the Firebase\JWT library already vendored for LTI 1.3), issuer,
     * audience, and email_verified claim.
     *
     * @return array ['sub'=>, 'email'=>, 'given_name'=>, 'family_name'=>]
     * @throws Exception on any verification failure
     */
    public function verifyIdToken($idToken) {
        $certs = @file_get_contents('https://www.googleapis.com/oauth2/v3/certs');
        if ($certs === false) {
            throw new Exception('Unable to fetch Google signing keys');
        }
        $keySet = json_decode($certs, true);
        if (empty($keySet['keys'])) {
            throw new Exception('Google signing key response was invalid');
        }

        $keys = array();
        foreach ($keySet['keys'] as $key) {
            try {
                $pubkey = openssl_pkey_get_details(JWK::parseKey($key));
                $keys[$key['kid']] = $pubkey['key'];
            } catch (Exception $e) {
                continue;
            }
        }

        try {
            $payload = (array) JWT::decode($idToken, $keys, array('RS256'));
        } catch (Exception $e) {
            throw new Exception('Google ID token verification failed: ' . $e->getMessage());
        }

        if (empty($payload['iss']) || !in_array($payload['iss'], array('accounts.google.com', 'https://accounts.google.com'))) {
            throw new Exception('Google ID token has an unexpected issuer');
        }
        if (empty($payload['aud']) || $payload['aud'] !== $this->clientId) {
            throw new Exception('Google ID token has an unexpected audience');
        }
        if (empty($payload['email_verified'])) {
            throw new Exception('Google account email is not verified');
        }

        return array(
            'sub' => $payload['sub'],
            'email' => $payload['email'],
            'given_name' => $payload['given_name'] ?? '',
            'family_name' => $payload['family_name'] ?? '',
        );
    }

    /**
     * Stores a verified Google identity in the session, waiting to be connected to an
     * account. The form that connects it must post back the link token (see
     * getPendingProfileForToken), so an unrelated login in the same session can't
     * connect it.
     */
    public static function setPendingProfile(array $profile) {
        $_SESSION['google_pending_profile'] = array(
            'sub' => $profile['sub'],
            'email' => $profile['email'],
            'given_name' => $profile['given_name'],
            'family_name' => $profile['family_name'],
            'linktoken' => bin2hex(random_bytes(16)),
            'created' => time(),
        );
        return $_SESSION['google_pending_profile'];
    }

    /**
     * The pending Google identity from the session, or null if there is none or it expired.
     */
    public static function getPendingProfile() {
        if (empty($_SESSION['google_pending_profile'])) {
            return null;
        }
        $p = $_SESSION['google_pending_profile'];
        if (empty($p['linktoken']) || empty($p['created']) || time() - $p['created'] > self::PENDING_TTL) {
            unset($_SESSION['google_pending_profile']);
            return null;
        }
        return $p;
    }

    /**
     * The pending Google identity, only if $token is the link token issued with it.
     */
    public static function getPendingProfileForToken($token) {
        $p = self::getPendingProfile();
        if ($p === null || !is_string($token) || !hash_equals($p['linktoken'], $token)) {
            return null;
        }
        return $p;
    }

    public function findLinkedUserBySub($sub) {
        global $DBH;
        $stm = $DBH->prepare("SELECT user_id FROM imas_google WHERE google_sub = :sub");
        $stm->execute([':sub' => $sub]);
        $userId = $stm->fetchColumn();
        return $userId !== false ? (int)$userId : null;
    }

    public function findUsernamesByEmail($email) {
        global $DBH;
        $stm = $DBH->prepare("SELECT SID FROM imas_users WHERE email = :email AND SID NOT LIKE 'lti-%'");
        $stm->execute([':email' => $email]);
        return $stm->fetchAll(PDO::FETCH_COLUMN);
    }

    public function linkUser($userId, $sub, $email) {
        global $DBH;
        // Note: this DB connection uses PDO::ERRMODE_WARNING (not ERRMODE_EXCEPTION), so a
        // unique-constraint violation on insert does not throw - it just makes execute()
        // return false. Check that directly rather than relying on a caught PDOException.
        $stm = $DBH->prepare("INSERT INTO imas_google (user_id, google_sub, google_email) VALUES (:user_id, :sub, :email)");
        $success = $stm->execute([':user_id' => $userId, ':sub' => $sub, ':email' => $email]);
        if (!$success) {
            if ($this->getLinkedAccount($userId)) {
                throw new Exception('This account already has a different linked Google account.');
            }
            throw new Exception('This Google account is already linked to a different user.');
        }
    }

    public function unlinkUser($userId) {
        global $DBH;
        $stm = $DBH->prepare("DELETE FROM imas_google WHERE user_id = :user_id");
        $stm->execute([':user_id' => $userId]);
    }

    public function getLinkedAccount($userId) {
        global $DBH;
        $stm = $DBH->prepare("SELECT google_email, created_at FROM imas_google WHERE user_id = :user_id");
        $stm->execute([':user_id' => $userId]);
        $row = $stm->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }
}
