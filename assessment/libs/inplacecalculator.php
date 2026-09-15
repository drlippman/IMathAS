<?php
/*
 * inplacecalculator.php
 *
 * In-place fraction calculator for MathQuill answer boxes.
 * Place this file and inplacecalculator.js in assessment/libs.
 *
 * In Common Control:
 *     loadlibrary("inplacecalculator")
 *     $calculator = inplacecalculator()
 * and in Question Text put $calculator wherever the calculator should appear.
 *
 * inplacecalculator() renders the input box, the "Ctrl <enter>" button and the
 * output box. It may be used more than once on a page; each button fills the
 * boxes of the widget it sits in.
 *
 * inplacecalc() renders a "#" button on its own, with no boxes. Any number of
 * these may be placed in a question, typically just ahead of an answer box.
 *
 * inplacecalcload() loads the script and styles without rendering any button,
 * for a question that calls reduce() from its own code rather than through a
 * button. The two functions above load them anyway, so it is not needed with
 * either of them.
 *
 * Selecting part of an expression in an answer box and pressing ctrl-enter, or
 * clicking either button, replaces the selection with its reduced value. The
 * full button also shows the selection in the input box and the reduced value
 * in the output box; ctrl-enter and the "#" buttons leave the boxes alone.
 */

global $allowedmacros;
array_push($allowedmacros, "inplacecalculator", "inplacecalc", "inplacecalcload");

/*
 * The script tag. It is written with every button rather than once per page:
 * a question that redraws its text after a submit must get it back, and the
 * script itself does nothing on a second execution. The styles live in the
 * script, so they cannot be lost in a redraw either.
 */
function inplacecalculator_assets() {
  global $imasroot;
  $root = isset($imasroot) ? $imasroot : '';
  return '<script src="' . $root . '/assessment/libs/inplacecalculator.js"></script>';
}

/*
 * Loads the script and styles with no button. Only needed by a question that
 * uses window.InPlaceCalculator directly and places no calculator button.
 */
function inplacecalcload() {
  return inplacecalculator_assets();
}

/*
 * The full calculator: input box, button, output box.
 */
function inplacecalculator() {
  $out = inplacecalculator_assets();
  $out .= <<<'HTML'
<table class="inplacebutton"><tr><td>
<input class="ratIn" type="text" size="17" value="">
<button type="button" class="ratGo">Ctrl &crarr;</button>
<input class="ratOut" type="text" size="7" readonly>
</td></tr></table>
HTML;
  return $out;
}

/*
 * A minimal button, with no input or output boxes. Any number may be used.
 * An optional label replaces the default "#".
 */
function inplacecalc($label = '#') {
  $out = inplacecalculator_assets();
  $safe = htmlspecialchars($label, ENT_QUOTES);
  $out .= '<button type="button" class="ratGo2">' . $safe . '</button>';
  return $out;
}
