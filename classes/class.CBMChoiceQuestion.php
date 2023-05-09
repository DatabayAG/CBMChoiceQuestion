<?php

declare(strict_types=1);
/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class ilCBMChoiceQuestion
 *
 * @author Marvin Beym <mbeym@databay.de>
 */
class CBMChoiceQuestion extends assQuestion
{
    /**
     * @var ilCBMChoiceQuestionPlugin
     */
    private $plugin;

    /**
     * @var bool
     */
    private $measureHidden = false;

    /**
     * @var array<int, array{answerText: string, answerCorrect: bool, answerImage: string}>
     */
    private $answers = [];
    /**
     * @var ?integer
     */
    protected $thumbSize;
    /**
     * @var int
     */
    private $answerType = 0;

    /**
     * @var bool
     */
    private $allowMultipleSelection = false;

    /**
     * @var array<string, string>
     */
    private $scoringMatrix = [];

    public function __construct($title = "", $comment = "", $author = "", $owner = -1, $question = "")
    {
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        parent::__construct($title, $comment, $author, $owner, $question);
    }

    public function getHttpParameterNameForField(string $field) : string
    {
        return 'question-' . $this->getId() . '-' . $field;
    }

    public function saveWorkingData($active_id, $pass = null, $authorized = true)
    {
        if (null === $pass) {
            $pass = ilObjTest::_getPass($active_id);
        }

        $numEnteredValues = 0;

        $this->getProcessLocker()->executeUserSolutionUpdateLockOperation(function () use (
            &$numEnteredValues,
            $active_id,
            $pass,
            $authorized
        ) {
            $this->removeCurrentSolution($active_id, $pass, $authorized);

            $solutionSubmit = $this->getSolutionSubmit();
            foreach ($solutionSubmit as $solutionName => $solutionValue) {
                $this->saveCurrentSolution(
                    $active_id,
                    $pass,
                    $solutionName,
                    $solutionValue,
                    $authorized
                );

                if (is_string($solutionValue) && strlen($solutionValue) > 0) {
                    $numEnteredValues++;
                }
            }
        });

        if ($numEnteredValues && ilObjAssessmentFolder::_enabledAssessmentLogging()) {
            assQuestion::logAction(
                $this->lng->txtlng(
                    'assessment',
                    'log_user_entered_values',
                    ilObjAssessmentFolder::_getLogLanguage()
                ),
                $active_id,
                $this->getId()
            );
        } elseif (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
            assQuestion::logAction(
                $this->lng->txtlng(
                    'assessment',
                    'log_user_not_entered_values',
                    ilObjAssessmentFolder::_getLogLanguage()
                ),
                $active_id,
                $this->getId()
            );
        }

        return true;
    }

    public function calculateReachedPoints($active_id, $pass = null, $authorizedSolution = true, $returndetails = false)
    {
        $a = "";

        // TODO: Implement calculateReachedPoints() method.
    }

    public function getQuestionType()
    {
        return "CBMChoiceQuestion";
        // TODO: Implement getQuestionType() method.
    }

    public function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
    {
        if ($this->getId() <= 0) {
            return null;
        }

        $clone = clone $this;

        $originalId = assQuestion::_getOriginalId($this->getId());
        $clone->setId(-1);

        if ((int) $testObjId > 0) {
            $clone->setObjId($testObjId);
        }

        if ($title) {
            $clone->setTitle($title);
        }
        if ($author) {
            $clone->setAuthor($author);
        }
        if ($owner) {
            $clone->setOwner($owner);
        }

        if ($for_test) {
            $clone->saveToDb($originalId);
        } else {
            $clone->saveToDb('');
        }

        $clone->copyPageOfQuestion($this->getId());
        $clone->copyXHTMLMediaObjectsOfQuestion($this->getId());
        $clone->onDuplicate($this->getObjId(), $this->getId(), $clone->getObjId(), $clone->getId());

        return $clone->getId();
    }


    public function saveToDb($originalId = '')
    {
        $this->saveQuestionDataToDb($originalId);
        $this->saveAdditionalQuestionDataToDb();

        parent::saveToDb($originalId);
    }


    public function getAdditionalTableName()
    {
        return "cbm_choice_qst_data";
    }

