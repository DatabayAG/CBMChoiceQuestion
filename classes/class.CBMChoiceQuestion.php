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

use ILIAS\DI\Container;
use ILIAS\Plugin\CBMChoiceQuestion\Model\AnswerData;
use ILIAS\Plugin\CBMChoiceQuestion\Model\Solution;

require_once __DIR__ . "/../vendor/autoload.php";

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
     * @var AnswerData[]
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
     * @var array<string, array<string, float>>
     */
    private $scoringMatrix = [];

    /**
     * @var bool
     */
    private $cbmAnswerRequired = false;
    /**
     * @var Container
     */
    private $dic;

    public function __construct($title = "", $comment = "", $author = "", $owner = -1, $question = "")
    {
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        global $DIC;
        $this->dic = $DIC;
        parent::__construct($title, $comment, $author, $owner, $question);
    }

    public function isAnswered($active_id, $pass = null) : bool
    {
        //ToDo test if possible to manipulate if cbm is required.
        return assQuestion::getNumExistingSolutionRecords($active_id, $pass, $this->getId()) >= 1;
    }

    /**
     * @return array<string, string>
     */
    protected function getSolutionSubmit() : array
    {
        $post = $this->dic->http()->request()->getParsedBody()["answer"];

        $solution = [];
        $answerIds = [];

        foreach ($post["answer"] as $answerIdSelected) {
            $answerIds[] = (int) $answerIdSelected;
        }

        //Verify valid solution answer
        foreach ($this->getAnswers() as $existingAnswer) {
            if (in_array($existingAnswer->getId(), $answerIds, true)) {
                $solution["answer_{$existingAnswer->getId()}"] = $existingAnswer->getId();
            }
        }
        $solution["cbm"] = $post["cbm"] ?? null;

        return $solution;
    }

    public function saveWorkingData($active_id, $pass = null, $authorized = true) : bool
    {
        if ($pass === null) {
            $pass = ilObjTest::_getPass($active_id);
        }

        $numEnteredValues = 0;
        $cbmSelected = false;
        $this->getProcessLocker()->executeUserSolutionUpdateLockOperation(function () use (
            &$numEnteredValues,
            $active_id,
            $pass,
            $authorized,
            &$cbmSelected
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

                if ($solutionValue !== null) {
                    if ($solutionName === "cbm") {
                        $cbmSelected = true;
                    }
                    $numEnteredValues++;
                }
            }
        });

        if ($numEnteredValues && ilObjAssessmentFolder::_enabledAssessmentLogging()) {
            assQuestion::logAction(
                $this->lng->txtlng(
                    "assessment",
                    "log_user_entered_values",
                    ilObjAssessmentFolder::_getLogLanguage()
                ),
                $active_id,
                $this->getId()
            );
        } elseif (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
            assQuestion::logAction(
                $this->lng->txtlng(
                    "assessment",
                    "log_user_not_entered_values",
                    ilObjAssessmentFolder::_getLogLanguage()
                ),
                $active_id,
                $this->getId()
            );
        }

        if ($this->isCBMAnswerRequired()) {
            return $cbmSelected;
        }

        return true;
    }

    /**
     * @throws ilTestException
     */
    public function calculateReachedPoints($active_id, $pass = null, $authorizedSolution = true, $returndetails = false) : float
    {
        if ($returndetails) {
            throw new ilTestException("Return details not implemented for " . __METHOD__);
        }

        if (is_null($pass)) {
            $pass = $this->getSolutionMaxPass($active_id);
        }


        $solution = $this->mapSolution($this->getSolutionValues($active_id, $pass));
        if ($solution->getAnswers() === [] || $solution->getCbmChoice() === "") {
            return 0;
        }
        $cbmChoice = $solution->getCbmChoice();

        $allCorrect = true;
        foreach ($solution->getAnswers() as $answer) {
            if (!$answer->isAnswerCorrect()) {
                $allCorrect = false;
                break;
            }
        }

        $scoringMatrix = $this->getScoringMatrix();
        if ($allCorrect) {
            $scoringMatrixValue = $scoringMatrix["correct"][$cbmChoice];
        } else {
            $scoringMatrixValue = $scoringMatrix["incorrect"][$cbmChoice];
        }

        return (float) $scoringMatrixValue;
    }

    public function getQuestionType() : string
    {
        return "CBMChoiceQuestion";
    }

    public function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null) : ?int
    {
        if ((int) $this->getId() <= 0) {
            return null;
        }

        $clone = clone $this;

        $originalId = assQuestion::_getOriginalId($this->getId());
        $clone->setId(-1);

        $clone->setObjId((int) $testObjId > 0 ? $testObjId : $clone->getObjId());
        $clone->setTitle($title ?: $clone->getTitle());
        $clone->setAuthor($author ?: $clone->getAuthor());
        $clone->setOwner($owner ?: $clone->getOwner());

        $clone->saveToDb($for_test ? $originalId : "");

        $clone->copyPageOfQuestion($this->getId());
        $clone->copyXHTMLMediaObjectsOfQuestion($this->getId());
        $clone->onDuplicate($this->getObjId(), $this->getId(), $clone->getObjId(), $clone->getId());

        return (int) $clone->getId();
    }


    public function saveToDb($originalId = "") : void
    {
        $this->saveQuestionDataToDb($originalId);
        $this->saveAdditionalQuestionDataToDb();

        parent::saveToDb($originalId);
    }


    public function getAdditionalTableName() : string
    {
        return "cbm_choice_qst_data";
    }

    /**
     * @param string $questionId
     * @return void
     */
    public function loadFromDb($questionId) : void
    {
        $res = $this->db->queryF($this->buildQuestionDataQuery(), ["integer"], [$questionId]);

        while ($data = $this->db->fetchAssoc($res)) {
            $answers = [];
            foreach (unserialize($data["answers"] ?? "", ["allowed_classes" => true]) ?: [] as $answerData) {
                $answers[$answerData["id"]] = new AnswerData(
                    $answerData["id"],
                    $answerData["answerText"],
                    $answerData["answerImage"],
                    $answerData["answerCorrect"]
                );
            }

            $this->setId($questionId);
            $this->setOriginalId($data["original_id"]);
            $this->setObjId($data["obj_fi"]);
            $this->setTitle($data["title"]);
            try {
                $this->setLifecycle(ilAssQuestionLifecycle::getInstance($data['lifecycle']));
            } catch (ilTestQuestionPoolInvalidArgumentException $e) {
                $this->setLifecycle(ilAssQuestionLifecycle::getDraftInstance());
            }
            $this->setNrOfTries($data["nr_of_tries"]);
            $this->setComment($data["description"]);
            $this->setAuthor($data["author"]);
            $this->setOwner($data["owner"]);
            $this->setEstimatedWorkingTimeFromDurationString($data["working_time"]);
            $this->setLastChange($data["tstamp"]);
            $this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
            $this->setShuffle((bool) $data["shuffle"]);
            $this->setThumbSize($data["thumb_size"] ? (int) $data["thumb_size"] : null);
            $this->setAnswerType((int) $data["answer_type"]);
            $this->setAnswers($answers);
            $this->setAllowMultipleSelection((bool) $data["allow_multiple_selection"]);
            $this->setScoringMatrix(unserialize($data["scoring_matrix"] ?? "", ["allowed_classes" => false]) ?: []);
            $this->setCBMAnswerRequired((bool) $data["cbm_answer_required"]);

            try {
                $this->setAdditionalContentEditingMode($data["add_cont_edit_mode"]);
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
        $scoringMatrixPoints = 0;
        foreach ($this->getScoringMatrix() as $rowIndex => $data) {
            foreach ($data as $value) {
                if ($value > $scoringMatrixPoints) {
                    $scoringMatrixPoints = $value;
                }
            }
        }
        return $scoringMatrixPoints;
    }

    public function saveAdditionalQuestionDataToDb() : void
    {
        $answers = [];
        foreach ($this->getAnswers() as $answerData) {
            $answers[$answerData->getId()] = $answerData->toArray();
        }

        $this->db->replace(
            $this->getAdditionalTableName(),
            [
                "question_fi" => ["integer", $this->getId()],
            ],
            [
                "answers" => ["clob", serialize($answers)],
                "shuffle" => ["integer", (bool) $this->getShuffle()],
                "thumb_size" => ["integer", $this->getThumbSize()],
                "answer_type" => ["integer", $this->getAnswerType()],
                "allow_multiple_selection" => ["integer", $this->isAllowMultipleSelection()],
                "scoring_matrix" => ["clob", serialize($this->getScoringMatrix())],
                "cbm_answer_required" => ["integer", $this->isCBMAnswerRequired()],
            ]
        );
    }

    /**
     * @param array<int, array<string, mixed>> $solutionRecords
     * @return Solution
     */
    public function mapSolution(array $solutionRecords) : Solution
    {
        $answers = [];
        $cbmChoice = "";

        foreach ($solutionRecords as $solutionRecord) {
            if (strncmp($solutionRecord["value1"], "answer_", strlen("answer_")) === 0) {
                foreach ($this->getAnswers() as $existingAnswer) {
                    if (
                        isset($solutionRecord["value2"])
                        && $existingAnswer->getId() === (int) $solutionRecord["value2"]
                    ) {
                        $answers[] = $existingAnswer;
                        break;
                    }
                }
            }

            if ($solutionRecord["value1"] === "cbm") {
                $cbmChoice = $solutionRecord["value2"] ?? "";
            }
        }

        return new Solution($answers, $cbmChoice);
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
     * @return AnswerData[]
     */
    public function getAnswers() : array
    {
        return $this->answers;
    }

    /**
     * @param AnswerData[] $answers
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
     * @return array<string, array<string, float>>
     */
    public function getScoringMatrix() : array
    {
        return $this->scoringMatrix;
    }

    /**
     * @param array<string, array<string, float>> $scoringMatrix
     * @return CBMChoiceQuestion
     */
    public function setScoringMatrix(array $scoringMatrix) : CBMChoiceQuestion
    {
        $this->scoringMatrix = $scoringMatrix;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCBMAnswerRequired() : bool
    {
        return $this->cbmAnswerRequired;
    }

    /**
     * @param bool $cbmAnswerRequired
     * @return CBMChoiceQuestion
     */
    public function setCBMAnswerRequired(bool $cbmAnswerRequired) : CBMChoiceQuestion
    {
        $this->cbmAnswerRequired = $cbmAnswerRequired;
        return $this;
    }
}
