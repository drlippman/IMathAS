<?php
header('Content-Type: application/javascript');
$init_skip_csrfp = true;
require_once "init_without_validate.php";
?>
// Give the service worker access to Firebase Messaging.
// Note that you can only use Firebase Messaging here, other Firebase libraries
// are not available in the service worker.
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// Initialize the Firebase app in the service worker by passing in the
// messagingSenderId.
firebase.initializeApp({
  'messagingSenderId': '<?php echo $CFG['FCM']['SenderId']; ?>',
  'apiKey': '<?php echo $CFG['FCM']['webApiKey']; ?>',
  'projectId': '<?php echo $CFG['FCM']['project_id']; ?>',
	'appId': '<?php echo $CFG['FCM']['app_id']; ?>'
});

// Retrieve an instance of Firebase Messaging so that it can handle background
// messages.
const messaging = firebase.messaging();
