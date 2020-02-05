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

use mysql_xdevapi\Result;
use qtism\common\datatypes\QtiFloat;
use qtism\common\enums\BaseType;
use qtism\common\enums\Cardinality;
use qtism\data\AssessmentItem;
use qtism\data\AssessmentItemRef;
use qtism\data\AssessmentItemRefCollection;
use qtism\data\AssessmentTest;
use qtism\data\ExtendedAssessmentItemRef;
use qtism\data\results\AssessmentResult;
use qtism\data\results\ItemResult;
use qtism\data\results\ResultOutcomeVariable;
use qtism\data\state\OutcomeDeclaration;
use qtism\data\state\OutcomeDeclarationCollection;
use qtism\runtime\common\OutcomeVariable;
use qtism\runtime\common\State;
use qtism\runtime\tests\AssessmentItemSession;
use qtism\runtime\tests\AssessmentItemSessionCollection;

class OutcomeProcessingState extends State
{
    /** @var AssessmentTest */
    private $assessmentTest;
    /**
     * @var AssessmentResult
     */
    private $assessmentResult;

    public function __construct(
        AssessmentTest $assessmentTest,
        AssessmentResult $assessmentResult
    )
    {
        $stateVariables = [];

        /** @var OutcomeDeclaration $testVariable */
        foreach ($assessmentResult->getTestResult()->getItemVariables() as $testVariable) {
            $stateVariables[] = new OutcomeVariable(
                $testVariable->getIdentifier()->getValue(),
                $testVariable->getCardinality(),
                $testVariable->getBaseType()
            );
        }

        parent::__construct($stateVariables);
        $this->assessmentTest = $assessmentTest;
        $this->assessmentResult = $assessmentResult;
    }

    public function getItemSubset(): AssessmentItemRefCollection
    {
        $items = new AssessmentItemRefCollection();

        foreach ($this->assessmentTest->getComponentsByClassName('assessmentItemRef') as $item) {
            $items->attach($item);
        }

        return $items;
    }

    public function getAssessmentItemSessions(): AssessmentItemSessionCollection
    {

        $sessions = new AssessmentItemSessionCollection();

        /** @var AssessmentItemRef $testItem */
        foreach ($this->assessmentTest->getComponentsByClassName('assessmentItemRef') as $testItem) {
            $assessmentItem = new ExtendedAssessmentItemRef(
                $testItem->getIdentifier(),
                $testItem->getHref()
            );

            $itemSession = new AssessmentItemSession($assessmentItem);

            $resultItem = $this->findResultItemByIdentifier($testItem->getIdentifier());

            if (null === $resultItem) {
                continue;
            }

            foreach ($resultItem->getItemVariables() as $itemVariable) {
                if (!$itemVariable instanceof ResultOutcomeVariable) {
                    continue;
                }

                if ($itemVariable->getBaseType() !== BaseType::FLOAT) {
                    continue;
                }

                if (!in_array($itemVariable->getIdentifier(), ['SCORE', 'MAXSCORE'])) {
                    continue;
                }

                $values = $itemVariable->getValues();

                $value = (string) $values[0]->getValue();

                echo sprintf(
                    'Set value of var %s_%s equals to %s',
                    $testItem->getIdentifier(),
                    $itemVariable->getIdentifier()->getValue(),
                    $value
                ) . PHP_EOL;
                

                $variable = new OutcomeVariable(
                    $itemVariable->getIdentifier()->getValue(),
                    $itemVariable->getCardinality(),
                    $itemVariable->getBaseType(),
                    new QtiFloat((float) $value)
                );
                
                $itemSession->setVariable($variable);
            }
            
            $sessions->attach($itemSession);
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

}
