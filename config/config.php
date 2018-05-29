<?php

return [
    'tables' => [
        'categories' => 'categories',
        'categorizables' => 'categorizables',
    ],

    'models' => [
        'category' => \Mostafaznv\Categories\Models\Category::class,
    ],

    /**
     * extra rules key-value
     * @see https://github.com/dwightwatson/validating
     */
    'rules' => [
        //
    ],

    'html' => [
        'select' => [
            'separator' => ' > '
        ]
    ]
];
