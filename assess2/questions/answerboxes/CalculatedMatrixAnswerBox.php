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

        $out = '';
        $tip = '';
        $sa = '';
        $preview = '';
        $params = [];

        $optionkeys = ['ansprompt', 'answersize', 'answerboxsize', 'hidepreview', 'answerformat',
            'answer', 'reqdecimals', 'displayformat', 'readerlabel'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        $ansformats = array_map('trim', explode(',', $answerformat));

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}

        if (!empty($ansprompt) && !in_array('nosoln', $ansformats) && !in_array('nosolninf', $ansformats)) {
            $out .= $ansprompt;
        }
        if (!empty($answersize)) {
            list($tip, $shorttip) = formathint(_('each element of the matrix'), $ansformats, ($reqdecimals !== '') ? $reqdecimals : null, 'calcmatrix', false, true);
            //$tip = "Enter each element of the matrix as  number (like 5, -3, 2.2) or as a calculation (like 5/3, 2^3, 5+4)";

            if (empty($answerboxsize)) {$answerboxsize = 3;}
            $answersize = explode(",", $answersize);
            if (isset($GLOBALS['capturechoices'])) {
                $GLOBALS['answersize'][$qn] = $answersize;
            }
            if ($colorbox == '') {
                $out .= '<div id="qnwrap' . $qn . '">';
            } else {
                $out .= '<div class="' . $colorbox . '" id="qnwrap' . $qn . '">';
            }
            $out .= '<table>';
            if ($displayformat == 'det') {
                $out .= '<tr><td class="matrixdetleft">&nbsp;</td><td>';
            } else {
                $out .= '<tr><td class="matrixleft">&nbsp;</td><td>';
            }
            $arialabel = $this->answerBoxParams->getQuestionIdentifierString() .
                (!empty($readerlabel) ? ' ' . Sanitize::encodeStringForDisplay($readerlabel) : '');
            $out .= '<table role="group" aria-label="' . $arialabel . '">';
            $count = 0;
            $las = explode("|", $la);
            $cellcnt = $answersize[0] * $answersize[1];
            for ($row = 0; $row < $answersize[0]; $row++) {
                $out .= "<tr>";
                for ($col = 0; $col < $answersize[1]; $col++) {
                    $out .= '<td>';

                    $attributes = [
                        'type' => 'text',
                        'size' => $answerboxsize,
                        'name' => "qn$qn-$count",
                        'id' => "qn$qn-$count",
                        'value' => ($las[$count] ?? ''),
                        'autocomplete' => 'off',
                    ];

                    $params['matrixsize'] = $answersize;

                    $out .= '<input ' .
                    'aria-label="' . sprintf(_('Cell %d of %d'), $count + 1, $cellcnt) . '" ' .
                    Sanitize::generateAttributeString($attributes) .
                        '" />';

                    $out .= "</td>\n";
                    $count++;
                }
                $out .= "</tr>";
            }
            $out .= "</table>\n";
            if ($displayformat == 'det') {
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
            $shorttip = _('Enter your answer as a matrix');
            $tip = $shorttip . _(', like [(2,3,4),(1,4,5)]') . '<br/>' . formathint(_('each element of the matrix'), $ansformats, ($reqdecimals !== '') ? $reqdecimals : null, 'calcmatrix');
            if (empty($answerboxsize)) {$answerboxsize = 20;}

            $classes = ['text'];
            if ($colorbox != '') {
                $classes[] = $colorbox;
            }
            $attributes = [
                'type' => 'text',
                'size' => $answerboxsize,
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
        $params['tip'] = $shorttip;
        $params['longtip'] = $tip;
        $params['calcformat'] = $answerformat;
        if ($useeqnhelper) {
            $params['helper'] = 1;
        }

        if (in_array('nosoln', $ansformats) || in_array('nosolninf', $ansformats)) {
            list($out, $answer) = setupnosolninf($qn, $out, $answer, $ansformats, $la, $ansprompt, $colorbox);
        }

        if ($answer !== '' && !is_array($answer)) {
            $sa = '`' . $answer . '`';
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
