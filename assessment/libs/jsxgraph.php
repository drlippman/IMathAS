<?php
// JSXGraph interface for iMathias 2020 version 1.0
// by David Flenner
//
// adapted from JSXG.php by Grant Sandler


// https://www.onemathematicalcat.org/JSXGraphDocs/JSXGraphDocs.htm

// - Points: moveTo for animation?
// - Buttons, checkboxes?
// - Functions that allows the execution of javascript statements from the php code
// - Angle value should be able to be put in an answerbox

// - Mass create points

// - Allow user to indicate which function should be placed in the answerbox
// - Is it possible to have a function that just takes a string input of javascript code to insert?
// - What about a function to execute JessieCode?
// - Can I attach a picture to a problem and reference it within the JSX as an image object?

global $allowedmacros;

$allowedmacros[] = "jsxBoard";
$allowedmacros[] = "jsxSlider";
$allowedmacros[] = "jsxPoint";
$allowedmacros[] = "jsxGlider";
$allowedmacros[] = "jsxIntersection";
$allowedmacros[] = "jsxFunction";
$allowedmacros[] = "jsxParametric";
$allowedmacros[] = "jsxPolar";
$allowedmacros[] = "jsxText";
$allowedmacros[] = "jsxCircle";
$allowedmacros[] = "jsxLine";
$allowedmacros[] = "jsxSegment";
$allowedmacros[] = "jsxRay";
$allowedmacros[] = "jsxVector";
$allowedmacros[] = "jsxAngle";
$allowedmacros[] = "jsxPolygon";
$allowedmacros[] = "jsxTangent";
$allowedmacros[] = "jsxIntegral";
$allowedmacros[] = "jsxRiemannSum";
$allowedmacros[] = "jsx_getXCoord";
$allowedmacros[] = "jsx_getYCoord";
$allowedmacros[] = "jsx_getCoords";
$allowedmacros[] = "jsxSuspendUpdate";
$allowedmacros[] = "jsxUnsuspendUpdate";
$allowedmacros[] = "jsxSetChild";

function jsx_getlibrarylink() {
	return "//cdn.jsdelivr.net/npm/jsxgraph@1.4.5/distrib/jsxgraphcore.js";
}

function jsx_idlen() {
	return 13;
}

function jsx_validobjects() {
	$ob1 = ['point', 'slider', 'function', 'circle', 'polygon'];
	$ob2 = ['line', 'segment', 'ray', 'vector', 'text', 'angle'];
	$ob3 = ['glider', 'tangent', 'polar', 'parametric', 'integral'];
	$ob4 = ['riemannsum', 'intersection'];
	return array_merge($ob1, $ob2, $ob3, $ob4);
}

###########################################################################
##
## Basic structure for an object builder
##
###########################################################################

function jsxObject (&$board, $param, $ops=array()) {

	$id = "object_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values

	if (!$inputerror) {

		// Set default values
		

		// Begin object creation

		$out = "window.{$id} = board_{$boardID}.create('object', [";
		


		// Set attributes 
		
		$out .= "{

		})";
		
		if (!empty($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "error message";
	}
}



###########################################################################
##
## The following items are basic elemental items in that they contain basic
##   aspects that can be placed in an answerbox for grading.
##
## The elemental objects are:
##    Points, Sliders, Angles, and any kind of derivative point such as
##      Gliders (PointOnObject), Midpoints, Intersections, etc.
##
###########################################################################

########################################################################################
##
##  Adds a slider to a jsx board, which allows manipulation of the value of a variable.
##
##		$param = [min, max, step]
##
##  Position is automatically determined unless coordinates are specified, the position
##		will not change with zooms and pans.
##
##  Parameters may not be other jsx objects or properties.
##
########################################################################################

function jsxSlider (&$board, $param, $ops=array()) {

	$id = "slider_".uniqid();
	$boardID = jsx_getboardname($board);
	$nSliders = jsx_getslidercount($board) + 1;

	// $param - [min, max, step, default]
	
	if (!is_array($param) || !(count($param) == 3 || count($param) == 4)) {
		echo "Eek! jsxSlider requires an array with three or four inputs: [min, max, step, <i>defaultval</i>]";
	} else {

		$min = is_numeric($param[0]) ? $param[0] : 0;
		$max = is_numeric($param[1]) ? $param[1] : 10;
		$step = is_numeric($param[2]) ? $param[2] : 1;
		$defaultval = isset($param[3]) ? $param[3] : ($min + $max) / 2;
		
		// Parameters:
		//  label, color, default value, showLabel, LabelSize, decimals, position,
		//  answerbox
	
		$haslabel = !empty($ops['label']) ? 'true' : 'false';
		$label = !empty($ops['label']) ? $ops['label'] : '';
		$color = !empty($ops['color']) ? $ops['color'] : 'purple';
		$showlabel = !empty($ops['showlabel']) ? jsx_getbool($ops['showlabel']) : 'true';
		$fontsize = !empty($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = !empty($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$name = !empty($ops['name']) ? $ops['name'] : '';
		$decimals = !empty($ops['decimals']) ? $ops['decimals'] : 0;
		
		if (!isset($ops['position'])) {
			
			$relpos = true;
			$x1 = 0.05;
			$y1 = 0.05 * $nSliders;
			$x2 = 0.25;
			$y2 = 0.05 * $nSliders;
			
		} else {
			
			$relpos = false;
			$x1 = isset($ops['position'][0][0]) ? $ops['position'][0][0] : 1;
			$y1 = isset($ops['position'][0][1]) ? $ops['position'][0][1] : 1 * $nSliders;
			$x2 = isset($ops['position'][1][0]) ? $ops['position'][1][0] : 5;
			$y2 = isset($ops['position'][1][1]) ? $ops['position'][1][1] : 1 * $nSliders;
			
		}
		
		// Create the slider
		$out = "window.{$id} = board_{$boardID}.create('slider', [";
		
		// Set the positioning
		if ($relpos) {
			$out .= "[function() { return board_{$boardID}.getBoundingBox()[0] + {$x1} * (board_{$boardID}.getBoundingBox()[2] - board_{$boardID}.getBoundingBox()[0]); }, 
					  function() { return board_{$boardID}.getBoundingBox()[1] - {$y1} * (board_{$boardID}.getBoundingBox()[1] - board_{$boardID}.getBoundingBox()[3]); }],
					 [function() { return board_{$boardID}.getBoundingBox()[0] + {$x2} * (board_{$boardID}.getBoundingBox()[2] - board_{$boardID}.getBoundingBox()[0]); }, 
					  function() { return board_{$boardID}.getBoundingBox()[1] - {$y2} * (board_{$boardID}.getBoundingBox()[1] - board_{$boardID}.getBoundingBox()[3]); }],";
		} else {
			$out .= "[{$x1},{$y1}] , [{$x2},{$y2}],";
		}
		
		// Set the parameters
		$out .= " [{$min},{$defaultval},{$max}]],
			{
				snapWidth: {$step},
				precision: {$decimals},
				baseline: { fixed: true, highlight: false },
				ticks: { fixed: true, highlight: false} ,
				highline: { highlight: false, strokeColor: '{$color}' },
				strokeColor: '{$color}',
				name: '{$name}',
				label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
			})";
			
		// Set any remaining paramenters specified by user
		$out .= isset($ops['attributes']) ? ".setAttribute( {$ops['attributes']} )" : "";
			
		// Now handle the answerbox communication

		if (isset($ops['answerbox'])) {
			if (count($ops['answerbox']) == 1) { 
				$box = $ops['answerbox'][0] - 1;
			} else {
				$box = $ops['answerbox'][0] * 1000 + $ops['answerbox'][1];
			}
			
			// Add event listener
			$out .= ".on('up',function() { $('#qn{$box}, #tc{$box}').val(this.Value().toFixed(8));	});";
			$out .= jsx_getcolorinterval($boardID, $box, $id, "slider", [$min, $max]); 
	
		} else {
			$out .= ";";
		}

		$out .= jsx_setlabel($id, $label);

		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
		return $id;
		
	}

}

// Creats a point on a jsx board that can be moved around by the user.
// The point's coordinates can be saved in an answer box for grading.

