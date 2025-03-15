<?php

// Functions for getting data about videos

function getvideoid($url) {
	$vidid = '';
	if (strpos($url,'youtube.com/watch')!==false) {
		//youtube
		$vidid = substr($url,strrpos($url,'v=')+2);
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

$youtubeCaptionDatastore = [];
function getCaptionDataByVidId($vidid) {
    global $CFG, $youtubeCaptionDatastore;
    
    if (isset($youtubeCaptionDatastore[$vidid])) {
        return $youtubeCaptionDatastore[$vidid];
    }

    if (isset($CFG['YouTubeAPIKey'])) {
        $captioned = 0;
        $ctx = stream_context_create(array('http'=>array('timeout' => 1)));
        $resp = @file_get_contents('https://youtube.googleapis.com/youtube/v3/captions?part=snippet&videoId='.$vidid.'&key='.$CFG['YouTubeAPIKey'], false, $ctx);
        $capdata = json_decode($resp, true);
        if ($capdata !== null && isset($capdata['items'])) {
            foreach ($capdata['items'] as $cap) {
                if ($cap['snippet']['trackKind'] == 'standard') {
                    $captioned = 1;
                    break;
                }
            }
        }
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