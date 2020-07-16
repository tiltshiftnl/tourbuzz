<?php

$translations = json_decode(file_get_contents('translations/translations.json'), true);

$languages = ['en', 'de', 'es', 'fr'];

foreach ($translations['translations'] as $key => $arr) {
    $missing = [];
    foreach ($languages as $lang) {
        if (!isset($arr[$lang])) {
            $missing[] = $lang;
        }
    }

    if (count($missing)) {
        echo "--------------------------------------------------------------\n";
        echo $key . "\n";
        echo "Missende taal/talen: " . implode(", ", $missing) . "\n";
    }
}