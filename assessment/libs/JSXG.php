<?php
// JSXGraph Integration functions, Version 1.0, Fall 2016
// Grant Sander


global $allowedmacros;
//array_push($allowedmacros, "loadJSX", "JSXG_createAxes", "JSXG_addFunction", "JSXG_addParametric", "JSXG_addText", "JSXG_addSlider", "JSXG_addArrow");
$allowedmacros[] = "loadJSX";
$allowedmacros[] = "JSXG_createAxes";

$allowedmacros[] = "JSXG_createPolarAxes";
$allowedmacros[] = "JSXG_addPolar";

$allowedmacros[] = "JSXG_addFunction";
$allowedmacros[] = "JSXG_addParametric";
$allowedmacros[] = "JSXG_addText";
$allowedmacros[] = "JSXG_addSlider";
$allowedmacros[] = "JSXG_addTangent";
$allowedmacros[] = "JSXG_setAttribute";

// Geometry macros
$allowedmacros[] = "JSXG_createBlankBoard";
$allowedmacros[] = "JSXG_addArrow";
$allowedmacros[] = "JSXG_addPoint";
$allowedmacros[] = "JSXG_addSegment";
$allowedmacros[] = "JSXG_addLine";
$allowedmacros[] = "JSXG_addRay";
$allowedmacros[] = "JSXG_addAngle";
$allowedmacros[] = "JSXG_addCircle";
$allowedmacros[] = "JSXG_addPolygon";
$allowedmacros[] = "JSXG_addGlider";

####### BASIC FUNCTION THAT JUST LOADS THE JSXGRAPH SCRIPT
function loadJSX() {
    return getJSXscript();
}
function getJSXscript () {
  if (isset($GLOBALS['assessUIver']) && $GLOBALS['assessUIver'] > 1) {
		return '<script type="text/javascript" src="//cdn.jsdelivr.net/npm/jsxgraph@1.5.0/distrib/jsxgraphcore.js"></script>';
  } else {
		return '<script type="text/javascript">if (typeof JXG === "undefined" && typeof JXGscriptloaded === "undefined") {
			var jsxgloadscript = document.createElement("script");
      jsxgloadscript.src = "//cdn.jsdelivr.net/npm/jsxgraph@1.5.0/distrib/jsxgraphcore.js";
			document.getElementsByTagName("head")[0].appendChild(jsxgloadscript);
      JXGscriptloaded = true;
		}</script>';
  }
}

####################################
#### JSXG_setUpBoard ###############
# Set up a board. Auxillary functions
function JSXG_setUpBoard($label, $width=350, $height=350, $centered=true){
  $cntrd = $centered===true ? "margin:auto;" : "";
  $ratio = 100*($height/$width);
  // Start output string by getting script
  $out = getJSXscript();
  // make board
  $out .= "<div class='jxgboardwrapper' style='max-width:{$width}px; max-height:{$height}px; {$cntrd}'>";
  $out .= "<div id='jxgboard_{$label}' style='background-color:#FFF; width:100%; height:0px; padding-bottom:{$ratio}%;'></div>";
  $out .= "</div>";
  // Start script
  $out .= "<script type='text/javascript'>";
  // We build construction function inline, push function to initstack to load async
  $out .= "function makeBoard{$label}(){
           try{";
  $out .= "JXG.Options.text.fontSize = 16;";
  $out .= 'JXG.Options.text.cssDefaultStyle = "font-family:Serif;";';
  $out .= 'JXG.Options.axis.lastArrow.size = 5;';
   // This is where new content gets inserted
   $out .= "/*INSERTHERE*/";
   // End of construction function. Push it to initstack
   $out .= "} catch(err){console.log(err);}
          }
					initstack.push(makeBoard{$label});
          </script>"; // End of script

   return $out;

}

#####################################
######### JSXG_createBoard ##########
# creates a set of axes, and a board to construct on.
function JSXG_createAxes($label, $ops=array()){
    // Add some default values
   $width = isset($ops['size'][0]) ? $ops['size'][0] : 350; // board width
   $height = isset($ops['size'][1]) ? $ops['size'][1] : 350; // board height
   //set the min and max x-values if provided, else default to [-5, 5]
   $xmin = isset($ops['bounds'][0]) ? $ops['bounds'][0] : -5;
   $xmax = isset($ops['bounds'][1]) ? $ops['bounds'][1] : 5;
   $ymin = isset($ops['bounds'][2]) ? $ops['bounds'][2] : -5;
   $ymax = isset($ops['bounds'][3]) ? $ops['bounds'][3] : 5;

   $minorTicksX = isset($ops['minorTicks'][0]) ? $ops['minorTicks'][0] : 1;
   $minorTicksY = isset($ops['minorTicks'][1]) ? $ops['minorTicks'][1] : 1;
   $ticksDistanceX = isset($ops['ticksDistance'][0]) ? $ops['ticksDistance'][0] : floor((($xmax)-($xmin))/8);
   $ticksDistanceY = isset($ops['ticksDistance'][1]) ? $ops['ticksDistance'][1] : floor((($ymax)-($ymin))/8);

   $navBar = (isset($ops['controls']) && in_array('nav-bar', $ops['controls'])) ? "true" : "false";
   $zoom = (isset($ops['controls']) && in_array('zoom', $ops['controls'])) ? "true" : "false";
   $pan = (isset($ops['controls']) && in_array('no-pan', $ops['controls'])) ? "false" : "true";
   $centered = isset($ops['centered']) ? false : true;

   $useMathJax = (isset($ops['axisLabel']) && (strpos($ops['axisLabel'][0], "`")>-1 || strpos($ops['axisLabel'][1], "`")>-1)) ? "true" : "false";

    // Start output
   $out = "JXG.Options.layer = {numlayers: 20, text: 9, point: 9, glider: 9, arc: 8, line: 7, circle: 6,
             curve: 5, turtle: 5, polygon: 3, sector: 3, angle: 3, integral: 3, axis: 3, ticks: 2, grid: 1, image: 0, trace: 0};";
   //$out .= "JXG.Options.text.useMathJax = true;";
   // Create the board
   $defaultAxis = !empty($ops['tickDistance']) ? "false" : "true";
   $out .= "window.board_{$label} = JXG.JSXGraph.initBoard('jxgboard_{$label}', {
             boundingbox: [{$xmin}, {$ymax}, {$xmax}, {$ymin}],
             axis: false,
             showCopyright: false,
             showNavigation: {$navBar},
             zoom: {
              enabled: {$zoom},
              factorX: 1.25,
              factorY: 1.25,
              wheel: {$zoom},
              needshift: false,
              eps: 0.1
            },
            pan: {
              enabled: {$pan},
              needshift: false
            }
           });";

   $out .= "var xTicks{$label}, yTicks{$label}, bb{$label};";
   // x-axis
   $out .= "var xaxis{$label} = board_{$label}.create('axis', [[0,0], [1,0]], {
               strokeColor:'black',
               strokeWidth: 2,
               highlight:false,
               name:'" . (isset($ops['axisLabel'][0]) ? $ops['axisLabel'][0] : "") . "',
               withLabel:true,
               label: {position:'rt', offset:[-15,15], highlight:false, useMathJax:{$useMathJax}}
             });";
  // Remove standard ticks and create new ones based off of tickDistance
   $out .= "xaxis{$label}.removeAllTicks();";
   $out .= "var xticks{$label} = board_{$label}.create('ticks',[xaxis{$label}], {
             ticksDistance: {$ticksDistanceX},
             strokeColor: 'rgba(150,150,150,0.85)',
             majorHeight:-1,
             minorHeight: -1,
             highlight:false,
             drawLabels:true,
             label:{offset:[0,-5], anchorY:'top', anchorX:'middle', highlight:false},
             minorTicks: {$minorTicksX}
           });";

   // y-axis
   $out .= "var yaxis{$label} = board_{$label}.create('axis', [[0,0],[0,1]], {
               strokeColor:'black',
               strokeWidth: 2,
               highlight:false,
               name:'" . (isset($ops['axisLabel'][1]) ? $ops['axisLabel'][1] : "") . "',
               withLabel:true,
               label: {position:'rt', offset:[10,-15], highlight:false, useMathJax:{$useMathJax}}
             });";
   // Remove standard ticks and create new ones based off of tickDistance
    $out .= "yaxis{$label}.removeAllTicks();";
    $out .= "var yticks{$label} = board_{$label}.create('ticks',[yaxis{$label}], {
              ticksDistance: {$ticksDistanceY},
              strokeColor: 'rgba(150,150,150,0.85)',
              majorHeight:-1,
              minorHeight: -1,
              highlight:false,
              drawLabels:true,
              label:{offset:[-5, 0], anchorY:'middle', anchorX:'right', highlight:false},
              minorTicks: {$minorTicksY}
            });";

    // If zoom is allowed, we need to make sure ticks behave nicely
    if ($zoom=="true"){
      $out .= "xticks{$label}.ticksFunction = function(){return xTicks{$label};};";
      $out .= "yticks{$label}.ticksFunction = function(){return yTicks{$label};};";
      // Tick handling functions
      $out .= "var setTicks{$label} = function(){
          bb = board_{$label}.getBoundingBox();
          xTicksVal = Math.pow(10, Math.floor((Math.log(0.6*(bb[2]-bb[0])))/Math.LN10));
          if( (bb[2]-bb[0])/xTicksVal > 6) {
        	  xTicks{$label} = xTicksVal;
        	} else {
        	  xTicks{$label} = 0.5* xTicksVal;
        	}
        	yTicksVal = Math.pow(10, Math.floor((Math.log(0.6*(bb[1]-bb[3])))/Math.LN10));
        	if( (bb[1]-bb[3])/yTicksVal > 6) {
        	  yTicks{$label} = yTicksVal;
        	} else {
        	  yTicks{$label} = 0.5* yTicksVal;
        	}
        	board_{$label}.fullUpdate(); // full update is required
        };
        setTicks{$label}();
        board_{$label}.on('boundingbox', function(){setTicks{$label}();});
        board_{$label}.fullUpdate();";
    }
    $boardinit = JSXG_setUpBoard($label, $width, $height, $centered);
    return substr_replace($boardinit, $out, strpos($boardinit, "/*INSERTHERE*/"),0);

}

