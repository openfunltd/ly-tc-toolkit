<?php

include(__DIR__ . '/../vendor/autoload.php');

use OpenFun\LyTcToolkit\LyTcToolkit;

$test_case = [
    '二十三億四千五百萬三千四百五十六' => 2345003456,
    '一百二十三' => 123,
    '一百二十三萬' => 1230000,
    '壹仟零壹拾' => 1010,
    '貳佰萬' => 2000000,
    '十' => 10,
    '十一' => 11,
    '貳拾' => 20,
    '二' => 2,
    '３４５６' => 3456,
    '二二八' => 228,
    '廿三' => 23,
    '一百卅' => 130,
];

foreach ($test_case as $input => $expected) {
    $output = LyTcToolkit::parseNumber($input);
    if ($output != $expected) {
        echo "Test failed: $input, expected: $expected, got: $output\n";
    } else {
        echo "Test passed: $input, got: $output\n";
    }
}
echo "All tests passed\n";
