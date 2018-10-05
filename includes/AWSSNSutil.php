<?php
//IMathAS: handle AWS SNS stuff
//(c) 2018 David Lippman

if (isset($_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE']) 
    && $_SERVER['HTTP_X_AMZ_SNS_MESSAGE_TYPE']=='SubscriptionConfirmation') {
	//this is a subscript confirmation request.  Need to reply to it.
	
	$msg = json_decode(trim(file_get_contents("php://input")), true);
	if ($msg !== null && isset($msg['SubscribeURL'])) {
		//call subscription URL to confirm
		$res = file_get_contents($msg['SubscribeURL']);
		exit;
	}
}

function respondOK() {
    // check if fastcgi_finish_request is callable
    if (is_callable('fastcgi_finish_request')) {
        /*
         * This works in Nginx but the next approach not
         */
        session_write_close();
        fastcgi_finish_request();

        return;
    }

    ignore_user_abort(true);

    ob_start();
    echo "Action Started";
    $serverProtocole = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
    header($serverProtocole.' 200 OK');
    header('Content-Encoding: none');
    header('Content-Length: '.ob_get_length());
    header('Connection: close');

    ob_end_flush();
    ob_flush();
    flush();
}
