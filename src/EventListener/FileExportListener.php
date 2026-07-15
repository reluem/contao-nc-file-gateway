<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\EventListener;

use Reluem\ContaoNcFileGatewayBundle\Gateway\FileGateway;
use Reluem\ContaoNcFileGatewayBundle\Service\CsvExportHandler;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\NotificationCenterBundle\Event\CreateParcelEvent;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\GatewayConfigStamp;

#[AsEventListener(priority: 128)]
class FileExportListener
{
    public function __construct(private readonly CsvExportHandler $csvExportHandler)
    {
    }

    public function __invoke(CreateParcelEvent $event): void
    {
        $parcel = $event->getParcel();

        if (
            !$parcel->hasStamp(GatewayConfigStamp::class)
            || FileGateway::NAME !== $parcel->getStamp(GatewayConfigStamp::class)->gatewayConfig->getType()
        ) {
            return;
        }

        $this->csvExportHandler->exportForFileGateway($parcel);
    }
}
