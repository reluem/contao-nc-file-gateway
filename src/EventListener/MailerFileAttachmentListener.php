<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\EventListener;

use Reluem\ContaoNcFileGatewayBundle\Service\CsvExportHandler;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\NotificationCenterBundle\Event\CreateParcelEvent;

#[AsEventListener(priority: -128)]
class MailerFileAttachmentListener
{
    public const ATTACHMENT_TOKEN = 'nc_file_voucher';

    public function __construct(private readonly CsvExportHandler $csvExportHandler)
    {
    }

    public function __invoke(CreateParcelEvent $event): void
    {
        $event->setParcel($this->csvExportHandler->attachCsvToMailerParcel($event->getParcel()));
    }
}
