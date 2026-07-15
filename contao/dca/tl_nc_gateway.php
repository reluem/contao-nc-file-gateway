<?php

declare(strict_types=1);

use Reluem\ContaoNcFileGatewayBundle\Gateway\FileGateway;

$GLOBALS['TL_DCA']['tl_nc_gateway']['palettes'][FileGateway::NAME] = '{title_legend},title,type;{file_legend},file_type,file_path';

$GLOBALS['TL_DCA']['tl_nc_gateway']['fields']['file_type'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['csv'],
    'reference' => &$GLOBALS['TL_LANG']['tl_nc_gateway']['file_type'],
    'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 4, 'default' => 'csv'],
];

$GLOBALS['TL_DCA']['tl_nc_gateway']['fields']['file_path'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => [
        'mandatory' => true,
        'maxlength' => 255,
        'tl_class' => 'long clr',
        'helpwizard' => true,
    ],
    'explanation' => 'nc_file_path',
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];
