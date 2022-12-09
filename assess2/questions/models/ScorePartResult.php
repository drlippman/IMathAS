<?php

namespace IMathAS\assess2\questions\models;

/**
 * Class Score Represents a student's score for a single question.
 */
class ScorePartResult
{
    private $rawScore = 0;
    private $requiresManualGrading = false;
    private $lastAnswerAsGiven = '';        // Orig: $givenans
    private $lastAnswerAsNumber = '';       // Orig: $givenansval
    private $correctAnswerWrongFormat = false; // Orig: $GLOBALS['partlastanswer'] .= '$f$1'
    private $scoreMessages = '';            // Orig:
    private $extraData;

    /**
     * The student's raw score.
     *
     * @return float
     */
    public function getRawScore(): float
    {
        return $this->rawScore;
    }

    /**
     * The student's raw score.
     *
     * @param float $rawScore
     * @return ScorePartResult
     */
    public function setRawScore(float $rawScore): ScorePartResult
    {
        $this->rawScore = $rawScore;
        return $this;
    }

    /**
     * Does this part require manual grading?
     *
     * @return bool True for yes, false for no.
     */
    public function getRequiresManualGrading(): ?bool
    {
        return $this->requiresManualGrading;
    }

    /**
     * Does this part require manual grading?
     *
     * @param bool $requiresManualGrading ue for yes, false for no.
     * @return ScorePartResult
     */
    public function setRequiresManualGrading(bool $requiresManualGrading): ScorePartResult
    {
        $this->requiresManualGrading = $requiresManualGrading;
        return $this;
    }

    /**
     * The student's last answer, as entered by the student.
     *
     * @return string|array
     */
    public function getLastAnswerAsGiven()
    {
        return $this->lastAnswerAsGiven;
    }

    /**
     * The student's last answer, as entered by the student.
     *
     * @param string|array $lastAnswerAsGiven
     * @return ScorePartResult
     */
    public function setLastAnswerAsGiven($lastAnswerAsGiven): ScorePartResult
    {
        $this->lastAnswerAsGiven = $lastAnswerAsGiven;
        return $this;
    }

    /**
     * The student's last answers, as numbers.
     *
     * @return string|array
     */
    public function getLastAnswerAsNumber()
    {
        return $this->lastAnswerAsNumber;
    }

    /**
     * The student's last answers, as numbers.
     *
     * @param string|array $lastAnswerAsNumber
     * @return ScorePartResult
     */
    public function setLastAnswerAsNumber($lastAnswerAsNumber): ScorePartResult
    {
        $this->lastAnswerAsNumber = $lastAnswerAsNumber;
        return $this;
    }

    /**
     * Get scoring messages to be displayed directly to the user.
     *
     * Messages are returned as a single string, with individual messages
     * separated by HTML break tags. ("<br/>")
     *
     * Currently only used by FileScorePart. Examples:
     *   - Successful
     *   - Error storing file
     *   - Error uploading file - file too big
     *
     * @return string
     */
    public function getScoreMessages(): ?string
    {
        return $this->scoreMessages;
    }

    /**
     * The student has provided the correct answer, but incorrect format.
     *
     * Example: Entered "0.5" instead of "1/2"
     *
     * @return bool
     */
    public function getCorrectAnswerWrongFormat(): ?bool
    {
        return $this->correctAnswerWrongFormat;
    }

    /**
     * The student has provided the correct answer, but incorrect format.
     *
     * Example: Entered "0.5" instead of "1/2"
     *
     * @param bool $correctAnswerWrongFormat
     * @return ScorePartResult
     */
    public function setCorrectAnswerWrongFormat($correctAnswerWrongFormat): ScorePartResult
    {
        $this->correctAnswerWrongFormat = $correctAnswerWrongFormat;
        return $this;
    }

    /**
     * Set scoring messages to be displayed directly to the user.
     *
     * Individual messages should be separated by HTML break tags. ("<br/>")
     *
     * Currently only used by FileScorePart. Examples:
     *   - Successful
     *   - Error storing file
     *   - Error uploading file - file too big
     *
     * @param string $scoreMessages
     * @return ScorePartResult
     * @see addScoreMessage
     */
    public function setScoreMessages(string $scoreMessages): ScorePartResult
    {
        $this->scoreMessages = $scoreMessages;
        return $this;
    }

    /**
     * Append a scoring message to be displayed directly to the user.
     *
     * Currently only used by FileScorePart.
     *
     * @param string $scoreMessage
     * @return ScorePartResult
     * @see setScoreMessages
     */
    public function addScoreMessage(string $scoreMessage): ScorePartResult
    {
        $this->scoreMessages .=
            empty($this->scoreMessage) ? $scoreMessage : '<br/>' . $scoreMessage;
        return $this;
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
     * @return ScorePartResult An instance of self.
     */
    public function setExtraData(?array $extraData): ScorePartResult
    {
        $this->extraData = $extraData;
        return $this;
    }
}
