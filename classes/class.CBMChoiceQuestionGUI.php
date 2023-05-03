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

use ILIAS\DI\Container;
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

    /**
     * @param bool $checkonly
     */
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

            $form = new QuestionConfigForm($this);
            $form->setValuesByArray([
                "hide_measure" => $this->object->isMeasureHidden(),
                "answers_variant" => $this->object->getAnswersVariant(),
            ], true);

        }

        /**
         * @var ilSingleChoiceWizardInputGUI $answersSingle
         * @var ilMultipleChoiceWizardInputGUI $answersMulti
         */
        $answersSingle = $form->getItemByPostVar("answers_single");
        $answersMulti = $form->getItemByPostVar("answers_multi");

        if ($answersSingle->getValues() === []) {
            $answersSingle->setValues(array_map(
                static function (ASS_AnswerBinaryStateImage $value) {
                    $value->setAnswerText(html_entity_decode($value->getAnswerText()));
                    return $value;
                },
                $this->object->getAnswersSingle()
            ));
        }
        if ($answersMulti->getValues() === []) {
            $answersMulti->setValues(array_map(
                static function (ASS_AnswerMultipleResponseImage $value) {
                    $value->setAnswerText(html_entity_decode($value->getAnswerText()));
                    return $value;
                },
                $this->object->getAnswersMulti()
            ));
        }


        $this->tpl->setVariable('QUESTION_DATA', $form->getHTML());
    }

    /**
     * @inheritDoc
     */
    public function writePostData($always = false): int
    {
        $form = new QuestionConfigForm($this);
        if (!$form->checkInput()) {
            $form->setValuesByPost();
            $this->editQuestion($form);
            return 1;
        }
        $form->setValuesByPost();
        $this->writeQuestionGenericPostData();
        $this->object->setPoints((int)$form->getInput("points"));
        $this->object->setHideMeasure((bool)$form->getInput("hide_measure"));
        $this->object->setAnswersVariant($form->getInput("answers_variant"));
        $this->object->setAnswersSingle($form->getItemByPostVar("answers_single")->getValues());
        $this->object->setAnswersMulti($form->getItemByPostVar("answers_multi")->getValues());

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
    )
    {
        $this->dic->ui()->mainTemplate()->addCss($this->plugin->getDirectory() . '/data/css/styles.css');
        $this->dic->ui()->mainTemplate()->addJavaScript(
            $this->plugin->getDirectory() . '/data/js/main.js'
        );

        $measure = '';
        $notice = '';
        if (($active_id > 0) && (!$show_correct_solution)) {
            $solutions = $this->object->getSolutionMapFromSolutionRecords(
                (array)$this->object->getSolutionValues($active_id, $pass)
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
            $solutions = (array)$this->getPreviewSession()->getParticipantsSolution();
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
    )
    {
        $solutions = [];
        if ($active_id) {
            $solutions = $this->object->getSolutionMapFromSolutionRecords(
                (array)$this->object->getTestOutputSolutions($active_id, $pass)
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
    private function renderDynamicQuestionOutput(array $solutions): ilTemplate
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