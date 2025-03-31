<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/AnswerBox.php';

use Sanitize;

class CalculatedMatrixAnswerBox implements AnswerBox
{
    private $answerBoxParams;

    private $answerBox;
    private $jsParams;
    private $entryTip;
    private $correctAnswerForPart;
    private $previewLocation;

    public function __construct(AnswerBoxParams $answerBoxParams)
    {
        $this->answerBoxParams = $answerBoxParams;
    }

    public function generate(): void
    {
        global $RND, $myrights, $useeqnhelper, $showtips, $imasroot;

        $anstype = $this->answerBoxParams->getAnswerType();
        $qn = $this->answerBoxParams->getQuestionNumber();
        $multi = $this->answerBoxParams->getIsMultiPartQuestion();
        $partnum = $this->answerBoxParams->getQuestionPartNumber();
        $la = $this->answerBoxParams->getStudentLastAnswers();
        $options = $this->answerBoxParams->getQuestionWriterVars();
        $colorbox = $this->answerBoxParams->getColorboxKeyword();
        $isConditional = $this->answerBoxParams->getIsConditional();

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        $optionkeys = ['ansprompt', 'answersize', 'answerboxsize', 'hidepreview', 'answerformat',
            'answer', 'reqdecimals', 'displayformat', 'readerlabel'];
        if ($anstype === 'algmatrix') {
            array_push($optionkeys, 'domain', 'variables');
        }
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        $ansformats = array_map('trim', explode(',', $answerformat));
        $dispformats = array_map('trim', explode(',', $displayformat));

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if (!empty($ansprompt) && !in_array('nosoln', $ansformats) && !in_array('nosolninf', $ansformats)) {
            $out .= $ansprompt;
        }
        if (!empty($answersize)) {
            if ($anstype === 'algmatrix') {
                $shorttip = _('Enter an algebraic expression');
                $tip = _('Enter each element of the matrix as an algebraic expression. Example: 3x^2, x/5, (a+b)/c');
            } else {
                list($tip, $shorttip) = formathint(_('each element of the matrix'), $ansformats, ($reqdecimals !== '') ? $reqdecimals : null, $anstype, false, true);
            }
            //$tip = "Enter each element of the matrix as  number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)";

            if (empty($answerboxsize)) {$answerboxsize = 3;}
            $answersize = explode(",", $answersize);
            if (isset($GLOBALS['capturechoices'])) {
                $GLOBALS['answersize'][$qn] = $answersize;
            }
            if (in_array('inline', $dispformats)) {
                $style = ' style="display:inline-block;vertical-align:middle"';
            } else {
                $style = '';
            }
            if ($colorbox == '') {
                $out .= '<div id="qnwrap' . $qn . '"' . $style . '>';
            } else {
                $out .= '<div class="' . $colorbox . '" id="qnwrap' . $qn . '"' . $style . '>';
            }
            $out .= '<table>';
            if (in_array('det', $dispformats)) {
                $out .= '<tr><td class="matrixdetleft">&nbsp;</td><td style="padding:0px">';
            } else {
                $out .= '<tr><td class="matrixleft">&nbsp;</td><td style="padding:0px">';
            }
            
            $arialabel = $this->answerBoxParams->getQuestionIdentifierString() .
                ' ' . sprintf(_('matrix entry with %d rows and %d columns'), $answersize[0], $answersize[1]) .
                (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '');
            $out .= '<table role="group" aria-label="' . $arialabel . '">';
            $count = 0;
            $las = explode("|", $la);
            $cellcnt = $answersize[0] * $answersize[1];
            $augcolumn = -1;
            if (in_array('augmented', $dispformats)) {
                $augcolumn = $answersize[1] - 1;
            }
            for ($row = 0; $row < $answersize[0]; $row++) {
                $out .= "<tr>";
                for ($col = 0; $col < $answersize[1]; $col++) {
                    if ($col == $augcolumn) {
                        $out .= '<td style="border-left: 1px solid #000">';
                    } else {
                        $out .= '<td>';
                    }

                    $attributes = [
                        'type' => 'text',
                        'style' => 'width:'.sizeToCSS($answerboxsize),
                        'name' => "qn$qn-$count",
                        'id' => "qn$qn-$count",
                        'value' => ($las[$count] ?? ''),
                        'autocomplete' => 'off',
                    ];

                    $params['matrixsize'] = $answersize;

                    $out .= '<input ' .
                        Sanitize::generateAttributeString($attributes) .
                        '" />';

                    $out .= "</td>\n";
                    $count++;
                }
                $out .= "</tr>";
            }
            $out .= "</table>\n";
            if (in_array('det', $dispformats)) {
                $out .= '</td><td class="matrixdetright">&nbsp;</td></tr></table>';
            } else {
                $out .= '</td><td class="matrixright">&nbsp;</td></tr></table>';
            }
            $out .= "</div>\n";
        } else {
            if ($multi == 0) {
                $qnref = "$qn-0";
            } else {
                $qnref = ($multi - 1) . '-' . ($qn % 1000);
            }
            if ($anstype === 'calccomplexmatrix') {
                $shorttip = _('Enter a matrix of complex numbers');
                $tip = $shorttip . _(', like [(2+i,3,i),(2-i,4,5)]') . '<br/>' . formathint(_('each element of the matrix'), $ansformats, ($reqdecimals !== '') ? $reqdecimals : null, $anstype);
            } else if ($anstype === 'algmatrix') {
                $shorttip = _('Enter a matrix of algebraic expressions');
                $tip = $shorttip . _(', like [(x,2,x^2),(1,3x,5)]');
            } else {
                $shorttip = _('Enter your answer as a matrix');
                $tip = $shorttip . _(', like [(2,3,4),(1,4,5)]') . '<br/>' . formathint(_('each element of the matrix'), $ansformats, ($reqdecimals !== '') ? $reqdecimals : null, $anstype);
            }
            if (empty($answerboxsize)) {$answerboxsize = 20;}

            $classes = ['text'];
            if ($colorbox != '') {
                $classes[] = $colorbox;
            }
            $attributes = [
                'type' => 'text',
                'style' => 'width:'.sizeToCSS($answerboxsize),
                'name' => "qn$qn",
                'id' => "qn$qn",
                'value' => $la,
                'autocomplete' => 'off',
                'aria-label' => $this->answerBoxParams->getQuestionIdentifierString() .
                (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : ''),
            ];

            $out .= '<input ' .
            Sanitize::generateAttributeString($attributes) .
            'class="' . implode(' ', $classes) .
                '" />';
        }
        if (empty($hidepreview)) {
            $params['preview'] = 1;
            $preview .= '<button type=button class=btn id="pbtn' . $qn . '">';
            $preview .= _('Preview') . ' <span class="sr-only">' . $this->answerBoxParams->getQuestionIdentifierString() . '</span>';
            $preview .= '</button> &nbsp;';
        }
        $preview .= "<span id=p$qn></span> ";
        if ($anstype === 'algmatrix' && in_array('generalcomplex', $ansformats)) {
            $tip .= '<br>'._('Your answer can contain complex numbers.');
        }
        $params['tip'] = $shorttip;
        $params['longtip'] = $tip;
        $params['calcformat'] = $answerformat;
        if ($useeqnhelper) {
            $params['helper'] = 1;
        }

        if ($anstype === 'algmatrix') {
            if (empty($variables)) {$variables = "x";}
            $addvars = [];
            if (in_array('generalcomplex', $ansformats)) {
                $addvars[] = 'i';
            }
            list($variables, $ofunc, $newdomain, $restrictvartoint) = numfuncParseVarsDomain($variables, $domain, $addvars);
    
            $params['vars'] = $variables;
            $params['fvars'] = $ofunc;
            $params['domain'] = $newdomain;
        }

        $nosolntype = 0;
        if (in_array('nosoln', $ansformats) || in_array('nosolninf', $ansformats)) {
            list($out, $answer,$nosolntype) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
        }

        if ($answer !== '' && !is_array($answer) && !$isConditional) {
            if ($nosolntype > 0) {
                $sa = $answer;
            } else {
                $sa = '`' . $answer . '`';
            }
        }

        // Done!
        $this->answerBox = $out;
        $this->jsParams = $params;
        $this->entryTip = $tip;
        $this->correctAnswerForPart = (string) $sa;
        $this->previewLocation = $preview;
    }

    public function getAnswerBox(): string
    {
        return $this->answerBox;
    }

    public function getJsParams(): array
    {
        return $this->jsParams;
    }

    public function getEntryTip(): string
    {
        return $this->entryTip;
    }

    public function getCorrectAnswerForPart(): string
    {
        return $this->correctAnswerForPart;
    }

    public function getPreviewLocation(): string
    {
        return $this->previewLocation;
    }
}
