<?php declare(strict_types=1);
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 * @author Roman Kovalev <roman.kovalev@taotesting.com>
 */

namespace App;

use qtism\common\datatypes\QtiBoolean;
use qtism\common\datatypes\QtiDatatype;
use qtism\common\datatypes\QtiDuration;
use qtism\common\datatypes\QtiFloat;
use qtism\common\datatypes\QtiIdentifier;
use qtism\common\datatypes\QtiString;
use qtism\common\enums\BaseType;
use qtism\data\AssessmentItemRef;
use qtism\data\AssessmentItemRefCollection;
use qtism\data\AssessmentTest;
use qtism\data\ExtendedAssessmentItemRef;
use qtism\data\results\AssessmentResult;
use qtism\data\results\ItemResult;
use qtism\data\results\ResultOutcomeVariable;
use qtism\data\state\Value;
use qtism\runtime\common\OutcomeVariable;
use qtism\runtime\common\State;
use qtism\runtime\tests\AssessmentItemSession;
use qtism\runtime\tests\AssessmentItemSessionCollection;
use RuntimeException;
use Throwable;

class OutcomeProcessingState extends State
{
    /** @var AssessmentTest */
    private $assessmentTest;

    /** @var AssessmentResult */
    private $assessmentResult;

    public function __construct(
        AssessmentTest $assessmentTest,
        AssessmentResult $assessmentResult
    )
    {
        $this->assessmentTest = $assessmentTest;
        $this->assessmentResult = $assessmentResult;
        parent::__construct($this->getResultOutcomeVariables());
    }

    public function getItemSubset(): AssessmentItemRefCollection
    {
        $items = new AssessmentItemRefCollection();

        foreach ($this->getTestItems() as $testItem) {
            $items->attach($testItem);
        }

        return $items;
    }

    public function getAssessmentItemSessions(string $identifier): AssessmentItemSessionCollection
    {
        $sessions = new AssessmentItemSessionCollection();

        if ($testItem = $this->findTestItemByIdentifier($identifier)) {
            try {
                $itemSession = $this->createItemSession($testItem);
                $sessions->attach($itemSession);
            } catch (Throwable $e) {
                echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
            }
        }

        return $sessions;
    }

    public function findResultItemByIdentifier(string $itemId): ?ItemResult
    {
        /** @var ItemResult $itemResult */
        foreach ($this->assessmentResult->getItemResults() as $itemResult) {
            if ($itemResult->getIdentifier()->getValue() === $itemId) {
                return $itemResult;
            }
        }

        return null;
    }

    public function findTestItemByIdentifier(string $itemId)
    {
        foreach ($this->getTestItems() as $testItem) {
            if ($testItem->getIdentifier() === $itemId) {
                return $testItem;
            }
        }

        return null;
    }

    private function createItemSession(AssessmentItemRef $testItem): AssessmentItemSession
    {
        $itemId = $testItem->getIdentifier();

        $assessmentItem = new ExtendedAssessmentItemRef($itemId, $testItem->getHref());
        $itemSession = new AssessmentItemSession($assessmentItem);

        $resultItem = $this->findResultItemByIdentifier($itemId);

        if (null === $resultItem) {
            throw new RuntimeException(sprintf('Unable to find result item with ID %s', $itemId));
        }

        foreach ($resultItem->getItemVariables() as $resultItemVariable) {
            if (!$resultItemVariable instanceof ResultOutcomeVariable) {
                continue;
            }

            try {
                $outcomeVariable = $this->createOutcomeVariable($resultItemVariable);
                $itemSession->setVariable($outcomeVariable);
            } catch (Throwable $e) {
                echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
            }
        }

        return $itemSession;
    }

    private function getResultOutcomeVariables(): array
    {
        $resultVariables = [];

        /** @var ResultOutcomeVariable $testVariable */
        foreach ($this->assessmentResult->getTestResult()->getItemVariables() as $testVariable) {
            $resultVariables[] = new OutcomeVariable(
                $testVariable->getIdentifier()->getValue(),
                $testVariable->getCardinality(),
                $testVariable->getBaseType()
            );
        }

        return $resultVariables;
    }

    /**
     * @return AssessmentItemRef[]
     */
    private function getTestItems(): array
    {
        return $this->assessmentTest->getComponentsByClassName('assessmentItemRef')->getArrayCopy();
    }

    private function createOutcomeVariable(ResultOutcomeVariable $resultItemVariable): OutcomeVariable
    {
        $qtiValue = $this->createQtiValue(
            $resultItemVariable->getBaseType(),
            $resultItemVariable->getValues()->offsetGet(0)
        );

        return new OutcomeVariable(
            $resultItemVariable->getIdentifier()->getValue(),
            $resultItemVariable->getCardinality(),
            $resultItemVariable->getBaseType(),
            $qtiValue
        );
    }

    private function createQtiValue(int $baseType, $value): QtiDatatype
    {
        if ($value instanceof Value) {
            $value = $value->getValue();
        }

        if ($value instanceof QtiDatatype) {
            return $value;
        }

        switch ($baseType) {
            case BaseType::IDENTIFIER:
                return new QtiIdentifier((string)$value);
            case BaseType::FLOAT:
                return new QtiFloat((float)$value);
            case BaseType::BOOLEAN:
                return new QtiBoolean((bool)$value);
            case BaseType::DURATION:
                return new QtiDuration((string) $value);
            default:
                return new QtiString((string)$value);
        }
    }
}
