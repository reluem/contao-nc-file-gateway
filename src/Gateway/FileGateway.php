<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\Gateway;

use Terminal42\NotificationCenterBundle\Gateway\AbstractGateway;
use Terminal42\NotificationCenterBundle\Parcel\Parcel;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\GatewayConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\LanguageConfigStamp;
use Terminal42\NotificationCenterBundle\Receipt\Receipt;

/**
 * Writes CSV files during {@see \Reluem\ContaoNcFileGatewayBundle\EventListener\FileExportListener}.
 * This gateway only acknowledges delivery once the parcel is sealed.
 */
class FileGateway extends AbstractGateway
{
    public const NAME = 'file';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getRequiredStampsForSealing(): array
    {
        return [
            GatewayConfigStamp::class,
            LanguageConfigStamp::class,
        ];
    }

    protected function doSealParcel(Parcel $parcel): Parcel
    {
        return $parcel->seal();
    }

    protected function doSendParcel(Parcel $parcel): Receipt
    {
        return Receipt::createForSuccessfulDelivery($parcel);
    }
}
