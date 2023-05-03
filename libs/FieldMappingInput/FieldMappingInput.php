<?php

declare(strict_types=1);
/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *********************************************************************/

namespace ILIAS\Plugin\CBMChoiceQuestion\Form\Input;

use ilCBMChoiceQuestionPlugin;
use ilFormPropertyGUI;
use ILIAS\DI\Container;
use ilTemplate;
use ilTemplateException;
use Psr\Http\Message\ServerRequestInterface;
use ilGlyphGUI;
use ilSelectInputGUI;
use ilNumberInputGUI;
use ilUtil;
use ilGlobalTemplateInterface;
use ilTextInputGUI;

/**
 * Class FieldMappingInput
 * @author Marvin Beym <mbeym@databay.de>
 */
class FieldMappingInput extends ilFormPropertyGUI
{
    /**
     * @var ilGlobalTemplateInterface
     */
    private $mainTpl;
    private $errors = [];
    /**
     * @var ilCBMChoiceQuestionPlugin
     */
    private $plugin;
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var array<int, array{header: string, input: ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI }>
     */
    private $fieldsData = [];

    private $rowData = [];

    public function __construct(string $title = "", string $postVar = "")
    {
        global $DIC;
        $this->dic = $DIC;
        $this->mainTpl = $this->dic->ui()->mainTemplate();
        $this->lng = $DIC->language();
        $this->request = $DIC->http()->request();
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        parent::__construct($title, $postVar);
    }

    /**
     * @param ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI $input
     * @param bool                                             $required
     * @return FieldMappingInput
     */
    public function addField($input, bool $required = true) : self
    {
        $input->setRequired($required);
        $this->fieldsData[] = [
            "header" => $input->getTitle(),
            "input" => $input
        ];

        $input->setTitle("");
        return $this;
    }

    public function setRowData(array $rowData) : void
    {
        $this->rowData = $rowData;
    }

    /**
     * @throws ilTemplateException
     */
    public function insert(ilTemplate $a_tpl) : void
    {
        $tpl = new ilTemplate($this->getFolderPath("tpl.fieldMapping_input.html"), true, true);
        $tpl->setVariable("FIELD_ID", $this->getPostVar());
        foreach ($this->fieldsData as $index => $fieldData) {
            /**
             * @var string                                           $header
             * @var ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI $input
             */
            $header = $fieldData["header"];
            $input = $fieldData["input"];

            $tpl->setCurrentBlock("table_header");
            $tpl->setVariable("COLUMN_TABLE_HEADER", $header);
            if ($input->getRequired()) {
                $tpl->setVariable(
                    "COLUMN_TABLE_HEADER_REQUIRED",
                    "<span class=\"fieldMapping_header_required\">*</span>"
                );
            }
            $tpl->parseCurrentBlock("table_header");
        }

        foreach ($this->rowData as $rowNumber => $rowData) {
            $this->addRow($rowNumber, $tpl, $rowData);
        }

        $a_tpl->setCurrentBlock('prop_generic');
        $a_tpl->setVariable('PROP_GENERIC', $tpl->get());
        $a_tpl->parseCurrentBlock();
        $this->mainTpl->addJavascript($this->getFolderPath("fieldMapping_input.js"));
        $this->mainTpl->addCSS($this->getFolderPath("fieldMapping_input.css"));
    }

    private function addRow(int $rowNumber, ilTemplate $tpl, array $rowData) : void
    {
        $tpl->setVariable("ADD_BUTTON", ilGlyphGUI::get(ilGlyphGUI::ADD));
        $tpl->setVariable("REMOVE_BUTTON", ilGlyphGUI::get(ilGlyphGUI::REMOVE));

        $colsAdded = [];

        foreach ($rowData as $postVar => $value) {
            $inputTemplate = $this->getInputTemplate($postVar);

            if (!$inputTemplate) {
                continue;
            }

            $colsAdded[] = $postVar;
            $input = $this->createTmpInput($rowNumber, $value, $inputTemplate);

            $tpl->setCurrentBlock("table_data");
            $tpl->setVariable("FIELD_INPUT", $input->render());
            if ($input->getAlert()) {
                $tpl->setVariable("FIELD_INPUT_ERROR", $input->getAlert());
            }
            $tpl->parseCurrentBlock("table_data");
        }

        if (count($colsAdded) !== count($this->fieldsData)) {
            foreach ($this->fieldsData as $fieldData) {
                $postVar = $fieldData["input"]->getPostVar();
                if (!in_array($postVar, $colsAdded, true)) {
                    $inputTemplate = $this->getInputTemplate($postVar);

                    if (!$inputTemplate) {
                        continue;
                    }

                    $input = $this->createTmpInput($rowNumber, $inputTemplate->getValue(), $inputTemplate);

                    $tpl->setCurrentBlock("table_data");
                    $tpl->setVariable("FIELD_INPUT", $input->render());
                    if ($input->getAlert()) {
                        $tpl->setVariable("FIELD_INPUT_ERROR", $input->getAlert());
                    }
                    $tpl->parseCurrentBlock("table_data");
                }
            }
        }

        $tpl->setCurrentBlock("row");
        $tpl->parseCurrentBlock("row");
    }

