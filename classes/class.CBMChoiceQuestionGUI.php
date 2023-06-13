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
 * If this is not the case or you just want to try ILIAS, you"ll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

use ILIAS\DI\Container;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\Plugin\CBMChoiceQuestion\Form\Input\ScoringMatrixInput\ScoringMatrixInput;
use ILIAS\Plugin\CBMChoiceQuestion\Form\QuestionConfigForm;
use ILIAS\Plugin\CBMChoiceQuestion\Model\AnswerData;
use ILIAS\Plugin\CBMChoiceQuestion\Model\Solution;
use ILIAS\Plugin\CBMChoiceQuestion\Stakeholder\AnswerImageStakeHolder;
use ILIAS\Plugin\CBMChoiceQuestion\Utils\AnswerTextSanitizer;
use ILIAS\ResourceStorage\Services;

require_once __DIR__ . "/../vendor/autoload.php";

/**
 * Class ilCBMChoiceQuestionGUI
 *
 * @author Marvin Beym <mbeym@databay.de>
 * @ilCtrl_IsCalledBy CBMChoiceQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 */
class CBMChoiceQuestionGUI extends assQuestionGUI
{
    /** @var CBMChoiceQuestion */
    public $object;
    /**
     * @var ilCBMChoiceQuestionPlugin
     */
    private $plugin;
    /**
     * @var Container
     */
    private $dic;
    /**
     * @var Services
     */
    private $resourceStorage;
    /**
     * @var ilGlobalPageTemplate
     */
    private $mainTpl;
    /**
     * @var AnswerTextSanitizer
     */
    private $answerTextSanitizer;

    public function __construct(?int $id = null)
    {
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        global $DIC;
        $this->dic = $DIC;
        $this->mainTpl = $this->dic->ui()->mainTemplate();
        $this->resourceStorage = $this->dic->resourceStorage();
        $this->answerTextSanitizer = new AnswerTextSanitizer();
        parent::__construct();
        $this->object = new CBMChoiceQuestion();
        if ($id && $id >= 0) {
            $this->object->loadFromDb($id);
        }
    }

    public function editQuestion(?QuestionConfigForm $form = null) : void
    {
        $this->getQuestionTemplate();

        $newQuestionCheckResult = $this->dic->database()->queryF(
            "SELECT question_fi FROM cbm_choice_qst_data WHERE question_fi = %s",
            ["integer"],
            [$this->object->getId()]
        );
        $newQuestion = $newQuestionCheckResult->numRows() === 0;

        if (!$form) {
            if ($newQuestion) {
                $sessionStoredScoringMatrix = unserialize(
                    ilSession::get(
                        ilCBMChoiceQuestionPlugin::CBM_CHOICE_SCORING_MATRIX_STORE_AS_DEFAULT_IN_SESSION_KEY
                    ) ?? "",
                    ["allowed_classes" => false]
                ) ?: [];
                $this->object->setScoringMatrix($sessionStoredScoringMatrix);
            }

            $form = new QuestionConfigForm($this, $this->object->getAnswerType() === ilCBMChoiceQuestionPlugin::ANSWER_TYPE_SINGLE_LINE);
            /**
             * @var ScoringMatrixInput $scoringMatrixInput
             */
            $scoringMatrixInput = $form->getItemByPostVar("scoringMatrix");

            $form->setValuesByArray([
                "shuffle" => $this->object->getShuffle(),
                "thumbSize" => $this->object->getThumbSize(),
                "answerType" => $this->object->getAnswerType(),
                "allowMultipleSelection" => $this->object->isAllowMultipleSelection(),
                "pointsForQuestion" => $this->object->getPointsForQuestion(),
                "scoringMatrix" => $scoringMatrixInput->unMapValuesFromArray($this->object->getScoringMatrix()),
                "cbmAnswerRequired" => $this->object->isCBMAnswerRequired()
            ], true);

            if ($this->object->getAnswers() !== []) {
                $answerData = [];
                foreach ($this->object->getAnswers() as $row => $answer) {
                    $answer->setAnswerText(
                        $this->object->getAnswerType() === ilCBMChoiceQuestionPlugin::ANSWER_TYPE_MULTI_LINE
                        ? $this->answerTextSanitizer->desanitize($answer->getAnswerText())
                        : $this->answerTextSanitizer->sanitize($answer->getAnswerText())
                    );
                    $answerData[$row] = $answer->toArray();
                }

                $form->setValuesByArray([
                    "answers" => $answerData
                ], true);
            }
        }

        $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
    }