#####################################
######### JSXG_addSlider ##########
# slider position is given in terms of percentage of board.
# Therefore, slider will stay fixed as the board zooms/pans
function JSXG_addSlider($board, $sname, $ops=array()){
  // Get Label string - so we know how to link elements
  $labStart = strpos($board, "jxgboard_") + 9;
  $labEnd = strpos($board, "'", $labStart);
  $label = substr($board, $labStart, $labEnd - $labStart);

  // Some position values
  $x1 = isset($ops['position'][0]) ? $ops['position'][0] : 0.05;
  $y1 = isset($ops['position'][1]) ? $ops['position'][1] : 0.05;
  $x2 = isset($ops['position'][2]) ? $ops['position'][2] : 0.25;
  $y2 = isset($ops['position'][3]) ? $ops['position'][3] : 0.05;
  // Defaults for slider range/default value
  $min = isset($ops['range'][0]) ? $ops['range'][0] : 1;
  $max = isset($ops['range'][1]) ? $ops['range'][1] : 10;
  $default = isset($ops['range'][2]) ? $ops['range'][2] : ((($min)+($max))/2);
  // Defaults for visual apperance/name
  $name = isset($ops['name']) ? $ops['name'] : '';
  $snapWidth = isset($ops['snapWidth']) ? $ops['snapWidth'] : -1;
  $withLabel = isset($ops['withLabel']) ? $ops['withLabel'] : "true";
  $color = isset($ops['color']) ? $ops['color'] : 'purple';
  $precision = isset($ops['precision']) ? $ops['precision'] : 2;

  // Create the slider.
  $out = "var param{$label}_{$sname} = board_{$label}.create('slider', [
            [function(){
              return board_{$label}.getBoundingBox()[0]+{$x1}*(board_{$label}.getBoundingBox()[2]-(board_{$label}.getBoundingBox()[0]));
            }, function(){
              return board_{$label}.getBoundingBox()[1]-{$y1}*(board_{$label}.getBoundingBox()[1]-(board_{$label}.getBoundingBox()[3]));
            }],
            [function(){
              return board_{$label}.getBoundingBox()[0]+{$x2}*(board_{$label}.getBoundingBox()[2]-(board_{$label}.getBoundingBox()[0]));
            }, function(){
              return board_{$label}.getBoundingBox()[1]-{$y2}*(board_{$label}.getBoundingBox()[1]-(board_{$label}.getBoundingBox()[3]));
            }],
            [{$min},{$default},{$max}]
          ], {
            snapWidth: {$snapWidth},
            withLabel: {$withLabel},
            precision: {$precision},
            name: '{$name}',
            snapWidth: {$snapWidth},
            baseline: {fixed:true, highlight:false},
            ticks: {fixed: true, highlight:false},
            highline: {highlight: false, strokeColor:'{$color}'},
            strokeColor: '{$color}',
            label: {color:'{$color}'}
          })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']})";
    }

    // If answerbox option provided, link the point to the answerbox
    if(isset($ops['answerbox']) ){
      if (count($ops['answerbox'])==1){
        $box = $ops['answerbox'][0] - 1;
      } else {
        $box = $ops['answerbox'][0]*1000 + $ops['answerbox'][1];
      }
      // Add event listener
      $out .= ".on('up',function(){
        $('#qn{$box}, #tc{$box}').val(this.Value()).trigger('change').trigger('input');
      });";
      // Change border color of JSXG board based off of answerbox color
      // (Green if correct, red if wrong, etc.)
      // Have to do this on an interval, since the answerbox might not be loaded when script called
      $out .= "
        var colorInterval{$label}_{$box} = setInterval(function(){
          if ($('#qn{$box}')[0] || $('#qn{$box}')[0]){
            if ($('#qn{$box}, #tc{$box}').is('.ansgrn')){
              $('#jxgboard_{$label}').css('border', '1px solid #0f0');
            } else if ($('#qn{$box}, #tc{$box}').is('.ansred') || $('#qn{$box}, #tc{$box}').is('.ansyel')){
              $('#jxgboard_{$label}').css('border','1px solid #f00');
            }
            /* Pull in answer from answerbox is possible
                Note that jsxg sliders dont have a setValue method, so....
                we have to manually move the slider point
            */
            if ($('#qn{$box}')[0] && $('#qn{$box}').val() !== ''){
              var tc = $('#qn{$box}').val();
              param{$label}_{$sname}.setGliderPosition(((tc)-({$min}))/(({$max})-({$min})));
              board_{$label}.update();
            } else if ($('#tc{$box}')[0] && $('#tc{$box}').val() !== ''){
              var tc = $('#tc{$box}').val();
              param{$label}_{$sname}.setGliderPosition(((tc)-({$min}))/(({$max})-({$min})));
              board_{$label}.update();
            }
            clearInterval(colorInterval{$label}_{$box});
          }
        }, 300);
      ";
    } else {
      $out .= ";";
    }

  // Append new output string to the board string
  return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);

}

#####################################
######### JSXG_addFunction ##########
# This will add a basic function plot to the board

function JSXG_addFunction($board, $ops=array(), $ref=null){
  // Get Label string - so we know how to link elements
  $labStart = strpos($board, "jxgboard_") + 9;
  $labEnd = strpos($board, "'", $labStart);
  $label = substr($board, $labStart, $labEnd - $labStart);

  // Make some default values
  $inpVar = isset($ops['inputVariable']) ? $ops['inputVariable'] : "x"; // input variable
  $rule = isset($ops['rule']) ? $ops['rule'] : "x"; // function rule
  $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : "blue"; // color of graph
  $width = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2; // Width of graph
  $dash = isset($ops['dash']) ? $ops['dash'] : 0; // Dash for the graph

  // Determine if bounds are properly set for the function (setting domain of func)
  $isBounds = (isset($ops['bounds']) && count($ops['bounds'])==2) ? true : false;
  $out = '';
  // If $ref provided, then give the function a name
  if ($ref!==null){
	$out .= "var func_{$label}_{$ref} = ";
  }

  // Start the output string
  $out .= "board_{$label}.create('functiongraph', [function({$inpVar}){";
  // First, handle case when slider value(s) being used via % in func rule
  if (isset($ops['slider-names']) && strpos($rule, "%")>-1){
    $out .= "var rule = '{$rule}';";
    // Loop through each variable name, add a JS statement if necessary to swap %a with a.Value()
    foreach ($ops['slider-names'] as $sn){
      if (strpos($rule, "%{$sn}")>-1){
        $out .= "rule = rule.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    // Use the mathjs() function to create JS-readable function, return it
    $out .= "with (Math) var ff = eval(mathjs(rule, '{$inpVar}'));
              return ff;";
  } else { // If no slider values
    $out .= "with (Math) var ff= eval(mathjs('{$rule}','{$inpVar}'));
             return ff;";
  }
  $out .= "}";
  //Handle bounds, if provided
  if ($isBounds){
    // First, start with lower bound. If a slider value is being used...
    if (isset($ops['bounds'][0]) && strpos($ops['bounds'][0], "%")>-1){
      $out .= ",function(){
                var lbs = '{$ops['bounds'][0]}';";
      foreach($ops['slider-names'] as $sn){
        if (strpos($ops['bounds'][0], "%{$sn}")>-1){
          $out .= "lbs = lbs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
        }
      }
      // Evaluate the bound statement using mathjs()
      $out .= "with (Math) var lb = eval(mathjs(lbs));
                return lb;
              }, ";
    } else { // Or, if no slider values used, just input the bound
      $out .= "," . $ops['bounds'][0] . ",";
    }
    // Upper bounds -- same process as for lower bound
    if (isset($ops['bounds'][1]) && strpos($ops['bounds'][1], "%")>-1){
      $out .= "function(){
                var ubs = '{$ops['bounds'][1]}';";
      foreach($ops['slider-names'] as $sn){
        if (strpos($ops['bounds'][1], "%{$sn}")>-1){
          $out .= "ubs = ubs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
        }
      }
      $out .= "with (Math) var ub = eval(mathjs(ubs));
                return ub;
              } ";
    } else {
      $out .= $ops['bounds'][1];
    }

  }
  $out .= "]";
  // Handle attributes, then close up shop.
  $out .= ", {
            strokeColor: '{$color}',
            strokeWidth: {$width},
            dash: {$dash},
            fixed: true,
            highlight: false,
            name: " . (isset($ops['name']) ? $ops['name'] : "''") . "
          })";
  if (isset($ops['attributes'])){
    $out .= ".setAttribute({$ops['attributes']});";
  } else {
    $out .= ";";
  }

  // Append new output string to the board string
  return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
}

