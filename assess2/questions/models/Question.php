<?php

namespace IMathAS\assess2\questions\models;

/**
 * Class QuestionComponents Represents all components of a generated question
 *                          for display to a user.
 *
 * Question components returned:
 * - The question itself (may include previews)
 * - Answers for question part answer boxes
 * - Answer entry tips
 * - External references
 */
class Question
{
    private $questionContent;
    private $jsParams;
    private $answerPartWeights;
    private $solutionContent;
    private $solutionContentDetailed;
    private $answerEntryTips;
    private $correctAnswersForParts;
    private $externalReferences;
    private $extraData;
    private $questionLastMod = 0;

    private $errors = array();

    /**
     * Question constructor.
     *
     * @param string $questionContent The generated question text.
     * @param array $jsParams  The generated question javascript params.
     * @param array $answerPartWeights  The answer weights per part
     * @param string $solutionContent The question's solution.
     * @param string $solutionContentDetailed The solution text displayed in popups.
     * @param array $correctAnswersForParts (for formative quizzes only)
     * @param array $externalReferences Video links.
     */
    public function __construct(
        string $questionContent,
        array $jsParams,
        array $answerPartWeights,
        string $solutionContent,
        string $solutionContentDetailed,
        array $correctAnswersForParts,
        array $externalReferences
    )
    {
        $this->questionContent = $questionContent;
        $this->jsParams = $jsParams;
        $this->answerPartWeights = $answerPartWeights;
        $this->solutionContent = $solutionContent;
        $this->solutionContentDetailed = $solutionContentDetailed;
        $this->correctAnswersForParts = $correctAnswersForParts;
        $this->externalReferences = $externalReferences;
    }

    /**
     * Determine if any errors were encountered during question generation.
     *
     * @return bool True if errors exist, false if not.
     */
    public function hasErrors(): bool
    {
        return (count($this->getErrors()) > 0);
    }

    /**
     * Get any errors encountered during question generation, if any.
     *
     * @return array An array of errors, if any.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add to the array of errors encountered during question generation.
     *
     * @param array $errors Errors encountered during question generation.
     */
    public function addErrors(array $errors): void
    {
        $this->errors = array_merge($this->errors, $errors);
    }

    /**
     * Get (only) the question content.
     *
     * @return string
     */
    public function getQuestionContent(): string
    {
        return $this->questionContent;
    }

    /**
     * Get (only) the jsParams.
     *
     * @return string
     */
    public function getJsParams(): array
    {
        return $this->jsParams;
    }

    /**
     * Get array of answer part weights.
     *
     * @return array
     */
    public function getAnswerPartWeights(): array
    {
        return $this->answerPartWeights;
    }

    /**
     * Get the question's solution content.
     *
     * @return string
     */
    public function getSolutionContent(): string
    {
        return $this->solutionContent;
    }

    /**
     * Get the question's detailed solution content.
     *
     * @return string
     */
    public function getSolutionContentDetailed(): string
    {
        return $this->solutionContentDetailed;
    }

    /**
     * The correct answers that are displayed when the "Show Answer" button
     * is clicked. (for formative quizzes)
     *
     * Note: Original variable name was $showanspt.
     *
     * @return array
     */
    public function getCorrectAnswersForParts(): array
    {
        return $this->correctAnswersForParts;
    }

    /**
     * Get all external references.
     * @param string $format   'list' (def) for array, 'html' for html of buttons
     * @return string|array
     */
    public function getExternalReferences($format = 'list')
    {
        if ($format === 'list') {
          return $this->externalReferences;
        } else if ($format === 'html') {
          $out = '<div><p class="tips">' . _('Get help: ');
          foreach ($this->externalReferences as $extref) {
            if ($extref['label'] == 'video') {
              $label = _('Video');
            } else if ($extref['label'] == 'read') {
              $label = _('Read');
            } else if ($extref['label'] == 'ex') {
              $label = _('Written Example');
            } else {
              $label = $extref['label'];
            }
            $out .= formpopup($label, $extref['url'], $extref['w'], $extref['h'], "button", true, "help", $extref['ref']);
          }
          $out .= '</p></div>';
          return $out;
        }
    }

    /**
     * Get extra question data.
     *
     * @return array|null An associative array of extra question data.
     */
    public function getExtraData(): ?array
    {
        return $this->extraData;
    }

    /**
     * Set extra, arbitrary question data. This data will NOT be saved to the database.
     *
     * Note: Namespaced keys are recommended to avoid data collisions.
     *       Example value for $extraData:
     *                            [
     *                              'mydomain' => [
     *                                              'count' => 42
     *                                            ]
     *                            ]
     *
     * @param array|null $extraData An associative array of extra question data.
     * @return Question An instance of self.
     */
    public function setExtraData(?array $extraData): Question
    {
        $this->extraData = $extraData;
        return $this;
    }

    /**
     * Get last date question was modified.
     *
     * @return int timestamp.
     */
    public function getQuestionLastMod(): int
    {
        return $this->questionLastMod;
    }

    /**
     * Set last date question was modified.
     * @param int timestamp
     */
    public function setQuestionLastMod($time): void
    {
        $this->questionLastMod = intval($time);
    }
}