    /**
     * @inheritDoc
     */
    public function writePostData($always = false) : int
    {
        $form = new QuestionConfigForm($this, $this->object->getAnswerType() === ilCBMChoiceQuestionPlugin::ANSWER_TYPE_SINGLE_LINE);
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->editQuestion($form);
            return 1;
        }
        $form->setValuesByPost();
        $this->writeQuestionGenericPostData();
        $thumbSize = (string) $form->getInput("thumbSize");
        $this->object->setPointsForQuestion((float) $form->getInput("pointsForQuestion"));
        $this->object->setPoints($this->object->getPointsForQuestion());
        $this->object->setShuffle((bool) $form->getInput("shuffle"));
        $this->object->setThumbSize($thumbSize ? ((int) $thumbSize) : null);
        $this->object->setCBMAnswerRequired((bool) $form->getInput("cbmAnswerRequired"));
        $this->object->setAllowMultipleSelection((bool) $form->getInput("allowMultipleSelection"));
        /**
         * @var ScoringMatrixInput $scoringMatrixInput
         */
        $scoringMatrixInput = $form->getItemByPostVar("scoringMatrix");
        $this->object->setScoringMatrix($scoringMatrixInput->mapValuesToArray($scoringMatrixInput->getValue()));

        if ($scoringMatrixInput->isStoreAsDefaultForSession()) {
            ilSession::set(
                ilCBMChoiceQuestionPlugin::CBM_CHOICE_SCORING_MATRIX_STORE_AS_DEFAULT_IN_SESSION_KEY,
                serialize($this->object->getScoringMatrix())
            );
        }

        $upload = $this->dic->upload();
        $uploadResults = [];
        if ($upload->hasUploads()) {
            try {
                if (!$upload->hasBeenProcessed()) {
                    $upload->process();
                }
                $uploadResults = $upload->getResults();
            } catch (IllegalStateException $e) {
                ilUtil::sendFailure($this->plugin->txt("question.config.answerImage.uploadFailure"), true);
                $this->editQuestion($form);
                return 1;
            }
        }

        /**
         * @var AnswerData[] $answers
         */
        $answers = [];
        /**
         * @var array<string, string|array> $answerData
         * @noinspection PhpUndefinedMethodInspection
         */
        foreach ($form->getItemByPostVar("answers")->getValue($form) as $rowIndex => $row) {
            $answerImage = $row["answerImage"];
            $file = $answerImage["file"];
            $deleteImage = (bool) $answerImage["delete"];
            $imageIdentification = "";

            $imageUploaded = false;
            if (isset($file["tmp_name"]) && $file["tmp_name"]) {
                $uploadResult = $uploadResults[$file["tmp_name"]];
                if ($uploadResult && $uploadResult->isOK()) {
                    $identification = $this->resourceStorage->manage()->upload($uploadResult, new AnswerImageStakeHolder());
                    try {
                        $imageIdentification = $identification->serialize();
                        $imageUploaded = true;
                    } catch (Throwable $ex) {
                        //ignore, act as no image uploaded
                    }
                }
            }

            if (!$imageUploaded && $deleteImage) {
                $imageIdentification = "";
            }

            if (!$imageUploaded && !$deleteImage) {
                //If no image is uploaded and image should not be deleted, try to find existing image identification
                foreach ($this->object->getAnswers() as $existingKey => $existingAnswer) {
                    if ($existingAnswer->getAnswerText() === $row["answerText"] && $rowIndex === $existingKey) {
                        $imageIdentification = $existingAnswer->getAnswerImage();
                        break;
                    }
                }
            }

            $answers[] = new AnswerData($rowIndex, $row["answerText"], $imageIdentification, $row["answerCorrect"] === "1");
        }

        $this->object->setAnswers($answers);

        $answersContainImage = false;
        foreach ($answers as $answer) {
            if ($answer->getAnswerImage()) {
                $answersContainImage = true;
                break;
            }
        }

        $answerType = (int) $form->getInput("answerType");

        if ($answersContainImage && $answerType === 1) {
            $answerType = 0;
            ilUtil::sendInfo($this->lng->txt("info_answer_type_change"), true);
        }
        $this->object->setAnswerType($answerType);


