<?php
require_once __DIR__."/../../assessment/mathparser.php";
// ASCIIsvgIMG.php
// (c) 2006-2009 David Lippman   http://www.pierce.ctc.edu/dlippman
// Generates an image based on an ASCIIsvg script
// as a backup for ASCIIsvg.js SVG generator script
//
// Revised 3/08 to add angle to text
// Revised 8/09 to add arc
//
// Based on ASCIIsvg.js (c) Peter Jipsen
// http://www.chapman.edu/~jipsen/svg/asciisvg.html
//
// Recognized commands:
//	setBorder(border) or setBorder(left,bottom,right,top)
//	initPicture(xmin,xmax,{ymin,ymax})
//	axes(xtick,ytick,{"labels",xgrid,ygrid,dox,doy})      //0 for off
//
//	plot("f(x)",{xmin,xmax,steps})
//	plot("[x(t),y(t)]",{tmin,tmax,steps})
//	slopefield("dy/dx",{xres,yres})

//	line([x1,y1],[x2,y2])
//	path([[x1,y1],...,[xn,yn]])
//	circle([x1,y1],rad)
//	ellipse([x1,y1],xrad,yrad)
//      arc([x1,y1],[x2,y2],rad)       //arc drawn along counterclockwise arc
//	rect([x1,y1],[x2,y2])
//	text([x1,y1],"string",{pos,angle});  	//(pos: left,right,above,below,aboveleft,...)
//	textabs([pixelx,pixely],"string",{pos,angle});
//	dot([x1,y1],{type,label,pos});	//(type: open, closed)
//	stroke = "color"		//line color
//	fill = "color"			//fill color
//      fontfill = "color"		//text color
//	fontbackground = "color"	//background color for text
//	strokewidth=width		//line thickness
//	strokedasharray=array		//dash array, ie "5 3" for 5 pixel color, 3 white
//	marker = marker			//"dot" or "arrow" or "arrowdot" or "none"
//					//marker for lines or paths
//
// Use:
//	$AS = new AStoIMG(width,height);
//	$AS->processScript($ASscriptstring);
//		or
//	$AS->processShortScript($ASsscrString);
//	$AS->outputimage({filename});	//if no filename, outputed to stream

