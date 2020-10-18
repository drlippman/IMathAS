<?php

/**
 * This is a basic version, more suitable for admins than general users. 
 * Eventually flushthis out with ability for teachers or group admins to edit their
 * own registrations.
 *
 * TODO:  Add subsitution variables for all platforms
 *   context_history = $Context.id.history
 *
 */



require("../../init.php");

if (($myrights < 100 && empty($CFG['LTI']['useradd13'])) || $myrights < 40) {
  exit;
}

if (isset($_POST['delete']) && $myrights == 100) {
  $stm = $DBH->prepare("DELETE FROM imas_lti_platforms WHERE id=?");
  $stm->execute(array($_POST['delete']));
  header('Location: ' . $basesiteurl . "/lti/admin/platforms.php");
  exit;
}
$lms = $_POST['lms'];
if (!empty(trim($_POST[$lms.'_issuer'])) &&
  !empty(trim($_POST[$lms.'_clientid'])) &&
  !empty(trim($_POST[$lms.'_keyseturl'])) &&
  !empty(trim($_POST[$lms.'_tokenurl'])) &&
  !empty(trim($_POST[$lms.'_authurl']))
) {
  $stm = $DBH->prepare("INSERT INTO imas_lti_platforms (issuer,client_id,auth_login_url,auth_token_url,key_set_url,uniqid) VALUES (?,?,?,?,?,?)");
  $stm->execute(array(
    trim($_POST[$lms.'_issuer']),
    trim($_POST[$lms.'_clientid']),
    trim($_POST[$lms.'_authurl']),
    trim($_POST[$lms.'_tokenurl']),
    trim($_POST[$lms.'_keyseturl']),
    trim($_POST[$lms.'_uniqid'])
  ));
  header('Location: ' . $basesiteurl . "/lti/admin/platforms.php");
  exit;
}

$bbclientid = false;
$query = "SELECT ip.id,ip.issuer,ip.client_id,ip.created_at,
  GROUP_CONCAT(CONCAT(ig.name,' (',DATE_FORMAT(iga.created_at,'%e %b %Y'),')') SEPARATOR ';;') AS groups FROM
  imas_lti_platforms AS ip
  LEFT JOIN imas_lti_deployments AS id ON id.platform=ip.id
  LEFT JOIN imas_lti_groupassoc AS iga ON iga.deploymentid=id.id
  LEFT JOIN imas_groups AS ig ON iga.groupid=ig.id ";
if ($myrights < 100) {
    $query .= 'WHERE iga.groupid=? ';
}
$query .= "GROUP BY ip.id ORDER BY ip.issuer,ip.created_at";
if ($myrights < 100) {
    $stm = $DBH->prepare($query);
    $stm->execute(array($groupid));
} else {
    $stm = $DBH->query($query);
}
$platforms = $stm->fetchAll(PDO::FETCH_ASSOC);
foreach ($platforms as $row) {
  if ($row['issuer'] == 'https://blackboard.com') {
    $bbclientid = $row['client_id'];
  }
}

$uniqid = uniqid();

$pagetitle = _('LTI 1.3 Platforms');
require("../../header.php");

echo '<div class=breadcrumb>'.$breadcrumbbase;
echo '<a href="../../admin/admin2.php">'._('Admin').'</a> ';
echo '&gt; '._('LTI 1.3 Platforms').'</div>';

$domain = Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']);

$domainsite = $httpmode . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']);

echo '<h1>'._('LTI 1.3 Platforms').'</h1>';

if ($myrights == 100) {
    echo '<div class="cp">';
    echo '<a href="privkeys.php">'._('Manage Private Keys').'</a>';
    echo '</div>';
}
echo '<h2>'._('Existing Platforms').'</h2>';

echo '<form method="post" action="platforms.php">';

