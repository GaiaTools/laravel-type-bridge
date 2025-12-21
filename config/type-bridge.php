<?php

declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | Type Bridge Configuration
 |--------------------------------------------------------------------------
 | This file controls how Laravel Type Bridge discovers types, generates
 | files, and formats output for enums and translations.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Output Format
    |--------------------------------------------------------------------------
    | Output format for all generated files.
    |
    | Supported: 'ts' (TypeScript), 'js' (JavaScript)
    */
    'output_format' => env('TYPE_BRIDGE_OUTPUT_FORMAT', 'ts'),

    /*
    |--------------------------------------------------------------------------
    | Max Line Length
    |--------------------------------------------------------------------------
    | Max line length used for emitting ESLint disable directives.
    | Set to 0 or a negative number to disable the directive generation.
    */
    'max_line_length' => env('TYPE_BRIDGE_MAX_LINE_LENGTH', 120),

    /*
    |--------------------------------------------------------------------------
    | Trailing Commas
    |--------------------------------------------------------------------------
    | Whether to include trailing commas in generated objects and arrays.
    */
    'trailing_commas' => env('TYPE_BRIDGE_TRAILING_COMMAS', true),

    /*
    |--------------------------------------------------------------------------
    | i18n Library
    |--------------------------------------------------------------------------
    | Target i18n library for all translation-related generation.
    |
    | Options:
    |  - 'vue-i18n': Vue.js projects
    |  - 'i18next' : i18next (React, react-i18next, Node, etc.)
    */
    'i18n_library' => env('TYPE_BRIDGE_I18N_LIBRARY', 'i18next'),

    /*
    |--------------------------------------------------------------------------
    | Enums
    |--------------------------------------------------------------------------
    | Configuration for PHP enum discovery and generated outputs.
    */
    'enums' => [
        /* Whether to generate translators for backed enums only or all enums */
        'generate_backed_enums' => true,

        /* Paths used to discover enums */
        'discovery' => [
            'include_paths' => [
                app_path('Enums'),
            ],
            'exclude_paths' => [],
        ],

        /* Where generated enum artifacts will be written (relative to project root) */
        'output_path' => 'js/enums/generated',

        /*
        | Import base used when generating import statements for enums.
        | Typically mirrors the output_path but with your project alias (e.g. '@').
        | Example: output_path 'js/enums/generated' → import_base '@/enums/generated'
        */
        'import_base' => '@/enums/generated',
    ],

    /*
    |--------------------------------------------------------------------------
    | Translations
    |--------------------------------------------------------------------------
    | Configuration for translation file discovery and generated outputs.
    */
    'translations' => [
        /* Paths used to discover translation files */
        'discovery' => [
            'include_paths' => [
                base_path('lang'),
            ],
            'exclude_paths' => [],
        ],

        /* Where generated translation artifacts will be written */
        'output_path' => 'js/lang/generated',

        /*
        | Custom adapter class (optional) for advanced users who want to
        | provide their own i18n integration.
        | Example: \App\TypeBridge\CustomI18nAdapter::class
        */
        'custom_adapter' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Enum Translators
    |--------------------------------------------------------------------------
    | Configuration for discovering enums and generating translator utilities
    | and composables used in front-end code.
    */
    'enum_translators' => [
        /* Paths used to discover enums for generating translators */
        'discovery' => [
            'include_paths' => [
                app_path('Enums'),
            ],
            'exclude_paths' => [],
        ],

        /* Where enum translator files will be generated */
        'translator_output_path' => 'js/composables/generated',

        /*
        | Output locations for shared utilities used by generated translators
        | Import bases (aliases) to mirror the above output paths.
        | Example: 'js/composables' → '@/composables'
        */
        'utils_composables_output_path' => 'js/composables',
        'utils_composables_import_path' => '@/composables',

        'utils_lib_output_path' => 'js/lib',
        'utils_lib_import_path' => '@/lib',
    ],
];