function jsxPoint(&$board, $param, $ops=array()) {
	
	$id = "point_".uniqid();
	$boardID = jsx_getboardname($board);
	$out = "";
	
	if (is_jsxpoint($param)) {
		
		// Parameter: $fixed, $color, $size, $face, $label, $visible
				
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'false';
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$size = isset($ops['size']) ? $ops['size'] : 2;
		$face = isset($ops['face']) ? $ops['face'] : 'circle';
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';
		$snapSizeX = isset($ops['xsnapsize']) ? $ops['xsnapsize'] : 1;
		$snapSizeY = isset($ops['ysnapsize']) ? $ops['ysnapsize'] : 1;
		$trace = isset($ops['trace']) ? jsx_getbool($ops['trace']) : 'false';
		
		if ((isset($ops['xsnapsize'])) || (isset($ops['ysnapsize']))) {
			$snapToGrid = 'true';
		} else {
			$snapToGrid = 'false';
		}
			
		// Start making the point
		$out = "window.{$id} = board_{$boardID}.create('point', ".jsx_pointToJS($param).",";
		
		// Set the attributes
		
		$out .= "{
			highlight: !{$fixed},
			showInfobox: false,
			fixed: {$fixed},
			color: '{$color}',
			size: {$size},
			face: '{$face}',
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false },
			name: '',
			trace: {$trace},
			visible: {$visible},
			snapSizeX: {$snapSizeX},
			snapSizeY: {$snapSizeY},
			snapToGrid: {$snapToGrid}
		})";
		
		// Set any remaining paramenters specified by user
		$out .= isset($ops['attributes']) ? ".setAttribute( {$ops['attributes']} )" : "";
		
		// If answerbox option provided, set up box number
		if (isset($ops['answerbox'])) {
			
			if (count($ops['answerbox']) == 1) { 
				$box = $ops['answerbox'][0] - 1;
			} else {
				$box = $ops['answerbox'][0] * 1000 + $ops['answerbox'][1];
			}

			$answerfill = "'(' + this.X().toFixed(4) + ',' + this.Y().toFixed(4) + ')'";
			
			$out .= ".on('up', function() {	$('#qn{$box}, #tc{$box}').val({$answerfill}); } );";  
			$out .= jsx_getcolorinterval($boardID, $box, $id, "point");
				
		} else {
			$out .= ";";
		}     
		
		$out .= jsx_setlabel($id, $label);
		
	} else {
		echo "Eek! parameters for a point must include the board to place it on
			  and the coordinates as an array.<br>";
	}
	
	// Append new output string to the board string
	$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
	return $id;
	
}

// Creates a point that can be moved around the plane, but its movement is
// restricted to a curve. JSX normally called this a 'glider'.

function jsxGlider (&$board, $param, $ops=array()) {
	
	$id = "glider_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values
	if (!is_array($param) && count($param) != 2) {
		$inputerror = true;
	} else if(!is_jsxpoint($param[0]) || !is_jsxobjectref($param[1])) {
		$inputerror = true;
	}

	if (!$inputerror) {

		// Set default values
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'false';
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$size = isset($ops['size']) ? $ops['size'] : 2;
		$face = isset($ops['face']) ? $ops['face'] : 'circle';
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$snapSizeX = isset($ops['xsnapsize']) ? $ops['xsnapsize'] : 1;
		$snapSizeY = isset($ops['ysnapsize']) ? $ops['ysnapsize'] : 1;
		$trace = isset($ops['trace']) ? jsx_getbool($ops['trace']) : 'false';
		
		if ((isset($ops['xsnapsize'])) || (isset($ops['ysnapsize']))) {
			$snapToGrid = 'true';
		} else {
			$snapToGrid = 'false';
		}

		// Begin object creation
		$out = "window.{$id} = board_{$boardID}.create('glider', [";
		$out .= jsx_valueToJS($param[0][0]).", ";
		$out .= jsx_valueToJS($param[0][1]).", ";
		$out .= "{$param[1]}], ";

		// Set attributes 
		$out .= "{
            highlight: !{$fixed},
            showInfobox: false,
			fixed: {$fixed},
			trace: {$trace},
            color: '{$color}',
            size: {$size},
            face: '{$face}',
			name: '',
            label: { color: '{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false },
			snapSizeX: {$snapSizeX},
			snapSizeY: {$snapSizeY},
			snapToGrid: {$snapToGrid}
        })";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']})";
		} 
		
		// If answerbox option provided, link the point to the answerbox
		if (isset($ops['answerbox'])) {
			
			if (count($ops['answerbox']) == 1) {
				$box = $ops['answerbox'][0] - 1;
			} else {
				$box = $ops['answerbox'][0] * 1000 + $ops['answerbox'][1];
			}

			$answerfill = "'(' + this.X().toFixed(4) + ',' + this.Y().toFixed(4) + ')'";		
			$out .= ".on('up', function() {	$('#qn{$box}, #tc{$box}').val({$answerfill}); } );";
					
			$out .= jsx_getcolorinterval($boardID, $box, $id, "point"); 
				
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! To attach a point on another object, you must provide a location, and the object.";
	}

}

// Creats a point at the intersection of two objects

function jsxIntersection(&$board, $param, $ops=array()) {
	
	$id = "intersection_".uniqid();
	$boardID = jsx_getboardname($board);
	$out = "";
	
	if (is_array($param) && (is_jsxcircleref($param[0]) || is_jsxcircleref($param[1]) || is_jsxlineref($param[0]) || is_jsxlineref($param[1]))) {
		
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$size = isset($ops['size']) ? $ops['size'] : 2;
		$face = isset($ops['face']) ? $ops['face'] : 'circle';
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';
		$trace = isset($ops['trace']) ? jsx_getbool($ops['trace']) : 'false';
		$negativeroot = isset($ops['negativeroot']) ? jsx_getbool($ops['negativeroot']) : 'false';
			
		// Start making the point
		$out = "window.{$id} = board_{$boardID}.create('intersection', [{$param[0]}, {$param[1]}, ";
		
		$out .= $negativeroot == 'true' ? "1]," : "0],";
		
		// Set the attributes
		
		$out .= "{
			showInfobox: false,
			color: '{$color}',
			size: {$size},
			face: '{$face}',
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false },
			name: '',
			trace: {$trace},
			visible: {$visible}
		})";
		
		// Set any remaining paramenters specified by user
		$out .= isset($ops['attributes']) ? ".setAttribute( {$ops['attributes']} )" : "";
		
		// If answerbox option provided, set up box number
		if (isset($ops['answerbox'])) {
			
			if (count($ops['answerbox']) == 1) { 
				$box = $ops['answerbox'][0] - 1;
			} else {
				$box = $ops['answerbox'][0] * 1000 + $ops['answerbox'][1];
			}

			$answerfill = "'(' + this.X().toFixed(4) + ',' + this.Y().toFixed(4) + ')'";
			
			$out .= ".on('up', function() {	$('#qn{$box}, #tc{$box}').val({$answerfill}); } );";  
			$out .= jsx_getcolorinterval($boardID, $box, $id, "point");
				
		} else {
			$out .= ";";
		}     
		
		$out .= jsx_setlabel($id, $label);
		
	} else {
		echo "Eek! parameters for an intersection must include the board to place it on
			  and two parameters that are either circles or lines to intersect.<br>";
	}
	
	// Append new output string to the board string
	$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
	return $id;
	
}


function jsxCircle(&$board, $param, $ops=array()) {
	
	$id = "circle_".uniqid();
	$boardID = jsx_getboardname($board);
	$out = "window.{$id} = ";
	$is_error = false;

    // attributes
    $highlight = isset($ops['highlight']) ? jsx_getbool($ops['highlight']) : 'false';
    $fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'true';
    $color = isset($ops['color']) ? $ops['color'] : 'black';
    $dash = isset($ops['dash']) ? $ops['dash'] : 0;
    $width = isset($ops['width']) ? $ops['width'] : 2;
	$haslabel = isset($ops['label']) ? 'true' : 'false';
	$label = isset($ops['label']) ? $ops['label'] : '';
	$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
	$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
	$fillcolor = isset($ops['fillcolor']) ? $ops['fillcolor'] : null;
	$fillopacity = isset($ops['fillopacity']) ? $ops['fillopacity'] : 0.3;
	$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';

	if (count($param) == 3 && is_jsxpoint($param[0]) && is_jsxpoint($param[1]) && is_jsxpoint($param[2])) {
		// Circle through three points
		$out .= "board_{$boardID}.create('circle', [";
		$out .= jsx_pointToJS($param[0]).", ";
		$out .= jsx_pointToJS($param[1]).", ";
		$out .= jsx_pointToJS($param[2])."], ";
		
	} else if (count($param) == 2 && is_jsxpoint($param[0]) && is_jsxpoint($param[1])) {
		
		// Circle with center and point
		$out .= "board_{$boardID}.create('circle', [";
		$out .= jsx_pointToJS($param[0]).", ".jsx_pointToJS($param[1])."], ";

	} else if (count($param) == 2 && is_jsxpoint($param[0]) && is_jsxvalue($param[1])) {
		// Circle with center and radius
		$out .= "board_{$boardID}.create('circle', [";
		$out .= jsx_pointToJS($param[0]).", ".jsx_valueToJS($param[1])."], ";
		
	} else {
		echo "Invalid parameters for circle, use: [[x, y], r], [[x1, y1], [x2, y2]], or give three points";
		$is_error = true;
	}
	
	if ($is_error == false) {
		// Set attributes and close up shop.
		$out .= "{
			highlight: {$highlight},
            fixed: {$fixed},
            strokeColor: '{$color}',
            dash: {$dash},
            strokeWidth: {$width},
			visible: {$visible},
			withLabel: {$haslabel},
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
		";
		if ($fillcolor != null) {
			$out .= ",fillColor: '{$fillcolor}', fillOpacity: {$fillopacity} })";
        } else {
			$out .= '})';
		}
		if (isset($ops['attributes'])) {
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);
		
	}
	
	$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
	return $id;

}

