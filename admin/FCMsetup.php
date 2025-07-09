<?php
require_once "../init.php";

if (isset($_POST['token'])) {
	$stm = $DBH->prepare("UPDATE imas_users SET FCMtoken=:token WHERE id=:id");
	$stm->execute(array(":token"=>$_POST['token'], ":id"=>$userid));
	echo "OK";
	exit;
}
if (isset($_POST['remove'])) {
	$stm = $DBH->prepare("UPDATE imas_users SET FCMtoken='' WHERE id=:id");
	$stm->execute(array(":id"=>$userid));
	echo "OK";
	exit;
}

$placeinhead = '<script src="https://www.gstatic.com/firebasejs/3.5.3/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/3.5.3/firebase-messaging.js"></script>
<script>
  // Initialize Firebase
  var FCMconfig = {
    apiKey: "'.Sanitize::encodeStringForJavascript($CFG['FCM']['webApiKey']).'",
    messagingSenderId: "'.Sanitize::encodeStringForJavascript($CFG['FCM']['SenderId']).'"
  };
  firebase.initializeApp(FCMconfig);

	const messaging = firebase.messaging();
	if ("serviceWorker" in navigator) {
	  navigator.serviceWorker.register(imasroot+"/firebase-messaging-sw.php").then(function(registration) {
	    // Registration was successful
	    console.log("ServiceWorker registration successful with scope: ", registration.scope);
			messaging.useServiceWorker(registration);
			if ("'.Sanitize::encodeStringForJavascript($FCMtoken).'" != "") {
				messaging.getToken()
				.then(function(token) {
					if (token=="'.Sanitize::encodeStringForJavascript($FCMtoken).'") {
						$("#havetoken").show(); $("#dosetup").hide();
					}
				})
				.catch(function(err) {
					console.log("Unable to retrieve token ", err);
				});;
			}
	  }).catch(function(err) {
	    // registration failed :(
	    console.log("ServiceWorker registration failed:" , err);
	  });
	}

function removeNotifications() {
	$.ajax({
		url: "FCMsetup.php",
		type: "POST",
		data: {"remove": true}
	}).done(function() {
		$("#havetoken").hide(); $("#otherdevice").hide(); $("#dosetup").show(); $("#stopnotifications").hide();
	});
}
function askPermission() {
	messaging.requestPermission()
	.then(function() {
	  console.log("Notification permission granted.");
	  return messaging.getToken();
	})
	.then(function(token) {
		$("#havetoken").show(); $("#stopnotifications").show(); $("#dosetup").hide();
		console.log("token:"+token);
		$.ajax({
			url: "FCMsetup.php",
			type: "POST",
			data: {"token": token}
		});
	})
	.catch(function(err) {
	  console.log("Unable to get permission to notify.", err);
		$("#havetoken").hide(); $("#dosetup").show();
	});
}

	messaging.onTokenRefresh(function() {
	  messaging.getToken()
	  .then(function(refreshedToken) {
	    console.log("Token refreshed.");
			$.ajax({
				url: "FCMsetup.php",
				type: "POST",
				data: {"token": refreshedToken}
			});
	  })
	  .catch(function(err) {
	    console.log("Unable to retrieve refreshed token ", err);
	  });
	});
messaging.onMessage(function(payload) {
	console.log(payload);
});

</script>';
$pagetitle = _('Notification Settings');
require_once "../header.php";
?>
<div class="breadcrumb"><a href="../index.php">Home</a> &gt; <a href="../forms.php?action=chguserinfo">User Profile</a> &gt; Notification Settings</div>

<h1><?php echo $pagetitle; ?></h1>

<div id="dosetup">
	<p>If you would like to receive new message notifications from this system, even when not visiting this site,
	click the button below.  When your browser asks for permissions, click Allow.  Note this system only works in some browsers
	(Chrome and Firefox).</p>
	<?php
	if ($FCMtoken != '') {
		echo '<p id="otherdevice">You already have notifications set up on another device.  You can only receive notifications on one device at a time, so only click the button
	   below if you want to replace your current device with this one.</p>';
	}
	?>
	<p><button type="button" onclick="askPermission()">Set up Notifications</button></p>
</div>
<div id="havetoken" style="display:none">Notifications are set up on this device.</div>

<p id="stopnotifications" <?php	if ($FCMtoken=='') { echo 'style="display:none"';} ?>>
<button type="button" onclick="removeNotifications()">Stop Notifications</button></p>

<?php
require_once "../footer.php";
?>