if ($platforms === false) {
  echo '<p>'._('No platforms').'</p>';
} else {
  echo '<table class=gb><thead><tr>';
  echo '<th>'._('Issuer').'</th>';
  echo '<th>'._('ClientID').'</th>';
  echo '<th>'._('Created').'</th>';
  echo '<th>'._('Groups').'</th>';
  if ($myrights == 100) {
    echo '<th>'._('Delete').'</th>';
  }
  echo '</tr></thead><tbody>';
  foreach ($platforms as $i=>$row) {
    echo '<tr class="'.($i%2==0?'even':'odd').'">';
    echo '<td>'.Sanitize::encodeStringForDisplay($row['issuer']).'</td>';
    echo '<td>'.Sanitize::encodeStringForDisplay($row['client_id']).'</td>';
    echo '<td>'. date("j M Y ", strtotime($row['created_at'])).'</td>';
    echo '<td>'. str_replace(';;','<br>',Sanitize::encodeStringForDisplay($row['groups'])).'</td>';
    if ($myrights == 100) {
        echo '<td><button type=submit name="delete" value="'.Sanitize::encodeStringForDisplay($row['id']).'" ';
        echo 'onclick="return confirm(\''._('Are you SURE you want to delete this platform?').'\');">';
        echo _('Delete').'</button></td>';
    }
    echo '</tr>';
  }
  echo '</tbody></table>';

}
echo '<h2>'._('New Platform').'</h2>';

if (count($platforms)>0) {
    if ($myrights < 100) {
        echo '<p class="noticetext">'._('Since you already have an existing platform registration, you should not need to add a New Platform unless you have changed LMSs').'</p>';
    }
}
echo '<p><label for=lms>'._('Select your LMS').'</label>: ';
echo '<select id=lms name=lms>';
echo  '<option value=other>'._('Other').'</option>';
echo  '<option value=canvas>Canvas</option>';
echo  '<option value=bb>Blackboard</option>';
echo  '<option value=d2l>D2L Brightspace</option>';
echo  '<option value=moodle>Moodle</option>';
echo '</select></p>';