#####################################
######### JSXG_addParametric ##########
# Add a parametric curve to an existing board/axes
function JSXG_addParametric($board, $ops=array()){
  // Get Label string -- so we know how to link elements
  $labStart = strpos($board, "jxgboard_") + 9;
  $labEnd = strpos($board, "'", $labStart);
  $label = substr($board, $labStart, $labEnd - $labStart);

  // Make some default values
  $inpVar = isset($ops['inputVariable']) ? $ops['inputVariable'] : "t"; // input variable
  $xRule = isset($ops['rule'][0]) ? $ops['rule'][0] : "Math.cos(t)"; // rule for x(t)
  $yRule = isset($ops['rule'][1]) ? $ops['rule'][1] : "Math.sin(t)"; // rule for y(t)
  $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : "blue"; // color of graph
  $width = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2; // width of graph
  $tStart = isset($ops['bounds'][0]) ? $ops['bounds'][0] : 0; // start value of t
  $tEnd = isset($ops['bounds'][1]) ? $ops['bounds'][1] : 1; // end value of t


  $out = "board_{$label}.create('curve', [function({$inpVar}){";
  ###### Handle x-rule ######
  // If slider value is used...
  if (isset($ops['slider-names']) && strpos($xRule, "%")>-1){
    $out .= "var xrs = '{$xRule}';";
    // Replace %a with a.Value for each slider name that applies
    foreach($ops['slider-names'] as $sn){
      if (strpos($xRule, "%{$sn}")>-1){
        $out .= "xrs = xrs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    // Use mathjs() to evaluate the bound statement
    $out .= "with (Math) var xf = eval(mathjs(xrs, '{$inpVar}')); return xf;},";
  } else {
    $out .= "with (Math) var xf= eval(mathjs('{$xRule}','{$inpVar}')); return xf;},";
  }
  ###### Handle y-rule <-- same process as x-rule ######
  $out .= "function({$inpVar}){";
  if (isset($ops['slider-names']) && strpos($yRule, "%")>-1){
    $out .= "var yrs = '{$yRule}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($yRule, "%{$sn}")>-1){
        $out .= "yrs = yrs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    $out .= "with (Math) var yf = eval(mathjs(yrs, '{$inpVar}')); return yf;},";
  } else {
    $out .= "with (Math) var yf= eval(mathjs('{$yRule}','{$inpVar}')); return yf;},";
  }

  ###### Handle Lower bounds <-- similar process as for rules ######
  if (isset($ops['slider-names']) && strpos($tStart, "%")>-1){
    $out .= "function(){var lbs = '{$tStart}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($tStart, "%{$sn}")>-1){
        $out .= "lbs = lbs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    $out .= "with (Math) var lb = eval(mathjs(lbs)); return lb;},";
  } else {
    $out .= "{$tStart},";
  }
  ###### Handle Upper bounds
  if (isset($ops['slider-names']) && strpos($tEnd, "%")>-1){
    $out .= "function(){var ubs = '{$tEnd}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($tEnd, "%{$sn}")>-1){
        $out .= "ubs = ubs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    $out .= "with (Math) var ub = eval(mathjs(ubs)); return ub;},";
  } else {
    $out .= "{$tEnd}";
  }

  ###### Attributes, and close up shop. ######
  $out .= "], {
            strokeColor: '{$color}',
            strokeWidth: {$width},
            highlight:false,
            name: " . (isset($ops['name']) ? $ops['name'] : "''") . "
          })";
  if (isset($ops['attributes'])){
    $out .= ".setAttribute({$ops['attributes']})";
  } else {
    $out .= ";";
  }
  // Append new output string to the board string
  return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
}


#####################################
######### JSXG_addText ##########
function JSXG_addText($board, $ops=array()){
  // Get Label string -- so we know how to link elements
  $labStart = strpos($board, "jxgboard_") + 9;
  $labEnd = strpos($board, "'", $labStart);
  $label = substr($board, $labStart, $labEnd - $labStart);

  // Make some default values
  $text = isset($ops['text']) ? $ops['text'] : "Something happened..."; // text to display
  $xPos = isset($ops['position'][0]) ? $ops['position'][0] : 0;
  $yPos = isset($ops['position'][1]) ? $ops['position'][1] : 0;
  // attributes
  $anchorX = isset($ops['anchor'][0]) ? $ops['anchor'][0] : "middle";
  $anchorY = isset($ops['anchor'][1]) ? $ops['anchor'][1] : "middle";
  $fontSize = isset($ops['fontSize']) ? $ops['fontSize'] : 16;
  $highlight = isset($ops['highlight']) ? $ops['highlight'] : "false";
  $fixed = isset($ops['fixed']) ? $ops['fixed'] : "true";
  $rotate = isset($ops['rotate']) ? $ops['rotate'] : 0;
  $color = isset($ops['color']) ? $ops['color'] : 'black';

  $useMathJax = strpos($text, "`")>-1 ? "true" : "false";

  $out = "board_{$label}.create('text', [";
  // x-position. Handle slider values first, if present
  if (isset($ops['slider-names']) && strpos($xPos, "%")>-1){
    $out .= "function(){
              var xps = '{$xPos}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($xPos, "%{$sn}")>-1){
        $out .= "xps = xps.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    $out .= "with (Math) var xp = eval(mathjs(xps));
             return xp;},";
  } else {
    $out .= "{$xPos},";
  }
  // y-position. Handle slider values first, if present
  if (isset($ops['slider-names']) && strpos($yPos, "%")>-1){
    $out .= "function(){
              var yps = '{$yPos}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($yPos, "%{$sn}")>-1){
        $out .= "yps = yps.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    $out .= "with (Math) var yp = eval(mathjs(yps));
             return yp;},";
  } else {
    $out .= "{$yPos},";
  }
  // Text
  if (isset($ops['slider-names']) && strpos($text, "%")>-1){
    $out .= "function(){
      var ts = '{$text}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($text, "%{$sn}")>-1){
        $out .= "
        ts = ts.replace(/%{$sn}/g, param{$label}_{$sn}.Value().toFixed(3));";
      }
    }
    $out .= "
    return ts;}],";
  } else {
    $out .= "'{$text}'], ";
  }
  // Set attributes and close up shop.
  $out .= "{
            useMathJax: {$useMathJax},
            fontSize: {$fontSize},
            anchorX: '{$anchorX}',
            anchorY: '{$anchorY}',
            highlight: {$highlight},
            fixed: {$fixed},
            rotate: {$rotate},
            color: '{$color}',
            name: " . (isset($ops['name']) ? $ops['name'] : "''") . "
          })";
  if (isset($ops['attributes'])){
    $out .= ".setAttribute({$ops['attributes']});";
  } else {
    $out .= ";";
  }
    // Append new output string to the board string
    return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
}






#############################################
#############################################
####### Polar Functionality #################

