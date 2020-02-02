<?php

/** @noinspection PhpUnhandledExceptionInspection */

use qtism\common\datatypes\QtiFloat;
use qtism\data\results\AssessmentResult;
use qtism\data\results\ItemResult;
use qtism\data\results\ResultOutcomeVariable;
use qtism\data\state\Value;
use qtism\data\storage\xml\XmlResultDocument;
use qtism\runtime\processing\OutcomeProcessingEngine;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
$inputPath = 'data/input';
$outputPath = 'data/output';
$fileName = 'ass-result-1.xml';

$requestData = [
    'item-1' => [
        'GRAMMAR' => 4,
        'VALUE' => 3
    ],
    'item-2' => [
        'UNIQUENESS' => 6,
        'QUALITY' => 2
    ],
];

// read
$xmlDocument = new XmlResultDocument();
$xmlDocument->load("$inputPath/$fileName");

/** @var AssessmentResult $assessmentResult */
$assessmentResult = $xmlDocument->getDocumentComponent();

// modify
foreach ($requestData as $itemId => $scores) {
    /** @var ItemResult $itemResult */
    foreach ($assessmentResult->getItemResults() as $itemResult) {
        if ($itemResult->getIdentifier()->getValue() === $itemId) {
            foreach ($itemResult->getItemVariables() as $itemVariable) {
                if (!$itemVariable instanceof ResultOutcomeVariable) {
                    continue;
                }

                $outcomeDeclaration = $itemVariable;

                foreach ($scores as $outcomeId => $score) {
                    if ($outcomeDeclaration->getIdentifier()->getValue() === $outcomeId) {
                        /** @var Value $value */
                        foreach ($outcomeDeclaration->getValues() as $value) {
                            $oldValue = (float) $value->getValue();
                            $value->setValue(new QtiFloat($oldValue + (float) $score));
                        }
                    }
                }
            }
        }

        $itemProcessingEngine = new OutcomeProcessingEngine($itemResult);
        $itemProcessingEngine->process();
    }
}

// write
$xmlDocument = new XmlResultDocument();
$xmlDocument->setDocumentComponent($assessmentResult);
file_put_contents("$outputPath/$fileName", $xmlDocument->saveToString());
