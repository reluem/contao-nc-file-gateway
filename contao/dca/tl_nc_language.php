<?php

declare(strict_types=1);

use Reluem\ContaoNcFileGatewayBundle\Gateway\FileGateway;
use Terminal42\NotificationCenterBundle\Token\TokenContext;

$GLOBALS['TL_DCA']['tl_nc_language']['palettes'][FileGateway::NAME] = '{general_legend},language,fallback;{file_legend},file_name,file_storage_mode,file_content';

$GLOBALS['TL_DCA']['tl_nc_language']['fields']['file_name'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['mandatory' => true, 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
    'nc_context' => TokenContext::Text,
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_nc_language']['fields']['file_storage_mode'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['append', 'overwrite'],
    'reference' => &$GLOBALS['TL_LANG']['tl_nc_language']['file_storage_mode'],
    'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 8, 'default' => 'append'],
];

$GLOBALS['TL_DCA']['tl_nc_language']['fields']['file_content'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['mandatory' => true, 'tl_class' => 'clr', 'decodeEntities' => true],
    'nc_context' => TokenContext::Text,
    'sql' => ['type' => 'text', 'default' => null, 'notnull' => false],
];
