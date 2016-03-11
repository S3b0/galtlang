<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "galtlang".
 ***************************************************************/

$EM_CONF[ 'galtlang' ] = [
    'title'              => 'Google Alternate Language',
    'description'        => 'Provides Google alternate hreflang (+canonical) tags for multilingual sites.',
    'category'           => 'Frontend',
    'shy'                => false,
    'version'            => '7.6.0',
    'dependencies'       => '',
    'conflicts'          => '',
    'priority'           => '',
    'loadOrder'          => '',
    'module'             => '',
    'state'              => 'excludeFromUpdates',
    'uploadfolder'       => false,
    'createDirs'         => '',
    'modify_tables'      => '',
    'clearcacheonload'   => false,
    'lockType'           => '',
    'author'             => 'Sebastian Iffland',
    'author_email'       => 'Sebastian.Iffland@ecom-ex.com',
    'author_company'     => 'ecom instruments GmbH',
    'CGLcompliance'      => null,
    'CGLcompliance_note' => null,
    'constraints'        =>
        [
            'depends'   => [
                'static_info_tables' => '6.0.0',
                'php'                => '5.6'
            ],
            'conflicts' => [],
            'suggests'  => []
        ]
];