<?php

/**
 * Test script to demonstrate improved sequence challenge instructions
 * 
 * This script shows how the new sequence instructions work with different
 * sequence types and provides clear, helpful guidance to users.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Mock the service to test sequence generation
class SequenceTest
{
    public function testSequenceInstructions()
    {
        echo "=== P-CAPTCHA Sequence Challenge Test ===\n\n";
        
        // Test arithmetic sequences
        $arithmeticTests = [
            ['type' => 'arithmetic', 'start' => 1, 'step' => 2, 'length' => 4],
            ['type' => 'arithmetic', 'start' => 2, 'step' => 3, 'length' => 4],
            ['type' => 'arithmetic', 'start' => 5, 'step' => 5, 'length' => 4],
            ['type' => 'arithmetic', 'start' => 1, 'step' => 4, 'length' => 4],
        ];
        
        echo "Arithmetic Sequences:\n";
        echo "====================\n";
        
        foreach ($arithmeticTests as $test) {
            $sequence = $this->generateSequence($test);
            $instruction = $this->generateSequenceInstruction($test, $sequence);
            $lastNumber = end($sequence);
            $nextNumber = $lastNumber + $test['step'];
            
            echo "Sequence: " . implode(', ', $sequence) . " → ?\n";
            echo "Instruction: {$instruction}\n";
            echo "Answer: {$nextNumber}\n";
            echo "---\n";
        }
        
        echo "\nGeometric Sequences:\n";
        echo "===================\n";
        
        // Test geometric sequences
        $geometricTests = [
            ['type' => 'geometric', 'start' => 2, 'ratio' => 2, 'length' => 4],
            ['type' => 'geometric', 'start' => 3, 'ratio' => 2, 'length' => 4],
            ['type' => 'geometric', 'start' => 1, 'ratio' => 3, 'length' => 4],
        ];
        
        foreach ($geometricTests as $test) {
            $sequence = $this->generateSequence($test);
            $instruction = $this->generateSequenceInstruction($test, $sequence);
            $lastNumber = end($sequence);
            $nextNumber = $lastNumber * $test['ratio'];
            
            echo "Sequence: " . implode(', ', $sequence) . " → ?\n";
            echo "Instruction: {$instruction}\n";
            echo "Answer: {$nextNumber}\n";
            echo "---\n";
        }
    }
    
    protected function generateSequence(array $config): array
    {
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
    
    protected function generateSequenceInstruction(array $config, array $sequence): string
    {
        switch ($config['type']) {
            case 'arithmetic':
                $step = $config['step'];
                $lastNumber = end($sequence);
                
                if ($step == 1) {
                    return "Add 1 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 2) {
                    return "Add 2 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 3) {
                    return "Add 3 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 4) {
                    return "Add 4 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 5) {
                    return "Add 5 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 7) {
                    return "Add 7 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step == 10) {
                    return "Add 10 to the last number ({$lastNumber}) to get the next number.";
                } elseif ($step > 0) {
                    return "Add {$step} to the last number ({$lastNumber}) to get the next number.";
                } else {
                    return "Subtract " . abs($step) . " from the last number ({$lastNumber}) to get the next number.";
                }
                
            case 'geometric':
                $ratio = $config['ratio'];
                $lastNumber = end($sequence);
                
                if ($ratio == 2) {
                    return "Double the last number ({$lastNumber}) to get the next number.";
                } elseif ($ratio == 3) {
                    return "Triple the last number ({$lastNumber}) to get the next number.";
                } else {
                    return "Multiply the last number ({$lastNumber}) by {$ratio} to get the next number.";
                }
                
            default:
                return "Complete the sequence by selecting the next number.";
        }
    }
}

// Run the test
$test = new SequenceTest();
$test->testSequenceInstructions(); 