<?php

return [
    // This nested key should be hoisted by TranslationTransformer::readAndMerge
    'enums' => [
        'NestedStatus' => [
            'A' => 'a',
            'B' => 'b',
        ],
    ],

    // Other non-enum keys remain grouped under 'mixed'
    'misc' => [
        'hello' => 'world',
    ],
];
