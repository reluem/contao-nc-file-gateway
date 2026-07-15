<?php

declare(strict_types=1);

namespace Reluem\ContaoNcFileGatewayBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Stores bulky-item vouchers for CSV files written during the current request.
 * Mailer messages in the same notification can attach them via ##nc_file_voucher##.
 */
class FileExportContext
{
    private const REQUEST_ATTRIBUTE = '_nc_file_gateway_vouchers';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function add(int $notificationId, string $voucher): void
    {
        $vouchers = $this->all();
        $vouchers[$notificationId][] = $voucher;
        $this->store($vouchers);
    }

    /**
     * @return array<string>
     */
    public function get(int $notificationId): array
    {
        return $this->all()[$notificationId] ?? [];
    }

    /**
     * @return array<int, array<string>>
     */
    private function all(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return [];
        }

        $vouchers = $request->attributes->get(self::REQUEST_ATTRIBUTE);

        return \is_array($vouchers) ? $vouchers : [];
    }

    /**
     * @param array<int, array<string>> $vouchers
     */
    private function store(array $vouchers): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $vouchers);
    }
}
