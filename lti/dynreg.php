<?php

require("../init_without_validate.php");
$flexwidth = true;
$nologo = true;
require("../header.php");

if (empty($CFG['LTI']['autoreg'])) {
    echo "Dynamic registration is not currently enabled. Contact the system admin.";
    exit;
}

function regerror($err) {
    echo '<p>'.$err.'<p>';
    echo '<p><button type=button onclick="(window.opener || window.parent).postMessage({subject:\'org.imsglobal.lti.close\'},\'*\');">';
    echo _('Close') . '</button></p>';
    exit;
}

// Read configuration endpoint from query string
$configurl = $_GET['openid_configuration'];
$token = $_GET['registration_token'];

// Call config endpoint to get config data
$configdata = json_decode(file_get_contents($configurl), true);;

if (empty($configdata['issuer']) || 
    empty($configdata['authorization_endpoint']) || 
    empty($configdata['token_endpoint']) || 
    empty($configdata['jwks_uri']) || 
    empty($configdata['registration_endpoint'])
) {
    regerror(_("Invalid configuration data from platform."));
    exit;
}

// TODO: check for scopes_supported are sufficient, messages_supported?
/*
if (empty($configdata['scopes_supported'])) {
    echo "Missing scopes_supported claim";
    exit;
}
*/

$iss = $configdata['issuer'];
$auth_endpoint = $configdata['authorization_endpoint'];
$token_endpoint = $configdata['token_endpoint'];
$jwks = $configdata['jwks_uri'];
$regurl = $configdata['registration_endpoint'];
$auth_server = $configdata['authorization_server'] ?? '';

// check valid configurl; must start with issuer
if (strpos($configurl, $iss) !== 0) {
    regerror(_("Invalid openid_configuration/issuer"));
    exit;
}

// Submit registration request

$uniqid = uniqid();
$domain = Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']);

$post = [
    'application_type' => 'web',
    'response_types' => ['id_token'],
    'grant_types' => ["implict", "client_credentials"],
    "initiate_login_uri" => $basesiteurl.'/lti/login.php?u='.$uniqid,
    "redirect_uris" =>
      [$basesiteurl.'/lti/launch.php',
       $basesiteurl.'/bltilaunch.php'],
    "client_name" => $installname,
    "jwks_uri" => $basesiteurl.'/lti/jwks.php',
    "token_endpoint_auth_method" => "private_key_jwt",
    "scope" => "https://purl.imsglobal.org/spec/lti-ags/scope/score https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly https://purl.imsglobal.org/spec/lti-ags/scope/lineitem https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly",
    "https://purl.imsglobal.org/spec/lti-tool-configuration" => [
        "domain" => $domain,
        "description" => _("Math Assessment"),
        "target_link_uri" => $basesiteurl,
        "custom_parameters" => [
            "context_history" => '$Context.id.history',
            'link_end_avail_time' => '$ResourceLink.available.endDateTime',
            'link_end_sub_time' => '$ResourceLink.submission.endDateTime',
            'link_history' => '$ResourceLink.id.history'
        ],
        "claims" => ["iss", "sub", "name", "given_name", "family_name"],
        "messages" => [
            [
                "type" => "LtiDeepLinkingRequest",
                "target_link_uri" => $basesiteurl.'/lti/launch.php',
                "label" => sprintf(_("Add %s Assessment"), $installname)
            ],
            [
                "type" => "LtiResourceLink",
                "target_link_uri" => $basesiteurl.'/lti/launch.php'
            ]
        ]
    ]
];

$ch = curl_init($regurl);
$authorization = "Authorization: Bearer ".$token; // Prepare the authorisation token
curl_setopt($ch, CURLOPT_HTTPHEADER, array($authorization, 'Content-Type: application/json')); // Inject the token into the header
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1); // Specify the request method as POST
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post)); // Set the posted fields
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // This will follow any redirects
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$result = curl_exec($ch); // Execute the cURL statement
curl_close($ch); // Close the cURL connection

// Get the client_id from the registration result
$regdata = json_decode($result, true); // Return the received data

if (empty($regdata['client_id'])) {
    regerror(_("Registration did not return a client_id.  Aborting."));
    exit;
}
$clientid = $regdata['client_id'];

// record the registration

$stm = $DBH->prepare("SELECT * FROM imas_lti_platforms WHERE issuer=? AND client_id=?");
$stm->execute([$iss, $clientid]);
$existing = $stm->fetch(PDO::FETCH_ASSOC);

echo '<p>';
if ($existing === false) {
    // new registration
    $stm = $DBH->prepare("INSERT INTO imas_lti_platforms (issuer,client_id,auth_login_url,auth_token_url,auth_server,key_set_url,uniqid,created_by) VALUES (?,?,?,?,?,?,?,?)");
    $stm->execute(array(
        trim($iss),
        trim($clientid),
        trim($auth_endpoint),
        trim($token_endpoint),
        trim($auth_server),
        trim($jwks),
        trim($uniqid),
        0
    ));
    echo _("Registration Complete");
} else {
    // existing registration found.  Updating it could allow security holes
    echo sprintf(_("Existing registration on %s found. Tool data was resent to the LMS, but LMS data in %s will not be changed."), $installname, $installname);
}
echo '</p>';
echo '<p><button type=button onclick="(window.opener || window.parent).postMessage({subject:\'org.imsglobal.lti.close\'},\'*\');">';
echo _('Done') . '</button></p>';
