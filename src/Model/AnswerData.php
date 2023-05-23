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

use ReflectionClass;
use Throwable;

/**
 * Class AnswerData
 * @package ILIAS\Plugin\CBMChoiceQuestion\Model
 * @author Marvin Beym <mbeym@databay.de>
 */
class AnswerData
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $answerText;

    /**
     * @var string
     */
    private $answerImage;

    /**
     * @var bool
     */
    private $answerCorrect;

    /**
     * @param string $answerText
     * @param string $answerImage
     * @param bool $answerCorrect
     */
    public function __construct(int $id, string $answerText, string $answerImage, bool $answerCorrect)
    {
        $this->id = $id;
        $this->answerText = $answerText;
        $this->answerImage = $answerImage;
        $this->answerCorrect = $answerCorrect;
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAnswerText() : string
    {
        return $this->answerText;
    }

    /**
     * @param string $answerText
     * @return AnswerData
     */
    public function setAnswerText(string $answerText) : AnswerData
    {
        $this->answerText = $answerText;
        return $this;
    }

    /**
     * @return string
     */
    public function getAnswerImage() : string
    {
        return $this->answerImage;
    }

    /**
     * @param string $answerImage
     * @return AnswerData
     */
    public function setAnswerImage(string $answerImage) : AnswerData
    {
        $this->answerImage = $answerImage;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAnswerCorrect() : bool
    {
        return $this->answerCorrect;
    }

    /**
     * @param bool $answerCorrect
     * @return AnswerData
     */
    public function setAnswerCorrect(bool $answerCorrect) : AnswerData
    {
        $this->answerCorrect = $answerCorrect;
        return $this;
    }

    /**
     * @param string[] $propertyBlacklist
     * @return array<string, mixed>
     */
    public function toArray(array $propertyBlacklist = []) : array
    {
        $values = [];
        $refClass = new ReflectionClass($this);
        foreach ($refClass->getProperties() as $property) {
            if (in_array($property->getName(), $propertyBlacklist, true)) {
                continue;
            }
            $property->setAccessible(true);
            $values[$property->getName()] = $property->getValue($this);
        }

        return $values;
    }
}