#####################################
######### JSXG_createPolarAxes ##########
# creates a set of axes, and a board to construct on.
function JSXG_createPolarAxes($label, $ops=array()){
    // Add some default values
   $size = isset($ops['size']) ? $ops['size'] : 350;
   //set the min and max x-values if provided, else default to [-5, 5]
   $rmax = isset($ops['r'][0]) ? (float) $ops['r'][0] : 5;
   $rInc = isset($ops['r'][1]) ? (float) $ops['r'][1] : 1;
   $thetaType = isset($ops['theta'][0]) ? $ops['theta'][0] : "radian";
   $thetaInc = isset($ops['theta'][1]) ? (float) $ops['theta'][1] : 1;

   $rMaxBoard = 1.2 * $rmax;
   $rMaxLabel = 1.1 * $rmax;

   $boardYScale = isset($ops['pad-top']) ? 1.1 : 1;
   $boardHeight = $boardYScale * $size;
   $yMax = (1+2*($boardYScale-1))*$rMaxBoard;


   $navBar = (isset($ops['controls']) && in_array('nav-bar', $ops['controls'])) ? "true" : "false";
   $zoom = (isset($ops['controls']) && in_array('zoom', $ops['controls'])) ? "true" : "false";
   $pan = (isset($ops['controls']) && in_array('no-pan', $ops['controls'])) ? "false" : "true";
   $centered = isset($ops['centered']) ? false : true;


   // Layering options. Push the axis in front of ticks
   $out = "
   JXG.Options.layer = {numlayers: 20, text: 9, point: 9, glider: 9, arc: 8, line: 7, circle: 6,curve: 8, turtle: 5, polygon: 3, sector: 3, angle: 3, integral: 3, axis: 3, ticks: 2, grid: 1, image: 0, trace: 0};
   ";
   $out .= "
   JXG.Options.text.fontSize = 18;
   ";
   //$out .= "JXG.Options.text.useMathJax = true;";
   // Create the board
   $defaultAxis = !empty($ops['tickDistance']) ? "false" : "true";
   $out .= "
   window.board_{$label} = JXG.JSXGraph.initBoard('jxgboard_{$label}', {
     boundingbox: [-{$rMaxBoard}, {$yMax}, {$rMaxBoard}, -{$rMaxBoard}],
     axis: false,
     showCopyright: false,
     showNavigation: {$navBar},
     zoom: {
      enabled: {$zoom},
      factorX: 1.25,
      factorY: 1.25,
      wheel: {$zoom},
      needshift: false,
      eps: 0.1
    },
    pan: {
      enabled: {$pan},
      needshift: false
    }
   });
   ";

    // Create radial circles, label them
    $out .= "
    var r = {$rInc};
    while (r <= {$rmax}){
      board_{$label}.create('circle',[[0,0], r], {
        highlight: false,
        fixed:true,
        strokeColor: '#b6b6b6',
        strokeWidth: 1
      });
      board_{$label}.create('text', [r, 0, r], {
        highlight:false,
        fixed:true,
        anchorX: 'middle',
        anchorY:'top'
      });
      r += {$rInc};
    }
    ";

    // Add in the angular lines
    switch ($thetaType){
      case "pi":
        // Start with a gcd function to use in labeling angles
        $out .= "
        function gcd(a, b){
          if (!b){return a;}
          return gcd(b, a%b);
        }
        function formatPiFrac(a, b){
          var num = Math.round(a/gcd(a, b));
          var den = Math.round(b/gcd(a,b));
          if (num==1 && den==1){
            return '&pi;';
          } else if (num == 1){
            return '&pi;/'+den;
          } else if (den == 1){
            return num+'&pi;';
          } else {
            return num+'&pi;/'+den;
          }
        }
        var inc = {$thetaInc};
        for (var i=0; i<=2*inc-1; i++){
          board_{$label}.create('line', [[0, 0], [Math.cos(i*Math.PI/inc), Math.sin(i*Math.PI/inc)]], {
            straightFirst: false,
            fixed: true,
            highlight: false,
            strokeColor: '#b6b6b6',
            strokeWidth: (i==0 ? 3 : 1)
          });
          if (i!=0){
            board_{$label}.create('text', [{$rMaxLabel}*Math.cos(i*Math.PI/inc), {$rMaxLabel}*Math.sin(i*Math.PI/inc), formatPiFrac(i,inc)], {
              fixed: true,
              highlight: false,
              anchorX: 'middle',
              anchorY: 'middle'
            });
          }
        }
        ";
        break;
      // Degree case. Nothing special here
      case "degrees":
        $out .= "
        var t = 0;
        while (Math.abs(t) < 360){
          board_{$label}.create('line', [[0, 0], [Math.cos(t*Math.PI/180), Math.sin(t*Math.PI/180)]], {
            straightFirst: false,
            fixed: true,
            highlight: false,
            strokeColor: '#b6b6b6',
            strokeWidth: (t==0 ? 3 : 1)
          });
          if (t!=0){
            board_{$label}.create('text', [{$rMaxLabel}*Math.cos(t*Math.PI/180), {$rMaxLabel}*Math.sin(t*Math.PI/180), t+'&deg;'], {
              fixed: true,
              highlight: false,
              anchorX: 'middle',
              anchorY: 'middle',
              fontSize: 16
            });
          }
          t += {$thetaInc};
        }
        ";
        break;
      // Custom unit of angle measure.
      // Increment now becomes number of units in one full rotation
      case "custom":
        $out .= "
        var n = {$thetaInc};
        var sn = Math.floor(n);
        for (var i=0; i<sn; i++){
          board_{$label}.create('line', [[0, 0], [Math.cos(i*2*Math.PI/n), Math.sin(i*2*Math.PI/n)]], {
            straightFirst: false,
            fixed: true,
            highlight: false,
            strokeColor: '#b6b6b6',
            strokeWidth: (i==0 ? 3 : 1)
          });
          if (i!=0){
            board_{$label}.create('text', [{$rMaxLabel}*Math.cos(i*2*Math.PI/n), {$rMaxLabel}*Math.sin(i*2*Math.PI/n), i], {
              fixed: true,
              highlight: false,
              anchorX: 'middle',
              anchorY: 'middle'
            });
          }
        }
        ";
        break;
      // The "radian" case, and fallback
      default:
        $out .= "
        var t = 0;
        while (Math.abs(t) < 6.283185307){
          board_{$label}.create('line', [[0,0], [Math.cos(t), Math.sin(t)]], {
            straightFirst: false,
            fixed: true,
            highlight: false,
            strokeColor: '#b6b6b6',
            strokeWidth: (t==0 ? 3 : 1)
          });
          if (t!=0){
            board_{$label}.create('text', [{$rMaxLabel}*Math.cos(t), {$rMaxLabel}*Math.sin(t), t], {
              fixed: true,
              highlight: false,
              anchorX: 'middle',
              anchorY: 'middle'
            });
          }
        t += {$thetaInc};
        }
        ";
    }
    // Get initial board, add this output string to it.
    $boardinit = JSXG_setUpBoard($label, $size, $boardHeight, $centered);
    return substr_replace($boardinit, $out, strpos($boardinit, "/*INSERTHERE*/"),0);
}

#####################################
######### JSXG_addPolar ##########
# Add a polar curve to an existing board
function JSXG_addPolar($board, $ops=array()){
  // Get Label string -- so we know how to link elements
  $labStart = strpos($board, "jxgboard_") + 9;
  $labEnd = strpos($board, "'", $labStart);
  $label = substr($board, $labStart, $labEnd - $labStart);

  // Make some default values
  $inpVar = isset($ops['inputVariable']) ? $ops['inputVariable'] : "t"; // input variable
  $rule = isset($ops['rule']) ? $ops['rule'] : "t"; // rule for x(t)
  $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : "red"; // color of graph
  $width = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2; // width of graph
  $tStart = isset($ops['bounds'][0]) ? $ops['bounds'][0] : 0; // start value of t
  $tEnd = isset($ops['bounds'][1]) ? $ops['bounds'][1] : 6.283185307; // end value of t

  $out ="
  board_{$label}.create('curve', [
    function({$inpVar}){";
  // If slider value present
  if (isset($ops['slider-names']) && strpos($rule, "%")>-1){
    $out .= "
        var rs = '{$rule}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($rule, "%{$sn}")-1){
        $out .= "
            rs = rs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    $out .= "
        with (Math) var rule = eval(mathjs(rs, '{$inpVar}'));
        return rule;
      },
    ";
  } else { // Otherwise, just use the rule
    $out .= "
        with (Math) var rule = eval(mathjs('{$rule}', '{$inpVar}'));
        return rule;
      },
    ";
  }
  // center of curve
  $out .="
     [0,0],";
  // Start value for theta
  if (isset($ops['slider-names']) && strpos($tStart, "%")>-1){
    $out .= "
    function(){
      var tss = '{$tStart}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($tStart, "%{$sn}")>-1){
        $out .= "
          tss = tss.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    $out .= "
      with (Math) var ts = eval(mathjs(tss));
      return ts;
    },";
  } else {
    $out .= "{$tStart},";
  }
  // End value for theta
  if (isset($ops['slider-names']) && strpos($tEnd, "%")>-1){
    $out .= "
    function(){
      var tes = '{$tEnd}';";
    foreach($ops['slider-names'] as $sn){
      if (strpos($tEnd, "%{$sn}")>-1){
        $out .= "
          tes = tes.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
      }
    }
    $out .= "
      with (Math) var te = eval(mathjs(tes));
      return te;
    }]";
  } else {
    $out .= "{$tEnd}]";
  }

  $out .= "
  ,{
      curveType: 'polar',
      highlight:false,
      fixed:true,
      strokeColor: '{$color}',
      strokeWidth: {$width}
    }
  )
  ";
  if (isset($ops['attributes'])){
    $out .= ".setAttribute({$ops['attributes']});";
  } else {
    $out .= ";";
  }

  // Append new output string to the board string
  return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
}




