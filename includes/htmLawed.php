<?php

/*
htmLawed 1.0.8, 27 May 2008
Copyright Santosh Patnaik
GPL v3 license
A PHP Labware internal utility - http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed

See htmLawed_README.txt/.htm

Stripslashes() GET/POST if magic_quotes is on
*/

require_once("filehandler.php");

function convertdatauris($in) {
	global $CFG,$userid;
	$okext = array('jpeg'=>'.jpg','gif'=>'.gif','png'=>'.png');
	if (strpos($in,'data:image')===false) {return $in;}
	$in = preg_replace('/<img[^>]*src="data:image[^>]*$/','',$in);
	if (!isset($CFG['GEN']['noFileBrowser'])) {
		preg_match_all('/<img[^>]*src="(data:image\/(\w+);base64,([^"]*))"/',$in,$matches);
		foreach ($matches[3] as $k=>$code) {
			$img = base64_decode($code);
			$ext = $matches[2][$k];
			if (!isset($okext[$ext])) { continue;}
			$key = "ufiles/$userid/pastedimage".tzdate("ymd-His",time()).'-'.$k.$okext[$ext];
			storecontenttofile($img,$key,"public");
			$in = str_replace($matches[1][$k],getuserfileurl($key),$in);
		}	
		return $in;
	} else {
		$in = preg_replace('/<img[^>]*src="data:image[^>]*>/','',$in);
	}
	return $in;
}

