<?php

/**
 * P-CAPTCHA Sequence Test Script
 * 
 * This script helps debug sequence generation and validation issues.
 * Run this to see exactly what's happening with the sequence logic.
 */

// Simple test without full Laravel bootstrap
echo "=== P-CAPTCHA Sequence Test ===\n\n";

// Test the sequence generation logic directly
function generateSequence($config) {
    $sequence = [];
    
    switch ($config['type']) {
        case 'arithmetic':
            for ($i = 0; $i < $config['length']; $i++) {
                $sequence[] = $config['start'] + ($i * $config['step']);
            }
            break;
            
        case 'geometric':
            for ($i = 0; $i < $config['length']; $i++) {
                $sequence[] = $config['start'] * pow($config['ratio'], $i);
            }
            break;
    }
    
    return $sequence;
}

function generateSequenceInstruction($config, $sequence) {
    switch ($config['type']) {
        case 'arithmetic':
            $step = $config['step'];
            $lastNumber = end($sequence);
            
            if ($step == 2) {
                return "Add 2 to the last number ({$lastNumber}) to get the next number.";
            }
            break;
    }
    
    return "Complete the sequence by selecting the next number.";
}

// Test your specific case
echo "1. Testing Your Specific Case:\n";
echo "------------------------------\n";

// Simulate the sequence [1, 3, 5] with step 2
$testConfig = ['type' => 'arithmetic', 'start' => 1, 'step' => 2, 'length' => 4];
$fullSequence = generateSequence($testConfig);
$correctAnswer = array_pop($fullSequence);
$sequence = $fullSequence; // This is now the sequence without the answer

echo "Sequence Config: " . json_encode($testConfig) . "\n";
echo "Full Sequence: " . implode(', ', $fullSequence) . "\n";
echo "Sequence After Pop: " . implode(', ', $sequence) . "\n";
echo "Correct Answer: " . $correctAnswer . "\n";
echo "Correct Answer Type: " . gettype($correctAnswer) . "\n";

$instruction = generateSequenceInstruction($testConfig, $sequence);
echo "Instruction: " . $instruction . "\n";

// Test validation logic
echo "\n2. Testing Validation Logic:\n";
echo "-----------------------------\n";

$testCases = [
    ['answer' => $correctAnswer],
    ['answer' => (string) $correctAnswer],
    ['answer' => $correctAnswer + 1],
    ['answer' => (string) ($correctAnswer + 1)],
];

foreach ($testCases as $i => $testCase) {
    $userAnswer = $testCase['answer'];
    $expectedValid = ($userAnswer == $correctAnswer);
    
    // Convert both to int for comparison
    $correctAnswerInt = (int) $correctAnswer;
    $userAnswerInt = (int) $userAnswer;
    $isValid = ($correctAnswerInt === $userAnswerInt);
    
    echo "Test " . ($i + 1) . ":\n";
    echo "  User Answer: " . $userAnswer . " (type: " . gettype($userAnswer) . ")\n";
    echo "  Correct Answer: " . $correctAnswer . " (type: " . gettype($correctAnswer) . ")\n";
    echo "  User Answer (int): " . $userAnswerInt . "\n";
    echo "  Correct Answer (int): " . $correctAnswerInt . "\n";
    echo "  Expected Valid: " . ($expectedValid ? 'YES' : 'NO') . "\n";
    echo "  Is Valid: " . ($isValid ? 'YES' : 'NO') . "\n";
    echo "  ---\n";
}

echo "\n=== Test Complete ===\n"; 