#############################################
#############################################
####### Geometry Functionality #################

#####################################
######### JSXG_createBlankBoard ##########
# creates a board with no axes
function JSXG_createBlankBoard($label, $ops){
  // Add some default values
  $width = isset($ops['size'][0]) ? $ops['size'][0] : 350; // board width
  $height = isset($ops['size'][1]) ? $ops['size'][1] : 350; // board height
  //set the min and max x-values if provided, else default to [-5, 5]
  $xmin = isset($ops['bounds'][0]) ? $ops['bounds'][0] : -5;
  $xmax = isset($ops['bounds'][1]) ? $ops['bounds'][1] : 5;
  $ymin = isset($ops['bounds'][2]) ? $ops['bounds'][2] : -5;
  $ymax = isset($ops['bounds'][3]) ? $ops['bounds'][3] : 5;

  $navBar = (isset($ops['controls']) && in_array('nav-bar', $ops['controls'])) ? "true" : "false";
  $zoom = (isset($ops['controls']) && in_array('zoom', $ops['controls'])) ? "true" : "false";
  $pan = (isset($ops['controls']) && in_array('no-pan', $ops['controls'])) ? "false" : "true";
  $centered = isset($ops['centered']) ? false : true;

  // Create the board
  $out = "window.board_{$label} = JXG.JSXGraph.initBoard('jxgboard_{$label}', {
           boundingbox: [{$xmin}, {$ymax}, {$xmax}, {$ymin}],
           axis: false,
           showCopyright: false,
           showNavigation: {$navBar},
           zoom: {
            enabled: {$zoom},
            factorX: 1.25,
            factorY: 1.25,
            wheel: {$zoom},
            needshift: false,
            eps: 0.1
          },
          pan: {
            enabled: {$pan},
            needshift: false
          }
         });";


  // CLOSE UP shop
  $boardinit = JSXG_setUpBoard($label, $width, $height, $centered);
  return substr_replace($boardinit, $out, strpos($boardinit, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addPoint ##########
  function JSXG_addPoint($board, $ops=array(), $ref = null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    // Make some default values
    $x = isset($ops['position'][0]) ? $ops['position'][0] : 0;
    $y = isset($ops['position'][1]) ? $ops['position'][1] : 0;

    // attributes
    $highlight = isset($ops['highlight']) ? $ops['highlight'] : "true";
    $draggable = (isset($ops['draggable']) || isset($ops['answerbox'])) ? "true" : "false";
    $fixed = isset($ops['fixed']) ? "true" : "false";

    $color = isset($ops['color']) ? $ops['color'] : 'purple';
    $size = isset($ops['size']) ? $ops['size'] : 2;
    $face = isset($ops['face']) ? $ops['face'] : 'circle';

    // If answerbox option provided, set up box number
    if(isset($ops['answerbox']) ){
      if (count($ops['answerbox'])==1){
        $box = $ops['answerbox'][0] - 1;
      } else {
        $box = $ops['answerbox'][0]*1000 + $ops['answerbox'][1];
      }
      $ref = ($ref!==null ? $ref : $box);
    }
    $out = '';
    if ($ref!==null){
      // name point, which is used in persistence
      $out .= "
        var p_{$label}_{$ref} = ";
    }

    $out .= "board_{$label}.create('point', [";
    // x value
    if (isset($ops['slider-names']) && strpos($x, "%")>-1){
      $out .= "function(){
        var xs = '{$x}';";
      foreach($ops['slider-names'] as $sn){
        if (strpos($x, "%{$sn}")>-1){
          $out .= "xs = xs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
        }
      }
      $out .= "with (Math) var x = eval(mathjs(xs)); return x;},";
    } else {
      $out .= "{$x},";
    }
    // y value
    if (isset($ops['slider-names']) && strpos($y, "%")>-1){
      $out .= "function(){
        var ys = '{$y}';";
      foreach($ops['slider-names'] as $sn){
        if (strpos($y, "%{$sn}")>-1){
          $out .= "ys = ys.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
        }
      }
      $out .= "with (Math) var y = eval(mathjs(ys)); return y;}],";
    } else {
      $out .= "{$y}],";
    }

    // Set attributes
    $out .= "{
              highlight: {$highlight},
              showInfobox: false,
              fixed: {$fixed},
              color: '{$color}',
              size: {$size},
              face: '{$face}',
              label: {color:'{$color}', useMathJax:true},
              name: '" . (isset($ops['name']) ? $ops['name'] : "") . "'
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']})";
    }
    // If answerbox option provided, link the point to the answerbox
    if(isset($ops['answerbox']) ){
      $out .= ".on('up',function(){
        $('#qn{$box}, #tc{$box}').val('('+this.X().toFixed(4)+','+this.Y().toFixed(4)+')')
            .trigger('change').trigger('input');
      });";
      // Change border color of JSXG board based off of answerbox color
      // (Green if correct, red if wrong, etc.)
      // Have to do this on an interval, since the answerbox might not be loaded when script called
      $out .= "
        var colorInterval{$label}_{$box} = setInterval(function(){
          if ($('#qn{$box}')[0] || $('#qn{$box}')[0]){
            if ($('#qn{$box}, #tc{$box}').is('.ansgrn')){
              $('#jxgboard_{$label}').css('border', '1px solid #0f0');
            } else if ($('#qn{$box}, #tc{$box}').is('.ansred') || $('#qn{$box}, #tc{$box}').is('.ansyel')){
              $('#jxgboard_{$label}').css('border','1px solid #f00');
            }
            /* Pull in answer from answerbox is possible */
            if ($('#qn{$box}')[0] && $('#qn{$box}').val() !== ''){
              var coords = $('#qn{$box}').val();
              coords = coords.substring(1, coords.length - 2);
              coords = coords.split(',');
              p_{$label}_{$ref}.setPosition(JXG.COORDS_BY_USER, [parseFloat(coords[0]),parseFloat(coords[1])]);
              board_{$label}.fullUpdate();
            } else if ($('#tc{$box}')[0] && $('#tc{$box}').val() !== ''){
              var coords = $('#tc{$box}').val();
              coords = coords.substring(1, coords.length - 2);
              coords = coords.split(',');
              p_{$label}_{$ref}.setPosition(JXG.COORDS_BY_USER, [parseFloat(coords[0]),parseFloat(coords[1])]);
              board_{$label}.fullUpdate();
            }
            clearInterval(colorInterval{$label}_{$box});
          }
        }, 300);
      ";
    } else {
      $out .= ";";
    }

    // Append new output string to the board string
    return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addSegment ##########
  function JSXG_addSegment($board, $ops=array(), $ref=null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    $p1 = isset($ops['position'][0]) ? $ops['position'][0] : [0,0];
    $p2 = isset($ops['position'][1]) ? $ops['position'][1] : [3,3];

    // Make some default values

    // attributes
    $highlight = isset($ops['highlight']) ? $ops['highlight'] : "false";
    $fixed = isset($ops['fixed']) ? $ops['fixed'] : "true";
    $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : 'black';
    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $strokeWidth = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2;
    $out = '';

    // If reference provided...
    if ($ref!==null){
      $out .= "
      var seg_{$label}_{$ref} = ";
    }
    $out .= "board_{$label}.create('segment', [";
    if (!is_array($p1)){
      $out .= $p1 . ",";
    } else {
      $x1 = $p1[0]; $y1 = $p1[1];
      $out .= "[";
      // x1 value
      if (isset($ops['slider-names']) && strpos($x1, "%")>-1){
        $out .= "function(){
          var x1s = '{$x1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x1, "%{$sn}")>-1){
            $out .= "x1s = x1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x1 = eval(mathjs(x1s)); return x1;},";
      } else {
        $out .= "{$x1},";
      }
      // y1 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y1s = '{$y1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y1, "%{$sn}")>-1){
            $out .= "y1s = y1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y1 = eval(mathjs(y1s)); return y1;}],[";
      } else {
        $out .= "{$y1}],";
      }
    }
    if (!is_array($p2)){
      $out .= $p2 . "],";
    } else{
      $x2 = $p2[0]; $y2 = $p2[1];
      $out .= "[";
      // x2 value
      if (isset($ops['slider-names']) && strpos($x2, "%")>-1){
        $out .= "function(){
          var x2s = '{$x2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x2, "%{$sn}")>-1){
            $out .= "x2s = x2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x2 = eval(mathjs(x2s)); return x2;},";
      } else {
        $out .= "{$x2},";
      }
      // y2 value
      if (isset($ops['slider-names']) && strpos($y2, "%")>-1){
        $out .= "function(){
          var y2s = '{$y2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y2, "%{$sn}")>-1){
            $out .= "y2s = y2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y2 = eval(mathjs(y2s)); return y2;}]],";
      } else {
        $out .= "{$y2}]],";
      }
    }

    // Set attributes and close up shop.
    $out .= "{
              highlight: {$highlight},
              fixed: {$fixed},
              strokeColor: '{$color}',
              dash: {$dash},
              strokeWidth: {$strokeWidth},
              name: " . (isset($ops['name']) ? $ops['name'] : "''") . "
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']});";
    } else {
      $out .= ";";
    }

      // Append new output string to the board string{
      return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addArrow ##########
  function JSXG_addArrow($board, $ops=array(), $ref=null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    // Make some default values
    $p1 = isset($ops['position'][0]) ? $ops['position'][0] : 0;
    $p2 = isset($ops['position'][1]) ? $ops['position'][1] : 1;

    // attributes
    $highlight = isset($ops['highlight']) ? $ops['highlight'] : "false";
    $fixed = isset($ops['fixed']) ? $ops['fixed'] : "true";
    $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : 'black';
    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $strokeWidth = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2;
    $out = '';

    if ($ref!==null){
      $out .= "
      var vec_{$label}_{$ref} = ";
    }

    $out .= "board_{$label}.create('arrow', [";

    if (!is_array($p1)){
      $out .= $p1 . ",";
    } else {
       $x1 = $p1[0]; $y1 = $p1[1];
       $out .= "[";
      // x1 value
      if (isset($ops['slider-names']) && strpos($x1, "%")>-1){
        $out .= "function(){
          var x1s = '{$x1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x1, "%{$sn}")>-1){
            $out .= "x1s = x1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x1 = eval(mathjs(x1s)); return x1;},";
      } else {
        $out .= "{$x1},";
      }
      // y1 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y1s = '{$y1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y1, "%{$sn}")>-1){
            $out .= "y1s = y1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y1 = eval(mathjs(y1s)); return y1;}],[";
      } else {
        $out .= "{$y1}],";
      }
    }
    if (!is_array($p2)){
      $out .= $p2 . "],";
    } else {
      $x2 = $p2[0]; $y2 = $p2[1];
      $out .= "[";
      // x2 value
      if (isset($ops['slider-names']) && strpos($x2, "%")>-1){
        $out .= "function(){
          var x2s = '{$x2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x2, "%{$sn}")>-1){
            $out .= "x2s = x2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x2 = eval(mathjs(x2s)); return x2;},";
      } else {
        $out .= "{$x2},";
      }
      // y2 value
      if (isset($ops['slider-names']) && strpos($y2, "%")>-1){
        $out .= "function(){
          var y2s = '{$y2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y2, "%{$sn}")>-1){
            $out .= "y2s = y2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y2 = eval(mathjs(y2s)); return y2;}]],";
      } else {
        $out .= "{$y2}]],";
      }
    }

    // Set attributes and close up shop.
    $out .= "{
              highlight: {$highlight},
              fixed: {$fixed},
              strokeColor: '{$color}',
              strokeWidth: {$strokeWidth},
              dash: {$dash},
              name: " . (isset($ops['name']) ? $ops['name'] : "''") . "
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']});";
    } else {
      $out.=";";
    }

      // Append new output string to the board string{
      return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addLine ##########
  function JSXG_addLine($board, $ops=array(), $ref=null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    $p1 = isset($ops['position'][0]) ? $ops['position'][0] : [0,0];
    $p2 = isset($ops['position'][1]) ? $ops['position'][1] : [3,3];

    // Make some default values

    // attributes
    $highlight = isset($ops['highlight']) ? $ops['highlight'] : "false";
    $fixed = isset($ops['fixed']) ? $ops['fixed'] : "true";
    $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : 'black';
    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $strokeWidth = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2;
    $out = '';

    // If reference provided...
    if ($ref!==null){
      $out .= "
      var line_{$label}_{$ref} = ";
    }
    $out .= "board_{$label}.create('line', [";
    if (!is_array($p1)){
      $out .= $p1 . ",";
    } else {
      $x1 = $p1[0]; $y1 = $p1[1];
      $out .= "[";
      // x1 value
      if (isset($ops['slider-names']) && strpos($x1, "%")>-1){
        $out .= "function(){
          var x1s = '{$x1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x1, "%{$sn}")>-1){
            $out .= "x1s = x1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x1 = eval(mathjs(x1s)); return x1;},";
      } else {
        $out .= "{$x1},";
      }
      // y1 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y1s = '{$y1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y1, "%{$sn}")>-1){
            $out .= "y1s = y1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y1 = eval(mathjs(y1s)); return y1;}],[";
      } else {
        $out .= "{$y1}],";
      }
    }
    if (!is_array($p2)){
      $out .= $p2 . "],";
    } else{
      $x2 = $p2[0]; $y2 = $p2[1];
      $out .= "[";
      // x2 value
      if (isset($ops['slider-names']) && strpos($x2, "%")>-1){
        $out .= "function(){
          var x2s = '{$x2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x2, "%{$sn}")>-1){
            $out .= "x2s = x2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x2 = eval(mathjs(x2s)); return x2;},";
      } else {
        $out .= "{$x2},";
      }
      // y2 value
      if (isset($ops['slider-names']) && strpos($y2, "%")>-1){
        $out .= "function(){
          var y2s = '{$y2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y2, "%{$sn}")>-1){
            $out .= "y2s = y2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y2 = eval(mathjs(y2s)); return y2;}]],";
      } else {
        $out .= "{$y2}]],";
      }
    }

    // Set attributes and close up shop.
    $out .= "{
              highlight: {$highlight},
              fixed: {$fixed},
              strokeColor: '{$color}',
              dash: {$dash},
              strokeWidth: {$strokeWidth},
              name: " . (isset($ops['name']) ? $ops['name'] : "''") . "
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']});";
    } else {
      $out .= ";";
    }
      // Append new output string to the board string{
      return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addRay ##########
  function JSXG_addRay($board, $ops=array(), $ref=null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    $p1 = isset($ops['position'][0]) ? $ops['position'][0] : [0,0];
    $p2 = isset($ops['position'][1]) ? $ops['position'][1] : [3,3];

    // Make some default values

    // attributes
    $highlight = isset($ops['highlight']) ? $ops['highlight'] : "false";
    $fixed = isset($ops['fixed']) ? $ops['fixed'] : "true";
    $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : 'black';
    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $strokeWidth = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2;
    $out = '';

    // If reference provided...
    if ($ref!==null){
      $out .= "
      var ray_{$label}_{$ref} = ";
    }
    $out .= "board_{$label}.create('line', [";
    if (!is_array($p1)){
      $out .= $p1 . ",";
    } else {
      $x1 = $p1[0]; $y1 = $p1[1];
      $out .= "[";
      // x1 value
      if (isset($ops['slider-names']) && strpos($x1, "%")>-1){
        $out .= "function(){
          var x1s = '{$x1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x1, "%{$sn}")>-1){
            $out .= "x1s = x1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x1 = eval(mathjs(x1s)); return x1;},";
      } else {
        $out .= "{$x1},";
      }
      // y1 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y1s = '{$y1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y1, "%{$sn}")>-1){
            $out .= "y1s = y1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y1 = eval(mathjs(y1s)); return y1;}],[";
      } else {
        $out .= "{$y1}],";
      }
    }
    if (!is_array($p2)){
      $out .= $p2 . "],";
    } else{
      $x2 = $p2[0]; $y2 = $p2[1];
      $out .= "[";
      // x2 value
      if (isset($ops['slider-names']) && strpos($x2, "%")>-1){
        $out .= "function(){
          var x2s = '{$x2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x2, "%{$sn}")>-1){
            $out .= "x2s = x2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x2 = eval(mathjs(x2s)); return x2;},";
      } else {
        $out .= "{$x2},";
      }
      // y2 value
      if (isset($ops['slider-names']) && strpos($y2, "%")>-1){
        $out .= "function(){
          var y2s = '{$y2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y2, "%{$sn}")>-1){
            $out .= "y2s = y2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y2 = eval(mathjs(y2s)); return y2;}]],";
      } else {
        $out .= "{$y2}]],";
      }
    }

    // Set attributes and close up shop.
    $out .= "{
              highlight: {$highlight},
              fixed: {$fixed},
              strokeColor: '{$color}',
              dash: {$dash},
              strokeWidth: {$strokeWidth},
              name: " . (isset($ops['name']) ? $ops['name'] : "''") . ",
              lastArrow: true,
              straightFirst: false
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']});";
    } else {
      $out .= ";";
    }
      // Append new output string to the board string{
      return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addAngle ##########
  function JSXG_addAngle($board, $ops=array(), $ref=null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    $p1 = isset($ops['position'][0]) ? $ops['position'][0] : [3,0];
    $p2 = isset($ops['position'][1]) ? $ops['position'][1] : [0,0];
    $p3 = isset($ops['position'][2]) ? $ops['position'][2] : [0,3];

    // Make some default values

    // attributes
    $highlight = isset($ops['highlight']) ? $ops['highlight'] : "false";
    $fixed = isset($ops['fixed']) ? $ops['fixed'] : "true";
    $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : 'black';
    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $strokeWidth = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2;
    $out = '';

    // initial ray
    // If reference provided...
    if ($ref!==null){
      $out .= "
      var angle_{$label}_{$ref}_i = ";
    }
    $out .= "board_{$label}.create('line', [";
    if (!is_array($p2)){
      $out .= $p2 . ",";
    } else {
      $x2 = $p2[0]; $y2 = $p2[1];
      $out .= "[";
      // x2 value
      if (isset($ops['slider-names']) && strpos($x2, "%")>-1){
        $out .= "function(){
          var x2s = '{$x2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x2, "%{$sn}")>-1){
            $out .= "x2s = x2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x2 = eval(mathjs(x2s)); return x2;},";
      } else {
        $out .= "{$x2},";
      }
      // y2 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y2s = '{$y2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y2, "%{$sn}")>-1){
            $out .= "y2s = y2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y2 = eval(mathjs(y1s)); return y2;}],[";
      } else {
        $out .= "{$y2}],";
      }
    }
    if (!is_array($p1)){
      $out .= $p1 . "],";
    } else{
      $x1 = $p1[0]; $y1 = $p1[1];
      $out .= "[";
      // x2 value
      if (isset($ops['slider-names']) && strpos($x1, "%")>-1){
        $out .= "function(){
          var x1s = '{$x1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x1, "%{$sn}")>-1){
            $out .= "x1s = x1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x1 = eval(mathjs(x1s)); return x1;},";
      } else {
        $out .= "{$x1},";
      }
      // y1 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y1s = '{$y1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y1, "%{$sn}")>-1){
            $out .= "y1s = y1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y1 = eval(mathjs(y1s)); return y1;}]],";
      } else {
        $out .= "{$y1}]],";
      }
    }
    // Set attributes
    $out .= "{
              highlight: {$highlight},
              fixed: {$fixed},
              strokeColor: '{$color}',
              dash: {$dash},
              strokeWidth: {$strokeWidth},
              name: " . (isset($ops['name']) ? $ops['name'] : "''") . ",
              lastArrow: true,
              straightFirst: false
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']});";
    } else {
      $out .= ";";
    }

    // Terminal ray...
    // If reference provided...
    if ($ref!==null){
      $out .= "
      var angle_{$label}_{$ref}_t = ";
    }
    $out .= "board_{$label}.create('line', [";
    if (!is_array($p2)){
      $out .= $p2 . ",";
    } else {
      $x2 = $p2[0]; $y2 = $p2[1];
      $out .= "[";
      // x2 value
      if (isset($ops['slider-names']) && strpos($x2, "%")>-1){
        $out .= "function(){
          var x2s = '{$x2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x2, "%{$sn}")>-1){
            $out .= "x2s = x2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x2 = eval(mathjs(x2s)); return x2;},";
      } else {
        $out .= "{$x2},";
      }
      // y2 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y2s = '{$y2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y2, "%{$sn}")>-1){
            $out .= "y2s = y2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y2 = eval(mathjs(y1s)); return y2;}],[";
      } else {
        $out .= "{$y2}],";
      }
    }
    if (!is_array($p3)){
      $out .= $p3 . "],";
    } else{
      $x3 = $p3[0]; $y3 = $p3[1];
      $out .= "[";
      // x3 value
      if (isset($ops['slider-names']) && strpos($x3, "%")>-1){
        $out .= "function(){
          var x3s = '{$x3}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x3, "%{$sn}")>-1){
            $out .= "x3s = x3s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x3 = eval(mathjs(x3s)); return x3;},";
      } else {
        $out .= "{$x3},";
      }
      // y1 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y3s = '{$y3}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y3, "%{$sn}")>-1){
            $out .= "y3s = y3s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y3 = eval(mathjs(y3s)); return y3;}]],";
      } else {
        $out .= "{$y3}]],";
      }
    }
    // Set attributes
    $out .= "{
              highlight: {$highlight},
              fixed: {$fixed},
              strokeColor: '{$color}',
              dash: {$dash},
              strokeWidth: {$strokeWidth},
              name: " . (isset($ops['name']) ? $ops['name'] : "''") . ",
              lastArrow: true,
              straightFirst: false
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']});";
    } else {
      $out .= ";";
    }
      // Append new output string to the board string{
      return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addCircle ##########
  function JSXG_addCircle($board, $ops=array(), $ref=null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    $p1 = isset($ops['position'][0]) ? $ops['position'][0] : [3,0];
    $p2 = isset($ops['position'][1]) ? $ops['position'][1] : [0,0];

    // Make some default values

    // attributes
    $highlight = isset($ops['highlight']) ? $ops['highlight'] : "false";
    $fixed = isset($ops['fixed']) ? $ops['fixed'] : "true";
    $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : 'black';
    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $strokeWidth = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2;
    $out = '';

    if ($ref!==null){
      $out = "
      var circ_{$label}_{$ref} = ";
    }
    $out .= "board_{$label}.create('circle', [";
    if (!is_array($p1)){
      $out .= $p1 . ",";
    } else {
      $x1 = $p1[0]; $y1 = $p1[1];
      $out .= "[";
      // x1 value
      if (isset($ops['slider-names']) && strpos($x1, "%")>-1){
        $out .= "function(){
          var x1s = '{$x1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x1, "%{$sn}")>-1){
            $out .= "x1s = x1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x1 = eval(mathjs(x1s)); return x1;},";
      } else {
        $out .= "{$x1},";
      }
      // y1 value
      if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
        $out .= "function(){
          var y1s = '{$y1}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y1, "%{$sn}")>-1){
            $out .= "y1s = y1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y1 = eval(mathjs(y1s)); return y1;}],[";
      } else {
        $out .= "{$y1}],";
      }
    }
    // Radius or second point
    if (!is_array($p2)){ // If radius provided...
      if (is_float($p2)){ // raw radius provided
        $out .= $p2 . "],";
      } elseif (strpos($p2, "%")>-1){ // Slider value provided
        $out .= "function(){
          var rs = '{$p2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($p2, "%{$sn}")>-1){
            $out .= "rs = rs.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var r = eval(mathjs(rs)); return r;}],";
      } else { // Anything else... like function(){return P.X();}, etc.
        $out .= $p2 . "],";
      }
    } else{ // If point provided
      $x2 = $p2[0]; $y2 = $p2[1];
      $out .= "[";
      // x2 value
      if (isset($ops['slider-names']) && strpos($x2, "%")>-1){
        $out .= "function(){
          var x2s = '{$x2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($x2, "%{$sn}")>-1){
            $out .= "x2s = x2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var x2 = eval(mathjs(x2s)); return x2;},";
      } else {
        $out .= "{$x2},";
      }
      // y2 value
      if (isset($ops['slider-names']) && strpos($y2, "%")>-1){
        $out .= "function(){
          var y2s = '{$y2}';";
        foreach($ops['slider-names'] as $sn){
          if (strpos($y2, "%{$sn}")>-1){
            $out .= "y2s = y2s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
          }
        }
        $out .= "with (Math) var y2 = eval(mathjs(y2s)); return y2;}]],";
      } else {
        $out .= "{$y2}]],";
      }
    }

    // Set attributes and close up shop.
    $out .= "{
              highlight: {$highlight},
              fixed: {$fixed},
              strokeColor: '{$color}',
              dash: {$dash},
              strokeWidth: {$strokeWidth},
              name: " . (isset($ops['name']) ? $ops['name'] : "''") . "
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']});";
    } else {
      $out .= ";";
    }

      // Append new output string to the board string{
      return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addPolygon ##########
  function JSXG_addPolygon($board, $ops=array(), $ref=null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    $points = isset($ops['position']) ? $ops['position'] : [[-1,-1],[1,-1],[1,1],[-1,1]];

    // Make some default values

    // attributes
    $highlight = isset($ops['highlight']) ? $ops['highlight'] : "false";
    $fixed = isset($ops['fixed']) ? $ops['fixed'] : "true";
    $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : 'black';
    $fillColor = isset($ops['fillColor']) ? $ops['fillColor'] : 'white';
    $fillOpacity = isset($ops['fillOpacity']) ? $ops['fillOpacity'] : 0;

    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $strokeWidth = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2;
    $out = '';

    if ($ref!==null){
      $out = "
      var poly_{$label}_{$ref} = ";
    }
    $out .= "board_{$label}.create('polygon', [";
    $numPoints = count($points);
    for ($i=0; $i<$numPoints; $i++){
      $point = $points[$i];
      if (!is_array($point)){ // draggable point provided
        $out .= $point;
      } else {  // point with coordinates specified
        $x1 = $point[0]; $y1 = $point[1];
        $out .= "[";
        // x1 value
        if (isset($ops['slider-names']) && strpos($x1, "%")>-1){
          $out .= "function(){
            var x1s = '{$x1}';";
          foreach($ops['slider-names'] as $sn){
            if (strpos($x1, "%{$sn}")>-1){
              $out .= "x1s = x1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
            }
          }
          $out .= "with (Math) var x1 = eval(mathjs(x1s)); return x1;},";
        } else {
          $out .= "{$x1},";
        }
        // y1 value
        if (isset($ops['slider-names']) && strpos($y1, "%")>-1){
          $out .= "function(){
            var y1s = '{$y1}';";
          foreach($ops['slider-names'] as $sn){
            if (strpos($y1, "%{$sn}")>-1){
              $out .= "y1s = y1s.replace(/%{$sn}/g, '('+param{$label}_{$sn}.Value()+')');";
            }
          }
          $out .= "with (Math) var y1 = eval(mathjs(y1s)); return y1;}]";
        } else {
          $out .= "{$y1}]";
        }
      }
      // trailing comma except for last point
      if ($i != $numPoints){ $out .= ",";}
    }
    $out .= "],";

    // Set attributes and close up shop.
    $out .= "{
              highlight: {$highlight},
              fixed: {$fixed},
              fillColor: '{$fillColor}',
              fillOpacity: {$fillOpacity},
              vertices: {
                showInfobox: false,
                fixed:true,
                highlight:false,
                color: 'black',
                size: 2
              },
              borders: {
                fixed: {$fixed},
                highlight: {$highlight},
                showInfobox: false,
                strokeColor: '{$color}',
                strokeWidth: {$strokeWidth},
                dash: {$dash}
              }
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']});";
    } else {
      $out .= ";";
    }
      // Append new output string to the board string{
      return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }

  #####################################
  ######### JSXG_addGlider ##########
  function JSXG_addGlider($board, $ops=array(), $ref=null){
    // Get Label string -- so we know how to link elements
    $labStart = strpos($board, "jxgboard_") + 9;
    $labEnd = strpos($board, "'", $labStart);
    $label = substr($board, $labStart, $labEnd - $labStart);

    $x = isset($ops['position'][0]) ? $ops['position'][0] : 0;
    $y = isset($ops['position'][1]) ? $ops['position'][1] : 0;
    $obj = isset($ops['position'][2]) ? $ops['position'][2] : '';

    // Make some default values

    // attributes
    $color = isset($ops['strokeColor']) ? $ops['strokeColor'] : 'purple';
    $size = isset($ops['size']) ? $ops['size'] : 2;
    $face = isset($ops['face']) ? $ops['face'] : 'circle';

    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $strokeWidth = isset($ops['strokeWidth']) ? $ops['strokeWidth'] : 2;
    $out = '';

    // If answerbox option provided, set up box number
    if(isset($ops['answerbox']) ){
      if (count($ops['answerbox'])==1){
        $box = $ops['answerbox'][0] - 1;
      } else {
        $box = $ops['answerbox'][0]*1000 + $ops['answerbox'][1];
      }
      $ref = ($ref!==null ? $ref : $box);
    }

    if ($ref!==null){
      $out = "
      var glider_{$label}_{$ref} = ";
    }
    $out .= "board_{$label}.create('glider', [";
    $out .= "{$x}, {$y}, {$obj}],";

    // Set attributes
    $out .= "{
              highlight: true,
              showInfobox: false,
              fixed: false,
              color: '{$color}',
              size: {$size},
              face: '{$face}',
              label: {color:'{$color}', useMathJax:true},
              name: '" . (isset($ops['name']) ? $ops['name'] : "") . "'
            })";
    if (isset($ops['attributes'])){
      $out .= ".setAttribute({$ops['attributes']})";
    }

      // // If answerbox option provided, link the point to the answerbox
      if(isset($ops['answerbox']) ){
        $out .= ".on('up',function(){
          $('#qn{$box}, #tc{$box}').val('('+this.X().toFixed(4)+','+this.Y().toFixed(4)+')')
            .trigger('change').trigger('input');
        });";
        // Change border color of JSXG board based off of answerbox color
        // (Green if correct, red if wrong, etc.)
        // Have to do this on an interval, since the answerbox might not be loaded when script called
        $out .= "
          var colorInterval{$label}_{$box} = setInterval(function(){
            if ($('#qn{$box}')[0] || $('#qn{$box}')[0]){
              if ($('#qn{$box}, #tc{$box}').is('.ansgrn')){
                $('#jxgboard_{$label}').css('border', '1px solid #0f0');
              } else if ($('#qn{$box}, #tc{$box}').is('.ansred') || $('#qn{$box}, #tc{$box}').is('.ansyel')){
                $('#jxgboard_{$label}').css('border','1px solid #f00');
              }
              /* Pull in answer from answerbox is possible */
              if ($('#qn{$box}')[0] && $('#qn{$box}').val() !== ''){
                var coords = $('#qn{$box}').val();
                coords = coords.substring(1, coords.length - 2);
                coords = coords.split(',');
                glider_{$label}_{$ref}.setPosition(JXG.COORDS_BY_USER, [parseFloat(coords[0]),parseFloat(coords[1])]);
                board_{$label}.fullUpdate();
              } else if ($('#tc{$box}')[0] && $('#tc{$box}').val() !== ''){
                var coords = $('#tc{$box}').val();
                coords = coords.substring(1, coords.length - 2);
                coords = coords.split(',');
                glider_{$label}_{$ref}.setPosition(JXG.COORDS_BY_USER, [parseFloat(coords[0]),parseFloat(coords[1])]);
                board_{$label}.fullUpdate();
              }
              clearInterval(colorInterval{$label}_{$box});
            }
          }, 300);
        ";
      } else {
        $out .= ";";
      }

      // Append new output string to the board string{
      return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
  }


	function JSXG_addTangent($board, $ops=array(), $ref=null) {
	
		// Get Label string -- so we know how to link elements
		$labStart = strpos($board, "jxgboard_") + 9;
		$labEnd = strpos($board, "'", $labStart);
		$label = substr($board, $labStart, $labEnd - $labStart);
	
		 // Defaults for visual apperance/name
		$color = isset($ops['color']) ? $ops['color'] : 'blue';
		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$width = isset($ops['width']) ? $ops['width'] : 2;

		$visible = !isset($ops['visible']) ?  'true' : $ops['visible'];
		
		// You must have a glider to attach a tangent to, if this isn't set then exit
	    $obj = isset($ops['glider']) ? $ops['glider'] : '';
        $out = '';

		if($obj !== '') {
				
			// If $ref provided, then give the object a name
			if ($ref !== null) {
				$out .= " var tangent_{$label}_{$ref} = ";
			}
			
			// Create the tangent line
			$out .= "board_{$label}.create('tangent', [ {$obj} ], { ";
			
			// Add any attributes that were specified
			
			// -- The name field is a bit more complex so that math 
			//    notation can be used; f'(x) needs wrapped in "f'(x)" for instance
			$out .= 'name: "' . (isset($ops['label']) ? $ops['label'] : "") . '",';
			
			$out .= "
				strokeColor: '{$color}',
				dash: {$dash},
				strokeWidth: {$width},
				visible: {$visible},
				withLabel: true,
				fixed: false,
				highlight: false,
				label: { color:'{$color}', useMathJax: true }
			})";
			if (isset($ops['attributes'])) {
				$out .= ".setAttribute({$ops['attributes']});";
			} else {
				$out .= ";";
			}
	
			// Append new output string to the board string{
			return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
			
		} else {
			echo "Eek! You must provide the name of a glider to attach the tangent line to.";
			return $board;
		}

	}

	function JSXG_setAttribute($board, $obj, $parameters) {
	
		// Get Label string -- so we know how to link elements
		$labStart = strpos($board, "jxgboard_") + 9;
		$labEnd = strpos($board, "'", $labStart);
		$label = substr($board, $labStart, $labEnd - $labStart);
			
		$out = "{$obj}.setAttribute( ".$parameters." );";
	
		// Append new output string to the board string{
		return substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);

	}

?>