// Other
echo '<div id=other class=lmsinstr>';
echo '<p>'._('Info to put in the LMS').'</p>';
echo '<ul>';
echo '<li>'._('Target Link URI / Tool URL / Redirect URI:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
echo '<li>'._('OpenID Connect / Initiate Login URL:').' <span class=tocopy>'.$basesiteurl.'/lti/login.php?u='.$uniqid.'</span></li>';
echo '<li>'._('Keyset URL:').' <span class=tocopy>'.$basesiteurl.'/lti/jwks.php</span></li>';
echo '</ul>';
echo '<p>'._('Info from LMS').'</p>';
echo '<ul>';
echo '<li><label>'._('Issuer/Platform ID:').' <input name=other_issuer size=50/></label></li>';
echo '<li><label>'._('Client ID:').' <input name=other_clientid size=50/></label></li>';
echo '<li><label>'._('Keyset URL:').' <input name=other_keyseturl size=50/></label></li>';
echo '<li><label>'._('Token URL:').' <input name=other_tokenurl size=50/></label></li>';
echo '<li><label>'._('Authentication URL:').' <input name=other_authurl size=50/></label></li>';
echo '<li><label>'._('The u= from the OpenID Connect URL:').' <input size=15 name=other_uniqid value="'.Sanitize::encodeStringForDisplay($uniqid).'" /></label></li>';
echo '</ul>';
echo '<button type=submit>'._('Add Platform').'</button></p>';
echo '</div>';

// Canvas
echo '<div id=canvas class=lmsinstr style="display:none;">';
echo '<p>'._('To enable LTI 1.3 in a Canvas instance, the site administrator should:').'</p>';
echo '<ul>';
echo '<li>'._('Go to Admin, then Developer Keys, click +Developer Key, then select +LTI Key').'</li>';
echo '<li>'._('For Redirect URIs, enter:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';

echo '<li>'._('Set Configure Method: Enter URL').'</li>';
echo '<li>'._('Enter JSON URL:').' <span class=tocopy>'.$basesiteurl.'/lti/canvasconfig.php</span></li>';
/* manual setup instructions:
echo '<li>'._('Set Configure Method: Manual Entry, and enter the values:');
echo '<ul>';
echo ' <li>'._('Target Link URI:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
echo ' <li>'._('OpenID Connect Initiation Url:').' <span class=tocopy>'.$basesiteurl.'/lti/login.php?u='.$uniqid.'</span></li>';
echo ' <li>'._('JWK Method: Public JWK URL').'</li>';
echo ' <li>'._('Public JWK URL:').' <span class=tocopy>'.$basesiteurl.'/lti/jwks.php</span></li>';
echo ' </ul></li>';
echo '<li>'._('Under LTI Advantage Services, enable:').'<ul>';
echo ' <li>'._('Can create and view assignment data in the gradebook associated with the tool.').'</li>';
echo ' <li>'._('Can view submission data for assignments associated with the tool.').'</li>';
echo ' <li>'._('Can create and update submission results for assignments associated with the tool.').'</li>';
echo ' </ul></li>';
echo '<li>'._('In Placements, remove the default values, and add Assignment Selection.').'<ul>';
echo ' <li>'._('In the Assignment Selection options, set the Message Type to: LtiDeepLinkingRequest').'</li>';
echo ' <li>'._('For Target Link URI, enter:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
echo ' </ul></li>';
*/
echo '<li>'._('After submitting, turn the State ON').'</li>';
echo '<li>'._('Copy the numeric value shown in the Details column.  This is the Client ID. (You do not need to click the Show Key button)').'</li>';
echo '<li>'._('Go to Admin, then Settings, then Apps, and click View App Configurations').'</li>';
echo '<li>'._('Click +App').'</li>';
echo '<li>'._('For Configuration Type, select By Client ID. Paste in the Client ID you copied down above, and hit Submit.').'</li>';
echo '</ul>';

echo '<p>'._('Now enter the Client ID you copied down above from the Details column.').'</p>';
echo '<ul>';
echo '<li><label>'._('Details value (Client ID):').' <input name=canvas_clientid size=50/></label></li>';
echo '</ul>';
echo '<input type="hidden" name=canvas_issuer value="https://canvas.instructure.com"/>';
echo '<input type="hidden" name=canvas_keyseturl value="https://canvas.instructure.com/api/lti/security/jwks"/>';
echo '<input type="hidden" name=canvas_tokenurl value="https://canvas.instructure.com/login/oauth2/token"/>';
echo '<input type="hidden" name=canvas_authurl value="https://canvas.instructure.com/api/lti/authorize_redirect"/>';
echo '<input type="hidden" name=canvas_uniqid value="" />';

echo '<button type=submit>'._('Add Platform').'</button></p>';
echo '</div>';

// Blackboard
echo '<div id=bb class=lmsinstr style="display:none;">';
if ($bbclientid === false && $myrights == 100) {
  echo '<p>'._('For Blackboard, the tool only has to be registered once by the tool provider (not the school), on developer.blackboard.com. ');
  echo _('After that, individidual schools only need the Client ID to enable the tool in their Blackboard instance. ');
  echo '</p>';
  echo '<p>'._('Info to use when registering the tool:').'</p>';
  echo '<ul>';
  echo '<li>'._('Login Initiation URL:').' <span class=tocopy>'.$basesiteurl.'/lti/login.php</span></li>';
  echo '<li>'._('Tool Redirect URLs:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
  echo '<li>'._('Tool JWKS URL:').' <span class=tocopy>'.$basesiteurl.'/lti/jwks.php</span></li>';
  echo '<li>'._('Signing Algorithm:').' RS256</li>';
  echo ' <li>'._('In the Tool Provider Custom Parameters box, enter:').' <code>context_history=$Context.id.history</code></li>';
  echo '</ul>';
  echo '<p>'._('Info from LMS').'</p>';
  echo '<ul>';
  echo '<li><label>'._('Application ID / Client ID:').' <input name=bb_clientid size=50/></label></li>';
  echo '<li><label>'._('Issuer:').' <input name=bb_issuer size=50 value="https://blackboard.com"/></label></li>';
  echo '<li><label>'._('Public Keyset URL:').' <input name=bb_keyseturl size=50/></label></li>';
  echo '<li><label>'._('Auth Token Endpoint:').' <input name=bb_tokenurl size=50/></label></li>';
  echo '<li><label>'._('OIDC auth request endpoint:').' <input name=bb_authurl size=50/></label></li>';
  echo '<input type="hidden" name=bb_uniqid value="" />';
  echo '</ul>';
  echo '<button type=submit>'._('Add Blackboard').'</button></p>';
} else if ($bbclientid === false) {
  echo '<p>'._('This site is not yet set up for BlackBoard integration').'</p>';
} else {
  echo '<p>'._('To enable LTI 1.3 in a BlackBoard instance, the site administrator should:').'</p>';
  echo '<ul>';
  echo ' <li>'._('Go to Admin, then Integrations: LTI Tool Providers').'</li>';
  echo ' <li>'._('Click Register LTI 1.3/Advantage Tool').'</li>';
  echo ' <li>'._('Enter the Client ID:').' <span class=tocopy>'.Sanitize::encodeStringForDisplay($bbclientid).'</span></li>';
  echo ' <li>'._('On the tool status page, make sure the tool is Approved. ').'</li>';
  echo ' <li>'._('Also ensure User Fields to Send includes Name and Role in Course, and set Allow grade service access and Allow Membership Service Access to Yes.').'</li>';
  echo '</ul>';
  echo '<p>'._('If you want to enable deep linking, to allow easy addition of new items in Blackboard:').'</p>';
  echo '<ul>';
  echo ' <li>'._('Find the tool and using the dropdown select Manage Placements.').'</li>';
  echo ' <li>'._('Click Create Placement').'</li>';
  echo ' <li>'._('Give it a label, and make it available').'</li>';
  echo ' <li>'._('Set the type to Deep Linking content tool, and disable the Allow student access').'</li>';
  echo ' <li>'._('Enter the Tool Provider URL:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
  echo '</ul>';
}
echo '</div>';

// D2l
echo '<div id=d2l class=lmsinstr style="display:none;">';
echo '<p>'._('To enable LTI 1.3 in a D2L Brightspace instance, the site administrator should:').'</p>';
echo '<ul>';
echo '<li>'._('Go to the Admin Tool: Manage Extensibility tool').'</li>';
echo '<li>'._('Go to the LTI Advantage tab, and click Register Tool.').'</li>';
echo '<li>'._('Enter these values:').'<ul>';
echo ' <li>'._('Domain:').' <span class=tocopy>'.$domainsite.'</span></li>';
echo ' <li>'._('Redirect URLs:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
echo ' <li>'._('OpenID Connect Login URL:').' <span class=tocopy>'.$basesiteurl.'/lti/login.php?u='.$uniqid.'</span></li>';
echo ' <li>'._('Keyset URL:').' <span class=tocopy>'.$basesiteurl.'/lti/jwks.php</span></li>';
echo ' </ul></li>';
echo '<li>'._('Enable the Extensions: Assignment and Grade Services and Deep Linking').'</li>';
echo '</ul>';

echo '<p>'._('After clicking Register, copy the provided values here:').'</p>';
echo '<ul>';
echo '<li><label>'._('Client Id:').' <input name=d2l_clientid size=50/></label></li>';
echo '<li><label>'._('Brightspace Keyset URL:').' <input name=d2l_keyseturl size=50/></label></li>';
echo '<li><label>'._('Brightspace OAuth2 Access Token URL:').' <input name=d2l_tokenurl size=50/></label></li>';
echo '<li><label>'._('OpenID COnnect Authentication Endpoint:').' <input name=d2l_authurl size=50/></label></li>';
echo '<li><label>'._('Issuer:').' <input name=d2l_issuer size=50/></label></li>';
echo '<li><label>'._('The u= from the OpenID Connect URL:').'<input size=15 name=d2l_uniqid value="'.Sanitize::encodeStringForDisplay($uniqid).'" /></label></li>';
echo '</ul>';

echo '<p>'.('Once that is done, click View Deployments, then click New Deployment').'</p>';
echo '<ul>';
echo '<li>'._('Select the tool you just added, and enter a Name.').'</li>';
echo '<li>'._('Enable the Extensions: Assignment and Grade Services and Deep Linking.').'</li>';
echo '<li>'._('Under Security Settings, enable Name (First and Last).').'</li>';
echo '<li>'._('Select the Org Units you want to make the tool available to.  For example, you could make it just available to the Math department.').'</li>';
echo '</ul>';

echo '<p>'.('Once that is done, click View Links, then click New Link').'</p>';
echo '<ul>';
echo '<li>'._('Enter a name.').'</li>';
echo '<li>'._('For URL enter:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
echo '<li>'._('For Type, select: Deep Linking Quicklink.').'</li>';
echo '</ul>';

echo '<button type=submit>'._('Add Platform').'</button></p>';
echo '</div>';

// Moodle
echo '<div id=moodle class=lmsinstr style="display:none;">';
echo '<p>'._('To enable LTI 1.3 in a Moodle instance, the site administrator should:').'</p>';

echo '<ul>';
echo '<li>'._('Go to Site Administration, Plugins, then External Tools: Manage Tools.').'</li>';
echo '<li>'._('Click Configure a tool manually.').'</li>';
echo '<li>'._('Enter these values:').'<ul>';
echo ' <li>'._('Tool URL:').' <span class=tocopy>'.$domain.'</span></li>';
echo ' <li>'._('LTI version: LTI 1.3').'</li>';
echo ' <li>'._('Public key type: Keyset Url.').'</li>';
echo ' <li>'._('Public Keyset:').' <span class=tocopy>'.$basesiteurl.'/lti/jwks.php</span></li>';
echo ' <li>'._('Initiate Login URL:').' <span class=tocopy>'.$basesiteurl.'/lti/login.php</span></li>';
echo ' <li>'._('Redirection URL(s):').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
echo ' <li>'._('Click Show more, then Enable Content-Item Message').'</li>';
echo ' <li>'._('Content Selection URL:').' <span class=tocopy>'.$basesiteurl.'/lti/launch.php</span></li>';
echo ' </ul></li>';
echo '<li>'._('Under Services, for IMS LTI Assignment and Grade Services select Use this service for grade sync and column management.').'</li>';
echo '<li>'._('Under Services, for IMS LTI Names and Role Provisining Services select Use this service.').'</li>';
echo '<li>'._('Under Privacy, set Share launcher\'s name with tool to Always, and enable Force SSL.').'</li>';
echo '</ul>';

echo '<p>'._('After hitting Save, find the tool and click the Details icon, and copy the values here:').'</p>';
echo '<ul>';
echo '<li><label>'._('Platform ID:').' <input name=moodle_issuer size=50/></label></li>';
echo '<li><label>'._('Client ID:').' <input name=moodle_clientid size=50/></label></li>';
echo '<li><label>'._('Public keyset URL:').' <input name=moodle_keyseturl size=50/></label></li>';
echo '<li><label>'._('Access token URL:').' <input name=moodle_tokenurl size=50/></label></li>';
echo '<li><label>'._('Authentication request URL:').' <input name=moodle_authurl size=50/></label></li>';
echo '</ul>';
echo '<input type="hidden" name=moodle_uniqid value="" />';
echo '<button type=submit>'._('Add Platform').'</button></p>';
echo '</div>';
if ($myrights < 100) {
    echo '<p>'._('Note: Platforms are not associated with a group until the first launch from the LMS, so your added platform may not display here until that happens.').'</p>';
}

echo '</form>';
?>
<script type="text/javascript">
$(function() {
  $('.tocopy').each(function(i,eltocopy) {
    $(eltocopy).after($('<button>', {type: 'button', text: '<?php echo _('Copy');?>'})
      .on('click', function() {
        var el = document.createElement('textarea');
        el.value = $(this).prev().text();
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
      }));
  });
  $('#lms').on('change', function() {
    var lms = this.value;
    $(".lmsinstr").hide();
    $("#"+lms).show();
  });
  $('input[name=moodle_issuer]').on('change', function () {
    var iss = this.value;
    $("input[name=moodle_keyseturl]").val(iss+'/mod/lti/certs.php');
    $("input[name=moodle_tokenurl]").val(iss+'/mod/lti/token.php');
    $("input[name=moodle_authurl]").val(iss+'/mod/lti/auth.php');
  });
});
</script>
<?php
require('../../footer.php');
