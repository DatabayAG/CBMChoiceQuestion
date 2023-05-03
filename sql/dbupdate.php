<#1>
<?php
/** @var $ilDB ilDBInterface */
$pluginName = "CBMChoiceQuestion";

$result = $ilDB->queryF(
    "SELECT * FROM qpl_qst_type WHERE type_tag = %s",
    ["text"],
    [$pluginName]
);
if ($result->numRows() === 0) {
    $maxQuestionTypeIdResult = $ilDB->query("SELECT MAX(question_type_id) AS maxid FROM qpl_qst_type");
    $data = $maxQuestionTypeIdResult->fetchAssoc();
    $max = ((int) $data["maxid"]) + 1;

    $ilDB->manipulateF(
        "INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, 1)",
        ["integer", "text", "integer"],
        [$max, $pluginName, 1]
    );
}
?>
<#2>
<?php
if (!$ilDB->tableExists("cbm_choice_qst_data")) {
    $ilDB->createTable("cbm_choice_qst_data", [
        "question_fi" => [
        "type" => "integer",
        "length" => 8,
        "notnull" => false,
    ],
    "hide_measure" => [
        "type" => "integer",
        "length" => 1,
        "notnull" => true,
        "default" => 0,
    ]
    ]);
    $ilDB->addPrimaryKey("cbm_choice_qst_data", ["question_fi"]);
}
?>
<#3>
<?php
if ($ilDB->tableExists("cbm_choice_qst_data")) {
    if (!$ilDB->tableColumnExists("cbm_choice_qst_data", "answers_single")) {
        $ilDB->addTableColumn("cbm_choice_qst_data", "answers_single", [
            "type" => "clob",
            "notnull" => true,
        ]);
    }
    if (!$ilDB->tableColumnExists("cbm_choice_qst_data", "answers_multi")) {
        $ilDB->addTableColumn("cbm_choice_qst_data", "answers_multi", [
            "type" => "clob",
            "notnull" => true,
        ]);
    }
}
?>
<#4>
<?php
if (
    $ilDB->tableExists("cbm_choice_qst_data")
    && !$ilDB->tableColumnExists("cbm_choice_qst_data", "answers_variant")
) {
    $ilDB->addTableColumn("cbm_choice_qst_data", "answers_variant", [
        "type" => "text",
        "length" => 40,
        "notnull" => true,
        "default" => "answers_variant_single",
    ]);
}
?>
