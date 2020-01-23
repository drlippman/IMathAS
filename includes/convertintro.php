<?php

function convertintro($current_intro) {
	if (($intro=json_decode($current_intro,true))!==null) { //is json intro
		return false;
	} else if (strpos($current_intro,'[QUESTION')===false && strpos($current_intro,'[Q')===false) {
		return false;
	} else {
		$intro = $current_intro;
	$introjson = array();
	$isembed = false;
	if (strpos($intro,'[QUESTION')!==false) {
		$isembed = true;
		$intro = preg_replace('/<p[^>]*>((<span|<strong|<em)[^>]*>)*?(&nbsp;|\s)*\[QUESTION\s+(\d+)\s*\]/','[QUESTION $4]',$intro);
		$intro = preg_replace('/\[QUESTION\s+(\d+)\s*\](&nbsp;|\s)*(<br\s*\/>\s*)?((<\/span|<\/strong|<\/em)[^>]*>)*?<\/p>/','[QUESTION $1]',$intro);
		//no reason for this $intro = preg_replace('/\[QUESTION\s+(\d+)\s*\]/','</div>[QUESTION $1]<div class="intro">',$intro);
		if (strpos($intro,'[PAGE')!==false) {
			$intro = preg_replace('/<p[^>]*>((<span|<strong|<em)[^>]*>)?\[PAGE\s*([^\]]*)\]((<\/span|<\/strong|<\/em)[^>]*>)?<\/p>/','[PAGE $3]',$intro);
			//no reason for this $intro = preg_replace('/\[PAGE\s*([^\]]*)\]/','</div>[PAGE $1]<div class="intro">',$intro);
			$intropages = preg_split('/\[PAGE\s*([^\]]*)\]/',$intro,-1,PREG_SPLIT_DELIM_CAPTURE); //main pagetitle cont 1 pagetitle
			$mainintro = $intropages[0];
			$introjson[] = $mainintro;
			$lastqn = -1;
			for ($i=1;$i<count($intropages);$i+=2) {
				$qpages = preg_split('/\[QUESTION\s*(\d+)\]/',$intropages[$i+1],-1,PREG_SPLIT_DELIM_CAPTURE);
				for ($j=0;$j<count($qpages);$j+=2) {
					$qpages[$j] = myhtmLawed($qpages[$j]);
					$qpages[$j] = preg_replace('/<span[^>]*>(&nbsp;|\s)*<\/span>/','',$qpages[$j]);
					$qpages[$j] = preg_replace('/<div[^>]*>\s*(&nbsp;|<p[^>]*>(\s|&nbsp;)*<\/p>|<\/p>|\s*)\s*<\/div>/','',$qpages[$j]);
					if (trim($qpages[$j])!='') {
						if (isset($qpages[$j+1])) {
							$qn = $qpages[$j+1]-1;
							$lastqn = $qn;
						} else {
							$qn = $lastqn+1;
						}
						$introjson[] = array(
							'displayBefore'=>$qn,
							'displayUntil'=>$qn,
							'text'=>str_replace(array("\n","\r"),array(' ',' '),myhtmLawed($qpages[$j])),
							'ispage'=>($j==0)?1:0,
							'pagetitle'=>($j==0)?strip_tags(str_replace(array("\n","\r","]"),array(' ',' ','&#93;'),$intropages[$i])):''
							);

					} else if (isset($qpages[$j+1])) {
						$lastqn = $qpages[$j+1]-1;
					}
				}
			}
		} else {
			$mainintro = '';
			$introjson[] = $mainintro;
			$qpages = preg_split('/\[QUESTION\s*(\d+)\]/',$intro,-1,PREG_SPLIT_DELIM_CAPTURE);
			for ($j=0;$j<count($qpages);$j+=2) {
				$qpages[$j] = myhtmLawed($qpages[$j]);
				$qpages[$j] = preg_replace('/<span[^>]*>(&nbsp;|\s)*<\/span>/','',$qpages[$j]);
				$qpages[$j] = preg_replace('/<div[^>]*>\s*(&nbsp;|<p[^>]*>(\s|&nbsp;)*<\/p>|<\/p>|\s*)\s*<\/div>/','',$qpages[$j]);
				if (trim($qpages[$j])!='') {
					if (isset($qpages[$j+1])) {
						$qn = $qpages[$j+1]-1;
					} else {
						$qn = $qpages[$j-1];
					}
					$introjson[] = array(
						'displayBefore'=>$qn,
						'displayUntil'=>$qn,
						'text'=>str_replace(array("\n","\r"),array(' ',' '),myhtmLawed($qpages[$j])),
						'ispage'=>0,
						'pagetitle'=>''
						);
				}
			}
		}
	} else if (strpos($intro,'[Q')!==false) {
		$intro = preg_replace('/((<span|<strong|<em)[^>]*>)?\[Q\s+(\d+(\-(\d+))?)\s*\]((<\/span|<\/strong|<\/em)[^>]*>)?/','[Q $3]',$intro);
		if(preg_match_all('/\<p[^>]*>\s*\[Q\s+(\d+)(\-(\d+))?\s*\]\s*<\/p>/',$intro,$introdividers,PREG_SET_ORDER)) {
			$intropieces = preg_split('/\<p[^>]*>\s*\[Q\s+(\d+)(\-(\d+))?\s*\]\s*<\/p>/',$intro);
			foreach ($introdividers as $k=>$v) {
				if (count($v)==4) {
					$introdividers[$k][2] = $v[3];
				} else if (count($v)==2) {
					$introdividers[$k][2] = $v[1];
				}
			}
			$mainintro = array_shift($intropieces);
			$introjson[] = $mainintro;
			foreach ($introdividers as $k=>$v) {
				$introjson[] = array(
					'displayBefore'=>$v[1]-1,
					'displayUntil'=>$v[2]-1,
					'text'=>str_replace(array("\n","\r"),array(' ',' '),myhtmLawed($intropieces[$k])),
					'ispage'=>0,
					'pagetitle'=>''
					);
			}
		}
	}
		return array($introjson,$isembed);
	}
}