function htmLawed($in, $cf = 1, $spec = array()){
$in = convertdatauris($in);
$cf = is_array($cf) ? $cf : array();
// config: valid_xhtml
if(!empty($cf['valid_xhtml'])){
 $cf['elements'] = !empty($cf['elements']) ? $cf['elements'] : '*-center-dir-font-isindex-menu-s-strike-u';
 $cf['make_tag_strict'] = isset($cf['make_tag_strict']) ? $cf['make_tag_strict'] : 2;
 $cf['xml:lang'] = isset($cf['xml:lang']) ? $cf['xml:lang'] : 2;
}
// config: elements
$ec = array('a'=>1, 'abbr'=>1, 'acronym'=>1, 'address'=>1, 'applet'=>1, 'area'=>1, 'b'=>1, 'bdo'=>1, 'big'=>1, 'blockquote'=>1, 'br'=>1, 'button'=>1, 'caption'=>1, 'center'=>1, 'cite'=>1, 'code'=>1, 'col'=>1, 'colgroup'=>1, 'dd'=>1, 'del'=>1, 'dfn'=>1, 'dir'=>1, 'div'=>1, 'dl'=>1, 'dt'=>1, 'em'=>1, 'embed'=>1, 'fieldset'=>1, 'font'=>1, 'form'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'i'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'ins'=>1, 'isindex'=>1, 'kbd'=>1, 'label'=>1, 'legend'=>1, 'li'=>1, 'map'=>1, 'menu'=>1, 'noscript'=>1, 'object'=>1, 'ol'=>1, 'optgroup'=>1, 'option'=>1, 'p'=>1, 'param'=>1, 'pre'=>1, 'q'=>1, 'rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1, 'ruby'=>1, 's'=>1, 'samp'=>1, 'script'=>1, 'select'=>1, 'small'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'sub'=>1, 'sup'=>1, 'table'=>1, 'tbody'=>1, 'td'=>1, 'textarea'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1, 'tt'=>1, 'u'=>1, 'ul'=>1, 'var'=>1); // 86 incl. deprecated, embed, ruby set
if(!empty($cf['safe'])){
 unset($ec['applet'], $ec['embed'], $ec['iframe'], $ec['object'], $ec['script']);
}
$tmp = !empty($cf['elements']) ? str_replace(array("\n", "\r", "\t", ' '), '', $cf['elements']) : '*';
if($tmp == '-*'){$ec = array();}
elseif(strpos($tmp, '*') === false){$ec = array_flip(explode(',', $tmp));}
else{
 if(isset($tmp[1])){
  preg_match_all('`(?:^|-|\+)[^\-+]+?(?=-|\+|$)`', $tmp, $m, PREG_SET_ORDER);
  for($i=count($m); --$i>=0;){$m[$i] = $m[$i][0];}
  foreach($m as $v){
   if($v[0] == '+'){$ec[substr($v, 1)] = 1;}
   if($v[0] == '-' && isset($ec[($v = substr($v, 1))]) && !in_array('+'. $v, $m)){unset($ec[$v]);}
  }
 }
}
$cf['elements'] =& $ec;
// config: denied attributes
$cf['deny_attribute'] = !empty($cf['deny_attribute']) ? array_flip(explode(',', str_replace(array("\n", "\r", "\t", ' '), '', $cf['deny_attribute']. (!empty($cf['safe']) ? ',on*' : '')))) : (!empty($cf['safe']) ? array('on*'=>1) : array());
if(isset($cf['deny_attribute']['on*'])){
 unset($cf['deny_attribute']['on*']);
 $cf['deny_attribute'] += array('onblur'=>1, 'onchange'=>1, 'onclick'=>1, 'ondblclick'=>1, 'onfocus'=>1, 'onkeydown'=>1, 'onkeypress'=>1, 'onkeyup'=>1, 'onmousedown'=>1, 'onmousemove'=>1, 'onmouseout'=>1, 'onmouseover'=>1, 'onmouseup'=>1, 'onreset'=>1, 'onselect'=>1, 'onsubmit'=>1);
}
// config: URL schemes
$tmp = (isset($cf['schemes'][2]) && strpos($cf['schemes'], ':')) ? strtolower($cf['schemes']) : 'href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; *:file, http, https';
$cf['schemes'] = array();
foreach(explode(';', str_replace(array(' ', "\t", "\r", "\n"), '', $tmp)) as $v){
 $tmp = $tmp2 = null; list($tmp, $tmp2) = explode(':', $v, 2);
 if($tmp2){$cf['schemes'][$tmp] = array_flip(explode(',', $tmp2));}
}
if(!isset($cf['schemes']['*'])){$cf['schemes']['*'] = array('file'=>1, 'http'=>1, 'https'=>1,);}
if(!empty($cf['safe']) && empty($cf['schemes']['style'])){$cf['schemes']['style'] = array('nil'=>1);}
// config: abs/rel URL
$cf['abs_url'] = isset($cf['abs_url']) ? $cf['abs_url'] : 0;
if(!isset($cf['base_url']) or !preg_match('`^[a-zA-Z\d.+\-]+://[^/]+/(.+?/)?$`', $cf['base_url'])){
 $cf['base_url'] = $cf['abs_url'] = 0;
}
// config: rest
$cf['and_mark'] = !empty($cf['and_mark']) ? 1 : 0;
$cf['anti_link_spam'] = (isset($cf['anti_link_spam']) && is_array($cf['anti_link_spam']) && count($cf['anti_link_spam']) == 2 && (empty($cf['anti_link_spam'][0]) or hl_regex($cf['anti_link_spam'][0])) && (empty($cf['anti_link_spam'][1]) or hl_regex($cf['anti_link_spam'][1]))) ? $cf['anti_link_spam'] : 0;
$cf['anti_mail_spam'] = isset($cf['anti_mail_spam']) ? $cf['anti_mail_spam'] : 0;
$cf['balance'] = isset($cf['balance']) ? (bool)$cf['balance'] : 1;
$cf['cdata'] = isset($cf['cdata']) ? $cf['cdata'] : (empty($cf['safe']) ? 3 : 0);
$cf['clean_ms_char'] = isset($cf['clean_ms_char']) ? $cf['clean_ms_char'] : 0;
$cf['comment'] = isset($cf['comment']) ? $cf['comment'] : (empty($cf['safe']) ? 3 : 0);
$cf['css_expression'] = isset($cf['css_expression']) ? (bool)$cf['css_expression'] : 0;
$cf['hexdec_entity'] = isset($cf['hexdec_entity']) ? $cf['hexdec_entity'] : 1;
$cf['hook'] = (!empty($cf['hook']) && function_exists($cf['hook'])) ? $cf['hook'] : 0;
$cf['keep_bad'] = isset($cf['keep_bad']) ? $cf['keep_bad'] : 6;
$cf['lc_std_val'] = isset($cf['lc_std_val']) ? (bool)$cf['lc_std_val'] : 1;
$cf['make_tag_strict'] = isset($cf['make_tag_strict']) ? $cf['make_tag_strict'] : 1;
$cf['named_entity'] = isset($cf['named_entity']) ? (bool)$cf['named_entity'] : 1;
$cf['no_deprecated_attr'] = isset($cf['no_deprecated_attr']) ? $cf['no_deprecated_attr'] : 1;
$cf['parent'] = isset($cf['parent'][0]) ? strtolower($cf['parent']) : 'body';
$cf['show_setting'] = isset($cf['show_setting'][0]) ? $cf['show_setting'] : 0;
$cf['unique_ids'] = isset($cf['unique_ids']) ? $cf['unique_ids'] : 1;
$cf['xml:lang'] = isset($cf['xml:lang']) ? $cf['xml:lang'] : 0;
if(isset($GLOBALS['cf'])){$resetCf = $GLOBALS['cf'];}
$GLOBALS['cf'] = $cf;
// $spec
$spec = is_array($spec) ? $spec : hl_spec($spec);
if(isset($GLOBALS['spec'])){$resetSpec = $GLOBALS['spec'];}
$GLOBALS['spec'] = $spec;
// chars
$in = preg_replace('`[\x00-\x08\x0b-\x0c\x0e-\x1f]`', '', $in);
if($cf['clean_ms_char']){
 $el = array("\x7f"=>'', "\x80"=>'&#8364;', "\x81"=>'', "\x83"=>'&#402;', "\x85"=>'&#8230;', "\x86"=>'&#8224;', "\x87"=>'&#8225;', "\x88"=>'&#710;', "\x89"=>'&#8240;', "\x8a"=>'&#352;', "\x8b"=>'&#8249;', "\x8c"=>'&#338;', "\x8d"=>'', "\x8e"=>'&#381;', "\x8f"=>'', "\x90"=>'', "\x95"=>'&#8226;', "\x96"=>'&#8211;', "\x97"=>'&#8212;', "\x98"=>'&#732;', "\x99"=>'&#8482;', "\x9a"=>'&#353;', "\x9b"=>'&#8250;', "\x9c"=>'&#339;', "\x9d"=>'', "\x9e"=>'&#382;', "\x9f"=>'&#376;');
 $el = $el + ($cf['clean_ms_char'] == 1 ? array("\x82"=>'&#8218;', "\x84"=>'&#8222;', "\x91"=>'&#8216;', "\x92"=>'&#8217;', "\x93"=>'&#8220;', "\x94"=>'&#8221;') : array("\x82"=>'\'', "\x84"=>'"', "\x91"=>'\'', "\x92"=>'\'', "\x93"=>'"', "\x94"=>'"'));
 $in = strtr($in, $el);
}
// comments/CDATA secs
if($cf['cdata'] or $cf['comment']){$in = preg_replace_callback('`<!(?:(?:--.*?--)|(?:\[CDATA\[.*?\]\]))>`sm', 'hl_cmtcd', $in);}
// entities
$in = preg_replace_callback('`&amp;([A-Za-z][A-Za-z0-9]{1,30}|#(?:[0-9]{1,8}|[Xx][0-9A-Fa-f]{1,7}));`', 'hl_ent', str_replace('&', '&amp;', $in));
// for unique-ID check; global for multiple calls
if($cf['unique_ids'] && !isset($GLOBALS['hl_Ids'])){$GLOBALS['hl_Ids'] = array();}
// custom hook
if($cf['hook']){$in = $cf['hook']($in, $cf, $spec);}
// show finalized cf & spec
if($cf['show_setting']){
 $GLOBALS[$cf['show_setting']] = array('config'=>$cf, 'spec'=>$spec, 'time'=>microtime());
}
// main work
$in = preg_replace_callback('`<(?:(?:\s|$)|(?:[^>]*(?:>|$)))|>`m', 'hl_tag', $in);
$in = ($cf['balance'] ? hl_bal($in, $cf['keep_bad'], $cf['parent']) : $in);
$in = (($cf['cdata'] or $cf['comment']) && strpos($in, "\x01") !== false) ? str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05"), array('', '', '&', '<', '>'), $in) : $in;
 // end
unset($cf, $ec, $ac);
if(isset($resetCf)){$GLOBALS['cf'] = $resetCf;}
if(isset($resetSpec)){$GLOBALS['spec'] = $resetSpec;}
//add custom removal of instructor-specific stuff
if (!isset($GLOBALS['teacherid'])) {
  $in = remove_instr_only($in);	
}
return $in;
// eof
}

function hl_attrval($v, $p){
// check attr val against user spec
$o = 1; $l = strlen($v);
foreach($p as $pn=>$pv){
 switch($pn){
  case 'maxlen':if($l > $pv){$o = 0;}
  break; case 'minlen': if($l < $pv){$o = 0;}
  break; case 'maxval': if((float)($v) > $pv){$o = 0;}
  break; case 'minval': if((float)($v) < $pv){$o = 0;}
  break; case 'match': if(!preg_match($pv, $v)){$o = 0;}
  break; case 'nomatch': if(preg_match($pv, $v)){$o = 0;}
  break; case 'oneof':
   $o2 = 0;
   foreach(explode('|', $pv) as $k=>$v2){if($v == $v2){$o2 = 1; break;}}
   $o = $o2;
  break; case 'noneof':
   $o2 = 1;
   foreach(explode('|', $pv) as $k=>$v2){if($v == $v2){$o2 = 0; break;}}
   $o = $o2;
  break; default:
  break;
 }
 if(!$o){break;}
}
return ($o ? $v : (isset($p['default']) ? $p['default'] : 0));
// eof
}

function hl_bal($tx, $do = 1, $in = 'div'){
// balance non-frameset, body HTML tags - 86 elements: HTML 4/XHTML 1/deprecated/ruby/embed in $tx. Entitify non-tag < & >. Proper empty ele closure. Allow comments/CDATA secs. Fix invalid nesting, remove/neutralize bad as per $do: 0, remove; 1, neutralize tags and content; 2, remove tags but neutralize content; 3 & 4, like 1 & 2 but remove if text is invalid in parent ele; 5 & 6, like 3 & 4 but linebreaks, tabs & spaces are left. $in, specifying ele holding $tx, affects ele permitted in $tx. Attempts to add div inside $cB eles if rqd. No correction optimization. Thus, the typo in '<tabl>...</table>' renders the whole block invalid.

// eles by Content
$cB = array('blockquote'=>1, 'form'=>1, 'map'=>1, 'noscript'=>1); // Block
$cE = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'param'=>1); // Empty
$cF = array('button'=>1, 'del'=>1, 'div'=>1, 'dd'=>1, 'fieldset'=>1, 'iframe'=>1, 'ins'=>1, 'li'=>1, 'noscript'=>1, 'object'=>1, 'td'=>1, 'th'=>1); // Flow; later context-wise dynamic move of ins & del to $cI
$cI = array('a'=>1, 'abbr'=>1, 'acronym'=>1, 'address'=>1, 'b'=>1, 'bdo'=>1, 'big'=>1, 'caption'=>1, 'cite'=>1, 'code'=>1, 'dfn'=>1, 'dt'=>1, 'em'=>1, 'font'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'i'=>1, 'kbd'=>1, 'label'=>1, 'legend'=>1, 'p'=>1, 'pre'=>1, 'q'=>1, 'rb'=>1, 'rt'=>1, 's'=>1, 'samp'=>1, 'small'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'sub'=>1, 'sup'=>1, 'tt'=>1, 'u'=>1, 'var'=>1); // Inline
$cN = array('a'=>array('a'=>1), 'button'=>array('a'=>1, 'button'=>1, 'fieldset'=>1, 'form'=>1, 'iframe'=>1, 'input'=>1, 'label'=>1, 'select'=>1, 'textarea'=>1), 'fieldset'=>array('fieldset'=>1), 'form'=>array('form'=>1), 'label'=>array('label'=>1), 'noscript'=>array('script'=>1), 'pre'=>array('big'=>1, 'font'=>1, 'img'=>1, 'object'=>1, 'script'=>1, 'small'=>1, 'sub'=>1, 'sup'=>1), 'rb'=>array('ruby'=>1), 'rt'=>array('ruby'=>1)); // Illegal
$cN2 = array_keys($cN);
$cR = array('blockquote'=>1, 'dir'=>1, 'dl'=>1, 'form'=>1, 'map'=>1, 'menu'=>1, 'noscript'=>1, 'ol'=>1, 'optgroup'=>1, 'rbc'=>1, 'rtc'=>1, 'ruby'=>1, 'select'=>1, 'table'=>1, 'tbody'=>1, 'tfoot'=>1, 'thead'=>1, 'tr'=>1, 'ul'=>1);
$cS = array('colgroup'=>array('col'=>1), 'dir'=>array('li'), 'dl'=>array('dd'=>1, 'dt'=>1), 'menu'=>array('li'=>1), 'ol'=>array('li'=>1), 'optgroup'=>array('option'=>1), 'option'=>array('#pcdata'=>1), 'rbc'=>array('rb'=>1), 'rp'=>array('#pcdata'=>1), 'rtc'=>array('rt'=>1), 'ruby'=>array('rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1), 'select'=>array('optgroup'=>1, 'option'=>1), 'script'=>array('#pcdata'=>1), 'table'=>array('caption'=>1, 'col'=>1, 'colgroup'=>1, 'tfoot'=>1, 'tbody'=>1, 'tr'=>1, 'thead'=>1), 'tbody'=>array('tr'=>1), 'tfoot'=>array('tr'=>1), 'textarea'=>array('#pcdata'=>1), 'thead'=>array('tr'=>1), 'tr'=>array('td'=>1, 'th'=>1), 'ul'=>array('li'=>1)); // Specific (immediate parent-child)
$cO = array('address'=>array('p'=>1), 'applet'=>array('param'=>1), 'blockquote'=>array('script'=>1), 'fieldset'=>array('legend'=>1, '#pcdata'=>1), 'form'=>array('script'=>1), 'map'=>array('area'=>1), 'object'=>array('param'=>1, 'embed'=>1)); // Other

// eles by block/inline type; ins & del both type; #pcdata: plain text
$eB = array('address'=>1, 'blockquote'=>1, 'center'=>1, 'del'=>1, 'dir'=>1, 'dl'=>1, 'div'=>1, 'fieldset'=>1, 'form'=>1, 'ins'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'isindex'=>1, 'menu'=>1, 'noscript'=>1, 'ol'=>1, 'p'=>1, 'pre'=>1, 'table'=>1, 'ul'=>1);
$eI = array('#pcdata'=>1, 'a'=>1, 'abbr'=>1, 'acronym'=>1, 'applet'=>1, 'b'=>1, 'bdo'=>1, 'big'=>1, 'br'=>1, 'button'=>1, 'cite'=>1, 'code'=>1, 'del'=>1, 'dfn'=>1, 'em'=>1, 'embed'=>1, 'font'=>1, 'i'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'ins'=>1, 'kbd'=>1, 'label'=>1, 'map'=>1, 'object'=>1, 'param'=>1, 'q'=>1, 'ruby'=>1, 's'=>1, 'samp'=>1, 'select'=>1, 'script'=>1, 'small'=>1, 'span'=>1, 'strike'=>1, 'strong'=>1, 'sub'=>1, 'sup'=>1, 'textarea'=>1, 'tt'=>1, 'u'=>1, 'var'=>1);
$eN = array('a'=>1, 'big'=>1, 'button'=>1, 'fieldset'=>1, 'font'=>1, 'form'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'label'=>1, 'object'=>1, 'ruby'=>1, 'script'=>1, 'select'=>1, 'small'=>1, 'sub'=>1, 'sup'=>1, 'textarea'=>1); // Exclude from specific ele; $cN values
$eO = array('area'=>1, 'caption'=>1, 'col'=>1, 'colgroup'=>1, 'dd'=>1, 'dt'=>1, 'legend'=>1, 'li'=>1, 'optgroup'=>1, 'option'=>1, 'rb'=>1, 'rbc'=>1, 'rp'=>1, 'rt'=>1, 'rtc'=>1, 'script'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'thead'=>1, 'th'=>1, 'tr'=>1); // Missing in $eB & $eI
$eF = $eB + $eI;

// $in sets allowed children
$in = ((isset($eF[$in]) && $in != '#pcdata') or isset($eO[$in])) ? $in : 'div';
if(isset($cE[$in])){
 return (!$do ? '' : str_replace(array('<', '>'), array('&lt;', '&gt;'), $tx));
}
if(isset($cS[$in])){$inOk = $cS[$in];}
elseif(isset($cI[$in])){$inOk = $eI; $cI['del'] = 1; $cI['ins'] = 1;}
elseif(isset($cF[$in])){$inOk = $eF; unset($cI['del'], $cI['ins']);}
elseif(isset($cB[$in])){$inOk = $eB; unset($cI['del'], $cI['ins']);}
if(isset($cO[$in])){$inOk = $inOk + $cO[$in];}
if(isset($cN[$in])){$inOk = array_diff_assoc($inOk, $cN[$in]);}

$tx = explode('<', $tx);
$ok = $q = array(); // $q seq list of open non-empty ele
ob_start();

for($i=-1, $ci=count($tx); ++$i<$ci;){
 // parent ele $p & allowed child $ok
 if($ql = count($q)){
  $p = array_pop($q);
  $q[] = $p;
  if(isset($cS[$p])){$ok = $cS[$p];}
  elseif(isset($cI[$p])){$ok = $eI; $cI['del'] = 1; $cI['ins'] = 1;}
  elseif(isset($cF[$p])){$ok = $eF; unset($cI['del'], $cI['ins']);}
  elseif(isset($cB[$p])){$ok = $eB; unset($cI['del'], $cI['ins']);}
  if(isset($cO[$p])){$ok = $ok + $cO[$p];}
  if(isset($cN[$p])){$ok = array_diff_assoc($ok, $cN[$p]);}
 }else{$ok = $inOk; unset($cI['del'], $cI['ins']);}
 // bad tags, & ele content
 if(isset($e) && ($do == 1 or (isset($ok['#pcdata']) && ($do == 3 or $do == 5)))){
  echo '&lt;', $s, $e, $a, '&gt;';
 }
 if(isset($x[0])){
  if($do < 3 or isset($ok['#pcdata'])){echo $x;}
  elseif(strpos($x, "\x02\x04")){
   foreach(preg_split('`(\x01\x02[^\x01\x02]+\x02\x01)`', $x, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $v){
    echo (substr($v, 0, 2) == "\x01\x02" ? $v : ($do > 4 ? preg_replace('`\S`', '', $v) : ''));
   }
  }elseif($do > 4){echo preg_replace('`\S`', '', $x);}
 }
 // get markup
 if(!preg_match('`^(/?)([a-zA-Z1-6]+)([^>]*)>(.*)`sm', $tx[$i], $r)){$x = $tx[$i]; continue;}
 $s = null; $e = null; $a = null; $x = null; list($all, $s, $e, $a, $x) = $r;
 // close tag
 if($s){
  if(isset($cE[$e]) or !in_array($e, $q)){continue;} // Empty/unopen
  if($p == $e){array_pop($q); echo '</', $e, '>'; unset($e); continue;} // Last open
  $add = ''; // Nesting - close open tags that need to be
  for($j=-1, $cj=count($q); ++$j<$cj;){  
   if(($d = array_pop($q)) == $e){break;}
   else{$add .= "</{$d}>";}
  }
  echo $add, '</', $e, '>'; unset($e); continue;
 }
 // open tag
 // $cB ele needs $eB ele as child - <form><INPUT>
 if(isset($cB[$e]) && strlen(trim($x))){
  $tx[$i] = "{$e}{$a}>";
  array_splice($tx, $i+1, 0, 'div>'. $x); unset($e, $x); ++$ci; --$i; continue;
 }
 if((($ql && isset($cB[$p])) or (isset($cB[$in]) && !$ql)) && !isset($eB[$e]) && !isset($ok[$e])){
  array_splice($tx, $i, 0, 'div>'); unset($e, $x); ++$ci; --$i; continue;
 }
 // if no open ele, $in is parent; except for certain cases, immediate parent-child relation should hold
 if(!$ql or (!isset($eN[$e]) or !array_intersect($q, $cN2))){
	 //if(!isset($ok[$e])){continue;}		//DLMOD:  Want to preserve improperly nested like <p><table> even though bad HTML, so don't remove bad children 
  if(!isset($cE[$e])){$q[] = $e;}
  echo '<', $e, $a, '>'; unset($e); continue;
 }
 // specific parent-child
 if(isset($cS[$p][$e])){
  if(!isset($cE[$e])){$q[] = $e;}
  echo '<', $e, $a, '>'; unset($e); continue;
 }
 // nesting
 $add = '';
 $q2 = array();
 for($k=-1, $kc=count($q); ++$k<$kc;){
  $d = $q[$k];
  $ok2 = array();
  if(isset($cS[$d])){$q2[] = $d; continue;}
  $ok2 = isset($cI[$d]) ? $eI : $eF;
  if(isset($cO[$d])){$ok2 = $ok2 + $cO[$d];}
  if(isset($cN[$d])){$ok2 = array_diff_assoc($ok2, $cN[$d]);}
  if(!isset($ok2[$e])){
   if(!$k && !isset($inOk[$e])){continue 2;}
   $add = "</{$d}>";
   for(;++$k<$kc;){$add = "</{$q[$k]}>{$add}";}
   break;
  }
  else{$q2[] = $d;}
 }
 $q = $q2;
 if(!isset($cE[$e])){$q[] = $e;}
 echo $add, '<', $e, $a, '>'; unset($e); continue;
}

// end
if($ql = count($q)){
 $p = array_pop($q);
 $q[] = $p;
 if(isset($cS[$p])){$ok = $cS[$p];}
 elseif(isset($cI[$p])){$ok = $eI; $cI['del'] = 1; $cI['ins'] = 1;}
 elseif(isset($cF[$p])){$ok = $eF; unset($cI['del'], $cI['ins']);}
 elseif(isset($cB[$p])){$ok = $eB; unset($cI['del'], $cI['ins']);}
 if(isset($cO[$p])){$ok = $ok + $cO[$p];}
 if(isset($cN[$p])){$ok = array_diff_assoc($ok, $cN[$p]);}
}else{$ok = $inOk; unset($cI['del'], $cI['ins']);}
if(isset($e) && ($do == 1 or (isset($ok['#pcdata']) && ($do == 3 or $do == 5)))){
 echo '&lt;', $s, $e, $a, '&gt;';
}
if(isset($x[0])){
 if(strlen(trim($x)) && (($ql && isset($cB[$p])) or (isset($cB[$in]) && !$ql))){
  echo '<div>', $x, '</div>';
 }
 elseif($do < 3 or isset($ok['#pcdata'])){echo $x;}
 elseif(strpos($x, "\x02\x04")){
  foreach(preg_split('`(\x01\x02[^\x01\x02]+\x02\x01)`', $x, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $v){
   echo (substr($v, 0, 2) == "\x01\x02" ? $v : ($do > 4 ? preg_replace('`\S`', '', $v) : ''));
  }
 }elseif($do > 4){echo preg_replace('`\S`', '', $x);}
}
while(!empty($q) && ($e = array_pop($q))){echo '</', $e, '>';}
$o = ob_get_contents();
ob_end_clean();
return $o;
// eof
}

function hl_cmtcd($in){
// comment/CDATA sec handler
$in = $in[0];
global $cf;
if($in[3] == '-'){ // Comment
 if(!$cf['comment']){return $in;}
 if($cf['comment'] == 1){return '';}
 if(substr(($in = substr($in, 4, -3)), -1) != ' '){$in .= ' ';}
 while(strpos($in, '--') !== false){$in = str_replace('--', '-', $in);} // No --
 $in = $cf['comment'] == 2 ? str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $in) : $in;
 $in = "\x01\x02\x04!--$in--\x05\x02\x01";
}else{
 if(!$cf['cdata']){return $in;}
 if($cf['cdata'] == 1){return '';}
 $in = substr($in, 1, -1);
 $in = $cf['cdata'] == 2 ? str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $in) : $in;
 $in = "\x01\x01\x04$in\x05\x01\x01";
}
return str_replace(array('&', '<', '>'), array("\x03", "\x04", "\x05"), $in);
// eof
}

function hl_ent($in){
// entitify invalids; optionally named to numeric, and hexdec. to dec., or opp.
global $cf;
$in = $in[1];
// univ named ents
static $U = array('quot'=>1,'amp'=>1,'lt'=>1,'gt'=>1);
// HTML named ents
static $N = array('fnof'=>'402', 'Alpha'=>'913', 'Beta'=>'914', 'Gamma'=>'915', 'Delta'=>'916', 'Epsilon'=>'917', 'Zeta'=>'918', 'Eta'=>'919', 'Theta'=>'920', 'Iota'=>'921', 'Kappa'=>'922', 'Lambda'=>'923', 'Mu'=>'924', 'Nu'=>'925', 'Xi'=>'926', 'Omicron'=>'927', 'Pi'=>'928', 'Rho'=>'929', 'Sigma'=>'931', 'Tau'=>'932', 'Upsilon'=>'933', 'Phi'=>'934', 'Chi'=>'935', 'Psi'=>'936', 'Omega'=>'937', 'alpha'=>'945', 'beta'=>'946', 'gamma'=>'947', 'delta'=>'948', 'epsilon'=>'949', 'zeta'=>'950', 'eta'=>'951', 'theta'=>'952', 'iota'=>'953', 'kappa'=>'954', 'lambda'=>'955', 'mu'=>'956', 'nu'=>'957', 'xi'=>'958', 'omicron'=>'959', 'pi'=>'960', 'rho'=>'961', 'sigmaf'=>'962', 'sigma'=>'963', 'tau'=>'964', 'upsilon'=>'965', 'phi'=>'966', 'chi'=>'967', 'psi'=>'968', 'omega'=>'969', 'thetasym'=>'977', 'upsih'=>'978', 'piv'=>'982', 'bull'=>'8226', 'hellip'=>'8230', 'prime'=>'8242', 'Prime'=>'8243', 'oline'=>'8254', 'frasl'=>'8260', 'weierp'=>'8472', 'image'=>'8465', 'real'=>'8476', 'trade'=>'8482', 'alefsym'=>'8501', 'larr'=>'8592', 'uarr'=>'8593', 'rarr'=>'8594', 'darr'=>'8595', 'harr'=>'8596', 'crarr'=>'8629', 'lArr'=>'8656', 'uArr'=>'8657', 'rArr'=>'8658', 'dArr'=>'8659', 'hArr'=>'8660', 'forall'=>'8704', 'part'=>'8706', 'exist'=>'8707', 'empty'=>'8709', 'nabla'=>'8711', 'isin'=>'8712', 'notin'=>'8713', 'ni'=>'8715', 'prod'=>'8719', 'sum'=>'8721', 'minus'=>'8722', 'lowast'=>'8727', 'radic'=>'8730', 'prop'=>'8733', 'infin'=>'8734', 'ang'=>'8736', 'and'=>'8743', 'or'=>'8744', 'cap'=>'8745', 'cup'=>'8746', 'int'=>'8747', 'there4'=>'8756', 'sim'=>'8764', 'cong'=>'8773', 'asymp'=>'8776', 'ne'=>'8800', 'equiv'=>'8801', 'le'=>'8804', 'ge'=>'8805', 'sub'=>'8834', 'sup'=>'8835', 'nsub'=>'8836', 'sube'=>'8838', 'supe'=>'8839', 'oplus'=>'8853', 'otimes'=>'8855', 'perp'=>'8869', 'sdot'=>'8901', 'lceil'=>'8968', 'rceil'=>'8969', 'lfloor'=>'8970', 'rfloor'=>'8971', 'lang'=>'9001', 'rang'=>'9002', 'loz'=>'9674', 'spades'=>'9824', 'clubs'=>'9827', 'hearts'=>'9829', 'diams'=>'9830', 'apos'=>'39',  'OElig'=>'338', 'oelig'=>'339', 'Scaron'=>'352', 'scaron'=>'353', 'Yuml'=>'376', 'circ'=>'710', 'tilde'=>'732', 'ensp'=>'8194', 'emsp'=>'8195', 'thinsp'=>'8201', 'zwnj'=>'8204', 'zwj'=>'8205', 'lrm'=>'8206', 'rlm'=>'8207', 'ndash'=>'8211', 'mdash'=>'8212', 'lsquo'=>'8216', 'rsquo'=>'8217', 'sbquo'=>'8218', 'ldquo'=>'8220', 'rdquo'=>'8221', 'bdquo'=>'8222', 'dagger'=>'8224', 'Dagger'=>'8225', 'permil'=>'8240', 'lsaquo'=>'8249', 'rsaquo'=>'8250', 'euro'=>'8364', 'nbsp'=>'160', 'iexcl'=>'161', 'cent'=>'162', 'pound'=>'163', 'curren'=>'164', 'yen'=>'165', 'brvbar'=>'166', 'sect'=>'167', 'uml'=>'168', 'copy'=>'169', 'ordf'=>'170', 'laquo'=>'171', 'not'=>'172', 'shy'=>'173', 'reg'=>'174', 'macr'=>'175', 'deg'=>'176', 'plusmn'=>'177', 'sup2'=>'178', 'sup3'=>'179', 'acute'=>'180', 'micro'=>'181', 'para'=>'182', 'middot'=>'183', 'cedil'=>'184', 'sup1'=>'185', 'ordm'=>'186', 'raquo'=>'187', 'frac14'=>'188', 'frac12'=>'189', 'frac34'=>'190', 'iquest'=>'191', 'Agrave'=>'192', 'Aacute'=>'193', 'Acirc'=>'194', 'Atilde'=>'195', 'Auml'=>'196', 'Aring'=>'197', 'AElig'=>'198', 'Ccedil'=>'199', 'Egrave'=>'200', 'Eacute'=>'201', 'Ecirc'=>'202', 'Euml'=>'203', 'Igrave'=>'204', 'Iacute'=>'205', 'Icirc'=>'206', 'Iuml'=>'207', 'ETH'=>'208', 'Ntilde'=>'209', 'Ograve'=>'210', 'Oacute'=>'211', 'Ocirc'=>'212', 'Otilde'=>'213', 'Ouml'=>'214', 'times'=>'215', 'Oslash'=>'216', 'Ugrave'=>'217', 'Uacute'=>'218', 'Ucirc'=>'219', 'Uuml'=>'220', 'Yacute'=>'221', 'THORN'=>'222', 'szlig'=>'223', 'agrave'=>'224', 'aacute'=>'225', 'acirc'=>'226', 'atilde'=>'227', 'auml'=>'228', 'aring'=>'229', 'aelig'=>'230', 'ccedil'=>'231', 'egrave'=>'232', 'eacute'=>'233', 'ecirc'=>'234', 'euml'=>'235', 'igrave'=>'236', 'iacute'=>'237', 'icirc'=>'238', 'iuml'=>'239', 'eth'=>'240', 'ntilde'=>'241', 'ograve'=>'242', 'oacute'=>'243', 'ocirc'=>'244', 'otilde'=>'245', 'ouml'=>'246', 'divide'=>'247', 'oslash'=>'248', 'ugrave'=>'249', 'uacute'=>'250', 'ucirc'=>'251', 'uuml'=>'252', 'yacute'=>'253', 'thorn'=>'254', 'yuml'=>'255');
if($in[0] != '#'){
 return ($cf['and_mark'] ? "\x06" : '&'). (isset($U[$in]) ? $in : (isset($N[$in]) ? (!$cf['named_entity'] ? '#'. ($cf['hexdec_entity'] > 1 ? 'x'. dechex($N[$in]) : $N[$in]) : $in) : 'amp;'. $in)). ';';
}
if(($n = ctype_digit($in = substr($in, 1)) ? intval($in) : hexdec(substr($in, 1))) < 9 or ($n > 13 && $n < 32) or $n == 11 or $n == 12 or ($n > 126 && $n < 160 && $n != 133) or ($n > 64975 && $n < 64992) or $n > 65535){
 return ($cf['and_mark'] ? "\x06" : '&'). "amp;#{$in};";
}
return ($cf['and_mark'] ? "\x06" : '&'). '#'. (((ctype_digit($in) && $cf['hexdec_entity'] < 2) or !$cf['hexdec_entity']) ? $n : 'x'. dechex($n)). ';';
// eof
}

function hl_prot($p, $c=null){
// check URL scheme
global $cf;
$b = $a = '';
if($c == null){$c = 'style'; $b = $p[1]; $a = $p[3]; $p = trim($p[2]);}
$c = isset($cf['schemes'][$c]) ? $cf['schemes'][$c] : $cf['schemes']['*'];
if(isset($c['*']) or !strcspn($p, '#?;')){return "{$b}{$p}{$a}";} // All ok, frag, query, param
if(preg_match('`^([a-z\d\-+.&#; ]+?)(:|&#(58|x3a);|%3a|\\\\0{0,4}3a).`i', $p, $m) && !isset($c[strtolower($m[1])])){ // Denied prot
 return "{$b}denied:{$p}{$a}";
}
if($cf['abs_url']){
 if($cf['abs_url'] == -1 && strpos($p, $cf['base_url']) === 0){ // Make url rel
  $p = substr($p, strlen($cf['base_url']));
 }elseif(empty($m[1])){ // Make rel URL abs; re rfc 1808; not inherit param/query/frag
  if(substr($p, 0, 2) == '//'){$p = substr($cf['base_url'], 0, strpos($cf['base_url'], ':')+1). $p;}
  elseif($p[0] == '/'){$p = preg_replace('`(^.+?://[^/]+)(.*)`', '$1', $cf['base_url']). $p;}
  elseif(strcspn($p, './')){$p = $cf['base_url']. $p;}
  else{
   preg_match('`^([a-zA-Z\d\-+.]+://[^/]+)(.*)`', $cf['base_url'], $m);
   $p = preg_replace('`(?<=/)\./`', '', $m[2]. $p);
   while(preg_match('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', $p)){
    $p = preg_replace('`(?<=/)([^/]{3,}|[^/.]+?|\.[^/.]|[^/.]\.)/\.\./`', '', $p);
   }
   $p = $m[1]. $p;
  }
 }
}
return "{$b}{$p}{$a}";
// eof
}

function hl_regex($p){
// ?ok regex
if(empty($p)){return 0;}
if($t = ini_get('track_errors')){$o = isset($php_errormsg) ? $php_errormsg : null;}
else{ini_set('track_errors', 1);}
unset($php_errormsg);
if(($d = ini_get('display_errors'))){ini_set('display_errors', 0);}
preg_match($p, '');
if($d){ini_set('display_errors', 1);}
$r = isset($php_errormsg) ? 0 : 1;
if($t){$php_errormsg = isset($o) ? $o : null;}
else{ini_set('track_errors', 0);}
return $r;
// eof
}

function hl_spec($t){
// finalize $spec
$s = array();
$t = str_replace(array("\t", "\r", "\n", ' '), '', preg_replace('/"(?>(`.|[^"])*)"/sme', 'substr(str_replace(array(";", "|", "~", " ", ",", "/", "(", ")", \'`"\'), array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\""), \'\\0\'), 1, -1)', trim($t)));
for($i = count(($t = explode(';', $t))); --$i>=0;){
 $w = $t[$i];
 if(empty($w) or ($e = strpos($w, '=')) === false or !strlen(($a =  substr($w, $e+1)))){continue;}
 $y = $n = array();
 foreach(explode(',', $a) as $v){
  if(!preg_match('`^([a-z:\-\*]+)(?:\((.*?)\))?`i', $v, $m)){continue;}
  if(($an = strtolower($m[1])) == '-*'){$n['*'] = 1; continue;}
  if($an[0] == '-'){$n[substr($an, 1)] = 1; continue;}
  if(!isset($m[2])){$y[$an] = 1; continue;}
  foreach(explode('/', $m[2]) as $m){
   if(empty($m) or ($pm = strpos($m, '=')) == 0 or $pm < 5){$y[$an] = 1; continue;}
   $y[$an][strtolower(substr($m, 0, $pm))] = str_replace(array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08"), array(";", "|", "~", " ", ",", "/", "(", ")"), substr($m, $pm+1));
  }
  if(isset($y[$an]['match']) && !hl_regex($y[$an]['match'])){unset($y[$an]['match']);}
  if(isset($y[$an]['nomatch']) && !hl_regex($y[$an]['nomatch'])){unset($y[$an]['nomatch']);}
 }
 if(!count($y) && !count($n)){continue;}
 if(!isset($n['*'])){
  foreach($y as $k=>$v){
   if(!is_array($v) or !count($v)){unset($y[$k]);}
  }
 }
 foreach(explode(',', substr($w, 0, $e)) as $v){
  if(!strlen(($v = strtolower($v)))){continue;}
  if(count($y)){$s[$v] = $y;}
  if(count($n)){$s[$v]['n'] = $n;}
 }
}
return $s;
// eof
}

function hl_tag($in){
// tag/attribute handler
global $cf;
$in = $in[0];
// entitify invalid < >
if($in == '< '){return '&lt; ';}
if($in == '>'){return '&gt;';}
if(!preg_match('`^<(/?)([a-zA-Z][a-zA-Z1-6]*)([^>]*?)\s?>$`m', $in, $m)){
 return str_replace(array('<', '>'), array('&lt;', '&gt;'), $in);
}elseif(!isset($cf['elements'][($e = strtolower($m[2]))])){
 return (($cf['keep_bad']%2) ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $in) : '');
}
// attr string
$a = str_replace(array("\xad", "\n", "\r", "\t"), ' ', trim($m[3]));
if(strpos($a, '&') !== false){
 str_replace(array('&#xad;', '&#173;', '&shy;'), ' ', $a);
}
// tag transform
static $eD = array('applet'=>1, 'center'=>1, 'dir'=>1, 'embed'=>1, 'font'=>1, 'isindex'=>1, 'menu'=>1, 's'=>1, 'strike'=>1, 'u'=>1); // Deprecated ele
if($cf['make_tag_strict'] && isset($eD[$e])){
 $trt = hl_tag2($e, $a, $cf['make_tag_strict']);
 if(!$e){return (($cf['keep_bad']%2) ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $in) : '');}
}
// close tag
static $eE = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'param'=>1); // Empty ele
if(!empty($m[1])){
 return (!isset($eE[$e]) ? "</$e>" : (($cf['keep_bad'])%2 ? str_replace(array('<', '>'), array('&lt;', '&gt;'), $in) : ''));
}

// open tag & attr
static $aN = array('script'=>array('embed'=>1, 'img'=>1), 'sscr'=>array('embed'=>1, 'img'=>1), 'abbr'=>array('td'=>1, 'th'=>1), 'accept-charset'=>array('form'=>1), 'accept'=>array('form'=>1, 'input'=>1), 'accesskey'=>array('a'=>1, 'area'=>1, 'button'=>1, 'input'=>1, 'label'=>1, 'legend'=>1, 'textarea'=>1), 'action'=>array('form'=>1), 'align'=>array('caption'=>1, 'embed'=>1, 'applet'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'object'=>1, 'legend'=>1, 'table'=>1, 'hr'=>1, 'div'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'p'=>1, 'col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'alt'=>array('applet'=>1, 'area'=>1, 'img'=>1, 'input'=>1), 'archive'=>array('applet'=>1, 'object'=>1), 'axis'=>array('td'=>1, 'th'=>1), 'bgcolor'=>array('embed'=>1, 'table'=>1, 'tr'=>1, 'td'=>1, 'th'=>1), 'border'=>array('table'=>1, 'img'=>1, 'object'=>1), 'bordercolor'=>array('table'=>1, 'td'=>1, 'tr'=>1), 'cellpadding'=>array('table'=>1), 'cellspacing'=>array('table'=>1), 'char'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'charoff'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'charset'=>array('a'=>1, 'script'=>1), 'checked'=>array('input'=>1), 'cite'=>array('blockquote'=>1, 'q'=>1, 'del'=>1, 'ins'=>1), 'classid'=>array('object'=>1), 'clear'=>array('br'=>1), 'code'=>array('applet'=>1), 'codebase'=>array('object'=>1, 'applet'=>1), 'codetype'=>array('object'=>1), 'color'=>array('font'=>1), 'cols'=>array('textarea'=>1), 'colspan'=>array('td'=>1, 'th'=>1), 'compact'=>array('dir'=>1, 'dl'=>1, 'menu'=>1, 'ol'=>1, 'ul'=>1), 'coords'=>array('area'=>1, 'a'=>1), 'data'=>array('object'=>1), 'datetime'=>array('del'=>1, 'ins'=>1), 'declare'=>array('object'=>1), 'defer'=>array('script'=>1), 'dir'=>array('bdo'=>1), 'disabled'=>array('button'=>1, 'input'=>1, 'optgroup'=>1, 'option'=>1, 'select'=>1, 'textarea'=>1), 'enctype'=>array('form'=>1), 'face'=>array('font'=>1), 'for'=>array('label'=>1), 'frame'=>array('table'=>1), 'frameborder'=>array('iframe'=>1), 'headers'=>array('td'=>1, 'th'=>1), 'height'=>array('embed'=>1, 'iframe'=>1, 'td'=>1, 'th'=>1, 'img'=>1, 'object'=>1, 'applet'=>1), 'href'=>array('a'=>1, 'area'=>1), 'hreflang'=>array('a'=>1), 'hspace'=>array('applet'=>1, 'img'=>1, 'object'=>1), 'ismap'=>array('img'=>1, 'input'=>1), 'label'=>array('option'=>1, 'optgroup'=>1), 'language'=>array('script'=>1), 'longdesc'=>array('img'=>1, 'iframe'=>1), 'marginheight'=>array('iframe'=>1), 'marginwidth'=>array('iframe'=>1), 'maxlength'=>array('input'=>1), 'method'=>array('form'=>1), 'model'=>array('embed'=>1), 'multiple'=>array('select'=>1), 'name'=>array('button'=>1, 'embed'=>1, 'textarea'=>1, 'applet'=>1, 'select'=>1, 'form'=>1, 'iframe'=>1, 'img'=>1, 'a'=>1, 'input'=>1, 'object'=>1, 'map'=>1, 'param'=>1), 'nohref'=>array('area'=>1), 'noshade'=>array('hr'=>1), 'nowrap'=>array('td'=>1, 'th'=>1), 'object'=>array('applet'=>1), 'onblur'=>array('a'=>1, 'area'=>1, 'button'=>1, 'input'=>1, 'label'=>1, 'select'=>1, 'textarea'=>1), 'onchange'=>array('input'=>1, 'select'=>1, 'textarea'=>1), 'onfocus'=>array('a'=>1, 'area'=>1, 'button'=>1, 'input'=>1, 'label'=>1, 'select'=>1, 'textarea'=>1), 'onreset'=>array('form'=>1), 'onselect'=>array('input'=>1, 'textarea'=>1), 'onsubmit'=>array('form'=>1), 'pluginspage'=>array('embed'=>1), 'pluginurl'=>array('embed'=>1), 'prompt'=>array('isindex'=>1), 'readonly'=>array('textarea'=>1, 'input'=>1), 'rel'=>array('a'=>1), 'rev'=>array('a'=>1), 'rows'=>array('textarea'=>1), 'rowspan'=>array('td'=>1, 'th'=>1), 'rules'=>array('table'=>1), 'scope'=>array('td'=>1, 'th'=>1), 'scrolling'=>array('iframe'=>1), 'selected'=>array('option'=>1), 'shape'=>array('area'=>1, 'a'=>1), 'size'=>array('hr'=>1, 'font'=>1, 'input'=>1, 'select'=>1), 'span'=>array('col'=>1, 'colgroup'=>1), 'src'=>array('embed'=>1, 'script'=>1, 'input'=>1, 'iframe'=>1, 'img'=>1), 'standby'=>array('object'=>1), 'start'=>array('ol'=>1), 'summary'=>array('table'=>1), 'tabindex'=>array('a'=>1, 'area'=>1, 'button'=>1, 'input'=>1, 'object'=>1, 'select'=>1, 'textarea'=>1), 'target'=>array('a'=>1, 'area'=>1, 'form'=>1), 'type'=>array('a'=>1, 'embed'=>1, 'object'=>1, 'param'=>1, 'script'=>1, 'input'=>1, 'li'=>1, 'ol'=>1, 'ul'=>1, 'button'=>1), 'usemap'=>array('img'=>1, 'input'=>1, 'object'=>1), 'valign'=>array('col'=>1, 'colgroup'=>1, 'tbody'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1), 'value'=>array('input'=>1, 'option'=>1, 'param'=>1, 'button'=>1, 'li'=>1), 'valuetype'=>array('param'=>1), 'vspace'=>array('applet'=>1, 'img'=>1, 'object'=>1), 'width'=>array('embed'=>1, 'hr'=>1, 'iframe'=>1, 'img'=>1, 'object'=>1, 'table'=>1, 'td'=>1, 'th'=>1, 'applet'=>1, 'col'=>1, 'colgroup'=>1, 'pre'=>1), 'wmode'=>array('embed'=>1), 'xml:space'=>array('pre'=>1, 'script'=>1, 'style'=>1)); // Specific attrs
static $aNE = array('checked'=>1, 'compact'=>1, 'declare'=>1, 'defer'=>1, 'disabled'=>1, 'ismap'=>1, 'multiple'=>1, 'nohref'=>1, 'noresize'=>1, 'noshade'=>1, 'nowrap'=>1, 'readonly'=>1, 'selected'=>1); // 'Empty' attrs
static $aNP = array('action'=>1, 'cite'=>1, 'classid'=>1, 'codebase'=>1, 'data'=>1, 'href'=>1, 'longdesc'=>1, 'model'=>1, 'pluginspage'=>1, 'pluginurl'=>1, 'usemap'=>1); // Attrs needing URL protocol check; for attrs like onmouseover & src, using: '$n[0] != 'o' && strpos($n, 'src') === false'; 'style' separately handled
static $aNU = array('class'=>array('param'=>1, 'script'=>1), 'dir'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'iframe'=>1, 'param'=>1, 'script'=>1), 'id'=>array('script'=>1), 'lang'=>array('applet'=>1, 'br'=>1, 'iframe'=>1, 'param'=>1, 'script'=>1), 'xml:lang'=>array('applet'=>1, 'br'=>1, 'iframe'=>1, 'param'=>1, 'script'=>1), 'onclick'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'ondblclick'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onkeydown'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onkeypress'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onkeyup'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmousedown'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmousemove'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmouseout'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmouseover'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'onmouseup'=>array('applet'=>1, 'bdo'=>1, 'br'=>1, 'font'=>1, 'iframe'=>1, 'isindex'=>1, 'param'=>1, 'script'=>1), 'style'=>array('param'=>1, 'script'=>1), 'title'=>array('param'=>1, 'script'=>1)); // Univ attrs & exceptions

if($cf['lc_std_val']){
 // predef attr vals like radio for $eAL & $aNE ele
 static $aNL = array('all'=>1, 'baseline'=>1, 'bottom'=>1, 'button'=>1, 'center'=>1, 'char'=>1, 'checkbox'=>1, 'circle'=>1, 'col'=>1, 'colgroup'=>1, 'cols'=>1, 'data'=>1, 'default'=>1, 'file'=>1, 'get'=>1, 'groups'=>1, 'hidden'=>1, 'image'=>1, 'justify'=>1, 'left'=>1, 'ltr'=>1, 'middle'=>1, 'none'=>1, 'object'=>1, 'password'=>1, 'poly'=>1, 'post'=>1, 'preserve'=>1, 'radio'=>1, 'rect'=>1, 'ref'=>1, 'reset'=>1, 'right'=>1, 'row'=>1, 'rowgroup'=>1, 'rows'=>1, 'rtl'=>1, 'submit'=>1, 'text'=>1, 'top'=>1);
 static $eAL = array('a'=>1, 'area'=>1, 'bdo'=>1, 'button'=>1, 'col'=>1, 'form'=>1, 'img'=>1, 'input'=>1, 'object'=>1, 'optgroup'=>1, 'option'=>1, 'param'=>1, 'script'=>1, 'select'=>1, 'table'=>1, 'td'=>1, 'tfoot'=>1, 'th'=>1, 'thead'=>1, 'tr'=>1, 'xml:space'=>1);
 $lcase = isset($eAL[$e]) ? 1 : 0;
}

$depTr = 0;
if($cf['no_deprecated_attr']){
 // dep attr:applicable ele
 static $aND = array('align'=>array('caption'=>1, 'div'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'img'=>1, 'input'=>1, 'legend'=>1, 'object'=>1, 'p'=>1, 'table'=>1), 'bgcolor'=>array('table'=>1, 'td'=>1, 'th'=>1, 'tr'=>1), 'border'=>array('img'=>1, 'object'=>1), 'bordercolor'=>array('table'=>1, 'td'=>1, 'tr'=>1), 'clear'=>array('br'=>1), 'compact'=>array('dl'=>1, 'ol'=>1, 'ul'=>1), 'height'=>array('td'=>1, 'th'=>1), 'hspace'=>array('img'=>1, 'object'=>1), 'language'=>array('script'=>1), 'name'=>array('a'=>1, 'form'=>1, 'iframe'=>1, 'img'=>1, 'map'=>1), 'noshade'=>array('hr'=>1), 'nowrap'=>array('td'=>1, 'th'=>1), 'size'=>array('hr'=>1), 'start'=>array('ol'=>1), 'type'=>array('li'=>1, 'ol'=>1, 'ul'=>1), 'value'=>array('li'=>1), 'vspace'=>array('img'=>1, 'object'=>1), 'width'=>array('hr'=>1, 'pre'=>1, 'td'=>1, 'th'=>1));
 static $eAD = array('a'=>1, 'br'=>1, 'caption'=>1, 'div'=>1, 'dl'=>1, 'form'=>1, 'h1'=>1, 'h2'=>1, 'h3'=>1, 'h4'=>1, 'h5'=>1, 'h6'=>1, 'hr'=>1, 'iframe'=>1, 'img'=>1, 'input'=>1, 'legend'=>1, 'li'=>1, 'map'=>1, 'object'=>1, 'ol'=>1, 'p'=>1, 'pre'=>1, 'script'=>1, 'table'=>1, 'td'=>1, 'th'=>1, 'tr'=>1, 'ul'=>1);
 $depTr = isset($eAD[$e]) ? 1 : 0;
}

// attr name-vals
if(strpos($a, "\x01") !== false){$a = preg_replace('`\x01[^\x01]*\x01`', '', $a);} // No comments/CDATA secs
$mode = 0; $a = trim($a, ' /'); $aA = array();
while(strlen($a)){
 $w = 0;
 switch($mode){
  case 0: // Attr name - min 2 chars; : in xml:lang
   if(preg_match('`^[a-zA-Z][\-a-zA-Z:]+`', $a, $m)){
    $nm = strtolower($m[0]);
    $w = $mode = 1; $a = ltrim(substr_replace($a, '', 0, strlen($m[0])));
   }
  break; case 1:
   if($a[0] == '='){ // =
    $w = 1; $mode = 2; $a = ltrim($a, '= ');
   }else{ // No val
    $w = 1; $mode = 0; $a = ltrim($a);
    $aA[$nm] = '';
   }
  break; case 2: // Attr val
   if(preg_match('`^"[^"]*"`', $a, $m) or preg_match("`^'[^']*'`", $a, $m) or preg_match("`^\s*[^\s\"']+`", $a, $m)){
    $m = $m[0]; $w = 1; $mode = 0; $a = ltrim(substr_replace($a, '', 0, strlen($m)));
    $aA[$nm] = trim(($m[0] == '"' or $m[0] == '\'') ? substr($m, 1, -1) : $m);
   }
  break;
 }
 if($w == 0){ // Parse errs, deal with space, " & '
  $a = preg_replace('`^(?:"[^"]*("|$)|\'[^\']*(\'|$)|\S)*\s*`', '', $a);
  $mode = 0;
 }
}
if($mode == 1){$aA[$nm] = '';}

// clean attrs - remove invalids, escape ", values for 'empty' attr, lowercase predefined values, remove ones with unfit values, anti-spam, check scheme & expressions in style props, check scheme in other attr
global $spec;
$rl = isset($spec[$e]) ? $spec[$e] : array();
$a = array(); $nfr = 0;
foreach($aA as $k=>$v){
 if(!isset($cf['deny_attribute'][$k]) && ((!isset($rl['n'][$k]) && !isset($rl['n']['*'])) or isset($rl[$k])) && (isset($aN[$k][$e]) or (isset($aNU[$k]) && !isset($aNU[$k][$e])))){
  if(isset($aNE[$k])){$v = $k;}
  elseif(!empty($lcase) && (($e != 'button' or $e != 'input') or $k == 'type')){ // Rather loose but ?not cause issues
   $v = (isset($aNL[($v2 = strtolower($v))])) ? $v2 : $v;
  }
  if($k == 'style'){
   $v = preg_replace_callback('`((?:u|&#(?:x75|117);)(?:r|&#(?:x72|114);)(?:l|&#(?:x6c|108);)(?:\(|&#(?:x28|40);)(?: |&#(?:x20|32);)*(?:\'|"|&(?:quot|apos|34|39|x22|x27);)?)(.+)((?:\'|"|&(?:quot|apos|34|39|x22|x27);)?(?: |&#(?:x20|32);)*(?:\)|&#(?:x29|41);))`iS', 'hl_prot', $v);
   if(!$cf['css_expression']){
    $v = preg_replace('`(?::|&#(?:x3a|58);)(?: |&#(?:x20|32);)*e.+?n(?: |&#(?:x20|32);)*(?:\(|&#(?:x28|40);).*(?:\)|&#(?:x29|41);)`iS', '', $v);
   }
  }elseif(isset($aNP[$k]) or strpos($k, 'src') !== false or $k[0] == 'o'){
   $v = hl_prot($v, $k);
   if($k == 'href'){ // Anti-spam
    if($cf['anti_mail_spam'] && strpos($v, 'mailto:') === 0){
     $v = str_replace('@', htmlspecialchars($cf['anti_mail_spam']), $v);
    }elseif($cf['anti_link_spam']){
     $r1 = $cf['anti_link_spam'][1];
     if(!empty($r1) && preg_match($r1, $v)){continue;}
     $r0 = $cf['anti_link_spam'][0];
     if(!empty($r0) && preg_match($r0, $v)){
      if(isset($a['rel'])){
       if(!preg_match('`\bnofollow\b`i', $a['rel'])){$a['rel'] .= ' nofollow';}
      }elseif(isset($aA['rel'])){
       if(!preg_match('`\bnofollow\b`i', $aA['rel'])){$nfr = 1;}
      }else{$a['rel'] = 'nofollow';}
     }
    }
   }
  }
  if(isset($rl[$k]) && is_array($rl[$k]) && ($v = hl_attrval($v, $rl[$k])) === 0){continue;}
  $a[$k] = str_replace('"', '&quot;', $v);
 }
}
if($nfr){$a['rel'] = isset($a['rel']) ? $a['rel']. ' nofollow' : 'nofollow';}

// rqd attr
static $eAR = array('area'=>array('alt'=>'area'), 'bdo'=>array('dir'=>'ltr'), 'form'=>array('action'=>''), 'img'=>array('src'=>'', 'alt'=>'image'), 'map'=>array('name'=>''), 'optgroup'=>array('label'=>''), 'param'=>array('name'=>''), 'script'=>array('type'=>'text/javascript'), 'textarea'=>array('rows'=>'10', 'cols'=>'50'));
if(isset($eAR[$e])){
 foreach($eAR[$e] as $k=>$v){
  if(!isset($a[$k])){$a[$k] = isset($v[0]) ? $v : $k;}
 }
}

// depr attrs
if($depTr){
 $c = array();
 foreach($a as $k=>$v){
  if($k == 'style' or !isset($aND[$k][$e])){continue;}
  if($k == 'align'){
   unset($a['align']);
   if($e == 'img' && ($v == 'left' or $v == 'right')){$c[] = 'float: '. $v;}
   elseif(($e == 'div' or $e == 'table') && $v == 'center'){$c[] = 'margin: auto';}
   else{$c[] = 'text-align: '. $v;}
  }elseif($k == 'bgcolor'){
   unset($a['bgcolor']);
   $c[] = 'background-color: '. $v;
  }elseif($k == 'border'){
   unset($a['border']); $c[] = "border: {$v}px";
  }elseif($k == 'bordercolor'){
   unset($a['bordercolor']); $c[] = 'border-color: '. $v;
  }elseif($k == 'clear'){
   unset($a['clear']); $c[] = 'clear: '. ($v != 'all' ? $v : 'both');
  }elseif($k == 'compact'){
   unset($a['compact']); $c[] = 'font-size: 85%';
  }elseif($k == 'height' or $k == 'width'){
   unset($a[$k]); $c[] = $k. ': '. ($v[0] != '*' ? $v. (ctype_digit($v) ? 'px' : '') : 'auto');
  }elseif($k == 'hspace'){
   unset($a['hspace']); $c[] = "margin-left: {$v}px; margin-right: {$v}px";
  }elseif($k == 'language' && !isset($a['type'])){
   unset($a['language']);
   $a['type'] = 'text/'. strtolower($v);
  }elseif($k == 'name'){
   if($cf['no_deprecated_attr'] == 2 or ($e != 'a' && $e != 'map')){unset($a['name']);}
   if(!isset($a['id']) && preg_match('`[a-zA-Z][a-zA-Z\d.:_\-]*`', $v)){$a['id'] = $v;}
  }elseif($k == 'noshade'){
   unset($a['noshade']); $c[] = 'border-style: none; border: 0; background-color: gray; color: gray';
  }elseif($k == 'nowrap'){
   unset($a['nowrap']); $c[] = 'white-space: nowrap';
  }elseif($k == 'size'){
   unset($a['size']); $c[] = 'size: '. $v. 'px';
  }elseif($k == 'start' or $k == 'value'){
   unset($a[$k]);
  }elseif($k == 'type'){
   unset($a['type']);
   static $ol_type = array('i'=>'lower-roman', 'I'=>'upper-roman', 'a'=>'lower-latin', 'A'=>'upper-latin', '1'=>'decimal');
   $c[] = 'list-style-type: '. (isset($ol_type[$v]) ? $ol_type[$v] : 'decimal');
  }elseif($k == 'vspace'){
   unset($a['vspace']); $c[] = "margin-top: {$v}px; margin-bottom: {$v}px";
  }
 }
 if(count($c)){
  $c = implode('; ', $c);
  $a['style'] = isset($a['style']) ? rtrim($a['style'], ' ;'). '; '. $c. ';': $c. ';';
 }
}
// unique IDs
if($cf['unique_ids'] && isset($a['id'])){ // XHTML spec
 if(!preg_match('`^[A-Za-z][A-Za-z0-9_\-.:]*$`', ($id = $a['id'])) or (isset($GLOBALS['hl_Ids'][$id]) && $cf['unique_ids'] == 1)){unset($a['id']);
 }else{
  while(isset($GLOBALS['hl_Ids'][$id])){$id = $cf['unique_ids']. $id;}
  $GLOBALS['hl_Ids'][($a['id'] = $id)] = 1;
 }
}
// xml:lang
if($cf['xml:lang'] && isset($a['lang'])){
 $a['xml:lang'] = isset($a['xml:lang']) ? $a['xml:lang'] : $a['lang'];
 if($cf['xml:lang'] == 2){unset($a['lang']);}
}
// for transformed tag
if(!empty($trt)){
 $a['style'] = isset($a['style']) ? rtrim($a['style'], ' ;'). '; '. $trt : $trt;
}
// final attr
$aA = '';
foreach($a as $k=>$v){$aA .= " {$k}=\"{$v}\"";}
// return with empty ele's slash
return "<{$e}{$aA}". (isset($eE[$e]) ? ' /' : ''). '>';
// eof
}

function hl_tag2(&$e, &$a, $t=1){
// transform tag
if($e == 'center'){$e = 'div'; return 'text-align: center;';}
if($e == 'dir' or $e == 'menu'){$e = 'ul'; return '';}
if($e == 's' or $e == 'strike'){$e = 'span'; return 'text-decoration: line-through;';}
if($e == 'u'){$e = 'span'; return 'text-decoration: underline;';}
static $fs = array('0'=>'xx-small', '1'=>'xx-small', '2'=>'small', '3'=>'medium', '4'=>'large', '5'=>'x-large', '6'=>'xx-large', '7'=>'300%', '-1'=>'smaller', '-2'=>'60%', '+1'=>'larger', '+2'=>'150%', '+3'=>'200%', '+4'=>'300%');
if($e == 'font'){
 $a2 = '';
 if(preg_match('`face\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m)){
  $a2 .= ' font-family: '. str_replace('"', '\'', trim($m[2])). ';';
 }
 if(preg_match('`color\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m)){
  $a2 .= ' color: '. trim($m[2]). ';';
 }
 if(preg_match('`size\s*=\s*(\'|")?(.+?)(\\1|\s|$)`i', $a, $m) && isset($fs[($m = trim($m[2]))])){
  $a2 .= ' font-size: '. $fs[$m]. ';';
 }
 $e = 'span'; return ltrim($a2);
}
if($t == 2){$e = 0; return 0;}
return '';
// eof
}

function hl_version(){
// version
return '1.0.8';
// eof
}

function kses($in, $ok_htm, $ok_prots = array('http', 'https', 'ftp', 'news', 'nntp', 'telnet', 'gopher', 'mailto')){
// kses compat
foreach($ok_htm as $k=>$v){
 $ok_htm[$k]['n']['*'] = 1;
}
$cf['cdata'] = $cf['comment'] = $cf['lc_std_val'] = $cf['make_tag_strict'] = $cf['no_deprecated_attr'] = $cf['unique_ids'] = 0;
$cf['css_expression'] = $cf['keep_bad'] = 1;
$cf['elements'] = count($ok_htm) ? strtolower(implode(',', array_keys($ok_htm))) : '-*';
$cf['hook'] = 'kses_hook';
$cf['schemes'] = '*:'. implode(',', $ok_prots);
return htmLawed($in, $cf, $ok_htm);
// eof
}

function kses_hook($string, &$cf, &$spec){
// kses compat
return $string;
// eof
}

function remove_instr_only($str) {
	if (strpos($str,'[LTI:')!==false) {
		$str = preg_replace('/\[LTI:[^\]]*\]/', '', $str);
	}
	return $str;
}