class AStoIMG
{

var $usegd2, $usettf;
var $xmin = -5;
var $xmax = 5;
var $ymin = -5;
var $ymax = 5;
var $border = array(5,5,5,5);
var $origin = array(0,0);
var $width;
var $height;
var $img;
var $winxmax,$winxmin,$winymin,$winymax;
var $white,$black,$red,$orange,$yellow,$green,$blue,$cyan,$purple,$gray;
var $stroke = 'black', $fill = 'none', $curdash='', $isdashed=false, $marker='none';
var $markerfill = 'green', $gridcolor = 'gray', $axescolor = 'black', $arrowrelsize = 1, $arrowoffset = 0;
var $strokewidth = 1, $xunitlength, $yunitlength, $dotradius=8, $ticklength=4;
var $fontsize = 12, $fontfile, $fontfill='', $fontbackground='';
var $isinit = false;
var $colors = array();
var $isphp8 = false;
var $extendedcolors = ['aliceblue'=>[240,248,255], 'antiquewhite'=>[250,235,215], 'aqua'=>[0,255,255], 'aquamarine'=>[127,255,212], 'azure'=>[240,255,255], 'beige'=>[245,245,220], 'bisque'=>[255,228,196], 'black'=>[0,0,0], 'blanchedalmond'=>[255,235,205], 'blue'=>[0,0,255], 'blueviolet'=>[138,43,226], 'brown'=>[165,42,42], 'burlywood'=>[222,184,135], 'cadetblue'=>[95,158,160], 'chartreuse'=>[127,255,0], 'chocolate'=>[210,105,30], 'coral'=>[255,127,80], 'cornflowerblue'=>[100,149,237], 'cornsilk'=>[255,248,220], 'crimson'=>[220,20,60], 'cyan'=>[0,255,255], 'darkblue'=>[0,0,139], 'darkcyan'=>[0,139,139], 'darkgoldenrod'=>[184,134,11], 'darkgray'=>[169,169,169], 'darkgreen'=>[0,100,0], 'darkgrey'=>[169,169,169], 'darkkhaki'=>[189,183,107], 'darkmagenta'=>[139,0,139], 'darkolivegreen'=>[85,107,47], 'darkorange'=>[255,140,0], 'darkorchid'=>[153,50,204], 'darkred'=>[139,0,0], 'darksalmon'=>[233,150,122], 'darkseagreen'=>[143,188,143], 'darkslateblue'=>[72,61,139], 'darkslategray'=>[47,79,79], 'darkslategrey'=>[47,79,79], 'darkturquoise'=>[0,206,209], 'darkviolet'=>[148,0,211], 'deeppink'=>[255,20,147], 'deepskyblue'=>[0,191,255], 'dimgray'=>[105,105,105], 'dimgrey'=>[105,105,105], 'dodgerblue'=>[30,144,255], 'firebrick'=>[178,34,34], 'floralwhite'=>[255,250,240], 'forestgreen'=>[34,139,34], 'fuchsia'=>[255,0,255], 'gainsboro'=>[220,220,220], 'ghostwhite'=>[248,248,255], 'gold'=>[255,215,0], 'goldenrod'=>[218,165,32], 'gray'=>[128,128,128], 'green'=>[0,128,0], 'greenyellow'=>[173,255,47], 'grey'=>[128,128,128], 'honeydew'=>[240,255,240], 'hotpink'=>[255,105,180], 'indianred'=>[205,92,92], 'indigo'=>[75,0,130], 'ivory'=>[255,255,240], 'khaki'=>[240,230,140], 'lavender'=>[230,230,250], 'lavenderblush'=>[255,240,245], 'lawngreen'=>[124,252,0], 'lemonchiffon'=>[255,250,205], 'lightblue'=>[173,216,230], 'lightcoral'=>[240,128,128], 'lightcyan'=>[224,255,255], 'lightgoldenrodyellow'=>[250,250,210], 'lightgray'=>[211,211,211], 'lightgreen'=>[144,238,144], 'lightgrey'=>[211,211,211], 'lightpink'=>[255,182,193], 'lightsalmon'=>[255,160,122], 'lightseagreen'=>[32,178,170], 'lightskyblue'=>[135,206,250], 'lightslategray'=>[119,136,153], 'lightslategrey'=>[119,136,153], 'lightsteelblue'=>[176,196,222], 'lightyellow'=>[255,255,224], 'lime'=>[0,255,0], 'limegreen'=>[50,205,50], 'linen'=>[250,240,230], 'magenta'=>[255,0,255], 'maroon'=>[128,0,0], 'mediumaquamarine'=>[102,205,170], 'mediumblue'=>[0,0,205], 'mediumorchid'=>[186,85,211], 'mediumpurple'=>[147,112,219], 'mediumseagreen'=>[60,179,113], 'mediumslateblue'=>[123,104,238], 'mediumspringgreen'=>[0,250,154], 'mediumturquoise'=>[72,209,204], 'mediumvioletred'=>[199,21,133], 'midnightblue'=>[25,25,112], 'mintcream'=>[245,255,250], 'mistyrose'=>[255,228,225], 'moccasin'=>[255,228,181], 'navajowhite'=>[255,222,173], 'navy'=>[0,0,128], 'oldlace'=>[253,245,230], 'olive'=>[128,128,0], 'olivedrab'=>[107,142,35], 'orange'=>[255,165,0], 'orangered'=>[255,69,0], 'orchid'=>[218,112,214], 'palegoldenrod'=>[238,232,170], 'palegreen'=>[152,251,152], 'paleturquoise'=>[175,238,238], 'palevioletred'=>[219,112,147], 'papayawhip'=>[255,239,213], 'peachpuff'=>[255,218,185], 'peru'=>[205,133,63], 'pink'=>[255,192,203], 'plum'=>[221,160,221], 'powderblue'=>[176,224,230], 'purple'=>[128,0,128], 'red'=>[255,0,0], 'rosybrown'=>[188,143,143], 'royalblue'=>[65,105,225], 'saddlebrown'=>[139,69,19], 'salmon'=>[250,128,114], 'sandybrown'=>[244,164,96], 'seagreen'=>[46,139,87], 'seashell'=>[255,245,238], 'sienna'=>[160,82,45], 'silver'=>[192,192,192], 'skyblue'=>[135,206,235], 'slateblue'=>[106,90,205], 'slategray'=>[112,128,144], 'slategrey'=>[112,128,144], 'snow'=>[255,250,250], 'springgreen'=>[0,255,127], 'steelblue'=>[70,130,180], 'tan'=>[210,180,140], 'teal'=>[0,128,128], 'thistle'=>[216,191,216], 'tomato'=>[255,99,71], 'turquoise'=>[64,224,208], 'violet'=>[238,130,238], 'wheat'=>[245,222,179], 'white'=>[255,255,255], 'whitesmoke'=>[245,245,245], 'yellow'=>[255,255,0], 'yellowgreen'=>[154,205,50]];

var $AScom;

function __construct() {
	$this->usegd2 = function_exists('imagesetthickness');
	$this->usettf = function_exists('imagettftext');
    $this->isphp8 = (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000);
}

function AStoIMG($w=200, $h=200) {
	$this->xmin = -5; $this->xmax = 5; $this->ymin = -5; $this->ymax = 5; $this->border = array(5,5,5,5);
	$this->stroke = 'black'; $this->fill = 'none'; $this->curdash=''; $this->isdashed=false; $this->marker='none';
	$this->markerfill = 'green'; $this->gridcolor = 'gray'; $this->axescolor = 'black';
	$this->strokewidth = 1; $this->dotradius=8; $this->ticklength=4;
	$this->fontsize = 12; $this->fontfill=''; $this->fontbackground='';
	$this->isinit = false;
	$this->colors = array();

	if ($w<=0) {$w=200;}
	if ($h<=0) {$h=200;}
	$w = (int) round($w);
	$h = (int) round($h);
	
	if ($this->usegd2) {
		$this->img = imagecreatetruecolor($w,$h);
		$this->colors['transblue'] = imagecolorallocatealpha($this->img, 0,0,255,90);
		$this->colors['transgreen'] = imagecolorallocatealpha($this->img, 0,255,0,90);
		$this->colors['transred'] = imagecolorallocatealpha($this->img, 255,0,0,90);
		$this->colors['transwhite'] = imagecolorallocatealpha($this->img, 255,255,255,90);
		$this->colors['transorange'] = imagecolorallocatealpha($this->img, 255,165,0,90);
		$this->colors['transyellow'] = imagecolorallocatealpha($this->img, 255,255,0,90);
		$this->colors['transpurple'] = imagecolorallocatealpha($this->img, 128,0,128,90);
	} else {
		$this->img = imagecreate($w,$h);
	}
	$this->fontfile =  __DIR__.'/FreeSerifItalic.ttf';
	$this->width = $w;
	$this->height = $h;
	$this->xunitlength = $w/10;
	$this->yunitlength = $h/10;
	$this->origin = array(round($w/2),round($h/2));
	$this->colors['white'] = imagecolorallocate($this->img, 255,255,255);
	$this->colors['black'] = imagecolorallocate($this->img, 0,0,0);
	$this->colors['gray'] = imagecolorallocate($this->img, 200,200,200);
	$this->colors['red'] = imagecolorallocate($this->img, 255,0,0);
	$this->colors['orange']= imagecolorallocate($this->img, 255,165,0);
	$this->colors['yellow'] = imagecolorallocate($this->img, 255,255,0);
	$this->colors['gold'] = imagecolorallocate($this->img, 255,215,0);
	$this->colors['green'] = imagecolorallocate($this->img, 0,255,0);
	$this->colors['blue'] = imagecolorallocate($this->img, 0,0,255);
	$this->colors['cyan'] = imagecolorallocate($this->img, 0,255,255);
	$this->colors['purple'] = imagecolorallocate($this->img, 128,0,128);
    $this->colors['brown'] = imagecolorallocate($this->img, 165, 42, 42);
	imagefill($this->img,0,0,$this->colors['white']);
}

function processShortScript($script) {
	//$xmin = -5; $xmax = 5; $ymin = -5; $ymax = 5; $border = 5;
	//$stroke = 'black'; $fill = 'none'; $curdash=''; $isdashed=false; $marker='none';
        //$markerfill = 'green'; $gridcolor = 'gray'; $axescolor = 'black';
	//$strokewidth = 1; $dotradius=8; $ticklength=4; $fontsize = 12;

	$sa = explode(',',$script);
	if (count($sa)>10) {
		$this->border = 5;
		$this->AStoIMG($sa[9],$sa[10]);
		$this->ASinitPicture(array_slice($sa,0,4));//$sa[0] .','. $sa[1] .','. $sa[2] .','. $sa[3]);
		$this->ASaxes(array_slice($sa,4,5));//$sa[4] .','. $sa[5] .','. $sa[6] .','. $sa[7] .','. $sa[8]);
		$inx = 11;
		while (count($sa) > $inx+9) {
			$this->stroke = $sa[$inx+7];
			$this->strokewidth = $sa[$inx+8];
			if ($this->usegd2) {
				imagesetthickness($this->img,(int) round($this->strokewidth > 0.5 ? $this->strokewidth : 1));
			}
			if ($sa[$inx+9] != "") {
				$this->ASsetdash($sa[$inx+9]);
			} else {
				$this->ASsetdash('none');
			}
			if ($sa[$inx]=='slope') {
				$this->ASslopefield(array($sa[$inx+1],$sa[$inx+2],$sa[$inx+2]));
			} else if ($sa[$inx]=='label') {
				$this->AStext(array($sa[$inx+5].','.$sa[$inx+6],$sa[$inx+1]));
			} else {
				if ($sa[$inx]=='func') {
					$eqn = $sa[$inx+1];
				} else if ($sa[$inx]=='polar') {
					$eqn = '[cos(t)*('.$sa[$inx+1].'),sin(t)*('.$sa[$inx+1].')]';
				} else if ($sa[$inx]=='param') {
					$eqn = '['.$sa[$inx+1].','.$sa[$inx+2].']';
				}
				if (is_numeric($sa[$inx+5])) {
					$this->ASplot(array($eqn,$sa[$inx+5],$sa[$inx+6],null,null,$sa[$inx+3],$sa[$inx+4]));
				} else {
					$this->ASplot(array($eqn,null,null,null,null,$sa[$inx+3],$sa[$inx+4]));
				}
			}
			$inx += 10;
		}
	}
}

function processScript($script) {
	//$xmin = -5; $xmax = 5; $ymin = -5; $ymax = 5; $border = 5;
	//$stroke = 'black'; $fill = 'none'; $curdash=''; $isdashed=false; $marker='none';
        //$markerfill = 'green'; $gridcolor = 'gray'; $axescolor = 'black';
	//$strokewidth = 1; $dotradius=8; $ticklength=4; $fontfill = ''; $fontsize = 12;
	//$script = preg_replace('/&[^\s]*;
	$script = html_entity_decode($script, ENT_COMPAT, 'UTF-8');
	$this->AScom =  explode(';',$script);
	foreach ($this->AScom as $com) {
		if (preg_match('/\s*(\w+)\s*=(.+)/',$com,$matches)) { //is assignment operator
			$matches[2] = trim(str_replace(array('"','\''),'',$matches[2]));
			switch($matches[1]) {
				case 'border':
				case 'xmin':
				case 'xmax':
				case 'ymin':
				case 'ymax':
				case 'marker':
                case 'arrowrelsize':
                case 'arrowoffset':
					$this->{$matches[1]} = $matches[2];
					break;
				case 'fill':
				case 'markerfill':
				case 'fontbackground':
				case 'fontfill':
					if ($matches[2] != 'none' && !isset($this->colors[$matches[2]])) {
						$this->addcolor($matches[2]);
					}
                    $this->{$matches[1]} = $matches[2];
					break;
				case 'stroke':
					if ($matches[2] != 'none' && !isset($this->colors[$matches[2]])) {
						$this->addcolor($matches[2]);
					}
                    $this->stroke = $matches[2];
					if ($this->isdashed) {
						$this->ASsetdash();
					}
					break;
				case 'strokedasharray':
					$this->ASsetdash($matches[2]);
					break;
				case 'strokewidth':
					$matches[2] = $this->evalifneeded($matches[2]);
					$this->strokewidth = (int) round($matches[2] > 0.5 ? $matches[2] : 1);
					if ($this->usegd2) {
						imagesetthickness($this->img,$this->strokewidth);
					}
					break;
			}
		}
		if (preg_match('/\s*(\w+)\((.*)\)\s*$/',$com,$matches)) { //is function
			$argarr = $this->parseargs($matches[2]);
			switch($matches[1]) {
				case 'initPicture':
					$this->ASinitPicture($argarr);
					break;
				case 'setBorder':
					$this->border = $argarr;
					break;
				case 'axes':
					$this->ASaxes($argarr);
					break;
				case 'line':
					$this->ASline($argarr);
					break;
				case 'path':
					$this->ASpath($argarr);
					break;
				case 'circle':
					$this->AScircle($argarr);
					break;
				case 'ellipse':
					$this->ASellipse($argarr);
					break;
				case 'rect':
					$this->ASrect($argarr);
					break;
				case 'text':
					$this->AStext($argarr);
					break;
				case 'textabs':
					$this->AStextAbs($argarr);
					break;
				case 'dot':
					$this->ASdot2($argarr);
					break;
				case 'plot':
					$this->ASplot($argarr);
					break;
				case 'slopefield':
					$this->ASslopefield($argarr);
					break;
				case 'arc':
					$this->ASarc($argarr);
					break;
				case 'sector':
					$this->ASsector($argarr);
					break;
				case 'arrowhead':
					$this->ASarrowhead($argarr[0],$argarr[1]);
					break;
			}
		}
	}
}

function addcolor(&$origcolor) {
	$color = $origcolor;
	if (substr($color,0,5)=='trans') {
		$alpha = 90;
		$color = substr($color,5);
	} else {
		$alpha = 0;
	}
	if (strlen($color)>0 && $color[0]=='#') {
		$r = hexdec(substr($color,1,2));
		$g = hexdec(substr($color,3,2));
		$b = hexdec(substr($color,5,2));
		$this->colors[$origcolor] = imagecolorallocatealpha($this->img, $r, $g, $b, $alpha);
	} else if (isset($this->extendedcolors[strtolower($color)])) {
        $cd = $this->extendedcolors[strtolower($color)];
        $this->colors[$origcolor] = imagecolorallocatealpha($this->img, $cd[0], $cd[1], $cd[2], $alpha);
    } else {
        $origcolor = 'black';
    }
}
function ASsetdash() {
	if (!$this->isinit) {$this->ASinitPicture();}
	if (func_num_args()>0) {
		$dash = func_get_arg(0);
		$this->curdash = $dash;
	} else {
		$dash = $this->curdash;
	}
	if ($dash=='none' || !preg_match('/\d/',$dash) || $this->stroke=='none') {
		$this->isdashed = false;
	} else {
		$dash = preg_replace('/\s+/',',',$dash);
		$darr = explode(',',$dash);
		$style = array();
		$alt = 0;
		$doagain = count($darr)%2;  //do twice if odd number
		while ($doagain>-1) {
			for ($i=0;$i<count($darr);$i++) {
				if ($alt==0) {
					$color = $this->stroke;
				} else {
					$color = 'white';
				}
				$style = array_pad($style,count($style)+$darr[$i],$this->colors[$color]);
				$alt = 1-$alt;
			}
			$doagain--;
		}
        if (!empty($style)) {
            imagesetstyle($this->img,$style);
            $this->isdashed = true;
        } else {
            $this->isdashed = false;
        }
		
	}
}
function AStext($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$pos = '';  $angle = 0;
	if (func_num_args()>1) {
		$p = $this->pt2arr($arg);
		$st = func_get_arg(1);
		if (func_num_args()>2) {
			$pos = func_get_arg(2);
		}
		if (func_num_args()>3) {
			$angle = floatval(func_get_arg(3));
		}
	} else {
		$p = $this->pt2arr($arg[0]);
		$st = $arg[1];
		if (isset($arg[2])) {
			$pos = $arg[2];
		}
		if (isset($arg[3])) {
			$angle = floatval($arg[3]);
		}
	}
	$this->AStextInternal($p,$st,$pos,$angle);
}
function AStextAbs($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$pos = '';  $angle = 0;
	if (func_num_args()>1) {
		$pt = $arg;
		$st = func_get_arg(1);
		if (func_num_args()>2) {
			$pos = func_get_arg(2);
		}
		if (func_num_args()>3) {
			$angle = floatval(func_get_arg(3));
		}
	} else {
		$pt = $arg[0];
		$st = $arg[1];
		if (isset($arg[2])) {
			$pos = $arg[2];
		}
		if (isset($arg[3])) {
			$angle = floatval($arg[3]);
		}
	}
	$pt = str_replace(array('[',']'),'',$pt);
	$pt = explode(',',$pt);
	$pt[1] = $this->height - $pt[1];
	$this->AStextInternal($pt,$st,$pos,$angle);
}
function AStextInternal($p,$st,$pos,$angle) {
	/*if (func_num_args()>1) {
		$p = $this->pt2arr($arg);
		$st = func_get_arg(1);
		if (func_num_args()>2) {
			$pos = func_get_arg(2);
		}
		if (func_num_args()>3) {
			$angle = func_get_arg(3);
		}
	} else {
		$p = $this->pt2arr($arg[0]);
		$st = $arg[1];
		if (isset($arg[2])) {
			$pos = $arg[2];
		}
		if (isset($arg[3])) {
			$angle = $arg[3];
		}
	}*/
	/*else {
		if (preg_match('/\s*\[(.*?)\]\s*,\s*[\'"](.*?)[\'"]\s*,([^,]*)/',$arg,$m)) {
			$p = $this->pt2arr($m[1]);
			$st = $m[2];
			$pos = trim(str_replace(array('"',"'"),'',$m[3]));
		} else {
			$arg = explode(',',$arg);
			$p = $this->pt2arr($arg[0].','.$arg[1]);
			$st = str_replace(array('"',"'"),'',$arg[2]);
		}
	}*/

	if ($this->usettf) {
		$bb = imagettfbbox($this->fontsize,$angle,$this->fontfile,$st);

		$bbw = $bb[4]-$bb[0];
		$bbh = -1*($bb[5]-$bb[1]);

		$p[0] = $p[0] - .5*($bbw);
		$p[1] = $p[1] + .5*($bbh);

		if ($pos=='above' || $pos=='aboveright' || $pos=='aboveleft') {
			$p[1] = $p[1] - .5*(abs($bbh)) - $this->fontsize/2 - 2;
		}
		if ($pos=='below' || $pos=='belowright' || $pos=='belowleft') {
			$p[1] = $p[1] + .5*(abs($bbh)) + $this->fontsize/2 + 2;
		}
		if ($pos=='left' || $pos=='aboveleft' || $pos=='belowleft') {
			$p[0] = $p[0] - .5*(abs($bbw)) -$this->fontsize/2;
		}
		if ($pos=='right' || $pos=='aboveright' || $pos=='belowright') {
			$p[0] = $p[0] + .5*(abs($bbw)) +$this->fontsize/2;
		}
		if ($this->fontfill != '') {
			$color = $this->fontfill;
		} else {
			$color = $this->stroke;
		}
		if ($this->fontbackground != '' && $this->fontbackground != 'none') {
			$minX = min(array($bb[0],$bb[2],$bb[4],$bb[6]));
			$maxX = max(array($bb[0],$bb[2],$bb[4],$bb[6]));
			$minY = min(array($bb[1],$bb[3],$bb[5],$bb[7]));
			$maxY = max(array($bb[1],$bb[3],$bb[5],$bb[7]));
			imagefilledrectangle($this->img, (int) round($p[0]+$minX-2), (int) round($p[1]+$minY-1), (int) round($p[0]+$maxX+2), (int) round($p[1]+$maxY+1), $this->colors[$this->fontbackground]);
		}
		imagettftext($this->img,$this->fontsize,$angle, (int) round($p[0]), (int) round($p[1]),$this->colors[$color],$this->fontfile,$st);
	} else {
		if ($this->fontsize<9) {
			$fs = 1;
		} else if ($this->fontsize<13) {
			$fs = 2;
		} else {
			$fs = 4;
		}
		if ($angle==90 || $angle==270) {
			$bb = array(imagefontheight($fs),imagefontwidth($fs)*strlen($st));
		} else {
			$bb = array(imagefontwidth($fs)*strlen($st),imagefontheight($fs));
		}
		$p[0] = $p[0] - .5*$bb[0];
		if ($angle==90 || $angle==270) {
			$p[1] = $p[1] + .5*$bb[1];
		} else {
			$p[1] = $p[1] - .5*$bb[1];
		}
		if ($pos=='above' || $pos=='aboveright' || $pos=='aboveleft') {
			$p[1] = $p[1] - .5*$bb[1] - $fs*2;
		}
		if ($pos=='below' || $pos=='belowright' || $pos=='belowleft') {
			$p[1] = $p[1] + .5*$bb[1] + $fs*2;
		}
		if ($pos=='left' || $pos=='aboveleft' || $pos=='belowleft') {
			$p[0] = $p[0] - .5*$bb[0] - $fs*2;
		}
		if ($pos=='right' || $pos=='aboveright' || $pos=='belowright') {
			$p[0] = $p[0] + .5*$bb[0] + $fs*2;
		}
		if ($this->fontfill != '') {
			$color = $this->fontfill;
		} else {
			$color = $this->stroke;
		}
		if ($this->fontbackground != '' && $this->fontbackground != 'none') {
			imagefilledrectangle($this->img, (int) round($p[0]-2), (int) round($p[1]-2), (int) round($p[0]+$bb[0]+2), (int) round($p[1]+$bb[1]+2), $this->colors[$this->fontbackground]);
		}
		if ($angle==90 || $angle==270) {
			imagestringup($this->img,$fs,(int) round($p[0]), (int) round($p[1]),$st,$this->colors[$color]);
		} else {
			imagestring($this->img,$fs,(int) round($p[0]), (int) round($p[1]),$st,$this->colors[$color]);
		}
	}
}

function ASinitPicture($arg=array()) {

	//$arg = explode(',',$arg);
	if (isset($arg[0]) && $arg[0]!='') { $this->xmin = $this->evalifneeded($arg[0]);}
	if (isset($arg[1])) { $this->xmax = $this->evalifneeded($arg[1]);}
	if (isset($arg[2])) { $this->ymin = $this->evalifneeded($arg[2]);}
	if (isset($arg[3])) { $this->ymax = $this->evalifneeded($arg[3]);}

	if ($this->xmin == $this->xmax) {
		$this->xmax = $this->xmin + .000001;
	}
	if (!is_array($this->border)) {
		$this->border = array($this->border,$this->border,$this->border,$this->border);
	} else if (count($this->border)<4) {
		for ($i=count($this->border);$i<5;$i++) {
			if ($i==1) {
				$this->border[$i] = $this->border[0];
			} else {
				$this->border[$i] = $this->border[$i-2];
			}
		}
	}
	$this->xunitlength = ($this->width - $this->border[0] - $this->border[2])/($this->xmax - $this->xmin);
	if ($this->xunitlength<=0) {
		$this->xunitlength = 1;
	}
	$this->yunitlength = $this->xunitlength;
	if (!isset($arg[2])) { // no ymin
		$this->origin[0] = -$this->xmin*$this->xunitlength + $this->border[0];
		$this->origin[1] = $this->height / 2;
		$this->ymin = -($this->height - $this->border[1] - $this->border[3])/(2*$this->yunitlength);
		$this->ymax = -$this->ymin;
	} else {
		if (isset($arg[3])) {  // do have ymax too
			$this->yunitlength = ($this->height - $this->border[1] - $this->border[3])/($this->ymax - $this->ymin);
			if ($this->yunitlength<=0) {
				$this->yunitlength = 1;
			}
		} else {
			$this->ymax = ($this->height - $this->border[1] - $this->border[3])/($this->yunitlength) + $this->ymin;
		}
		$this->origin[0] = -$this->xmin*$this->xunitlength + $this->border[0];
		$this->origin[1] = -$this->ymin*$this->yunitlength + $this->border[1];
	}

	$this->winxmin = max($this->border[0] - 5,0);
	$this->winxmax = min($this->width - $this->border[2] + 5, $this->width);
	$this->winymin = max($this->border[3] -5,0);
	$this->winymax = min($this->height - $this->border[1] + 5 , $this->height);

	$this->isinit = true;
}
function ASaxes($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	//$arg = explode(',',$arg);
	$xscl = 0; $yscl = 0; $xgrid = 0; $ygrid = 0; $dolabels = false; $dogrid = false; $dosmallticks = false;
	$fqonlyx = false; $fqonlyy = false;
	$dox = true;
	$doy = true;
	if (!is_bool($arg[0])) {
		$xscl = $this->evalifneeded($arg[0]);
	} else {
		$dolabels = true;
	}
	if (count($arg)>1) {
		if (!is_bool($arg[1])) {
			$yscl = $this->evalifneeded($arg[1]);
		} else {
			$dogrid = true;
		}
	}
	if (count($arg)>2) {
		if ($arg[2]=='0' || $arg[2]=='null' || $arg[2]=='"null"') {
			$dolabels = false;
		} else {
			$dolabels = true;
		}
	}
	if (count($arg)>3) {
		if ($arg[3]=='0' || $arg[3]=='null' || $arg[3]=='"null"') {
			$dogrid = false;
		} else {
			$xgrid = $this->evalifneeded($arg[3]);
			$dogrid = true;
		}
	}
	if (count($arg)>4) {
		$ygrid = $this->evalifneeded($arg[4]);
	}
	if (count($arg)>5) {
		if ($arg[5]==='fq') {
			$fqonlyx = true;
		} else if ($arg[5]=='off' || $arg[5]=='0') {
			$dox = false;
		}
	}
	if (count($arg)>6) {
		if ($arg[6]==='fq') {
			$fqonlyy = true;
		} else if ($arg[6]=='off' || $arg[6]=='0') {
			$doy = false;
		}
	}
	if (count($arg)>7) {
		if ($arg[7]=='1' && $dogrid==true) {
			$dogrid = false;
			$dosmallticks = true;
		}
	}
	if ($xscl<0) {
		$xscl *= -1;
	}
	if ($yscl<0) {
		$yscl *= -1;
	}
	if ($xgrid<0) {
		$xgrid *= -1;
	}
	if ($ygrid<0) {
		$ygrid *= -1;
	}
	if ($xscl==0) {
		$xscl = $this->xunitlength;
	} else {
		$xscl *= $this->xunitlength;
	}
	if ($yscl==0) {
		$yscl = $this->yunitlength;
	} else {
		$yscl *= $this->yunitlength;
	}
	if (!$doy) {
		$this->fontsize = min($xscl/2,12);
	} else if (!$dox) {
		$this->fontsize = min($yscl/2,12);
	} else {
		$this->fontsize = min($xscl/2,$yscl/2,12);
	}
	$this->ticklength = max($this->fontsize/4,4);
	if ($this->usegd2) {
		imagesetthickness($this->img,1);
	}
	if ($xgrid==0) {
		$xgrid = $this->xunitlength;
	} else {
		$xgrid *= $this->xunitlength;
	}
	if ($ygrid==0) {
		$ygrid = $this->yunitlength;
	} else {
		$ygrid *= $this->yunitlength;
	}
	if (($this->winxmax - $this->winxmin)/$xgrid > $this->width) {
		$xgrid = ($this->winxmax - $this->winxmin);
	}
	if (($this->winymax - $this->winymin)/$ygrid > $this->height) {
		$ygrid = ($this->winymax - $this->winymin);
	}
	if (($this->winxmax - $this->winxmin)/$xscl > $this->width) {
		$xscl = ($this->winxmax - $this->winxmin);
	}
	if (($this->winymax - $this->winymin)/$yscl > $this->height) {
		$yscl = ($this->winymax - $this->winymin);
	}
	if ($dogrid) {
		$gc = $this->gridcolor;
		if ($dox && $xgrid>0) {
			for ($x=$this->origin[0]+($doy?$xgrid:0); $x<=$this->winxmax; $x += $xgrid) {
				if ($x>=$this->winxmin) {
					imageline($this->img, (int) round($x), (int) round($this->winymin), (int) round($x), (int) round(($fqonlyy?$this->height-$this->origin[1]:$this->winymax)), $this->colors[$gc]);
				}
			}
			if (!$fqonlyx) {
				for ($x=$this->origin[0]-$xgrid; $x>=$this->winxmin; $x -= $xgrid) {
					if ($x<=$this->winxmax) {
						imageline($this->img, (int) round($x), (int) round($this->winymin), (int) round($x), (int) round(($fqonlyy?$this->height-$this->origin[1]:$this->winymax)), $this->colors[$gc]);
					}
				}
			}
		}
		if ($doy && $ygrid>0) {
			if (!$fqonlyy) {
				for ($y=$this->height - $this->origin[1]+($dox?$ygrid:0); $y<=$this->winymax; $y += $ygrid) {
					if ($y>=$this->winymin) {
						imageline($this->img, (int) round(($fqonlyx?$this->origin[0]:$this->winxmin)), (int) round($y), (int) round($this->winxmax), (int) round($y), $this->colors[$gc]);
					}
				}
			}
			for ($y=$this->height - $this->origin[1]-$ygrid; $y>$this->winymin; $y -= $ygrid) {
				if ($y<=$this->winymax) {
					imageline($this->img, (int) round(($fqonlyx?$this->origin[0]:$this->winxmin)), (int) round($y), (int) round($this->winxmax), (int) round($y), $this->colors[$gc]);
				}
			}
		}
	} else if ($dosmallticks) {
		$ac = $this->axescolor;

		if ($dox && $xgrid>0) {
			for ($x=$this->origin[0]+($doy?$xgrid:0); $x<=$this->winxmax; $x += $xgrid) {
				if ($x>=$this->winxmin) {
					imageline($this->img, (int) round($x), (int) round($this->height-$this->origin[1]-.5*$this->ticklength), (int) round($x), (int) round($this->height-$this->origin[1]+.5*$this->ticklength), $this->colors[$ac]);
				}
			}
			for ($x=$this->origin[0]-$xgrid; $x>=$this->winxmin; $x -= $xgrid) {
				if ($x<=$this->winxmax) {
					imageline($this->img, (int) round($x), (int) round($this->height-$this->origin[1]-.5*$this->ticklength), (int) round($x), (int) round($this->height-$this->origin[1]+.5*$this->ticklength), $this->colors[$ac]);
				}
			}
		}
		if ($doy && $ygrid>0) {
			for ($y=$this->height - $this->origin[1]+($dox?$ygrid:0); $y<=$this->winymax; $y += $ygrid) {
				if ($y>=$this->winymin) {
					imageline($this->img, (int) round($this->origin[0]-.5*$this->ticklength), (int) round($y), (int) round($this->origin[0]+.5*$this->ticklength), (int) round($y), $this->colors[$ac]);
				}
			}
			for ($y=$this->height - $this->origin[1]-$ygrid; $y>$this->winymin; $y -= $ygrid) {
				if ($y<=$this->winymax) {
					imageline($this->img, (int) round($this->origin[0]-.5*$this->ticklength), (int) round($y), (int) round($this->origin[0]+.5*$this->ticklength), (int) round($y), $this->colors[$ac]);
				}
			}
		}
	}

	$ac = $this->axescolor;
	if ($doy && $yscl>0) {
		if ($this->origin[0]>=$this->winxmin && $this->origin[0]<=$this->winxmax) {
			imageline($this->img, (int) round($this->origin[0]), (int) round($this->winymin), (int) round($this->origin[0]), (int) round(($fqonlyy?$this->height-$this->origin[1]:$this->winymax)), $this->colors[$ac]);
			//ticks
			if (!$fqonlyy) {
				for ($y=$this->height - $this->origin[1]; $y<=$this->winymax; $y += $yscl) {
					if ($y>=$this->winymin) {
						imageline($this->img, (int) round($this->origin[0]-$this->ticklength), (int) round($y), (int) round($this->origin[0]+$this->ticklength), (int) round($y), $this->colors[$ac]);
					}
				}
			}
			for ($y=$this->height - $this->origin[1]-$yscl; $y>=$this->winymin; $y -= $yscl) {
				if ($y<=$this->winymax) {
					imageline($this->img, (int) round($this->origin[0]-$this->ticklength), (int) round($y), (int) round($this->origin[0]+$this->ticklength), (int) round($y), $this->colors[$ac]);
				}
			}
		}
	}
	if ($dox && $xscl>0) {
		if ($this->origin[1]>=$this->winymin && $this->origin[1]<=$this->winymax) {
			imageline($this->img, (int) round(($fqonlyx?$this->origin[0]:$this->winxmin)), (int) round($this->height-$this->origin[1]), (int) round($this->winxmax), (int) round($this->height-$this->origin[1]), $this->colors[$ac]);
			//ticks
			for ($x=$this->origin[0]; $x<=$this->winxmax; $x += $xscl) {
				if ($x>=$this->winxmin) {
					imageline($this->img, (int) round($x), (int) round($this->height- $this->origin[1] -$this->ticklength), (int) round($x), (int) round($this->height- $this->origin[1] +$this->ticklength), $this->colors[$ac]);
				}
			}
			if (!$fqonlyx) {
				for ($x=$this->origin[0]-$xscl; $x>=$this->winxmin; $x -= $xscl) {
					if ($x<=$this->winxmax) {
						imageline($this->img, (int) round($x), (int) round($this->height-$this->origin[1]-$this->ticklength), (int) round($x), (int) round($this->height-$this->origin[1]+$this->ticklength), $this->colors[$ac]);
					}
				}
			}
		}
	}

	if ($dolabels) {
		$ldx = $xscl/$this->xunitlength;
		$ldy = $yscl/$this->yunitlength;
		if ($this->xmin>0 || $this->xmax<0) {
			$lx = $this->xmin;
			$lyp = 'right';
		} else {
			$lx = 0;
			$lyp = 'left';
		}
		if ($this->ymin>0 || $this->ymax<0) {
			$ly = $this->ymin;
			$lxp = 'above';
		} else {
			$ly = 0;
			$lxp = 'below';
		}

		$backupstroke = $this->stroke;
		$this->stroke = 'black';
		if ($dox && $ldx>0) {
			for ($x=($doy?$ldx:0);$x<=$this->xmax; $x += $ldx) {
				if ($x>=$this->xmin) {
					$this->AStext("[$x,$ly]",$x,$lxp);
				}
			}
			if (!$fqonlyx) {
				for ($x=-$ldx;$this->xmin<=$x; $x -= $ldx) {
					if ($x<=$this->xmax) {
						$this->AStext("[$x,$ly]",$x,$lxp);
					}
				}
			}
		}
		if ($doy && $ldy>0) {
			for ($y=($dox?$ldy:0);$y<=$this->ymax; $y += $ldy) {
				if ($y>=$this->ymin) {
					$this->AStext("[$lx,$y]",$y,$lyp);
				}
			}
			if (!$fqonlyy) {
				for ($y=-$ldy;$this->ymin<=$y; $y -= $ldy) {
					if ($y<=$this->ymax) {
						$this->AStext("[$lx,$y]",$y,$lyp);
					}
				}
			}
		}
		$this->stroke = $backupstroke;
	}
	if ($this->usegd2) {
		imagesetthickness($this->img, (int) round($this->strokewidth > 0.5 ? $this->strokewidth : 1));
	}
}

function ASline($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	//$arg = explode('],[',$arg);
	if (count($arg)<2) { return;}
	$p = $this->pt2arr($arg[0]);
	$q = $this->pt2arr($arg[1]);
	if ($this->isdashed) {
		imageline($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($q[0]), (int) round($q[1]), IMG_COLOR_STYLED);
	} else {
		$color = $this->stroke;
		imageline($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($q[0]), (int) round($q[1]), $this->colors[$color]);
	}
	if ($this->marker=='dot' || $this->marker=='arrowdot') {
		$this->ASdot($p,8);
		$this->ASdot($q,8);
	}
	if ($this->marker=='arrow' || $this->marker=='arrowdot') {
		$this->ASarrowhead($arg[0],$arg[1]);
	}
}
function ASpath($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$arg = str_replace(array('[',']'),'',$arg[0]);
	$arg = explode(',',$arg);
	if (count($arg)<4) { return;}

	if (count($arg)>5 && $this->fill != 'none') {
		$pt = array();
		for ($i=0;$i<count($arg);$i++) {
			if ($i%2==0) { //x coord
				$pt[$i] = (int) round($this->evalifneeded($arg[$i])*$this->xunitlength + $this->origin[0]);
			} else {
				$pt[$i] = (int) round($this->height - $this->evalifneeded($arg[$i])*$this->yunitlength - $this->origin[1]);
			}
		}
		$color = $this->fill;
        if ($this->isphp8) {
		    imagefilledpolygon($this->img,$pt,$this->colors[$color]);
        } else {
            imagefilledpolygon($this->img,$pt,count($pt)/2,$this->colors[$color]);
        }
	}
	if ($this->stroke != 'none') {
		for ($i=0; $i<count($arg)-2; $i += 2) {
			//$this->ASline("[{$arg[$i]},{$arg[$i+1]}],[{$arg[$i+2]},{$arg[$i+3]}]");
			$this->ASline(array("[{$arg[$i]},{$arg[$i+1]}]","[{$arg[$i+2]},{$arg[$i+3]}]"));
		}
	}
}
function AScircle($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	//$arg = explode(',',$arg);
	//$this->ASellipse("[{$arg[0]},{$arg[1]}],{$arg[2]},{$arg[2]}");
	$this->ASellipse(array($arg[0],$arg[1],$arg[1]));
}
function ASellipse($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	//$arg = explode(',',$arg);
	//$p = $this->pt2arr($arg[0].','.$arg[1]);
	$p = $this->pt2arr($arg[0]);
	$arg[1] = $this->evalifneeded($arg[1])*$this->xunitlength;
	$arg[2] = $this->evalifneeded($arg[2])*$this->yunitlength;
	if ($this->fill != 'none') {
		$color = $this->fill;
		if ($this->usegd2) {
			imagefilledellipse($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($arg[1]*2), (int) round($arg[2]*2), $this->colors[$color]);
		}
	}
	if ($this->isdashed) {
		imageellipse($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($arg[1]*2), (int) round($arg[2]*2), IMG_COLOR_STYLED);
	} else {
		$color = $this->stroke;
		imageellipse($this->img, (int) round($p[0]), (int) round($p[1]), (int) round($arg[1]*2), (int) round($arg[2]*2), $this->colors[$color]);
	}
}
function ASrect($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	//$arg = explode(',',$arg);
	$p = $this->pt2arr($arg[0]);
	$q = $this->pt2arr($arg[1]);
	$sx = min($p[0],$q[0]); $bx = max($p[0],$q[0]);
	$sy = min($p[1],$q[1]); $by = max($p[1],$q[1]);
	if ($this->fill != 'none') {
		$color = $this->fill;
		imagefilledrectangle($this->img, (int) round($sx), (int) round($sy), (int) round($bx), (int) round($by), $this->colors[$color]);
	}

	if ($this->isdashed) {
		imagerectangle($this->img, (int) round($sx), (int) round($sy), (int) round($bx), (int) round($by), IMG_COLOR_STYLED);
	} else {
		$color = $this->stroke;
		if ($color != 'none') {
			imagerectangle($this->img, (int) round($sx), (int) round($sy), (int) round($bx), (int) round($by), $this->colors[$color]);
		}
	}
}
function ASsector($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	list($cx,$cy) = $this->pt2arr($arg[0]);
	$r = $this->evalifneeded($arg[1]);
	$origstart = $this->evalifneeded($arg[2]);
	$origend = $this->evalifneeded($arg[3]);

	if ($origend < $origstart) {
		$startt = 2*M_PI - $origstart;
		$endt = 2*M_PI - $origend;
	} else {
		$startt = 2*M_PI - $origend;
		$endt = 2*M_PI - $origstart;
	}

	$xdiam = 2*$r*$this->xunitlength;
	$ydiam = 2*$r*$this->yunitlength;

	if ($this->fill != 'none') {
		$color = $this->fill;
		imagefilledarc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), $this->colors[$color], IMG_ARC_PIE);
	}
	$color = $this->stroke;
	if ($this->isdashed) {
		imagefilledarc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), IMG_COLOR_STYLED, IMG_ARC_PIE|IMG_ARC_NOFILL|IMG_ARC_EDGED);

	} else {
		if ($color != 'none') {
			imagefilledarc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), $this->colors[$color], IMG_ARC_PIE|IMG_ARC_NOFILL|IMG_ARC_EDGED);
		}
	}

}
function ASarc($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$p = $this->pt2arr($arg[0]);
	$q = $this->pt2arr($arg[1]);
	$r = $this->evalifneeded($arg[2]);
	$po[0] = ($p[0]-$this->origin[0])/$this->xunitlength;
	$qo[0] = ($q[0]-$this->origin[0])/$this->xunitlength;
	$po[1] = ($this->height - $p[1] - $this->origin[1])/$this->yunitlength;
	$qo[1] = ($this->height - $q[1] - $this->origin[1])/$this->yunitlength;
	$t[0] = ($po[0]+$qo[0])/2;
	$t[1] = ($po[1]+$qo[1])/2;

	$m = sqrt(($po[0]-$qo[0])*($po[0]-$qo[0]) + ($po[1]-$qo[1])*($po[1]-$qo[1]));
	$cxo = $t[0] + sqrt($r*$r-($m*$m/4))*($po[1]-$qo[1])/$m;
	$cyo = $t[1] - sqrt($r*$r-($m*$m/4))*($po[0]-$qo[0])/$m;
	$cx = round($cxo*$this->xunitlength + $this->origin[0]);
	$cy = round($this->height - $cyo*$this->yunitlength - $this->origin[1]);

	$endt = atan2(-$po[1]+$cyo,$po[0]-$cxo);
	$startt = atan2(-$qo[1]+$cyo,$qo[0]-$cxo);
	$xdiam = 2*$r*$this->xunitlength;
	$ydiam = 2*$r*$this->yunitlength;
	if ($this->fill != 'none') {
		$color = $this->fill;
		imagefilledarc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), $this->colors[$color], IMG_ARC_PIE);
	}

	if ($this->isdashed) {
		imagearc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), IMG_COLOR_STYLED);
	} else {
		$color = $this->stroke;
		if ($color != 'none') {
			imagearc($this->img, (int) round($cx), (int) round($cy), (int) round($xdiam), (int) round($ydiam), (int) round($startt*180/M_PI), (int) round($endt*180/M_PI), $this->colors[$color]);
		}
	}
}

