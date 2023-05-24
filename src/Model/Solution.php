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

namespace ILIAS\Plugin\CBMChoiceQuestion\Model;

/**
 * Class Solution
 * @package ILIAS\Plugin\CBMChoiceQuestion\Model
 * @author Marvin Beym <mbeym@databay.de>
 */
class Solution
{
    /**
     * @var AnswerData[]
     */
    private $answers;
    /**
     * @var string
     */
    private $cbmChoice;

    /**
     * @param AnswerData[] $answers
     * @param string $cbmChoice
     */
    public function __construct(array $answers, string $cbmChoice)
    {
        $this->answers = $answers;
        $this->cbmChoice = $cbmChoice;
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
     * @return Solution
     */
    public function setAnswers(array $answers) : Solution
    {
        $this->answers = $answers;
        return $this;
    }

    /**
     * @return string
     */
    public function getCbmChoice() : string
    {
        return $this->cbmChoice;
    }

    /**
     * @param string $cbmChoice
     * @return Solution
     */
    public function setCbmChoice(string $cbmChoice) : Solution
    {
        $this->cbmChoice = $cbmChoice;
        return $this;
    }
}
