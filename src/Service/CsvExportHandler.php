<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\Service;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\StringUtil;
use Reluem\ContaoNcFileGatewayBundle\EventListener\MailerFileAttachmentListener;
use Symfony\Component\Mime\MimeTypesInterface;
use Terminal42\NotificationCenterBundle\BulkyItem\BulkyItemStorage;
use Terminal42\NotificationCenterBundle\BulkyItem\FileItem;
use Terminal42\NotificationCenterBundle\Gateway\MailerGateway;
use Terminal42\NotificationCenterBundle\Parcel\Parcel;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\BulkyItemsStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\GatewayConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\LanguageConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\Mailer\BackendAttachmentsStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\NotificationConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\TokenCollectionStamp;
use Terminal42\NotificationCenterBundle\Token\Token;

class CsvExportHandler
{
    public function __construct(
        private readonly CsvFileWriter $csvFileWriter,
        private readonly ExportDirectoryResolver $exportDirectoryResolver,
        private readonly FileExportContext $fileExportContext,
        private readonly BulkyItemStorage $bulkyItemStorage,
        private readonly SimpleTokenParser $simpleTokenParser,
        private readonly InsertTagParser $insertTagParser,
        private readonly MimeTypesInterface $mimeTypes,
    ) {
    }

    public function exportForFileGateway(Parcel $parcel): void
    {
        if (
            !$parcel->hasStamp(GatewayConfigStamp::class)
            || !$parcel->hasStamp(LanguageConfigStamp::class)
            || !$parcel->hasStamp(NotificationConfigStamp::class)
        ) {
            return;
        }

        $gatewayConfig = $parcel->getStamp(GatewayConfigStamp::class)->gatewayConfig;
        $languageConfig = $parcel->getStamp(LanguageConfigStamp::class)->languageConfig;

        $filePath = $this->exportDirectoryResolver->resolve($gatewayConfig->getString('file_path'));
        $fileName = trim($languageConfig->getString('file_name'));
        $storageMode = $languageConfig->getString('file_storage_mode') ?: 'append';
        $fileContent = $languageConfig->getString('file_content');

        if (null === $filePath || '' === $fileName || '' === $fileContent) {
            return;
        }

        $line = $this->replaceTokensAndInsertTags($parcel, $fileContent);
        $resolvedFileName = $this->replaceTokensAndInsertTags($parcel, $fileName);
        $absolutePath = $this->csvFileWriter->write($filePath, $resolvedFileName, $line, $storageMode);

        $mimeType = $this->mimeTypes->guessMimeType($absolutePath) ?? 'text/csv';
        $voucher = $this->bulkyItemStorage->store(
            FileItem::fromPath($absolutePath, basename($absolutePath), $mimeType, (int) filesize($absolutePath)),
        );

        $notificationId = $parcel->getStamp(NotificationConfigStamp::class)->notificationConfig->getId();
        $this->fileExportContext->add($notificationId, $voucher);

        if ($parcel->hasStamp(TokenCollectionStamp::class)) {
            $parcel->getStamp(TokenCollectionStamp::class)->tokenCollection
                ->addToken(Token::fromValue(MailerFileAttachmentListener::ATTACHMENT_TOKEN, $voucher));
        }
    }

    public function attachCsvToMailerParcel(Parcel $parcel): Parcel
    {
        if (
            !$parcel->hasStamp(GatewayConfigStamp::class)
            || !$parcel->hasStamp(LanguageConfigStamp::class)
            || !$parcel->hasStamp(NotificationConfigStamp::class)
            || MailerGateway::NAME !== $parcel->getStamp(GatewayConfigStamp::class)->gatewayConfig->getType()
            || !$this->wantsFileAttachment($parcel)
        ) {
            return $parcel;
        }

        $notificationId = $parcel->getStamp(NotificationConfigStamp::class)->notificationConfig->getId();
        $vouchers = $this->fileExportContext->get($notificationId);

        if ([] === $vouchers) {
            return $parcel;
        }

        if ($parcel->hasStamp(TokenCollectionStamp::class)) {
            $parcel->getStamp(TokenCollectionStamp::class)->tokenCollection
                ->addToken(Token::fromValue(MailerFileAttachmentListener::ATTACHMENT_TOKEN, implode(',', $vouchers)));
        }

        $existingBulkyItems = $parcel->hasStamp(BulkyItemsStamp::class)
            ? $parcel->getStamp(BulkyItemsStamp::class)->all()
            : [];

        $parcel = $parcel->withStamp(new BulkyItemsStamp(array_merge($existingBulkyItems, $vouchers)));

        $existingAttachments = $parcel->hasStamp(BackendAttachmentsStamp::class)
            ? $parcel->getStamp(BackendAttachmentsStamp::class)->toArray()
            : [];

        return $parcel->withStamp(new BackendAttachmentsStamp(array_merge($existingAttachments, $vouchers)));
    }

    private function wantsFileAttachment(Parcel $parcel): bool
    {
        $languageConfig = $parcel->getStamp(LanguageConfigStamp::class)->languageConfig;
        $tokens = StringUtil::trimsplit(',', $languageConfig->getString('attachment_tokens'));

        foreach ($tokens as $token) {
            if (MailerFileAttachmentListener::ATTACHMENT_TOKEN === trim($token, '# ')) {
                return true;
            }
        }

        return false;
    }

    private function replaceTokensAndInsertTags(Parcel $parcel, string $value): string
    {
        $tokenCollection = $parcel->getStamp(TokenCollectionStamp::class)?->tokenCollection;

        if (null !== $tokenCollection) {
            $value = $this->simpleTokenParser->parse($value, $tokenCollection->forSimpleTokenParser());
        }

        return $this->insertTagParser->replaceInline($value);
    }
}
