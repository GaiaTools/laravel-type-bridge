<?php

declare(strict_types=1);

// config/type-bridge.php

return [
    // Output format for all generated files: 'ts' or 'js'
    'output_format' => env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts'),

    // Max line length for ESLint disable directive
    // Set to 0 or negative to disable
    'max_line_length' => env('TYPE_BRIDGE_MAX_LINE_LENGTH', 120),

    // Whether to include trailing commas in generated objects and arrays
    'trailing_commas' => env('TYPE_BRIDGE_TRAILING_COMMAS', true),

    'i18n' => [
        // Target i18n library for all translation-related generation
        // Options: 'vue-i18n', 'i18next', 'laravel', 'vanilla'
        // - 'vue-i18n': For Vue.js projects
        // - 'i18next': For i18next (works with React, vanilla JS, Node, etc.)
        // - 'laravel': Laravel syntax (no transformations)
        // - 'vanilla': Custom/framework-agnostic implementation
        'library' => env('TYPE_BRIDGE_I18N_LIBRARY', 'vue-i18n'),

        // Custom adapter class (optional - for users who want to provide their own)
        'custom_adapter' => null, // e.g., \App\TypeBridge\CustomI18nAdapter::class
    ],

    // Enum generation configuration
    'enums' => [
        'output_path' => 'js/enums/generated',
        'discovery' => [
            'paths' => [
                app_path('Enums'),
            ],
            'generate_backed_enums' => true,
            'excludes' => [],
        ],
    ],

    // Translation generation configuration
    'translations' => [
        'output_path' => 'js/locales/generated',
    ],

    // Enum translator generation configuration
    'enum_translators' => [
        'enabled' => true,
        'output_path' => 'js/composables/generated',
        'utils_composables_path' => 'js/composables',
        'utils_lib_path' => 'js/lib',
        'discovery_paths' => [
            app_path('Enums'),
        ],
        'excludes' => [],
    ],
];