###########################################################################
##
## Adds a polygon on the jsx board
##
###########################################################################

function jsxPolygon (&$board, $param, $ops=array()) {

	$id = "polygon_".uniqid();
	$boardID = jsx_getboardname($board);
	$out = "";	
	$inputerror = false;

	// Validate input values

	if (!is_array($param) || count($param) < 3) {
		$inputerror = true;
	} else {
		foreach($param as $item) {
			if (!is_jsxpoint($item)) {
				$inputerror = true;
			}
		}
	}

	if (!$inputerror) {

		// Set default values
		
		$highlight = isset($ops['highlight']) ? jsx_getbool($ops['highlight']) : 'false';
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'true';
		$color = isset($ops['color']) ? $ops['color'] : 'blue';
		$fillcolor = isset($ops['fillcolor']) ? $ops['fillcolor'] : 'blue';
		$fillopacity = isset($ops['fillopacity']) ? $ops['fillopacity'] : 0.4;
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;

		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$width = isset($ops['width']) ? $ops['width'] : 2;

		// Begin object creation

		$out .= "window.{$id} = board_{$boardID}.create('polygon', [";
		
		$count = 1;
		$total = count($param);
		
		foreach($param as $point) {
			$out .= jsx_pointToJS($point);
			$count < $total ? $out .= ", " : $out .= "], ";
			$count += 1;
		}

		// Set attributes 
		
		$out .= "{
			highlight: {$highlight},
			fixed: {$fixed},
			fillColor: '{$fillcolor}',
			fillOpacity: {$fillopacity},
			visible: {$visible},
			vertices: {
				showInfobox: false,
				fixed: true,
				highlight: false,
				visible: false
			},
			borders: {
				fixed: {$fixed},
				highlight: {$highlight},
				showInfobox: false,
				strokeColor: '{$color}',
				strokeWidth: {$width},
				dash: {$dash}
			},
			withLabel: {$haslabel},
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
		})";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! Invalid parameters for polygon object, an array of at least three points expected.";
	}
}

###########################################################################
##
## Function to draw a line on a JSX board
##
###########################################################################

function jsxLine (&$board, $param, $ops=array()) {

	$id = "line_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values

	if (!is_array($param) || count($param) != 2) {
		$inputerror = true;
	} else {
		foreach($param as $item) {
			if (!is_jsxpoint($item)) {
				$inputerror = true;
			}
		}
	}

	if (!$inputerror) {

		// Set default values
		$highlight = isset($ops['highlight']) ? jsx_getbool($ops['highlight']) : 'false';
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'true';
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$width = isset($ops['width']) ? $ops['width'] : 2;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';

		// Begin object creation

		$out = "window.{$id} = board_{$boardID}.create('line', [";
		$out .= jsx_pointToJS($param[0]).", ";
		$out .= jsx_pointToJS($param[1])."],";

		// Set attributes 
		
		$out .= "{
            highlight: {$highlight},
            fixed: {$fixed},
            strokeColor: '{$color}',
            dash: {$dash},
            strokeWidth: {$width},
			withLabel: {$haslabel},
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false },
			visible: {$visible}
		})";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}

		$out .= jsx_setlabel($id, $label);	

		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! Invalid parameters for JSX Line. Start point and end point expected.";
	}
}  

###########################################################################
##
## Function to draw a segment on a JSX board
##
###########################################################################

function jsxSegment (&$board, $param, $ops=array()) {

	$id = "segment_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values
	if (!is_array($param) || count($param) != 2) {
		$inputerror = true;
	} else {
		foreach($param as $item) {
			if (!is_jsxpoint($item)) {
				$inputerror = true;
			}
		}
	}

	if (!$inputerror) {

		// Set default values
		$highlight = isset($ops['highlight']) ? jsx_getbool($ops['highlight']) : 'false';
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'true';
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$width = isset($ops['width']) ? $ops['width'] : 2;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';
		$length = isset($ops['length']) ? $ops['length'] : -1;

		// Begin object creation

		$out = "window.{$id} = board_{$boardID}.create('segment', [";
		$out .= jsx_pointToJS($param[0]).", ";
		$out .= jsx_pointToJS($param[1]);
		$out .= $length == -1 ? "]," : ",{$length}],";

		//	name: '" .$label. "',

		// Set attributes 
		$out .= "{
            highlight: {$highlight},
            fixed: {$fixed},
            strokeColor: '{$color}',
            dash: {$dash},
            strokeWidth: {$width},
			visible: {$visible},
			withLabel: {$haslabel},
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
        })";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
				
		$out .= jsx_setlabel($id, $label);
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! Invalid parameters for JSX segment. Start point and end point expected.";
	}
}

###########################################################################
##
## Draws a ray extending from point $param[0] through point $param[1]
##
###########################################################################

function jsxRay (&$board, $param, $ops=array()) {

	$id = "ray_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values
	if (!is_array($param) || count($param) != 2) {
		$inputerror = true;
	} else {
		foreach($param as $item) {
			if (!is_jsxpoint($item)) {
				$inputerror = true;
			}
		}
	}

	if (!$inputerror) {

		// Set default values
		$highlight = isset($ops['highlight']) ? jsx_getbool($ops['highlight']) : 'false';
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'true';
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$width = isset($ops['width']) ? $ops['width'] : 2;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';

		// Begin object creation

		$out = "window.{$id} = board_{$boardID}.create('line', [";
		$out .= jsx_pointToJS($param[0]).", ";
		$out .= jsx_pointToJS($param[1])."],";

		// Set attributes 
		
		$out .= "{
			highlight: {$highlight},
            fixed: {$fixed},
            strokeColor: '{$color}',
            dash: {$dash},
            strokeWidth: {$width},
			visible: {$visible},
			withLabel: {$haslabel},
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false },
            lastArrow: true,
            straightFirst: false
        })";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);	
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! Invalid parameters for JSX ray. Two points expected.";
	}
}  


// Draws a 2D vector, represented as an arrow starting at point $param[0]
//   and terminates at point $param[1], or as a magnitude and direction
//   given in radians. In the second form, the vector will be in standard
//   position.

function jsxVector (&$board, $param, $ops=array()) {

	$id = "vector_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values
	if (is_jsxpoint($param[0]) && is_jsxpoint($param[1])) {
		$point0 = $param[0];
		$point1 = $param[1];
	} else if (is_jsxvalue($param[0]) && is_jsxvalue($param[1])) {
		$r = $param[0];
		$t = $param[1];
		$offset = isset($ops['offset']) ? $ops['offset'] : 'false';
		if ($offset !== 'false') {
			$point0 = ["$offset[0]", "$offset[1]"]; 
			$point1 = ["$offset[0] + $r * Math.cos($t)", "$offset[1] + $r * Math.sin($t)"];
		} else {
			$point0 = [0, 0]; 
			$point1 = ["$r * Math.cos($t)", "$r * Math.sin($t)"];
		}
	} else {
		$inputerror = true;
	}

	if (!$inputerror) {

		// Set default values
		$highlight = isset($ops['highlight']) ? jsx_getbool($ops['highlight']) : 'false';
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'true';
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$width = isset($ops['width']) ? $ops['width'] : 2;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;

		// Begin object creation
		$out = "window.{$id} = board_{$boardID}.create('arrow', [";
		$out .= jsx_pointToJS($point0).", ";
		$out .= jsx_pointToJS($point1)."],";	

		// Set attributes 
		$out .= "{
            highlight: {$highlight},
            fixed: {$fixed},
            strokeColor: '{$color}',
            strokeWidth: {$width},
            dash: {$dash},
			withLabel: {$haslabel},
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
        })";		
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);	
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! Invalid parameters for JSX vector. Initial and terminal points expected.";
	}
}  

// Draws a tangent line to a curve at a glider point.
// Param:
//    - reference to glider that is already attached to a curve
// Ops:
//    - color, dash, width, visible, label

function jsxTangent(&$board, $param, $ops=array()) {

	$id = "tangent_".uniqid();
	$boardID = jsx_getboardname($board);
	
	// Validate input values
	if (strpos($param, "glider_") !== false) { 

		// Set default values
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$width = isset($ops['width']) ? $ops['width'] : 2;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = !empty($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';
		
		// Begin object creation
		$out = "window.{$id} = board_{$boardID}.create('tangent', [ {$param} ], { ";

		// Set Attributes
		$out .= "
			strokeColor: '{$color}',
			dash: {$dash},
			strokeWidth: {$width},
			visible: {$visible},
			withLabel: {$haslabel},
			fixed: false,
			highlight: false,
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
		})";
		
		if (isset($ops['attributes'])) {
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}

		$out .= jsx_setlabel($id, $label);

		// Append new output string to the board string{
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! You must provide the name of a glider to attach the tangent line to.";
	}

}