        return 0;
    }

    /**
     * @inheritDoc
     * @throws ilTemplateException
     */
    public function getSolutionOutput(
        $active_id,
        $pass = null,
        $graphicalOutput = false,
        $result_output = false,
        $show_question_only = true,
        $show_feedback = false,
        $show_correct_solution = false,
        $show_manual_scoring = false,
        $show_question_text = true
    ) : string {
        $solution = new Solution([], "");
        if ($active_id && !$show_correct_solution) {
            $solution = $this->object->mapSolution($this->object->getSolutionValues($active_id, $pass));
        } elseif ($show_correct_solution) {
            $correctAnswers = [];
            //Get correct answers
            foreach ($this->object->getAnswers() as $answer) {
                if ($answer->isAnswerCorrect()) {
                    $correctAnswers[$answer->getId()] = $answer;
                }
            }

            $highestPoints = 0;
            $highestColKey = "certain";
            foreach ($this->object->getScoringMatrix() as $rowKey => $data) {
                foreach ($data as $colKey => $value) {
                    if ($value > $highestPoints) {
                        $highestPoints = $value;
                        $highestColKey = $colKey;
                    }
                }
            }

            $solution = new Solution($correctAnswers, $highestColKey);
        }

        $tpl = new ilTemplate($this->plugin->templatesFolder("tpl.cbm_question_output_solution.html"), true, true);

        if (($active_id > 0) && (!$show_correct_solution)) {
            if ($graphicalOutput) {
                $reachedPoints = $this->object->getReachedPoints($active_id, $pass);
                if ($reachedPoints === $this->object->getMaximumPoints()) {
                    $tpl->setCurrentBlock("icon_ok");
                    $tpl->setVariable("ICON_OK", ilUtil::getImagePath("icon_ok.svg"));
                    $tpl->setVariable("TEXT_OK", $this->lng->txt("answer_is_right"));
                } else {
                    $tpl->setCurrentBlock("icon_ok");
                    if ($reachedPoints > 0) {
                        $tpl->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_mostly_ok.svg"));
                        $tpl->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_not_correct_but_positive"));
                    } else {
                        $tpl->setVariable("ICON_NOT_OK", ilUtil::getImagePath("icon_not_ok.svg"));
                        $tpl->setVariable("TEXT_NOT_OK", $this->lng->txt("answer_is_wrong"));
                    }
                }
                $tpl->parseCurrentBlock();
            }
        }

        if ($this->renderPurposeSupportsFormHtml()) {
            $tpl->setCurrentBlock("answer_div");
            $questionContent = $this->object->prepareTextareaOutput(
                $this->renderDynamicQuestionOutput($solution, true, $show_question_text)->get(),
                true
            );

            //Remove name attribute from inputs to avoid having them act as inputs
            $questionContent = preg_replace('/name=["\'](.+?)["\']/', "", $questionContent);

            $tpl->setVariable(
                "DIV_ANSWER",
                $questionContent
            );
        } else {
            //ToDo: not rendering correctly
            $tpl->setCurrentBlock("answer_textarea");
            $tpl->setVariable("TA_ANSWER", $this->object->prepareTextareaOutput(
                $this->renderDynamicQuestionOutput($solution, true, $show_question_text)->get(),
                true,
                true
            ));
        }
        $tpl->parseCurrentBlock();

        $content = $tpl->get();
        if (!$show_question_only) {
            $content = $this->getILIASPage($content);
        }

        return $content;
    }

    /**
     *
     * @throws ilTemplateException
     */
    public function getPreview($show_question_only = false, $showInlineFeedback = false) : string
    {
        $solution = new Solution([], "");
        if (is_object($this->getPreviewSession())) {
            $solution = $this->object->mapSolution((array) $this->getPreviewSession()->getParticipantsSolution());
        }

        $template = $this->renderDynamicQuestionOutput($solution);

        $content = $template->get();
        if (!$show_question_only) {
            $content = $this->getILIASPage($content);
        }

        return $content;
    }

    /**
     * @inheritDoc
     * @throws ilTemplateException
     */
    public function getTestOutput(
        $active_id,
        $pass,
        $is_question_postponed,
        $user_post_solutions,
        $show_specific_inline_feedback
    ) : string {
        $solution = new Solution([], "");
        if ($active_id) {
            $solution = $this->object->mapSolution((array) $this->object->getTestOutputSolutions($active_id, $pass));
        }

        return $this->outQuestionPage(
            "",
            $is_question_postponed,
            $active_id,
            $this->renderDynamicQuestionOutput($solution)->get()
        );
    }

    /**
     * @param Solution $solution
     * @param bool $asSolutionOutput
     * @param bool $showQuestionText
     * @return ilTemplate
     * @throws ilTemplateException
     */
    private function renderDynamicQuestionOutput(Solution $solution, bool $asSolutionOutput = false, bool $showQuestionText = true) : ilTemplate
    {
        $tpl = new ilTemplate($this->plugin->templatesFolder("tpl.cbm_question_output.html"), true, true);

        if ($showQuestionText) {
            $tpl->setVariable("QUESTION_TEXT", $this->object->getQuestion());
        }
        $tpl->setVariable("CBM_TEXT", $this->plugin->txt("question.cbm.howCertain"));
        if ($this->object->isCBMAnswerRequired()) {
            $tpl->setVariable("CBM_REQUIRED_TEXT", $this->plugin->txt("question.cbm.required"));
        }
        $this->mainTpl->addCss($this->plugin->cssFolder("cbm_question_output.css"));
        $shuffleAnswers = $this->object->getShuffle();

        $answers = $shuffleAnswers ? $this->object->getShuffler()->shuffle($this->object->getAnswers()) : $this->object->getAnswers();
        $isSingleLineAnswer = $this->object->getAnswerType() === ilCBMChoiceQuestionPlugin::ANSWER_TYPE_SINGLE_LINE;
        $thumbSize = $this->object->getThumbSize();

        foreach (["certain", "uncertain"] as $value) {
            $tpl->setCurrentBlock("scoring_matrix_input");
            if ($asSolutionOutput) {
                $tpl->setVariable("DISABLED", "disabled");
            }
            $tpl->setVariable("SCORING_MATRIX_VALUE", $value);
            $tpl->setVariable("SCORING_MATRIX_TEXT", $this->plugin->txt("question.cbm.$value"));
            if ($solution->getCbmChoice() === $value) {
                $tpl->setVariable("CHECKED", "checked");
            }
            $tpl->parseCurrentBlock("scoring_matrix_input");
        }

        foreach ($answers as $answer) {
            $tpl->setCurrentBlock($isSingleLineAnswer ? "answer_single" : "answer_multi");
            $tpl->setVariable("Q_ID", $this->object->getId());
            $tpl->setVariable("ANSWER_ID", $answer->getId());

            if ($asSolutionOutput) {
                $tpl->setVariable("DISABLED", "disabled");
                foreach ($solution->getAnswers() as $solutionAnswer) {
                    if ($answer->getId() === $solutionAnswer->getId()) {
                        $tpl->setVariable(
                            "SOLUTION_ICON_SRC",
                            ilUtil::getImagePath(
                                $answer->isAnswerCorrect()
                                    ? "icon_ok.svg"
                                    : "icon_not_ok.svg"
                            )
                        );
                        $tpl->setVariable(
                            "SOLUTION_ICON_TEXT",
                            $this->lng->txt(
                                $answer->isAnswerCorrect()
                                    ? "answer_is_right"
                                    : "answer_is_wrong"
                            )
                        );
                    }
                }
            }

            foreach ($solution->getAnswers() as $solutionAnswer) {
                if ($answer->getId() === $solutionAnswer->getId()) {
                    $tpl->setVariable("CHECKED", "checked");
                    break;
                }
            }
            if ($isSingleLineAnswer && $answer->getAnswerImage()) {
                $resource = $this->resourceStorage->consume()->src(
                    $this->resourceStorage->manage()->find($answer->getAnswerImage())
                );
                if ($thumbSize) {
                    $tpl->setVariable("ANSWER_IMAGE_THUMB_SIZE", $thumbSize);
                }
                $tpl->setVariable("ANSWER_IMAGE_URL", $resource->getSrc());
            }


            $tpl->setVariable(
                "ANSWER_TEXT",
                $isSingleLineAnswer
                    ? $this->answerTextSanitizer->sanitize($answer->getAnswerText())
                    : $this->answerTextSanitizer->desanitize($answer->getAnswerText())
            );
            $tpl->parseCurrentBlock($isSingleLineAnswer ? "answer_single" : "answer_multi");
        }

        return $tpl;
    }

    /**
     * @inheritDoc
     */
    public function getSpecificFeedbackOutput($userSolution)
    {
        return "";
    }
}