function ASdot($pt,$r) {
	if (!$this->isinit) {$this->ASinitPicture();}
	if ($this->markerfill!='none') {
		$color = $this->markerfill;
		if ($this->usegd2) {
			imagefilledellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($r), (int) round($r), $this->colors[$color]);
		} else {
			imagefilledpolygon($this->img,array($pt[0]-$r,$pt[1],$pt[0],$pt[1]+$r,$pt[0]+$r,$pt[1],$pt[0],$pt[1]-$r),4,$this->colors[$color]);
		}
	}
	$color = $this->stroke;
	imageellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($r), (int) round($r), $this->colors[$color]);
}
function ASdot2($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$pt = $this->pt2arr($arg[0]);
	$color = $this->stroke;
	if (!isset($arg[1]) || $arg[1]!='open') {
		if ($this->usegd2) {
			imagefilledellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->colors[$color]);
		} else {
			$r = $this->dotradius;
			imagefilledpolygon($this->img,array($pt[0]-$r,$pt[1],$pt[0],$pt[1]+$r,$pt[0]+$r,$pt[1],$pt[0],$pt[1]-$r),4,$this->colors[$color]);
		}
	} else {
		if ($this->usegd2) {
			imagefilledellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->colors['white']);
		} else {
			$r = $this->dotradius;
			imagefilledpolygon($this->img,array($pt[0]-$r,$pt[1],$pt[0],$pt[1]+$r,$pt[0]+$r,$pt[1],$pt[0],$pt[1]-$r),4,$this->colors['white']);
		}
		imageellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->colors[$color]);
	}
	if (isset($arg[2])) {
		if (isset($arg[3])) {
			$this->AStext(array($arg[0],$arg[2],$arg[3]));
		} else {
			$this->AStext(array($arg[0],$arg[2]));
		}
	}
	/*
	if (preg_match('/\s*\[(.*?)\]\s*,\s*[\'"](.*?)[\'"]\s*(.*)/',$arg,$m)) {
		$pt = $this->pt2arr($m[1]);
		if ($m[2]=='closed') {
			imagefilledellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->$color);
		} else {
			imageellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->$color);
		}

		if (strlen($m[3])>0) {
			$this->AStext('['.$m[1].']'.$m[3]);
		}
	} else if (preg_match('/\s*\[(.*?)\]\s*,\s*,\s*(.*)/',$arg,$m)) {
		$pt = $this->pt2arr($m[1]);
		imageellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->$color);
		if (strlen($m[3])>0) {
			$this->AStext('['.$m[1].']'.$m[2]);
		}
	} else {
		$pt = $this->pt2arr($arg);
		imageellipse($this->img, (int) round($pt[0]), (int) round($pt[1]), (int) round($this->dotradius), (int) round($this->dotradius), $this->$color);
	}
	*/
}
//function ASarrowhead($v,$w) {
function ASarrowhead($p,$q) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$v = $this->pt2arr($p);
	$w = $this->pt2arr($q);
	$u = array($w[0]-$v[0],$w[1]-$v[1]);
	$d = sqrt($u[0]*$u[0]+$u[1]*$u[1]);
    $rs = $this->arrowrelsize;
    $off = $this->arrowoffset;
	if ($d > 0.00000001) {
		$u = array($u[0]/$d, $u[1]/$d);
		$up = array(-$u[1],$u[0]);
		$arr = array(
			(int) round($w[0]-$rs*15*$u[0]-$rs*4*$up[0]-$off*$u[0]),
			(int) round($w[1]-$rs*15*$u[1]-$rs*4*$up[1]-$off*$u[1]),
			(int) round($w[0]-1*$u[0]-$off*$u[0]),
			(int) round($w[1]-1*$u[1]-$off*$u[1]),
			(int) round($w[0]-$rs*15*$u[0]+$rs*4*$up[0]-$off*$u[0]),
			(int) round($w[1]-$rs*15*$u[1]+$rs*4*$up[1]-$off*$u[1]));
		$color = $this->stroke;
        if ($this->isphp8) {
		    imagefilledpolygon($this->img,$arr,$this->colors[$color]);
        } else {
		    imagefilledpolygon($this->img,$arr,count($arr)/2,$this->colors[$color]);
        }
	}
}
function ASslopefield($arg) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$func = $arg[0];
	if (count($arg)>1) {
		$dx = $this->evalifneeded($arg[1]);
		if ($dx*1==0) { $dx = 1;}
	} else {
		$dx = 1;
	}
	if (count($arg)>2) {
		$dy = $this->evalifneeded($arg[2]);
		if ($dy*1==0) { $dy = 1;}
	} else {
		$dy = 1;
	}
	/*preg_match_all('/[a-zA-Z]+/',$func,$matches,PREG_PATTERN_ORDER);
	$okfunc = array('sin','cos','tan','sec','csc','cot','arcsin','arccos','arctan','x','y','log','ln','e','pi','abs','sqrt','safepow');
	foreach ($matches[0] as $m) {
		if (!in_array($m,$okfunc)) { echo "$m"; return;}
	}
	*/
	$efunc = makeMathFunction($func, "x,y");
    if ($efunc === false) { return; }
	$dz = sqrt($dx*$dx + $dy*$dy)/6;
	$x_min = $dx*ceil($this->xmin/$dx);
	$y_min = $dy*ceil($this->ymin/$dy);
	for ($x = $x_min; $x<= $this->xmax; $x+= $dx) {
		for ($y = $y_min; $y<= $this->ymax; $y+= $dy) {
			$gxy = @$efunc(['x'=>$x, 'y'=>$y]);
			if ($gxy!=null && !is_infinite($gxy) && !is_nan($gxy)) {
				if ($gxy===false) {
					$u = 0; $v = $dz;
				} else {
					$u = $dz/sqrt(1+$gxy*$gxy);
					$v = $gxy*$u;
				}
				$this->ASline(array("[$x-1*$u,$y-1*$v]","[$x+$u,$y+$v]"));
			}
		}
	}
}