    public function loadFromDb($questionId)
    {
        $res = $this->db->queryF($this->buildQuestionDataQuery(), ['integer'], [$questionId]);

        while ($data = $this->db->fetchAssoc($res)) {
            $this->setId($questionId);
            $this->setOriginalId($data['original_id']);
            $this->setObjId($data['obj_fi']);
            $this->setTitle($data['title']);
            $this->setNrOfTries($data['nr_of_tries']);
            $this->setComment($data['description']);
            $this->setAuthor($data['author']);
            $this->setPoints($data['points']);
            $this->setOwner($data['owner']);
            $this->setEstimatedWorkingTimeFromDurationString($data['working_time']);
            $this->setLastChange($data['tstamp']);
            $this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data['question_text'], 1));
            $this->setHideMeasure((bool) $data['hide_measure']);
            $this->setShuffle((bool) $data["shuffle"]);
            $this->setThumbSize($data["thumb_size"] ? (int) $data["thumb_size"] : null);
            $this->setAnswerType((int) $data["answer_type"]);
            $this->setAnswers(unserialize($data["answers"] ?? "", ["allowed_classes" => false]) ?: []);
            $this->setAllowMultipleSelection((bool) $data["allow_multiple_selection"]);
            $this->setScoringMatrix(unserialize($data["scoring_matrix"] ?? "", ["allowed_classes" => false]) ?: []);

            try {
                $this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
            } catch (ilTestQuestionPoolException $e) {
            }
        }

        parent::loadFromDb($questionId);
    }

    public function isComplete() : bool
    {
        foreach ([$this->title, $this->author, $this->question] as $text) {
            if (!is_string($text) || $text === "") {
                return false;
            }
        }

        if ($this->getAnswers() === []) {
            return false;
        }

        return $this->getMaximumPoints() > 0;
    }

    public function getMaximumPoints() : float
    {
        $points = $this->getPoints();
        /*
        if ($this->getAnswersVariant() === "answers_variant_single") {
            foreach ($this->getAnswersSingle() as $answer) {
                if ((float) $answer->getPoints() > $points) {
                    $points = (float) $answer->getPoints();
                }
            }
        }

        if ($this->getAnswersVariant() === "answers_variant_multi") {
            foreach ($this->getAnswersMulti() as $answer) {
                $points += max((float) $answer->getPoints(), (float) $answer->getPointsUnchecked());
            }
        }*/
        return $points;
    }

    public function saveAdditionalQuestionDataToDb() : void
    {
        $this->db->replace(
            $this->getAdditionalTableName(),
            [
                "question_fi" => ["integer", $this->getId()],
            ],
            [
                "hide_measure" => ["integer", (int) $this->isMeasureHidden()],
                "answers" => ["clob", serialize($this->getAnswers())],
                "shuffle" => ["integer", (bool) $this->getShuffle()],
                "thumb_size" => ["integer", $this->getThumbSize()],
                "answer_type" => ["integer", $this->getAnswerType()],
                "allow_multiple_selection" => ["integer", $this->isAllowMultipleSelection()],
                "scoring_matrix" => ["clob", serialize($this->getScoringMatrix())]
            ]
        );
    }

    public function isMeasureHidden() : bool
    {
        return $this->measureHidden;
    }

    public function setHideMeasure(bool $status) : void
    {
        $this->measureHidden = $status;
    }

    /**
     * @return int|null
     */
    public function getThumbSize() : ?int
    {
        return $this->thumbSize;
    }

    /**
     * @param int|null $thumbSize
     * @return CBMChoiceQuestion
     */
    public function setThumbSize(?int $thumbSize) : CBMChoiceQuestion
    {
        $this->thumbSize = $thumbSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getAnswerType() : int
    {
        return $this->answerType;
    }

    /**
     * @param int $answerType
     * @return CBMChoiceQuestion
     */
    public function setAnswerType(int $answerType) : CBMChoiceQuestion
    {
        $this->answerType = $answerType;
        return $this;
    }

    /**
     * @return array[]
     */
    public function getAnswers() : array
    {
        return $this->answers;
    }

    /**
     * @param array[] $answers
     * @return CBMChoiceQuestion
     */
    public function setAnswers(array $answers) : CBMChoiceQuestion
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowMultipleSelection() : bool
    {
        return $this->allowMultipleSelection;
    }

    /**
     * @param bool $allowMultipleSelection
     * @return CBMChoiceQuestion
     */
    public function setAllowMultipleSelection(bool $allowMultipleSelection) : CBMChoiceQuestion
    {
        $this->allowMultipleSelection = $allowMultipleSelection;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getScoringMatrix() : array
    {
        return $this->scoringMatrix;
    }

    /**
     * @param string[] $scoringMatrix
     * @return CBMChoiceQuestion
     */
    public function setScoringMatrix(array $scoringMatrix) : CBMChoiceQuestion
    {
        $this->scoringMatrix = $scoringMatrix;
        return $this;
    }
}
