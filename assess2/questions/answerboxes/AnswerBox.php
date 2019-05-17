<?php

namespace IMathAS\assess2\questions\answerboxes;

/**
 * Interface AnswerBoxInterface
 *
 * Notes for makeanswerbox() from displayq2.php before refactor:
 * - Answer box refers to whatever is necessary to get input for one question part.
 * - For most types that's one input tag, but it might also be a set of radio
 *   buttons, or for matrix type an array of input tags.
 * - Each call to makeanswerbox returns the HTML that includes those input fields.
 * - That's HTML gets stored in $answerbox and is later placed into the question text.
 */
interface AnswerBox
{
    /**
     * Generate answer boxes for a question.
     *
     * @param AnswerBoxParams $answerBoxParams
     */
    public function __construct(AnswerBoxParams $answerBoxParams);

    /**
     * Generate answer boxes for a question.
     */
    public function generate(): void;

    /**
     * Get all answer boxes for a question.
     *
     * @return string
     */
    public function getAnswerBox(): string;

    /**
     * Get javascript parameters for a question.
     *
     * @return string
     */
    public function getJsParams(): array;

    /**
     * Get all answer box entry tips for a question.
     *
     * @return string
     */
    public function getEntryTip(): string;

    /**
     * Get the correct answers that are displayed when the "Show Answer" button
     * is clicked. (for formative quizzes)
     *
     * Note: Original variable name was $showanspt in displayq2.php.
     *
     * @return string
     */
    public function getCorrectAnswerForPart(): string;

    /**
     * Get the locations for the answer box's preview content.
     *
     * Note: Original variable name was $previewloc in displayq2.php.
     *
     * @return string
     */
    public function getPreviewLocation(): string;
}