// Creates an integral object, which allows to shade the area trapped between 
// a curve and the x-axis. $param[0] is an array of a starting x-value and 
// ending x-value for the integral, these can be float values or references
// to other jsx objects. If float values are provided, then a static integral
// will be created, if jsx objects are provided, then the integral will be
// interactable. $param[1] is a function created by jsx to find the area
// under. 

function jsxIntegral (&$board, $param, $ops=array()) {

	$id = "integral_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values
	if(!is_jsxpoint($param[0]) || !is_jsxobjectref($param[1])) {
		$inputerror = true;
	}

	if (!$inputerror) {

		// Set default values
		$color = isset($ops['color']) ? $ops['color'] : 'blue';
		$fillopacity = isset($ops['fillopacity']) ? $ops['fillopacity'] : 0.4;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;

		// Begin object creation
		$out = "window.{$id} = board_{$boardID}.create('integral', [";
		$out .= jsx_pointToJS($param[0]).", ";
		$out .= $param[1]."],";

		// Set attributes 
		$out .= "{
			color: '{$color}',
			fillOpacity: {$fillopacity},
			withLabel: {$haslabel},
			fixed: true,
			highlight: false,
			curveLeft: { visible: false },
			curveRight: { visible: false },
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
		})";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! jsxIntegral expects two inputs: the range for the integral [a, b] and a funtion f.";
	}
}

// Creates a Riemann sum trapped between a function and the x-axis, or between two
// different functions. The parameters expected are passed in an array and are: 
// an array containing the lower endpoint and upper endpoint of the interval and
// the number of rectangles to use. Each of these items can be numeric that will
// make them static. You can also use references to other jsx objects in order to
// make them dynamic. The second parameter can be a single function in order to
// create rectangles between the x-axis and the function, or an array of functions
// which will draw the rectanges between the functions. The return is an object 
// reference that can be used in the creation of other jsx objects.

function jsxRiemannSum (&$board, $param, $ops=array()) {

	$id = "riemannsum_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values
	if(!is_array($param[0]) || count($param[0]) != 3) {
		$inputerror = true;
	} 
	if(!is_jsxvalue($param[1])) {
		if(!is_jsxpoint($param[1])) {
			$inputerror = true;
		}
	}

	if (!$inputerror) {

		// Set default values
		$type = isset($ops['type']) ? $ops['type'] : 'left';
		$inputvariable = isset($ops['inputvariable']) ? $ops['inputvariable'] : 'x';
		$color = isset($ops['color']) ? $ops['color'] : 'blue';
		$fillcolor = isset($ops['fillcolor']) ? $ops['fillcolor'] : $color;
		$fillopacity = isset($ops['fillopacity']) ? $ops['fillopacity'] : 0.4;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;

		// Begin object creation

		$out = "window.{$id} = board_{$boardID}.create('riemannsum', [";
		
		if(is_array($param[1])) {
			if(strpos($param[1][0],"_") != false) {
				$out .= "[{$param[1][0]}_text,";
			} else {
				$out .= "[".jsx_functionToJS($param[1][0], $inputvariable).",";
			}
			if(strpos($param[1][1],"_") != false) {
				$out .= "{$param[1][1]}_text],";
			} else {
				$out .= jsx_functionToJS($param[1][1], $inputvariable)."],";
			}
		} else {
			// Check to see if the function is a reference to a jsxFunction
			if(strpos($param[1],"_") != false) {
				$out .= "{$param[1]}_text,";
			} else {
				$out .= jsx_functionToJS($param[1], $inputvariable).",";
			}
		}
		
		$out .= jsx_valueToJS($param[0][2]).", ";
		$out .= "'{$type}', ";
		$out .= jsx_valueToJS($param[0][0]).", ";
		$out .= jsx_valueToJS($param[0][1])."],";


		// Set attributes 
		
		$out .= "{
			strokeColor: '{$color}',
			fillColor: '{$fillcolor}',
			fillOpacity: {$fillopacity},
			withLabel: {$haslabel},
			fixed: true,
			highlight: false,
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
		})";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! jsxReimannSum expects an array like [a, b, n] for the first input, and either a
			function or an array of two functions for the second.";
	}
}


###########################################################################
##
## Creates a text object to place on a JSX board
##
###########################################################################

function jsxText (&$board, $param, $ops=array()) {

	$id = "text_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values
	if (is_jsxpoint($param[0]) && (is_jsxvalue($param[1]) || is_array($param[1]))) {
			
		// Set default values
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$highlight = isset($ops['highlight']) ? jsx_getbool($ops['highlight']) : 'false';
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'true';
		$color = isset($ops['color']) ? $ops['color'] : 'black';
		$anchor = isset($ops['anchor']) ? $ops['anchor'] : false;
		$anchorX = isset($ops['anchorX']) ? $ops['anchorX'] : false;
		$anchorY = isset($ops['anchorY']) ? $ops['anchorY'] : false;
		
		// Begin object creation

		$out = "window.{$id} = board_{$boardID}.create('text', [";
		
		if (is_array($param[0])) {
			$out .= jsx_valueToJS($param[0][0]).", ".jsx_valueToJS($param[0][1]).", ";
		} else if(is_jsxpointref($param[0])) {
			$out .= jsx_valueToJS("$param[0].X()").", ".jsx_valueToJS("$param[0].Y()").", ";
		}
		
		if (is_jsxvalue($param[1])) {
			if (count(jsx_getobjectreferences($param[1])) > 0) {
				$out .= "function() { return {$param[1]}; }],";
			} else {
				$out .= "'{$param[1]}'],";
			}
		} else if (is_array($param[1])) {
			
			$makepretty = false;
			$display = false;
			
			for ($i = 1; $i < count($param[1]); $i++) {
				
				switch ($param[1][$i]) {
					case 'makepretty' :	$makepretty = true;	break;
					case 'display' : $display = true; break;
					default:
						echo "Warning: Unknown label rendering hint encountered: {$param[1][$i]}";
				}
				
			}
			
			$label = $param[1][0];
			
			if ($label !== '') {

				if ($makepretty && !$display) {
					$out .= "function() { return jsx_clean({$label}); }],";
				} 
				
				if (!$makepretty && $display) {
					$out .= "function() { return '`' + {$label} + '`';  }],";
				}
				
				if ($makepretty && $display) {
					$out .= "function() { return '`' + jsx_clean({$label}) + '`';  }],";
				}
				
				if (!$makepretty && !$display) {
					if (count(jsx_getobjectreferences($label)) > 0) {
						$out .= "function() { return {$label}; }],";
					} else {
						$out .= "'{$label}'],";
					}
				}

			}
		
		} 

		// Set attributes 
		
		$out .= "{";
		$out .= "useMathJax: 'true',";
		$out .= "fontSize: {$fontsize},";
		$out .= "highlight: {$highlight},";
		$out .= "fixed: {$fixed},";
		$out .= "color: '{$color}',";
		if($anchor !== false) {
			$out .= "anchor: {$anchor},"; 
		}
		if($anchorX !== false) {
            $anchorX = preg_replace('/[^\w\-]/','',$anchorX);
			$out .= "anchorX: '{$anchorX}',"; 
		}
		if($anchorY !== false) {
            $anchorY = preg_replace('/[^\w\-]/','',$anchorY);
			$out .= "anchorY: '{$anchorY}',"; 
		}
        $out .= "})";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! Invalid parameters given to JSX text object. Expected: location and string.";
	}
}  
 
 
###########################################################################
##
## Creates an angle with three points, $param[1] is the center point.
##
###########################################################################

function jsxAngle (&$board, $param, $ops=array()) {

	$id = "angle_".uniqid();
	$boardID = jsx_getboardname($board);
	$inputerror = false;

	// Validate input values
	if (!is_array($param) || count($param) != 3) {
		$inputerror = true;
	} else {
		foreach($param as $item) {
			if (!is_jsxpoint($item)) {
				$inputerror = true;
			}
		}
	}

	if (!$inputerror) {

		// Set default values
		$highlight = isset($ops['highlight']) ? jsx_getbool($ops['highlight']) : 'false';
		$fixed = isset($ops['fixed']) ? jsx_getbool($ops['fixed']) : 'true';
		$color = isset($ops['color']) ? $ops['color'] : 'orange';
		$fillcolor = isset($ops['fillcolor']) ? $ops['fillcolor'] : 'orange';
		$fillopacity = isset($ops['fillopacity']) ? $ops['fillopacity'] : 0.5;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;

		// Begin object creation
		$out = "window.{$id} = board_{$boardID}.create('angle', [";
		$out .= jsx_pointToJS($param[0]).", ";
		$out .= jsx_pointToJS($param[1]).", ";
		$out .= jsx_pointToJS($param[2])."],";	

		// Set attributes 
		$out .= "{
            highlight: {$highlight},
            fixed: {$fixed},
            strokeColor: '{$color}',
			withLabel: {$haslabel},
			fillColor: '{$fillcolor}',
			fillOpacity: {$fillopacity},
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
        })";
		
		if (isset($ops['attributes'])) { 
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}

		$out .= jsx_setlabel($id, $label);

		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;
		
	} else {
		echo "Eek! Error in the parameters to JSX angle. Three points expected.";
	}
}  
 
