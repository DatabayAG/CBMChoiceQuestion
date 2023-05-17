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
use ilCheckboxInputGUI;
use ilFormPropertyGUI;
use ilGlobalTemplateInterface;
use ilGlyphGUI;
use ILIAS\DI\Container;
use ILIAS\ResourceStorage\Services;
use ilImageFileInputGUI;
use ilNumberInputGUI;
use ilPropertyFormGUI;
use ilSelectInputGUI;
use ilTemplate;
use ilTemplateException;
use ilTextAreaInputGUI;
use ilTextInputGUI;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

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
     * @var array<int, array{header: string, input: ilTextAreaInputGUI|ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI|ilImageFileInputGUI|ilCheckboxInputGUI }>
     */
    private $fieldsData = [];

    private $rowData = [];
    /**
     * @var Services
     */
    private $resourceStorage;

    public function __construct(string $title = "", string $postVar = "")
    {
        global $DIC;
        $this->dic = $DIC;
        $this->mainTpl = $this->dic->ui()->mainTemplate();
        $this->lng = $DIC->language();
        $this->request = $DIC->http()->request();
        $this->plugin = ilCBMChoiceQuestionPlugin::getInstance();
        $this->resourceStorage = $this->dic->resourceStorage();

        parent::__construct($title, $postVar);
    }

    /**
     * @param ilTextAreaInputGUI|ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI|ilImageFileInputGUI|ilCheckboxInputGUI $input
     * @param bool $required
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

    protected function sortRowsDataByFields(array $rowsData) : array
    {
        $sortedRowsData = [];

        foreach ($this->fieldsData as $fieldData) {
            foreach ($rowsData as $row => $rowData) {
                /**
                 * @var ilTextAreaInputGUI|ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI|ilImageFileInputGUI|ilCheckboxInputGUI $input
                 */
                $input = $fieldData["input"];
                $postVar = $input->getPostVar();
                $sortedRowsData[$row][$postVar] = $rowData[$postVar];
            }
        }
        return $sortedRowsData;
    }

    public function setRowData(array $rowsData) : void
    {
        $this->rowData = $this->sortRowsDataByFields($rowsData);
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
             * @var string $header
             * @var ilTextAreaInputGUI|ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI|ilImageFileInputGUI|ilCheckboxInputGUI $input
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
            $tpl->setVariable("HIDDEN_ITEM_POST_VAR", "{$this->getPostVar()}[$rowNumber][]");
            $tpl->setVariable("HIDDEN_ITEM_VALUE", $postVar);
            $inputTemplate = $this->getInputTemplate($postVar);

            if (!$inputTemplate) {
                continue;
            }

            $colsAdded[] = $postVar;
            $input = $this->createTmpInput($rowNumber, $value, $inputTemplate);

            $input->insert($tpl);

            //Fix for ilImageFileInputGUI not setting Variable "POST_VAR_D" for "has_value" block
            if ($input instanceof ilImageFileInputGUI && $tpl->blockExists("prop_generic")) {
                $tpl->blockdata["prop_generic"] = str_replace(
                    "name=\"_name\"",
                    "name=\"{$input->getPostVar()}_name\"",
                    $tpl->blockdata["prop_generic"]
                );
            }

            if ($input->getAlert()) {
                $tpl->setVariable("FIELD_INPUT_ERROR", $input->getAlert());
            }
            //$tpl->parseCurrentBlock("prop_generic");
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

                    $tpl->setCurrentBlock("prop_generic");
                    $tpl->setVariable("PROP_GENERIC", $input->render());
                    if ($input->getAlert()) {
                        $tpl->setVariable("FIELD_INPUT_ERROR", $input->getAlert());
                    }
                    $tpl->parseCurrentBlock("prop_generic");
                }
            }
        }

        $tpl->setCurrentBlock("row");
        $tpl->parseCurrentBlock("row");
    }

    /**
     * @return ilTextAreaInputGUI|ilNumberInputGUI|ilSelectInputGUI|ilTextInputGUI|ilImageFileInputGUI|ilCheckboxInputGUI|null
     */
    private function getInputTemplate(string $postVar) : ?ilFormPropertyGUI
    {
        foreach ($this->fieldsData as $fieldData) {
            /**
             * @var ilTextAreaInputGUI|ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI|ilImageFileInputGUI|ilCheckboxInputGUI $input
             */
            $input = $fieldData["input"];
            if ($input->getPostVar() === $postVar) {
                return $input;
            }
        }
        return null;
    }

    /**
     * @param int $rowNumber
     * @param mixed $value
     * @param ilTextAreaInputGUI|ilSelectInputGUI|ilNumberInputGUI|ilTextInputGUI|ilImageFileInputGUI|ilCheckboxInputGUI $inputTemplate
     * @return ilTextAreaInputGUI|ilNumberInputGUI|ilSelectInputGUI|ilTextInputGUI|ilImageFileInputGUI|ilCheckboxInputGUI
     */
    private function createTmpInput(int $rowNumber, $value, $inputTemplate) : ilFormPropertyGUI
    {
        $input = clone $inputTemplate;
        if ($input instanceof ilCheckboxInputGUI) {
            $input->setChecked($value === "1" || $value === 1 || $value === true);
        } else {
            $input->setValue($value);
            if ($input instanceof ilImageFileInputGUI && $value) {
                try {
                    $resource = $this->resourceStorage->consume()->src($this->resourceStorage->manage()->find($value));
                    $input->setImage($resource->getSrc());
                } catch (Throwable $ex) {
                    //ignore
                }
            }
        }
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
        $success = true;

        $rows = $this->request->getParsedBody()[$this->getPostVar()];

        $colCountSuccess = true;
        foreach ($rows as $rowIndex => $row) {
            if (count($row) !== count($this->fieldsData)) {
                $colCountSuccess = false;
            }
            foreach ($row as $colIndex => $inputPostVar) {
                $input = $this->createTmpInput($rowIndex, "", $this->fieldsData[$colIndex]["input"]);
                $inputSuccess = $this->inputCheckInput($input, $rowIndex) ? $success : false;
                $success = $success === true ? $inputSuccess : $success;
            }
        }
        return $colCountSuccess && $success;
    }

    private function getFolderPath(string $file = "") : string
    {
        return strstr(realpath(__DIR__), "Customizing") . "/$file";
    }

    private function createPostVar(int $rowNumber, string $var) : string
    {
        return "{$this->getPostVar()}_{$rowNumber}_$var";
    }

    /**
     * @param ilFormPropertyGUI $input
     * @param int $row
     * @return bool
     */
    private function inputCheckInput(ilFormPropertyGUI $input, int $row) : bool
    {
        if (!$input->checkInput()) {
            $this->errors[$row][$input->getPostVar()]["message"] = $input->getAlert();
            $this->errors[$row][$input->getPostVar()]["value"] = $this->dic->http()->request()->getParsedBody()[$input->getPostVar()] ?? "";
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
        return $this->errors[$row][$input->getPostVar()] ?? null;
    }

    public function getValue(ilPropertyFormGUI $form) : array
    {
        $values = [];
        $rows = $this->request->getParsedBody()[$this->getPostVar()];

        $colCountSuccess = true;
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $inputPostVar) {
                $postVar = $this->createPostVar($rowIndex, $inputPostVar);
                $inputTemplate = $this->fieldsData[$colIndex]["input"];
                if ($inputTemplate instanceof ilImageFileInputGUI) {
                    $values[$rowIndex][$inputPostVar] = [
                        "file" => $form->getInput($postVar),
                        "name" => $form->getInput($postVar . "_name")
                            ?? isset($form->getInput($postVar)["name"])
                            ? $form->getInput($postVar)["name"]
                            : "",
                        "delete" => $form->getInput($postVar . "_delete") ?? false,
                    ];
                    continue;
                }
                $values[$rowIndex][$inputPostVar] = $form->getInput($postVar);
            }
        }
        return $values;
    }
}
