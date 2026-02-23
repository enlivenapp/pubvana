<?php
return [
    'name'    => 'Archive List',
    'description' => 'Display post archives by month or year',
    'version' => '1.0.0',
    'options' => [
        'title'  => ['type' => 'text',   'label' => 'Title',  'default' => 'Archives'],
        'format' => ['type' => 'select', 'label' => 'Format', 'default' => 'monthly', 'options' => ['monthly' => 'Monthly', 'yearly' => 'Yearly']],
    ],
];