###############################################################################
##
##  Adds a function to bhe board
##
###############################################################################

function jsxFunction(&$board, $f, $ops=array()) { 
  
	$id = "function_".uniqid();
	$boardID = jsx_getboardname($board);

	// Parameters:
	//  inputvariable, color, width, dash, domain, label
	
	// Make some default values
	$inpVar = isset($ops['inputvariable']) ? $ops['inputvariable'] : 'x'; 
	$color = isset($ops['color']) ? $ops['color'] : 'blue';
	$width = isset($ops['width']) ? $ops['width'] : 2;
	$dash = isset($ops['dash']) ? $ops['dash'] : 0;
	$haslabel = isset($ops['label']) ? 'true' : 'false';
	$label = isset($ops['label']) ? $ops['label'] : '';
	$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
	$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : '16';
	$isBounds = (isset($ops['domain']) && count($ops['domain']) == 2) ? true : false;
	$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';
  
	$objects = jsx_getobjectreferences($f);
  
	// Here we preserve the text of the function so it can be used in other objects
	// like Riemannsum
	$out = "window.{$id}_text = ".jsx_functionToJS($f, $inpVar).";";
  
	// Start the output string
	$out .= "window.{$id} = board_{$boardID}.create('functiongraph', [";
	$out .= jsx_functionToJS($f, $inpVar); 
  
	// Handle domain restriction, if provided
	if ($isBounds) { 
		$out .= ','.jsx_valueToJS($ops['domain'][0]);
		$out .= ','.jsx_valueToJS($ops['domain'][1]);
	}
	
	$out .= "]";
  
	// Handle attributes, then close up shop.
	$out .= ", {
		strokeColor: '{$color}',
		strokeWidth: {$width},
		dash: {$dash},
		fixed: true,
		highlight: false,
		visible: {$visible},
		label: { color: '{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false },
		withLabel: {$haslabel}
	})";
  
	if (isset($ops['attributes'])) {
		$out .= ".setAttribute({$ops['attributes']});";
	} else {
		$out .= ";";
	}

	$out .= jsx_setlabel($id, $label);

	// Append new output string to the board string
	$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
	return $id;
}

// Adds a parametric graph to a jsx board.
// Parameters:
//   - A set of functions defining x & y coordinate pairs
// Options:
//   - $tStart and $tEnd: begin and end values for the domain of t
//   - inputvariable: the variable used in the parametric functions
//   - color, width

function jsxParametric (&$board, $param, $ops=array()) {

	$id = "parametric_".uniqid();
	$boardID = jsx_getboardname($board);
	
	// Validate input values
	if (is_jsxpoint($param)) {

		// Set default values
		$inpVar = isset($ops['inputvariable']) ? $ops['inputvariable'] : 't'; // input variable
		$tStart = isset($ops['domain'][0]) ? $ops['domain'][0] :-10; // start value of t
		$tEnd = isset($ops['domain'][1]) ? $ops['domain'][1] : 10; // end value of t
		$color = isset($ops['color']) ? $ops['color'] : 'blue'; // color of graph
		$width = isset($ops['width']) ? $ops['width'] : 2; // width of graph
		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : $color;
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';
		
		// Begin object creation
		$out = "window.{$id} = board_{$boardID}.create('curve', [";
		$out .= jsx_functionToJS($param[0], $inpVar).", "; 
		$out .= jsx_functionToJS($param[1], $inpVar).", ";
		$out .= jsx_valueToJS($tStart).", ";
		$out .= jsx_valueToJS($tEnd)."], ";

		// Set up attributes
		$out .= "{
			strokeColor: '{$color}',
            strokeWidth: {$width},
            highlight:	false,
			dash: {$dash},
			withLabel: {$haslabel},
			visible: {$visible},
            name: '{$label}',
			label: { color: '{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
        })";
		
		if (isset($ops['attributes'])) {
			$out .= ".setAttribute({$ops['attributes']})";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);		
		
		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"),0);
		return $id;
		
	} else {
		echo "Eek! Invalid parameters for paramtric function. Expecting an array with functions for x and y.";
	}	
}

// Draws the graph of a Polar function on a jsx board
//   this is expected in the form of r = f(t)
//
// Parameters:
//   - $inputvariable: allows user to control the name of the variable used
//   - $rule: the function rule
//   - $tStart, $tEnd: the lower and upper bounds for theta
//   - $color, $width

function jsxPolar (&$board, $rule, $ops = array()) {
	
	$id = "polar_".uniqid();
	$boardID = jsx_getboardname($board);
	
	// Validate input values
	if (is_string($rule)) {

		// Set default values
		$var = isset($ops['inputvariable']) ? $ops['inputvariable'] : 't'; // input variable
		$color = isset($ops['color']) ? $ops['color'] : 'blue'; // color of graph
		$width = isset($ops['width']) ? $ops['width'] : 2; // width of graph
		$dash = isset($ops['dash']) ? $ops['dash'] : 0;
		$tStart = isset($ops['domain'][0]) ? $ops['domain'][0] : 0; // start value of t
		$tEnd = isset($ops['domain'][1]) ? $ops['domain'][1] : 6.283185307; // end value of t
		$haslabel = isset($ops['label']) ? 'true' : 'false';
		$label = isset($ops['label']) ? $ops['label'] : '';
		$fontsize = isset($ops['fontsize']) ? $ops['fontsize'] : 16;
		$fontcolor = isset($ops['fontcolor']) ? $ops['fontcolor'] : 'black';
		$visible = isset($ops['visible']) ? jsx_getbool($ops['visible']) : 'true';

		// Begin object creation
		$out = "window.{$id} = board_{$boardID}.create('curve', [";
		$out .= jsx_functionToJS($rule, $var).", [0, 0], ";
  
		$out .= jsx_valueToJS($tStart).", ";
		$out .= jsx_valueToJs($tEnd)."], ";
		
		// Set attributes
		$out .= "{
			curveType: 'polar',
			highlight: false,
			fixed: true,
			dash: {$dash},
			strokeColor: '{$color}',
			strokeWidth: {$width},
			visible: {$visible},
			name: '{$label}',
			withLabel: {$haslabel},
			label: { color:'{$fontcolor}', fontSize: {$fontsize}, useMathJax: true, highlight: false }
		})";
		
		if (isset($ops['attributes'])) {
			$out .= ".setAttribute({$ops['attributes']});";
		} else {
			$out .= ";";
		}
		
		$out .= jsx_setlabel($id, $label);		

		// Append new output string to the board string
		$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
		return $id;

	} else {
		echo "Eek! Polar graph expects at least one input for the function rule.";
	}
}

###############################################################################
##
##  The remaining functions are dedicated to creating jsx boards that objects
##    can be placed onto. The only function called by the user is: 
##    jsxBoard() with an input indicating if a geometric, rectangular,
##    or polar board should be created for use.
##
###############################################################################

function jsxBoard($type, $ops=array()) {
	//jsx_getscript();
	
	$id = uniqid();
	
	if($type == 'rectangular') {
		$board = jsx_createrectangularboard($id, $ops);
	} elseif ($type == 'polar') {
		$board = jsx_createpolarboard($id, $ops);
	} elseif ($type == 'geometry') {
		$board = jsx_creategeometryboard($id, $ops);
	}

	return $board;
	
}