    /**
     * @return ilNumberInputGUI|ilSelectInputGUI|ilTextInputGUI|null
     */
    private function getInputTemplate(string $postVar) : ?ilFormPropertyGUI
    {
        foreach ($this->fieldsData as $fieldData) {
            /**
             * @var ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI $input
             */
            $input = $fieldData["input"];
            if ($input->getPostVar() === $postVar) {
                return $input;
            }
        }
        return null;
    }

    /**
     * @param int                                              $rowNumber
     * @param mixed                                            $value
     * @param ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI $inputTemplate
     * @return ilNumberInputGUI|ilSelectInputGUI|ilTextInputGUI
     */
    private function createTmpInput(int $rowNumber, $value, $inputTemplate) : ilFormPropertyGUI
    {
        $input = clone $inputTemplate;
        $input->setValue($value);
        $input->setPostVar($this->createPostVar($rowNumber, $input->getPostVar()));

        if ($this->inputSetAlert($input, $rowNumber)) {
            $input->setValue($this->getErrorData($input, $rowNumber)["value"]);
        }
        return $input;
    }

    public function setValueByArray(array $post) : void
    {
        $this->setRowData($post[$this->getPostVar()] ?? $this->rowData);
    }

    public function checkInput() : bool
    {
        $rowData = $this->request->getParsedBody()[$this->getPostVar()];
        $success = true;
        $basePostVar = $this->getPostVar();

        if (!$rowData || count($rowData) === 0) {
            ilUtil::sendFailure($this->plugin->txt("updateFailure"));
            return false;
        }

        foreach ($rowData as $rowNumber => $data) {
            if (count($data) !== count($this->fieldsData)) {
                return false;
            }
            foreach ($data as $postVar => $value) {
                $inputTemplate = $this->getInputTemplate($postVar);

                if (!$inputTemplate) {
                    return false;
                }

                $input = $this->createTmpInput($rowNumber, $value, $inputTemplate);

                $this->setFakeInputPost($input, $rowNumber, $basePostVar, $input->getValue());
                $success = $this->inputCheckInput($input, $rowNumber) ? $success : false;
                $this->resetFakeInputPost($input, $rowNumber, $basePostVar);
            }
        }

        return $success;
    }

    private function getFolderPath(string $file = "") : string
    {
        return strstr(realpath(__DIR__), "Customizing") . "/$file";
    }

    private function createPostVar(int $rowNumber, string $var) : string
    {
        return $this->getPostVar() . "[$rowNumber][$var]";
    }

    /**
     * @param ilFormPropertyGUI $input
     * @param int               $row
     * @param string            $basePostVar
     * @param                   $defaultValue
     * @TODO: Will not work in >= ILIAS 8
     */
    private function setFakeInputPost(ilFormPropertyGUI $input, int $row, string $basePostVar, $defaultValue) : void
    {
        $baseData = $this->request->getParsedBody()[$basePostVar][$row];
        $matches = [];
        preg_match("/$basePostVar\[$row]\[(.+)\]/", $input->getPostVar(), $matches);
        $realPostVar = $matches[1];
        $fakedPostVar = "tmp_faked_$realPostVar";
        $input->setPostVar($fakedPostVar);
        $_POST[$fakedPostVar] = $baseData[$realPostVar] ?? $defaultValue;
    }

    /**
     * @param ilFormPropertyGUI $input
     * @param int               $row
     * @param string            $basePostVar
     * @TODO: Will not work in >= ILIAS 8
     */
    private function resetFakeInputPost(ilFormPropertyGUI $input, int $row, string $basePostVar) : void
    {
        $fakedPostVar = $input->getPostVar();
        $realPostVar = str_replace("tmp_faked_", "", $fakedPostVar);
        $_POST[$basePostVar][$row][$realPostVar] = $_POST[$fakedPostVar];
        unset($_POST[$fakedPostVar]);
    }

    /**
     * @param ilFormPropertyGUI $input
     * @param int               $row
     * @return bool
     * @TODO: Will not work in >= ILIAS 8
     */
    private function inputCheckInput(ilFormPropertyGUI $input, int $row) : bool
    {
        $realPostVar = str_replace("tmp_faked_", "", $input->getPostVar());
        if (!$input->checkInput()) {
            $this->errors[$row][$realPostVar]["message"] = $input->getAlert();
            $this->errors[$row][$realPostVar]["value"] = $_POST[$input->getPostVar()] ?? "";
            return false;
        }
        return true;
    }

    private function inputSetAlert(ilFormPropertyGUI $input, int $row) : bool
    {
        $error = $this->getErrorData($input, $row);
        if ($error) {
            $input->setAlert($error["message"]);
            return true;
        }
        return false;
    }

    private function getErrorData(ilFormPropertyGUI $input, int $row)
    {
        $realPostVar = str_replace([
            $this->getPostVar() . "[$row][",
            "]"
        ], "", $input->getPostVar());
        return $this->errors[$row][$realPostVar] ?? null;
    }
}
