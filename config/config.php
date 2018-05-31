<?php

return [
    'tables' => [
        'categories' => 'categories',
        'categorizables' => 'categorizables',
    ],

    'models' => [
        'category' => \Mostafaznv\Categories\Models\Category::class,
    ],

    // collect stats
    'stats' => [
        'status' => true,
        'categorizable_type_field' => 'type'
    ],


    // extra rules key-value
    // @see https://github.com/dwightwatson/validating
    'rules' => [
        //
    ],

    'html' => [
        'select' => [
            'separator' => ' > '
        ]
    ]
];
