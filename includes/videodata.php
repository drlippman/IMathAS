<?php

// Functions for getting data about videos

function getvideoid($url) {
	$vidid = '';
	if (strpos($url,'youtube.com/watch')!==false) {
		//youtube
		$vidid = substr($url,strrpos($url,'v=')+2);
        if (strpos($vidid,'?')!==false) { //bad URL form, but handle it
			$vidid = substr($vidid,0,strpos($vidid,'?'));
		}
		if (strpos($vidid,'&')!==false) {
			$vidid = substr($vidid,0,strpos($vidid,'&'));
		}
		if (strpos($vidid,'#')!==false) {
			$vidid = substr($vidid,0,strpos($vidid,'#'));
		}
		$vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
	} else if (strpos($url,'youtu.be/')!==false) {
		//youtube
		$vidid = substr($url,strpos($url,'.be/')+4);
		if (strpos($vidid,'#')!==false) {
			$vidid = substr($vidid,0,strpos($vidid,'#'));
		}
		if (strpos($vidid,'?')!==false) {
			$vidid = substr($vidid,0,strpos($vidid,'?'));
		}
		$vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
	}
	return Sanitize::simpleString($vidid);
}

// temp store so we don't scan the same video twice in one script
$youtubeCaptionDatastore = [];
function getCaptionDataByVidId($vidid) {
    global $CFG, $DBH, $youtubeCaptionDatastore;
    
    if (isset($youtubeCaptionDatastore[$vidid])) {
        return $youtubeCaptionDatastore[$vidid];
    }

    if (isset($CFG['YouTubeAPIKey'])) {
        $captioned = 0;
        $ctx = stream_context_create(array('http'=>array('timeout' => 1)));
        $resp = @file_get_contents('https://youtube.googleapis.com/youtube/v3/captions?part=snippet&videoId='.$vidid.'&key='.$CFG['YouTubeAPIKey'], false, $ctx);
        if ($resp === false) {
            $code = explode(' ', $http_response_header[0])[1];
            if ($code == '404') {
                $query = "INSERT INTO imas_captiondata (vidid, captioned, status, lastchg) VALUES (?,0,3,?) ";
                $query .= "ON DUPLICATE KEY UPDATE status=VALUES(status),";
                $query .= "lastchg=VALUES(lastchg),";
                $query .= "captioned=VALUES(captioned)";
                $novid = $DBH->prepare($query);
                $novid->execute([$vidid, time()]);
                return '404';
            }
            return false;
        }
        $capdata = json_decode($resp, true);
        if ($capdata !== null && isset($capdata['items'])) {
            foreach ($capdata['items'] as $cap) {
                if ($cap['snippet']['trackKind'] == 'standard') {
                    $captioned = 1;
                    break;
                }
            }
        }
        $query = "INSERT INTO imas_captiondata (vidid, captioned, status, lastchg) VALUES (?,?,2,?) ";
        $query .= "ON DUPLICATE KEY UPDATE status=IF(VALUES(captioned)!=captioned OR status=0 OR status=3,VALUES(status),status),";
        $query .= "lastchg=IF(VALUES(captioned)!=captioned OR status=0 OR status=3,VALUES(lastchg),lastchg),";
        $query .= "captioned=IF(VALUES(captioned)!=captioned OR status=0 OR status=3,VALUES(captioned),captioned)";
        $stm = $DBH->prepare($query);
        $stm->execute([$vidid, $captioned, time()]);
    } else {
        $ctx = stream_context_create(array('http'=>
            array(
            'timeout' => 1,
            'header' => "Accept-language: en\r\n" . 
                        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
            )
        ));
        $t = @file_get_contents('https://www.youtube.com/watch?v='.$vidid, false, $ctx);
        // auto-gen captions have vssId of "a.langcode"; manual are just ".langcode"
        // so look for vssId that starts with .; don't care about language
        $captioned = (preg_match('/"vssId":\s*"\./', $t))?1:0; 
    }

    $youtubeCaptionDatastore[$vidid] = $captioned;

    return $captioned;
}