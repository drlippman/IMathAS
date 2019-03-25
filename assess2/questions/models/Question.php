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
    private $solutionContent;
    private $solutionContentDetailed;
    private $answerEntryTips;
    private $correctAnswersForParts;
    private $externalReferences;

    private $errors = array();

    /**
     * Question constructor.
     *
     * @param string $questionContent The generated question text.
     * @param string $solutionContent The question's solution.
     * @param string $solutionContentDetailed The solution text displayed in popups.
     * @param array $answerEntryTips Answer box (input fields) entry tips.
     * @param array $correctAnswersForParts (for formative quizzes only)
     * @param string $externalReferences Video links.
     */
    public function __construct(
        string $questionContent,
        string $solutionContent,
        string $solutionContentDetailed,
        array $answerEntryTips,
        array $correctAnswersForParts,
        string $externalReferences
    )
    {
        $this->questionContent = $questionContent;
        $this->solutionContent = $solutionContent;
        $this->solutionContentDetailed = $solutionContentDetailed;
        $this->answerEntryTips = $answerEntryTips;
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
     * Get all entry tips for answer boxes.
     *
     * @return array
     */
    public function getAnswerEntryTips(): array
    {
        return $this->answerEntryTips;
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
     *
     * @return string
     */
    public function getExternalReferences(): string
    {
        return $this->externalReferences;
    }
}
