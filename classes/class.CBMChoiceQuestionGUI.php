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

use GuzzleHttp\Psr7\UploadedFile;
use ILIAS\DI\Container;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\FileUpload\Location;
use ILIAS\Plugin\CBMChoiceQuestion\Form\Input\ScoringMatrixInput\ScoringMatrixInput;
use ILIAS\Plugin\CBMChoiceQuestion\Form\QuestionConfigForm;

require_once __DIR__ . '/../vendor/autoload.php';

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

    public function __construct(?int $id = null)
    {
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        global $DIC;
        $this->dic = $DIC;
        parent::__construct();
        $this->object = new CBMChoiceQuestion();
        if ($id && $id >= 0) {
            $this->object->loadFromDb($id);
        }
    }

    public function editQuestion(?QuestionConfigForm $form = null)
    {
        $this->getQuestionTemplate();

        if (!$form) {
            /*$answersSingle->setValues(array_map(
                static function (ASS_AnswerBinaryStateImage $value) {
                    $value->setAnswerText(html_entity_decode($value->getAnswerText()));
                    return $value;
                },
                $parent->object->getAnswersSingle()
            ));
            $answersMulti->setValues(array_map(
                static function (ASS_AnswerMultipleResponseImage $value) {
                    $value->setAnswerText(html_entity_decode($value->getAnswerText()));
                    return $value;
                },
                $parent->object->getAnswersMulti()
            ));
            */

            $form = new QuestionConfigForm($this, $this->object->getAnswerType() === 0);
            $form->setValuesByArray([
                "hideMeasure" => $this->object->isMeasureHidden(),
                "shuffle" => $this->object->getShuffle(),
                "thumbSize" => $this->object->getThumbSize(),
                "answerType" => $this->object->getAnswerType(),
                "allowMultipleSelection" => $this->object->isAllowMultipleSelection(),
                "points" => $this->object->getPoints(),
                "scoringMatrix" => $this->object->getScoringMatrix()
            ], true);

            if ($this->object->getAnswers() !== []) {
                $form->setValuesByArray([
                    "answers" => $this->object->getAnswers()
                ], true);
            }
        }

        $this->tpl->setVariable('QUESTION_DATA', $form->getHTML());
    }

    /**
     * @inheritDoc
     */
    public function writePostData($always = false) : int
    {
        $form = new QuestionConfigForm($this, $this->object->getAnswerType() === 0);
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->editQuestion($form);
            return 1;
        }
        $form->setValuesByPost();
        $this->writeQuestionGenericPostData();
        $thumbSize = $form->getInput("thumbSize");
        $this->object->setPoints((int) $form->getInput("points"));
        $this->object->setShuffle((bool) $form->getInput("shuffle"));
        $this->object->setThumbSize($thumbSize === "" ? null : (int) $thumbSize);
        $this->object->setHideMeasure((bool) $form->getInput("hideMeasure"));
        $this->object->setAnswerType((int) $form->getInput("answerType"));
        $this->object->setAllowMultipleSelection((bool) $form->getInput("allowMultipleSelection"));
        /**
         * @var ScoringMatrixInput $a
         */
        $scoringMatrixInput = $form->getItemByPostVar("scoringMatrix");
        $this->object->setScoringMatrix($scoringMatrixInput->getValue());

        /**
         * @var array $answers
         */
        $answers = $form->getInput("answers");

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

        foreach ($answers as $key => $answer) {
            $answers[$key]["answerCorrect"] = (bool) $answer["answerCorrect"];

            if ($answer["answerImage"] === null) {
                //Delete Existing File selected
                $answers[$key]["answerImage"] = "";
                continue;
            }
            //Check image uploaded for question
            if (!isset($uploadResults[$answer["answerImage"]])) {
                //If no image uploaded, try to reuse previous value to keep image
                foreach ($this->object->getAnswers() as $existingKey => $existingAnswer) {
                    if ($existingAnswer["answerText"] === $answer["answerText"] && $key === $existingKey) {
                        $answers[$key]["answerImage"] = $existingAnswer["answerImage"];
                        continue 2;
                    }
                }
                $answers[$key]["answerImage"] = "";
                continue;
            }
            $uploadResult = $uploadResults[$answer["answerImage"]];

            $destination = "cbm_choice_question/{$this->object->getId()}/answerImages";
            $fileName = $key . "." . pathinfo($uploadResult->getName(), PATHINFO_EXTENSION);

            $fullPath = "cbm_choice_question/{$this->object->getId()}/answerImages" . "/" . $fileName;
            if ($uploadResult->isOK()) {
                $upload->moveOneFileTo(
                    $uploadResult,
                    $destination,
                    Location::STORAGE,
                    $fileName,
                    true
                );
            }

            $answers[$key]["answerImage"] = $fullPath;
        }

        $this->object->setAnswers($answers);

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
    ) {
        $this->dic->ui()->mainTemplate()->addCss($this->plugin->getDirectory() . '/data/css/styles.css');
        $this->dic->ui()->mainTemplate()->addJavaScript(
            $this->plugin->getDirectory() . '/data/js/main.js'
        );

        $measure = '';
        $notice = '';
        if (($active_id > 0) && (!$show_correct_solution)) {
            $solutions = $this->object->getSolutionMapFromSolutionRecords(
                (array) $this->object->getSolutionValues($active_id, $pass)
            );

            $checkedAnswer = $solutions[qualityQuestion::DECISION_PARAMETER_NAME] ?? '';
            $measure = $solutions[qualityQuestion::MEASURE_PARAMETER_NAME] ?? '';
            $notice = $solutions[qualityQuestion::NOTICE_PARAMETER_NAME] ?? '';
        } else {
            $checkedAnswer = $this->dic->language()->txt('yes');
        }

        $template = $this->plugin->getTemplate('tpl.il_as_qpl_qualityquestion_output_solution.html');

        if (($active_id > 0) && (!$show_correct_solution)) {
            if ($graphicalOutput) {
                $reachedPoints = $this->object->getReachedPoints($active_id, $pass);
                if ($reachedPoints == $this->object->getMaximumPoints()) {
                    $template->setCurrentBlock('icon_ok');
                    $template->setVariable('ICON_OK', ilUtil::getImagePath('icon_ok.svg'));
                    $template->setVariable('TEXT_OK', $this->lng->txt('answer_is_right'));
                } else {
                    $template->setCurrentBlock('icon_ok');
                    if ($reachedPoints > 0) {
                        $template->setVariable('ICON_NOT_OK', ilUtil::getImagePath('icon_mostly_ok.svg'));
                        $template->setVariable('TEXT_NOT_OK', $this->lng->txt('answer_is_not_correct_but_positive'));
                    } else {
                        $template->setVariable('ICON_NOT_OK', ilUtil::getImagePath('icon_not_ok.svg'));
                        $template->setVariable('TEXT_NOT_OK', $this->lng->txt('answer_is_wrong'));
                    }
                }
                $template->parseCurrentBlock();
            }
        }

        $checkedAnswer = $this->object->getHtmlUserSolutionPurifier()->purify($checkedAnswer);
        $measure = $this->object->getHtmlUserSolutionPurifier()->purify($measure);
        $notice = $this->object->getHtmlUserSolutionPurifier()->purify($notice);
        if ($this->renderPurposeSupportsFormHtml()) {
            $template->setCurrentBlock('answer_div');
            $template->setVariable('DIV_ANSWER', $this->object->prepareTextareaOutput($checkedAnswer, true));
        } else {
            $template->setCurrentBlock('answer_textarea');
            $template->setVariable('TA_ANSWER', $this->object->prepareTextareaOutput($checkedAnswer, true, true));
        }
        $template->parseCurrentBlock();

        if (strlen($measure) > 0) {
            if (
                !$this->object->isMeasureHidden() &&
                0 === strcmp($checkedAnswer, qualityQuestion::DECISION_FAILURE_INDICATOR)
            ) {
                $template->setVariable('MEASURE', $this->object->prepareTextareaOutput($measure, true, true));
            }
        }

        if (strlen($notice) > 0) {
            $template->setVariable(
                'NOTICE',
                $this->object->prepareTextareaOutput($notice, true, !$this->renderPurposeSupportsFormHtml())
            );
        }

        if ($show_question_text) {
            $template->setVariable(
                'QUESTIONTEXT',
                $this->object->prepareTextareaOutput(
                    $this->object->getQuestion(),
                    !$this->renderPurposeSupportsFormHtml()
                )
            );
        }

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
    public function getPreview($show_question_only = false, $showInlineFeedback = false)
    {
        $solutions = [];
        if (is_object($this->getPreviewSession())) {
            $solutions = (array) $this->getPreviewSession()->getParticipantsSolution();
        }

        $template = $this->renderDynamicQuestionOutput($solutions);

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
    ) {
        $solutions = [];
        if ($active_id) {
            $solutions = $this->object->getSolutionMapFromSolutionRecords(
                (array) $this->object->getTestOutputSolutions($active_id, $pass)
            );
        }

        return $this->outQuestionPage(
            '',
            $is_question_postponed,
            $active_id,
            $this->renderDynamicQuestionOutput($solutions)->get()
        );
    }

    /**
     * @param array<string, string> $solutions
     * @return ilTemplate
     */
    private function renderDynamicQuestionOutput(array $solutions) : ilTemplate
    {
        $this->dic->ui()->mainTemplate()->addCss($this->plugin->getDirectory() . '/data/css/styles.css');
        $this->dic->ui()->mainTemplate()->addJavaScript(
            $this->plugin->getDirectory() . '/data/js/main.js'
        );

        $checkedAnswer = '';
        $measure = '';
        $notice = '';
        $measureDomContainerId = 'measure-container';

        foreach ($solutions as $solutionName => $solutionValue) {
            if (!is_string($solutionValue)) {
                continue;
            }

            switch ($solutionName) {
                case qualityQuestion::DECISION_PARAMETER_NAME:
                    $checkedAnswer = $solutionValue;
                    break;

                case qualityQuestion::MEASURE_PARAMETER_NAME:
                    $measure = $solutionValue;
                    break;

                case qualityQuestion::NOTICE_PARAMETER_NAME:
                    $notice = $solutionValue;
                    break;
            }
        }

        $template = $this->plugin->getTemplate('tpl.il_as_qpl_qualityquestion_output.html');

        $template->setVariable(
            'QUESTIONTEXT',
            $this->object->prepareTextareaOutput($this->object->getQuestion(), true)
        );

        if (0 === strcmp($checkedAnswer, qualityQuestion::DECISION_CORRECTNESS_INDICATOR)) {
            $template->setCurrentBlock('checked');
            $template->touchBlock('checked');
            $template->parseCurrentBlock();
        }
        $template->setCurrentBlock('answer_row');
        $template->setVariable('ANSWER_ID', qualityQuestion::DECISION_CORRECTNESS_INDICATOR);
        $template->setVariable('ANSWER_TEXT', $this->dic->language()->txt('yes'));
        $template->setVariable('ANSWER_PARAMETER_NAME', $this->object->getHttpParameterNameForField(
            qualityQuestion::DECISION_PARAMETER_NAME
        ));
        $template->parseCurrentBlock();

        if (0 === strcmp($checkedAnswer, qualityQuestion::DECISION_FAILURE_INDICATOR)) {
            $template->setCurrentBlock('checked');
            $template->touchBlock('checked');
            $template->parseCurrentBlock();
        }

        $template->setCurrentBlock('answer_row');
        $template->setVariable('ANSWER_ID', qualityQuestion::DECISION_FAILURE_INDICATOR);
        $template->setVariable('ANSWER_TEXT', $this->dic->language()->txt('no'));
        $template->setVariable('ANSWER_PARAMETER_NAME', $this->object->getHttpParameterNameForField(
            qualityQuestion::DECISION_PARAMETER_NAME
        ));
        $template->parseCurrentBlock();

        $template->setCurrentBlock('qualityExtensionFields');
        $template->setVariable('MEASURE_CONTAINER_ID', $measureDomContainerId);
        $template->setVariable('LABEL_MEASURE', $this->plugin->txt('label_measure'));
        $template->setVariable('MEASURE_PARAMETER_NAME', $this->object->getHttpParameterNameForField(
            qualityQuestion::MEASURE_PARAMETER_NAME
        ));
        $template->setVariable('MEASURE', $measure);
        $template->setVariable('LABEL_NOTICE', $this->plugin->txt('label_notice'));
        $template->setVariable('NOTICE_PARAMETER_NAME', $this->object->getHttpParameterNameForField(
            qualityQuestion::NOTICE_PARAMETER_NAME
        ));
        $template->setVariable('NOTICE', $notice);
        $template->parseCurrentBlock();

        $isNotChecked = (0 === strcmp($checkedAnswer, qualityQuestion::DECISION_FAILURE_INDICATOR));
        $decisionParameterName = $this->object->getHttpParameterNameForField(qualityQuestion::DECISION_PARAMETER_NAME);
        $this->tpl->addOnLoadCode(
            'il.qualityQuestion.init(' .
            json_encode([
                'measure_container_selector' => '#' . $measureDomContainerId,
                'measure_initially_visible' => !$this->object->isMeasureHidden() && $isNotChecked,
                'show_measure_on_dependency' => !$this->object->isMeasureHidden(),
                'show_measure_indicator_field_selector' => 'input[name="' . $decisionParameterName . '"]',
                'show_measure_indicator_value' => qualityQuestion::DECISION_FAILURE_INDICATOR,
            ]) .
            '); il.qualityQuestion.run();'
        );

        return $template;
    }

    /**
     * @inheritDoc
     */
    public function getSpecificFeedbackOutput($userSolution)
    {
        return '';
    }
}
