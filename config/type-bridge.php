<?php

declare(strict_types=1);

return [
    // Output format for all generated files: 'ts' or 'js'
    'output_format' => env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts'),

    // Max line length for ESLint disable directive
    // Set to 0 or negative to disable
    'max_line_length' => env('TYPE_BRIDGE_MAX_LINE_LENGTH', 120),

    // Whether to include trailing commas in generated objects and arrays
    'trailing_commas' => env('TYPE_BRIDGE_TRAILING_COMMAS', true),

    // Enum generation configuration
    'enums' => [
        // Output path (relative to resources directory)
        'output_path' => 'js/enums/generated',

        // Discovery configuration
        'discovery' => [
            'paths' => [
                app_path('Enums'),
            ],
            // When true: generates all backed enums
            // When false: generates ONLY enums with GenerateEnum attribute
            'generate_backed_enums' => true,
            // Exclude specific enums (by short name or FQCN)
            'excludes' => [],
        ],
    ],

    // Translation generation configuration
    'translations' => [
        // Output path (relative to resources directory)
        'output_path' => 'js/locales/generated',

        // Target i18n library for syntax transformation
        // Options: 'i18next' (default, works for react-i18next too), 'vue-i18n', 'laravel'
        'i18n_library' => env('TYPE_BRIDGE_I18N_LIBRARY', 'i18next'),

        // Custom adapter class (optional - for users who want to provide their own)
        'custom_adapter' => null, // e.g., \App\TypeBridge\CustomAdapter::class
    ],
];
