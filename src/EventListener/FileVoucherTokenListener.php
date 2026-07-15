<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\EventListener;

use Reluem\ContaoNcFileGatewayBundle\EventListener\MailerFileAttachmentListener;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\NotificationCenterBundle\Event\GetTokenDefinitionsForNotificationTypeEvent;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\FileTokenDefinition;

class FileVoucherTokenListener
{
    public function __construct(private readonly TokenDefinitionFactoryInterface $tokenDefinitionFactory)
    {
    }

    #[AsEventListener]
    public function onGetTokenDefinitions(GetTokenDefinitionsForNotificationTypeEvent $event): void
    {
        $event->addTokenDefinition(
            $this->tokenDefinitionFactory->create(
                FileTokenDefinition::class,
                MailerFileAttachmentListener::ATTACHMENT_TOKEN,
                MailerFileAttachmentListener::ATTACHMENT_TOKEN,
            ),
        );
    }
}