// Creates a link to the jsx include file
function jsx_getscript () {
	
	if (isset($GLOBALS['assessUIver']) && $GLOBALS['assessUIver'] > 1) {
		return '<script type="text/javascript" src="https:'.jsx_getlibrarylink().'"></script>';	
	} else {
		return 
			'<script type="text/javascript">if (typeof JXG === "undefined" && typeof JXGscriptloaded === "undefined") {
			var jsxgloadscript = document.createElement("script");
			jsxgloadscript.src = "'.jsx_getlibrarylink().'";
			document.getElementsByTagName("head")[0].appendChild(jsxgloadscript);
			JXGscriptloaded = true;
			}
			function jsx_clean(exp) {
				exp = exp.replaceAll(/\+\s*\-/g, "-");
				exp = exp.replaceAll(/\-\s*\+/g, "-");
				exp = exp.replaceAll(/\-\s*\-/g, "+");
				exp = exp.replaceAll(/\+\s*\+/g, "+");
				exp = exp.replaceAll(/\*\s*1(\D|$)/g, "$1"); // x*1 = x
				exp = exp.replaceAll(/(\D|^)(1\s*\*|1\s*\**([a-zA-Z\(]))/g, "$1$3"); // 1*x = x
				exp = exp.replaceAll(/(^|[\=\(\+\-])\s*(0\s*\*?)([a-zA-Z](\^[\-\d\.]+)*)/g, "$1"); //3+0x-4 -> 3-4
				exp = exp.replaceAll(/\+\s*\-/g, "-");
				exp = exp.replaceAll(/\-\s*\+/g, "-");
				exp = exp.replaceAll(/\-\s*\-/g, "+");
				exp = exp.replaceAll(/\+\s*\+/g, "+");
				exp = exp.replaceAll(/(^|[\(])\s*0(\s*[\+\-\)])/g, "$1");  //0+x, 0-x
				exp = exp.replaceAll(/\+\s*\-/g, "-");
				exp = exp.replaceAll(/\-\s*\+/g, "-");
				exp = exp.replaceAll(/\-\s*\-/g, "+");
				exp = exp.replaceAll(/\+\s*\+/g, "+");
				exp = exp.replaceAll(/[\+\-]\s*0(\)|\D|$)/g, "$1");  // x+0, x-0
				exp = exp.replaceAll(/\+\s*\-/g, "-");
				exp = exp.replaceAll(/\-\s*\+/g, "-");
				exp = exp.replaceAll(/\-\s*\-/g, "+");
				exp = exp.replaceAll(/\+\s*\+/g, "+");
				exp = exp.replaceAll(/=\s*\+/g, "="); // =+2x -> =2x
				exp = exp.replaceAll(/\+\s*\-/g, "-");
				exp = exp.replaceAll(/\-\s*\+/g, "-");
				exp = exp.replaceAll(/\-\s*\-/g, "+");	
				exp = exp.replaceAll(/\+\s*\+/g, "+");
				if (exp[0] == "+") {
					exp = exp.substring(1);
					return exp == "" ? "0" : exp;
				}
				exp = exp.replaceAll(/^([a-zA-Z])\s*=\s*$/g,"$1=0");
				return exp;

			}
			</script>';
	}
}

// Set up a board. Auxillary functions
function jsx_setupboard ($label, $width, $height, $centered) {
	
	$cntrd = $centered === 'true' ? "margin:auto;" : "";
	$ratio = 100 * ($height / $width);
  
	// Start output string by getting script
	$out = jsx_getscript();
  
	// make board
	$out .= "<div class='jxgboardwrapper' style='max-width:{$width}px; max-height:{$height}px; {$cntrd}'>";
	$out .= "<div id='jxgboard_{$label}' style='background-color:#FFF; width:100%; height:0px; padding-bottom:{$ratio}%;'></div>";
	$out .= "</div>";
  
	// Start script
	$out .= "<script type='text/javascript'>";
  
	// We build construction function inline, push function to initstack to load async
	$out .= "function makeBoard{$label}() {";
    $out .= "try {";
	$out .= "JXG.Options.text.fontSize = 16;";
	$out .= 'JXG.Options.text.cssDefaultStyle = "font-family:Serif;";';
	$out .= 'JXG.Options.axis.lastArrow.size = 5;';
	
	// This is where new content gets inserted
	$out .= "/*INSERTHERE*/";
	
	// End of construction function. Push it to initstack
	$out .= "} catch(err){console.log(err);}";
    $out .= "}";
	$out .= "initstack.push(makeBoard{$label});";
    $out .= "</script>"; // End of script

	return $out;

}

// creates a board with no axes
function jsx_creategeometryboard($label, $ops) {
  
	// Set default values
	$width = isset($ops['size'][0]) ? $ops['size'][0] : 350; // board width
	$height = isset($ops['size'][1]) ? $ops['size'][1] : 350; // board height
	$navBar = isset($ops['navbar']) ? jsx_getbool($ops['navbar']) : 'true';
	$zoom = isset($ops['zoom']) ? jsx_getbool($ops['zoom']) : 'true';
	$pan = isset($ops['pan']) ? jsx_getbool($ops['pan']) : 'true';
	$centered = isset($ops['centered']) ? jsx_getbool($ops['centered']) : 'false';
  
	//set the min and max x-values if provided, else default to [-5, 5]
	$xmin = isset($ops['bounds'][0]) ? $ops['bounds'][0] : -5;
	$xmax = isset($ops['bounds'][1]) ? $ops['bounds'][1] : 5;
	$ymin = isset($ops['bounds'][2]) ? $ops['bounds'][2] : -5;
	$ymax = isset($ops['bounds'][3]) ? $ops['bounds'][3] : 5;

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

	$boardinit = jsx_setupboard($label, $width, $height, $centered);
	return substr_replace($boardinit, $out, strpos($boardinit, "/*INSERTHERE*/"), 0);
}


// creates a board with a rectangular set of axes on it
function jsx_createrectangularboard ($label, $ops = array()) {
	
	// Set default values
	$width = isset($ops['size'][0]) ? $ops['size'][0] : 350; // board width
	$height = isset($ops['size'][1]) ? $ops['size'][1] : 350; // board height
	$navBar = isset($ops['navbar']) ? jsx_getbool($ops['navbar']) : 'true';
	$zoom = isset($ops['zoom']) ? jsx_getbool($ops['zoom']) : 'true';
	$pan = isset($ops['pan']) ? jsx_getbool($ops['pan']) : 'true';
	$centered = isset($ops['centered']) ? jsx_getbool($ops['centered']) : 'false';
   
	//set the min and max x-values if provided, else default to [-5, 5]
	$xmin = isset($ops['bounds'][0]) ? $ops['bounds'][0] : -5;
	$xmax = isset($ops['bounds'][1]) ? $ops['bounds'][1] : 5;
	$ymin = isset($ops['bounds'][2]) ? $ops['bounds'][2] : -5;
	$ymax = isset($ops['bounds'][3]) ? $ops['bounds'][3] : 5;

	$minorTicksX = isset($ops['minorticks'][0]) ? $ops['minorticks'][0] : 0;
	$minorTicksY = isset($ops['minorticks'][1]) ? $ops['minorticks'][1] : 0;
	$ticksDistanceX = isset($ops['ticksdistance'][0]) ? $ops['ticksdistance'][0] : max(1,floor((($xmax)-($xmin))/8));
	$ticksDistanceY = isset($ops['ticksdistance'][1]) ? $ops['ticksdistance'][1] : max(1,floor((($ymax)-($ymin))/8));
	
	$showXLabels = isset($ops['showlabels'][0]) ? jsx_getbool($ops['showlabels'][0]) : 'true';
	$showYLabels = isset($ops['showlabels'][1]) ? jsx_getbool($ops['showlabels'][1]) : 'true';
	
	$gridHeightX = isset($ops['gridheight'][0]) ? $ops['gridheight'][0] : -1;
	$gridHeightY = isset($ops['gridheight'][1]) ? $ops['gridheight'][1] : -1;
	
	$minorTickHeightX = isset($ops['minortickheight'][0]) ? $ops['minortickheight'][0] : 10;
	$minorTickHeightY = isset($ops['minortickheight'][1]) ? $ops['minortickheight'][1] : 10;

	$useMathJax = (isset($ops['axislabel']) && (strpos($ops['axislabel'][0], "`") > -1 || strpos($ops['axislabel'][1], "`") > -1)) ? "true" : "false";

	// Start output
	$out = "JXG.Options.layer = { numlayers: 20, text: 9, point: 9, glider: 9, arc: 8, line: 7, circle: 6,
            curve: 5, turtle: 5, polygon: 3, sector: 3, angle: 3, integral: 3, axis: 3, ticks: 2, grid: 1, image: 0, trace: 0};";
   
	// Create the board
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

	$out .= "var xTicks{$label}, yTicks{$label};";
   
	// x-axis
	$out .= "var xaxis{$label} = board_{$label}.create('axis', [[0,0], [1,0]], {
				strokeColor:'black',
				strokeWidth: 2,
				highlight:false,
				name:'" . (isset($ops['axislabel'][0]) ? $ops['axislabel'][0] : "") . "',
				label: { 
					position: 'rt', 
					offset: [-15,15], 
					highlight: false, 
					useMathJax: {$useMathJax}
				},
				ticks: {
					insertTicks: false,
					ticksDistance: $ticksDistanceX,
					minorTicks: $minorTicksX,
					majorHeight: $gridHeightX,
					minorHeight: $minorTickHeightX,
					color: 'black',
					label: { 
						offset: [0,-5], 
						anchorY: 'top', 
						anchorX: 'middle', 
						highlight: false, 
						visible: $showXLabels
					}
				} 
            });";

	// y-axis
	$out .= "var yaxis{$label} = board_{$label}.create('axis', [[0,0],[0,1]], {
               strokeColor:'black',
               strokeWidth: 2,
               highlight:false,
               name:'" . (isset($ops['axislabel'][1]) ? $ops['axislabel'][1] : "") . "',
               withLabel:true,
               label: {position:'rt', offset:[10,-15], highlight:false, useMathJax:{$useMathJax}},
			   ticks: {
					insertTicks: false,
					ticksDistance: $ticksDistanceY,
					minorTicks: $minorTicksY,
					majorHeight: $gridHeightY,
					minorHeight: $minorTickHeightY,
					label: {
						offset: [-5,0],
						anchorY: 'middle', 
						anchorX: 'right', 
						highlight: false,
						visible: $showYLabels
					}
				} 
             });";
	  
	$boardinit = jsx_setupboard($label, $width, $height, $centered);
    return substr_replace($boardinit, $out, strpos($boardinit, "/*INSERTHERE*/"), 0);

}

