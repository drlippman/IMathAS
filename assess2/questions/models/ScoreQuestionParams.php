<?php

namespace IMathAS\assess2\questions\models;

class ScoreQuestionParams
{
    private $userRights;        // Orig: $GLOBALS['myrights']
    private $questionNumber;    // Orig: $qnidx
    private $dbQuestionSetId;   // Orig: $qidx
    private $questionSeed;      // Orig: $seed
    private $givenAnswer;       // Orig: $givenans
    private $attemptNumber;     // Orig: $attemptn
    private $qnpointval;        // Orig: $qnpointval

    /**
     * The user's rights. (from imas_users table, "rights" column)
     * While this is not related directly to the question, this value is
     * used during question scoring somewhere. FIXME: Note where it's used?
     *
     * @return int The user's rights.
     */
    public function getUserRights(): ?int
    {
        return $this->userRights;
    }

    /**
     * The user's rights. (from imas_users table, "rights" column)
     * While this is not related directly to the question, this value is
     * used during question scoring somewhere. FIXME: Note where it's used?
     *
     * @param int $userRights The user's rights.
     * @return ScoreQuestionParams
     */
    public function setUserRights($userRights): ScoreQuestionParams
    {
        $this->userRights = $userRights;
        return $this;
    }

    /**
     * Get the question number as displayed to the user.
     *
     * @return int The question number.
     */
    public function getQuestionNumber(): ?int
    {
        return $this->questionNumber;
    }

    /**
     * Set the question number as displayed to the user.
     *
     * @param int $questionNumber The question number.
     * @return ScoreQuestionParams
     */
    public function setQuestionNumber(?int $questionNumber): ScoreQuestionParams
    {
        $this->questionNumber = $questionNumber;
        return $this;
    }

    /**
     * Get the question's database ID. (from table: imas_questionset)
     *
     * @return int The question's database ID.
     */
    public function getDbQuestionSetId(): ?int
    {
        return $this->dbQuestionSetId;
    }

    /**
     * Set the question's database ID. (from table: imas_questionset)
     *
     * @param int $dbQuestionSetId The question's database ID.
     * @return ScoreQuestionParams
     */
    public function setDbQuestionSetId(?int $dbQuestionSetId): ScoreQuestionParams
    {
        $this->dbQuestionSetId = $dbQuestionSetId;
        return $this;
    }

    /**
     * Get the question's RNG seed.
     *
     * @return int The question's RNG seed.
     */
    public function getQuestionSeed(): ?int
    {
        return $this->questionSeed;
    }

    /**
     * Set the question's RNG seed.
     *
     * @param int $questionSeed The question's RNG seed.
     * @return ScoreQuestionParams
     */
    public function setQuestionSeed(?int $questionSeed): ScoreQuestionParams
    {
        $this->questionSeed = $questionSeed;
        return $this;
    }

    /**
     * FIXME: Need a description.
     *
     * @return string
     */
    public function getGivenAnswer(): ?string
    {
        return $this->givenAnswer;
    }

    /**
     * FIXME: Need a description.
     *
     * @param string $givenAnswer
     * @return ScoreQuestionParams
     */
    public function setGivenAnswer($givenAnswer): ScoreQuestionParams
    {
        $this->givenAnswer = $givenAnswer;
        return $this;
    }

    /**
     * Get the student's attempt number.
     *
     * @return int The student's attempt number.
     */
    public function getAttemptNumber(): ?int
    {
        return $this->attemptNumber;
    }

    /**
     * Set the student's attempt number.
     *
     * @param int $attemptNumber The student's attempt number.
     * @return ScoreQuestionParams
     */
    public function setAttemptNumber($attemptNumber): ScoreQuestionParams
    {
        $this->attemptNumber = $attemptNumber;
        return $this;
    }

    /**
     * FIXME: Need a description.
     *
     * @return int
     */
    public function getQnpointval(): ?int
    {
        return $this->qnpointval;
    }

    /**
     * FIXME: Need a description.
     *
     * @param int $qnpointval
     * @return ScoreQuestionParams
     */
    public function setQnpointval($qnpointval): ScoreQuestionParams
    {
        $this->qnpointval = $qnpointval;
        return $this;
    }
}
