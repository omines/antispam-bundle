#!/bin/env php
<?php

$master = file_get_contents('https://www.unicode.org/iso15924/iso15924.txt');
$lines = array_filter(array_map('trim', explode("\n", $master)), fn(string $line) => !(empty($line) || $line[0] === '#'));

$output = <<<'EOT'
<?php

declare(strict_types=1);

namespace Omines\AntiSpamBundle\Type;

enum Script: string
{

EOT;

foreach ($lines as $line) {
    $parts = explode(';', $line);
    $name = $parts[4] ?: $parts[0];

    // Test whether the locale is valid for Unicode regexp matching - the regexp will fail to compile if not
    if(false === @preg_match('/[\p{' . $parts[0] . '}]/u', 'testString')) {
        // Uncomment to debug
        //echo sprintf("Cannot test for %s script, skipping\n", $parts[0]);
        continue;
    }

    $output .= sprintf("    case %s = '%s';\n", $name, strtolower($name)
    );
}
$output .= '}';

// Double check that we generated valid PHP - eval will crash otherwise
eval(substr($output, 5));

// Store the new version of the file (do run PHP-CS-Fixer ofc)
file_put_contents(__DIR__ . '/../src/Type/Script.php', $output);
