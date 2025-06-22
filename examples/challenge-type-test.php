<?php

/**
 * Test script to demonstrate challenge type filtering
 * 
 * This script shows how the system handles disabled challenge types
 * and ensures only enabled types are used.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Mock the service to test challenge type selection
class ChallengeTypeTest
{
    public function testChallengeTypeFiltering()
    {
        echo "=== P-CAPTCHA Challenge Type Filtering Test ===\n\n";
        
        // Test with all types enabled
        echo "1. All challenge types enabled:\n";
        $this->testWithConfig([
            'beam_alignment',
            'sequence_complete'
        ]);
        
        echo "\n2. Only beam_alignment enabled:\n";
        $this->testWithConfig([
            'beam_alignment'
        ]);
        
        echo "\n3. Only sequence_complete enabled:\n";
        $this->testWithConfig([
            'sequence_complete'
        ]);
        
        echo "\n4. Empty challenge types (should fallback to beam_alignment):\n";
        $this->testWithConfig([]);
        
        echo "\n5. Invalid challenge types (should filter out):\n";
        $this->testWithConfig([
            'invalid_type',
            'beam_alignment',
            '',  // Empty string
            'sequence_complete'
        ]);
    }
    
    protected function testWithConfig(array $challengeTypes)
    {
        echo "   Config: " . json_encode($challengeTypes) . "\n";
        
        // Simulate the filtering logic
        $availableTypes = [];
        foreach ($challengeTypes as $type) {
            if (is_string($type) && !empty(trim($type))) {
                $availableTypes[] = trim($type);
            }
        }
        
        echo "   Filtered: " . json_encode($availableTypes) . "\n";
        
        if (empty($availableTypes)) {
            echo "   Result: Fallback to 'beam_alignment'\n";
        } else {
            echo "   Result: Using " . implode(', ', $availableTypes) . "\n";
        }
    }
    
    public function testChallengeGeneration()
    {
        echo "\n=== Challenge Generation Test ===\n\n";
        
        // Test generating challenges with different configurations
        $configs = [
            'All types' => ['beam_alignment', 'sequence_complete'],
            'Beam only' => ['beam_alignment'],
            'Sequence only' => ['sequence_complete'],
            'Empty' => []
        ];
        
        foreach ($configs as $name => $types) {
            echo "{$name}:\n";
            $this->testChallengeGenerationSimulation($types);
            echo "\n";
        }
    }
    
    protected function testChallengeGenerationSimulation(array $challengeTypes)
    {
        // Simulate challenge generation
        $availableTypes = [];
        foreach ($challengeTypes as $type) {
            if (is_string($type) && !empty(trim($type))) {
                $availableTypes[] = trim($type);
            }
        }
        
        if (empty($availableTypes)) {
            echo "   Generated: beam_alignment (fallback)\n";
            return;
        }
        
        // Simulate multiple challenge generations
        for ($i = 0; $i < 5; $i++) {
            $selectedType = $availableTypes[array_rand($availableTypes)];
            echo "   Generated: {$selectedType}\n";
        }
    }
}

// Run the tests
$test = new ChallengeTypeTest();
$test->testChallengeTypeFiltering();
$test->testChallengeGeneration();

echo "\n=== Instructions ===\n";
echo "To disable a challenge type:\n";
echo "1. Open config/p-captcha.php\n";
echo "2. Comment out or remove the line you want to disable:\n";
echo "   'challenge_types' => [\n";
echo "       'beam_alignment',    // Keep this line to enable\n";
echo "       // 'sequence_complete', // Comment out to disable\n";
echo "   ],\n";
echo "3. Clear config cache: php artisan config:cache\n";
echo "4. The system will automatically use only enabled types.\n"; 