function ASplot($function) {
	if (!$this->isinit) {$this->ASinitPicture();}
	$funcstr = implode(',',$function);
	/*  safety check now in mathphp
	preg_match_all('/[a-zA-Z]+/',$funcstr,$matches,PREG_PATTERN_ORDER);
	$okfunc = array('sin','cos','tan','sec','csc','cot','arcsin','arccos','arctan','x','t','log','ln','e','pi','abs','sqrt','safepow');
	foreach ($matches[0] as $m) {
		if (!in_array($m,$okfunc)) { echo "$m"; return;}
	} //do safety check, as this will be eval'ed
	*/
	//$function = explode(',',str_replace(array('"','\'',';'),'',$function));
	$function = str_replace(array('"','\'',';'),'',$function);
	if (strpos($function[0],'[')===0) {
		$funcp = explode(',',$function[0]);
		$isparametric = true;
		$xfunc = str_replace("[","",$funcp[0]);
		$exfunc = makeMathFunction($xfunc, "t");
		$yfunc = str_replace("]","",$funcp[1]);
		$eyfunc = makeMathFunction($yfunc, "t");
        if ($exfunc === false || $eyfunc === false) {
            return;
        }
	} else {
		$isparametric = false;
		$efunc = makeMathFunction($function[0], "x");
        if ($efunc === false) {
            return;
        }
	}
	$avoid = array();
	if (isset($function[1]) && $function[1]!='' && $function[1]!='null') {
		$xmin = $this->evalifneeded($function[1]);
	} else {
		$xmin = $this->xmin - min($this->border[0],5)/$this->xunitlength;
	}
	if (isset($function[2]) && $function[2]!='' && $function[2]!='null') {
		$xmaxarr = explode('!',$function[2]);
		$xmax = $this->evalifneeded($xmaxarr[0]);
		$avoid = array_slice($xmaxarr,1);
	} else {
		$xmax = $this->xmax + min($this->border[2],5)/$this->xunitlength;
	}
	$xmin += ($xmax - $xmin)/100000; //avoid divide by zero errors
	if (isset($function[3]) && $function[3]!='' && $function[3]!='null') {
		$dx = ($xmax - $xmin)/($function[3]-1);
		$stopat = $function[3];
	} else {
		$dx = ($xmax - $xmin)/100;
		$stopat = 101;
	}
	$yymax = $this->ymax + $this->border[3]/$this->yunitlength;
	$yymin = $this->ymin - $this->border[1]/$this->yunitlength;
	$px = null;
	$py = null;
	$lasty = 0;
	$lastl = 0;

	for ($i = 0; $i<$stopat;$i++) {

		if ($isparametric) {
			$t = $xmin + $dx*$i;
			if (in_array($t,$avoid)) { continue;}
			$x = $exfunc(['t'=>$t]);
			$y = $eyfunc(['t'=>$t]);
			if (is_infinite($x) || is_infinite($y) || is_nan($x) || is_nan($y)) { continue; }
		} else {
			$x = $xmin + $dx*$i;
			if (in_array($x,$avoid)) { continue;}
			$y = $efunc(['x'=>$x]);
			if (is_infinite($y) || is_nan($y)) { continue;}
		}
		if ($i<2 || $i==$stopat-2) {
			$fx[$i] = $x;
			$fy[$i] = $y;
		}
		$lastx = $x;
		/*if (abs($y-$lasty) > ($this->ymax-$this->ymin)) {
			if ($lastl > 1) { $lastl = 0; }//break path
			$lasty = $y;
		} else {

			$lasty = $y;
			if ($lastl > 0) {
				$this->ASline(array("[$px,$py]","[$x,$y]"));
			}
			$px = $x;
			$py = $y;

			$lastl++;
		}*/
		if ($py===null) { //starting line

		} else if ($y>$yymax || $y<$yymin) { //going or still out of bounds
			if ($py <= $yymax && $py >= $yymin) { //going out
				if ($yymax-$py < .5*($yymax-$yymin)) { //closer to top
					$iy = $yymax;
					//if jumping from top of graph to bottom, change value
					//for interpolation purposes
					if ($y<$yymin) { $y = $yymax+.5*($yymax-$yymin);}
				} else { //going down
					$iy = $yymin;
					if ($y>$yymax) { $y = $yymin-.5*($yymax-$yymin);}
				}
				$ix = ($x-$px)*($iy - $py)/($y-$py) + $px;
				$this->ASline(array("[$px,$py]","[$ix,$iy]"));
			} else { //still out

			}
		} else if ($py>$yymax || $py<$yymin) { //coming or staying in bounds?
			if ($y <= $yymax && $y >= $yymin) { //coming in
				if ($yymax-$y < .5*($yymax-$yymin)) { //closer to top
					$iy = $yymax;
					if ($py<$yymin) { $py = $yymax+.5*($ymax-$ymin);}
				} else { //coming from bottom
					$iy = $yymin;
					if ($py>$yymax) { $py = $yymin-.5*($ymax-$ymin);}
				}
				$ix = ($x-$px)*($iy - $py)/($y-$py) + $px;
				$this->ASline(array("[$ix,$iy]","[$x,$y]"));
			} else { //still out

			}
		} else { //all in
			$this->ASline(array("[$px,$py]","[$x,$y]"));
		}
		$px = $x;
		$py = $y;
	}
	if (isset($function[5]) && $function[5]!='' && $function[5]!='null') {
		if ($function[5]==1) {
			//need pt2arr for xunit adjust
			$this->ASarrowhead("{$fx[1]},{$fy[1]}","{$fx[0]},{$fy[0]}");
		} else if ($function[5]==2) {
			$this->ASdot2(array("[{$fx[0]},{$fy[0]}]","open"));
		} else if ($function[5]==3) {
			$this->ASdot2(array("[{$fx[0]},{$fy[0]}]","closed"));
		}
	}
	if (isset($function[6]) && $function[6]!='' && $function[6]!='null') {
		if ($function[6]==1) {
			$this->ASarrowhead("{$fx[$stopat-2]},{$fy[$stopat-2]}","$x,$y");
		} else if ($function[6]==2) {
			$this->ASdot2(array("[$x,$y]","open"));
		} else if ($function[6]==3) {
			$this->ASdot2(array("[$x,$y]","closed"));
		}
	}
}
function pt2arr($pt) {
	$pt = str_replace(array('[',']'),'',$pt);
	$pt = explode(',',$pt);
	//$pt[0] = round($this->evalifneeded($pt[0])*$this->xunitlength + $this->origin[0]);
	//$pt[1] = round($this->height - $this->evalifneeded($pt[1])*$this->yunitlength - $this->origin[1]);
	$pt[0] =$this->evalifneeded($pt[0])*$this->xunitlength + $this->origin[0];
	$pt[1] = $this->height - $this->evalifneeded($pt[1])*$this->yunitlength - $this->origin[1];
	return $pt;
}
function parseargs($str) {
	$lp = 0; $qd = 0; $bd=0; $args = array();
	for($i=0; $i<strlen($str); $i++) {
		if ($str[$i]=='[' && $qd==0) { $bd++;}
		if ($str[$i]==']' && $qd==0) { $bd--;}
		if ($str[$i]=='"' || $str[$i]=='\'') {
			$qd = 1-$qd;
		}
		if ($str[$i]==',' && $qd==0 && $bd==0) {
			if ($i>$lp) {
				$args[] = substr($str,$lp,$i-$lp);
			} else {
				$args[] = '';
			}
			$lp = $i+1;
		}
	}
	$args[] = substr($str,$lp);
	for ($i=0;$i<count($args);$i++) {
		$args[$i] = trim(str_replace(array('"','\''),'',$args[$i]));
	}
	return $args;
}
function outputimage() {
	if (func_num_args()>0) {
		$filename = func_get_arg(0);
		imagepng($this->img,$filename,8);
	} else {
		imagepng($this->img,null,8);
	}
}
function evalifneeded($str) {
	//$str = str_replace('pi','3.141593', $str);
	if (is_numeric($str)) {
		return $str;
	} else {
        $val = evalMathParser($str);
        if (is_nan($val)) { return 0;}
        return $val;
    }/*else if (trim($str)=='' || preg_match('/[^\(\)\d+\-\/\*\.]/',$str)) {
		return 0; //return a value to prevent errors
	} else {
		try {
			eval("\$ret = $str;");
		} catch (Throwable $thrownerror) {
			return 1;
		}
		return $ret;
	}*/
}
} //end AStoIMG class

?>
