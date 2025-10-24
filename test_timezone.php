<?php
echo "=== Test Timezone ===\n";
echo "Timezone configuré: " . date_default_timezone_get() . "\n";
echo "Heure actuelle: " . date('Y-m-d H:i:s') . "\n";
echo "Timestamp: " . time() . "\n";
echo "\n";

// Test avec DateTime
$now = new DateTime();
echo "DateTime: " . $now->format('Y-m-d H:i:s') . "\n";
echo "Timezone: " . $now->getTimezone()->getName() . "\n";
