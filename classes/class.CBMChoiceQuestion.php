<?php declare(strict_types=1);
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
     * @var ASS_AnswerBinaryStateImage[]
     */
    private $answersSingle = [];
    /**
     * @var ASS_AnswerMultipleResponseImage[]
     */
    private $answersMulti = [];
    private $answersVariant = "answers_variant_single";

    public function __construct($title = "", $comment = "", $author = "", $owner = -1, $question = "")
    {
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        parent::__construct($title, $comment, $author, $owner, $question);
    }

    public function getHttpParameterNameForField(string $field): string
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

        if ((int)$testObjId > 0) {
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
            $this->setHideMeasure((bool)$data['hide_measure']);
            $this->setAnswersVariant($data['answers_variant']);
            $this->setAnswersSingle(unserialize($data['answers_single'] ?? "", ["allowed_classes" => true]) ?: []);
            $this->setAnswersMulti(unserialize($data['answers_multi'] ?? "", ["allowed_classes" => true]) ?: []);

            try {
                $this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
            } catch (ilTestQuestionPoolException $e) {
            }
        }

        parent::loadFromDb($questionId);
    }

    /**
     * @inheritDoc
     */
    public function saveAdditionalQuestionDataToDb()
    {
        $aa = serialize($this->getAnswersSingle());
        $this->db->replace(
            $this->getAdditionalTableName(),
            [
                "question_fi" => ["integer", $this->getId()],
            ],
            [
                "hide_measure" => ["integer", (int)$this->isMeasureHidden()],
                "answers_variant" => ["text", $this->getAnswersVariant()],
                "answers_single" => ["clob", serialize($this->getAnswersSingle())],
                "answers_multi" => ["clob", serialize($this->getAnswersMulti())],
            ]
        );
    }

    public function isMeasureHidden(): bool
    {
        return $this->measureHidden;
    }

    public function setHideMeasure(bool $status): void
    {
        $this->measureHidden = $status;
    }

    public function addAnswerSingle(string $answerText = "", float $points = 0.0, int $order = 0, string $answerImage = ""): void
    {
        $answerText = $this->getHtmlQuestionContentPurifier()->purify($answerText);
        if (array_key_exists($order, $this->getAnswersSingle())) {
            // insert answer
            $answer = new ASS_AnswerBinaryStateImage($answerText, $points, $order, 1, $answerImage);
            $newChoices = [];
            for ($i = 0; $i < $order; $i++) {
                $newChoices[] = $this->getAnswersSingle()[$i];
            }
            $newChoices[] = $answer;
            for ($i = $order; $i < count($this->getAnswersSingle()); $i++) {
                $changed = $this->getAnswersSingle()[$i];
                $changed->setOrder($i + 1);
                $newChoices[] = $changed;
            }
            $this->setAnswersMulti($newChoices);
        } else {
            // add answer
            $answer = new ASS_AnswerBinaryStateImage($answerText, $points, count($this->getAnswersSingle()), 1, $answerImage);
            $this->answersSingle[] = $answer;
        }
    }

    public function addAnswerMulti(
        string $answerText = "",
        float  $points = 0.0,
        float  $pointsUnchecked = 0.0,
        int    $order = 0,
        string $answerImage = ""
    ): void
    {
        $answerText = $this->getHtmlQuestionContentPurifier()->purify($answerText);
        if (array_key_exists($order, $this->getAnswersMulti())) {
            // insert answer
            $answer = new ASS_AnswerMultipleResponseImage($answerText, $points, $order, $pointsUnchecked, $answerImage);
            $newChoices = [];
            for ($i = 0; $i < $order; $i++) {
                $newChoices[] = $this->getAnswersMulti()[$i];
            }
            $newChoices[] = $answer;
            for ($i = $order; $i < count($this->getAnswersMulti()); $i++) {
                $changed = $this->getAnswersMulti()[$i];
                $changed->setOrder($i + 1);
                $newChoices[] = $changed;
            }
            $this->setAnswersMulti($newChoices);
        } else {
            // add answer
            $answer = new ASS_AnswerMultipleResponseImage($answerText, $points, count($this->getAnswersMulti()), $pointsUnchecked, $answerImage);
            $this->answersMulti[] = $answer;
        }
    }

    /**
     * @return ASS_AnswerBinaryStateImage[]
     */
    public function getAnswersSingle(): array
    {
        return $this->answersSingle;
    }

    /**
     * @param ASS_AnswerBinaryStateImage[] $answersSingle
     * @return CBMChoiceQuestion
     */
    public function setAnswersSingle(array $answersSingle): CBMChoiceQuestion
    {
        $this->answersSingle = $answersSingle;
        return $this;
    }

    /**
     * @return ASS_AnswerMultipleResponseImage[]
     */
    public function getAnswersMulti(): array
    {
        return $this->answersMulti;
    }

    /**
     * @param ASS_AnswerMultipleResponseImage[] $answersMulti
     * @return CBMChoiceQuestion
     */
    public function setAnswersMulti(array $answersMulti): CBMChoiceQuestion
    {
        $this->answersMulti = $answersMulti;
        return $this;
    }

    /**
     * @return string
     */
    public function getAnswersVariant(): string
    {
        return $this->answersVariant;
    }

    /**
     * @param string $answersVariant
     * @return CBMChoiceQuestion
     */
    public function setAnswersVariant(string $answersVariant): CBMChoiceQuestion
    {
        $this->answersVariant = $answersVariant;
        return $this;
    }
}