// creates a set of axes, and a board to construct on.
function jsx_createpolarboard ($label, $ops=array()) {

	// Add some default values
	$size = isset($ops['size'][0]) ? $ops['size'][0] : 350;
	$navBar = isset($ops['navbar']) ? jsx_getbool($ops['navbar']) : 'true';
	$zoom = isset($ops['zoom']) ? jsx_getbool($ops['zoom']) : 'false';
	$pan = isset($ops['pan']) ? jsx_getbool($ops['pan']) : 'false';
	$centered = isset($ops['centered']) ? jsx_getbool($ops['centered']) : 'false';
   
	//set the min and max x-values if provided, else default to [-5, 5]
	$rmax = isset($ops['r'][0]) ? (float) $ops['r'][0] : 5;
	$rInc = isset($ops['r'][1]) ? (float) $ops['r'][1] : 1;
	$thetaType = isset($ops['theta'][0]) ? $ops['theta'][0] : 'radian';
	$thetaInc = isset($ops['theta'][1]) ? (float) $ops['theta'][1] : 1;

	$rMaxBoard = 1.2 * $rmax;
	$rMaxLabel = 1.1 * $rmax;

	$boardYScale = isset($ops['padtop']) ? 1.1 : 1;
	$boardHeight = $boardYScale * $size;
	$yMax = (1 + 2*($boardYScale - 1)) * $rMaxBoard;

	// Layering options. Push the axis in front of ticks
	$out = "JXG.Options.layer = { numlayers: 20, text: 9, point: 9, 
		glider: 9, arc: 8, line: 7, circle: 6, curve: 8, turtle: 5, 
		polygon: 3, sector: 3, angle: 3, integral: 3, axis: 3, ticks: 2, 
		grid: 1, image: 0, trace: 0}; ";
		
	$out .= "JXG.Options.text.fontSize = 18;";
	//$out .= "JXG.Options.text.useMathJax = true;";
   
	// Create the board
	$defaultAxis = !empty($ops['ticksdistance']) ? "false" : "true";
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
						board_{$label}.create('segment', [[0, 0], [{$rmax}*Math.cos(i*Math.PI/inc), {$rmax}*Math.sin(i*Math.PI/inc)]], {
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
						board_{$label}.create('segment', [[0, 0], [{$rmax}*Math.cos(t*Math.PI/180), {$rmax}*Math.sin(t*Math.PI/180)]], {
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
						board_{$label}.create('segment', [[0, 0], [{$rmax}*Math.cos(i*2*Math.PI/n), {$rmax}*Math.sin(i*2*Math.PI/n)]], {
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
						board_{$label}.create('segment', [[0,0], [{$rmax}*Math.cos(t), {$rmax}*Math.sin(t)]], {
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
    $boardinit = jsx_setupboard($label, $size, $boardHeight, $centered);
    return substr_replace($boardinit, $out, strpos($boardinit, "/*INSERTHERE*/"), 0);
}

// Temporarily pauses all updates to a board object so that a lot of objects
// can be drawn more quickly
function jsxSuspendUpdate(&$board) {
	$boardID = jsx_getboardname($board);
	$out = "board_{$boardID}.suspendUpdate();";
	$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
}

// Unpauses updates to a board object so that objects will become interactive
// again
function jsxUnsuspendUpdate(&$board) {
	$boardID = jsx_getboardname($board);
	$out = "board_{$boardID}.unsuspendUpdate();";
	$board = substr_replace($board, $out, strpos($board, "/*INSERTHERE*/"), 0);
}


##############################################################################
##
##  The following functions are used to make grading questions more efficient
##
##############################################################################

// Takes a point, denoted as (x,y) in string format as input and returns 
// the x coordinate of the point

function jsx_getXCoord($point) {
	// Remove all spaces from the point string
	$point = str_replace(' ', '', $point);
	$start = stringpos("(", $point) + 1;
	$end = stringpos(",", $point);
	return substr($point, $start, $end - $start);
}

// Takes a point, denoted as (x,y) in string format as input and returns 
// the y coordinate of the point

function jsx_getYCoord($point) {
	// Remove all spaces from the point string
	$point = str_replace(' ', '', $point); 
	$start = stringpos(",", $point) + 1;
	$end = stringpos(")", $point);
	return substr($point, $start, $end - $start);
}

// Takes a point, denoted as (x,y) in string format as input and returns 
// the x and y coordinates of the point as an array
function jsx_getCoords($point) {
	return [jsx_getXCoord($point), jsx_getYCoord($point)];
}

function jsxSetChild($parentBoard, &$childBoard) {
    $parentID = jsx_getboardname($parentBoard);
    $childID = jsx_getboardname($childBoard);
	$out = "board_{$parentID}.addChild(board_{$childID});";
	$childBoard = substr_replace($childBoard, $out, strpos($childBoard, "/*INSERTHERE*/"), 0);
}

###############################################################################
##
##  The following functions are internal helper functions designed to make
##    coding objects easier and less prone to error.
##
###############################################################################

// Returns true if the $item provided is a reference to an object 
// type found in the objects type list. If $strict is set to true, 
// then the function returns true if the $items is only and object
// and has no property function attached. 
// A proper object reference looks like: point_iH5j2k31aB4jk

function is_jsxobjectref($item, $strict = true) {
	
	if ($item !== null && is_string($item)) {
		if ($strict) {
			$obj_pattern = "/^\s*(".jsx_validobjectlist("|").")_\w{".jsx_idlen()."}\s*$/i";
		} else {
			$obj_pattern = "/^\s*(".jsx_validobjectlist("|").")_\w{".jsx_idlen()."}/i";
		}
		return preg_match($obj_pattern, $item);
	} else {
		return false;
	}
	
}

// Returns true if the provided object is a point reference of some
// kind of point in JSX Graph. This can be a point, a glider, a midpoint,
// the center of a circle, etc. 

function is_jsxpointref($item) {
	if ($item !== null && is_string($item)) {
		$point_pattern = "/^\s*(point|glider|intersection)_\w{".jsx_idlen()."}/i";
		return preg_match($point_pattern, $item);
	}
}

// A point will look like an array of two values. You may use numeric entries,
// php variables, a reference to another jsx point, or one of the coordinates can
// be the x or y property of another jsx point

function is_jsxpoint($item) {
	
	// Make sure we have an array of two objects: the item looks like [x, y]
	if (is_array($item) && count($item) == 2) {

		// And it has two values x and y
		if (is_jsxvalue($item[0]) && is_jsxvalue($item[1])) {
			return true;
		}
		
	}
	
	// If $item is a reference to a jsx point, then is is valid too
	if (is_jsxpointref($item)) {
		return true;
	}
		
	// If neither of those cases applies, this is not a point
	return false;
	
}

// A value will either be a numeric constanst or a string that contains a
// formula to be executed

function is_jsxvalue($item) {
	if(is_numeric($item) || is_string($item)) {
		return true;
	} else {
		return false;
	}
}

// Returns true if the provided object is a circle reference

function is_jsxcircleref($item) {
	if ($item !== null && is_string($item)) {
		$point_pattern = "/^\s*(circle)_\w{".jsx_idlen()."}/i";
		return preg_match($point_pattern, $item);
	}
}

// Returns true if the provided object is a line reference of some kind 

function is_jsxlineref($item) {
	if ($item !== null && is_string($item)) {
		$point_pattern = "/^\s*(line|tangent|segment|ray|vector)_\w{".jsx_idlen()."}/i";
		return preg_match($point_pattern, $item);
	}
}

// Returns a list of the different valid object types as a string
// separated by comma (default) or any other character 
function jsx_validobjectlist($sep = null) {
	
	$separator = is_null($sep) ? "," : $sep;
	$len = -strlen($separator);
	$str = "";
	
	foreach(jsx_validobjects() as $obj) {
		$str .= $obj . $separator;
	}
	
	return substr($str, 0, $len);
}

// Finds all the referneces to jsxobjects within a string
function jsx_getobjectreferences($str) {
	
	$idLen = jsx_idlen();
	$objs = jsx_validobjectlist("|");

	$property_pattern = "/(?:".$objs.")_\w{".jsx_idlen()."}\.\w*\(\)/i";
	$object_pattern = "/(?:".$objs.")_\w{".jsx_idlen()."}/i";
	
	// First find all the property references, like $p.X()
	preg_match_all($property_pattern, $str, $properties);
	
	// Delete all those references
	$str = preg_replace($property_pattern, "", $str);
	
	// Now look for any remaining object references with no properties
	preg_match_all($object_pattern, $str, $objects);
	
	return array_merge($properties[0], $objects[0]);

}

// Determines the board name in order to be able to create and access objects
function jsx_getboardname($board) {

	$labStart = strpos($board, "jxgboard_") + 9;
	$labEnd = strpos($board, "'", $labStart);
	$label = substr($board, $labStart, $labEnd - $labStart);
	
	return $label;
}

function jsx_getslidercount($board) {
	$slider_pattern = "/var\s+slider_\w{" . jsx_idlen() . "}/i";
	return preg_match_all($slider_pattern, $board);
}

//  Converts a point in the form [x, y], ["$p.Value() + 5", 3], $p,
//    etc., in to a piece of javascript than can be interprested.
//    Result is wrapped in [,] to define the point.

function jsx_pointToJS($point) {
	
	if (is_array($point)) {
		$js = "[".jsx_valueToJS($point[0]).", ".jsx_valueToJS($point[1])."]";
	} else if(is_jsxpointref($point)) {
		$js = $point;
	}
	return $js;
	
}


//  Converts a value encoded into a string into a piece of 
//    javascript that can be executed. Result is not wrapped
//    in any additional notation. The extra set of parenthesis 
//    around the {$obj} replacement value handle issues of two
//    negative values ocurring in a row.

function jsx_valueToJS($value) {
	
	$js = "";
	if (is_numeric($value)) {
		$js = "{$value}";
	} else {
		$objs = jsx_getobjectreferences($value);
		$js = "function() {";
		$js .= "var xs = '{$value}';";
		foreach($objs as $obj) {
			// check to see if user just sent the reference to a slider, this
			// then adds the ".Value()" function on the end for simplier syntax
			if ((strpos($obj, "slider") !== false) && (strpos($obj, "Value()") === false)) {
				$js .= "xs = xs.replace('{$obj}', '('+{$obj}.Value()+')' );";
			} else {
				$js .= "xs = xs.replace('{$obj}', '('+{$obj}+')' );";
			}
        }
        $js .= "xs = xs.replace(/(\d)e(-?\d)/g, '$1E$2');";
		$js .= "with (Math) var x = eval(mathjs(xs)); return x;";
		$js .= "}";
	}	
	return $js;
}

//  Converts a text-object encoded into a string into a piece of 
//    javascript that can be used to display values of objects on
//    a jsx board. $decimals is used to round values to a given
//    number of decimal places.

function jsx_textToJS($text, $decimals) {
	
	$js = "";

	$objs = jsx_getobjectreferences($text);
	$js = "function() {";
	$js .= "var ts = '{$text}';";
	foreach($objs as $obj) {
		// check to see if user just sent the reference to a slider, this
		// then adds the ".Value()" function on the end for simplier syntax
		if ((strpos($obj, "slider") !== false) && (strpos($obj, "Value()") === false)) {
			$js .= "ts = ts.replace('{$obj}', Math.round(Math.pow(10, {$decimals}) * {$obj}.Value()) / Math.pow(10, {$decimals}));";
		} else {
			$js .= "ts = ts.replace('{$obj}', Math.round(Math.pow(10, {$decimals}) * {$obj}) / Math.pow(10, {$decimals}));";
		}
	}
	$js .= "return ts;";
	$js .= "}";
		
	return $js;
}

// Converts a function provided as a string to a piece of javascript
//   code that can be executed. $rule is the function rule, and $var
//   is the independent variable of the function.

function jsx_functionToJS($rule, $var) {
	
	$js = "";

	$objs = jsx_getobjectreferences($rule);

	$js = "function({$var}) {";
	$js .= "var ts = '{$rule}';";
	foreach($objs as $obj) {
		// check to see if user just sent the reference to a slider, this
		// then adds the ".Value()" function on the end for simplier syntax
		if ((strpos($obj, "slider") !== false) && (strpos($obj, "Value()") === false)) {
			$js .= "ts = ts.replace('{$obj}', '('+{$obj}.Value()+')');";
		} else {
			$js .= "ts = ts.replace('{$obj}', '('+{$obj}+')');";
		}
    }
    $js .= "ts = ts.replace(/(\d)e(-?\d)/g, '$1E$2');";
	$js .= "with (Math) var result = eval(mathjs(ts, '{$var}'));
            return result; }"; 
		
	return $js;
}

// Allows user to input values of boolean or string to represent boolean
// values from the options input strings

function jsx_getbool($bool) {
	if ($bool === true || $bool === 'true') { 
		return 'true';
	} else {
		return 'false';
	}
}


// Affixes a label to a jsx object, sets it up so that it can be dynamic 
// or static. A string for the label can be passed, or an array that
// contains the label string along with some rendering hints. The possible
// rendering hints are: makepretty, display, function.
// makepretty will attempt to clean up things like 1 * x, x + 0, x + - 1
// disp will make the label show up as a rendered math function
// function will allow the user to provide a javascript function to
//   allow for high levels of customization
function jsx_setlabel($id, $label) {
	
	$js = "{$id}.label.setText('');";
	
	if (is_string($label)) {
		if ($label !== '') {
			if (strpos($label, 'this.') !== false) {
				$label = str_replace('this', $id, $label);
			}

			if (count(jsx_getobjectreferences($label)) > 0) {
				$js = "{$id}.label.setText(function() { return {$label}; });";
			} else {
				$js = "{$id}.label.setText('{$label}');";
			}
		
			return $js;
		}
	} else if (is_array($label)) {

		$makepretty = false;
		$display = false;
		
		for ($i = 1; $i < count($label); $i++) {
			
			switch ($label[$i]) {
				case 'makepretty' :	$makepretty = true;	break;
				case 'display' : $display = true; break;
				default:
					echo "Warning: Unknown label rendering hint encountered: {$label[$i]}";
			}
			
		}
		
		$label = $label[0];
		
		if ($label !== '') {
			if (strpos($label, 'this.') !== false) {
				$label = str_replace('this', $id, $label);
			}

			if ($makepretty && !$display) {
				$js = "{$id}.label.setText(function() { return jsx_clean({$label}); });";
			} 
			
			if (!$makepretty && $display) {
				$js = "{$id}.label.setText(function() { return '`' + {$label} + '`';  });";
			}
			
			if ($makepretty && $display) {
				$js = "{$id}.label.setText(function() { return '`' + jsx_clean({$label}) + '`';  });";
			}
		
			if (!$makepretty && !$display) {
				if (count(jsx_getobjectreferences($label)) > 0) {
					$js = "{$id}.label.setText(function() { return {$label}; });";
				} else {
					$js = "{$id}.label.setText('{$label}');";
				}
			}
		
			return $js;
		}
		
	}
}

// Creates a colored boarder around the board depending on whether the
// question was answered correctly or not. This function also replaces
// the object in the answerbox to its position right before the 'submit'
// button was pressed by the user.

function jsx_getcolorinterval($boardID, $box, $obj, $type, $param = array()) {

	if ($type == "point") {
		$reposition_obj = 
			"var coords = $('#qn{$box}').val();
			coords = coords.substring(1, coords.length - 2);
			coords = coords.split(',');
			{$obj}.setPosition(JXG.COORDS_BY_USER, [parseFloat(coords[0]),parseFloat(coords[1])]);";
			
	} else if ($type == "slider") {
		
		$min = $param[0];
		$max = $param[1];
		$reposition_obj = 
			"var tc = $('#qn{$box}').val();
			{$obj}.setGliderPosition(((tc)-({$min}))/(({$max})-({$min})));";
	}

	$out = 
		"var colorInterval{$boardID}_{$box} = setInterval(function() {  
			if ($('#qn{$box}')[0] || $('#qn{$box}')[0]) {
				if ($('#qn{$box}, #tc{$box}').is('.ansgrn')) {
					$('#jxgboard_{$boardID}').css('border', '1px solid #0f0');
				} else if ($('#qn{$box}, #tc{$box}').is('.ansred') || $('#qn{$box}, #tc{$box}').is('.ansyel')) {
					$('#jxgboard_{$boardID}').css('border','1px solid #f00');
				}
				/* Pull in answer from answerbox is possible */
				if ($('#qn{$box}')[0] && $('#qn{$box}').val() !== '') {
					{$reposition_obj}
					board_{$boardID}.fullUpdate();
				} else if ($('#tc{$box}')[0] && $('#tc{$box}').val() !== '') {
					{$reposition_obj}
					board_{$boardID}.fullUpdate();
				}
				clearInterval(colorInterval{$boardID}_{$box});
			}
		}, 300);";
		
	return $out;
}

?>
