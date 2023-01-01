<?php

$files = array_diff(scandir(__DIR__), [".", "..", "run.php"]);
$test_script = __DIR__ . "/../../../../tests/parser/parserTests.php";

foreach ($files as $file) {
    $file_escaped = escapeshellarg(__DIR__ . "/" . $file);
    $result = "\e[32mRunning $file:\e[0m\n\t" . exec("php $test_script --file='$file_escaped'") . "\n";

    echo str_replace(["tests failed!", "test failed!", "ALL TESTS PASSED!"], ["\e[31mtests failed!\e[0m", "\e[31mtest failed!\e[0m", "\e[32mALL TESTS PASSED!\e[0m"], $result);
}
