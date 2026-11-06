<?php

/**
 * Login logging 
 * 
 * Appends one JSON line per event to a local file. 
 *
 * Enable with:  $CFG['keep_loginlog'] = true;
 * Optional:     $CFG['keep_loginlog'] = ['logdir' => '/var/log/imathas'];
 *
 * Files are written to <logdir>/loginlog-<installname>.log.
 *
 * If using this, you will likely want to setup logrotate to rotate the log,
 * and you will likely need to manually create the directory and ensure the
 * webserver user has rights to write to it.
 * 
 * If using Elastic Beanstalk, the amazon-cloudwatch-agent on each instance 
 * tails these files and ships them to CloudWatch Logs using the instance 
 * profile role, so no access keys are needed and logging never blocks a 
 * request on the network.
 * 
 * Use a postdeploy platform hook to create the directory, add the logrotate
 * entry, and setup the agent config.  
 */

function addLoginLog($eventtype, $userid, $additionalinfo = []) {
    global $CFG, $installname;

    $logdir = '/var/log/imathas';
    if (is_array($CFG['keep_loginlog']) && !empty($CFG['keep_loginlog']['logdir'])) {
        $logdir = rtrim($CFG['keep_loginlog']['logdir'], '/');
    }
    $install = preg_replace('/[^A-Za-z0-9_-]/', '', $installname ?? '');
    if ($install === '') {
        $install = 'site';
    }

    $baseinfo = [
        'level' => 'info',
        'install' => $install,
        'eventtype' => $eventtype,
        'userid' => $userid,
        'ip' => $_SERVER['HTTP_CF_CONNECTING_IP']
                ?? $_SERVER['HTTP_X_FORWARDED_FOR']
                ?? $_SERVER['REMOTE_ADDR']
                ?? $_SERVER['HTTP_CLIENT_IP']
                ?? '',
        'timestamp' => time(),
        'geo_country' => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    try {
        $line = json_encode($baseinfo + $additionalinfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($line !== false && is_dir($logdir)) {
            // suppress warnings; logging must never break a login
            @file_put_contents($logdir . '/loginlog-' . $install . '.log', $line . "\n", FILE_APPEND | LOCK_EX);
        }
    } catch (Throwable $e) {
        echo "Uh oh";
        exit;
        // prevent fail on logging error
    }
}
