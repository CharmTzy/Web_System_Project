<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;
use RuntimeException;

final class OrderManagementService
{
    private const ADMIN_STATUSES = [
        'pending',
        'paid',
        'processing',
        'packed',
        'shipped',
        'out_for_delivery',
        'delivered',
        'cancelled',
    ];

    private const SELLER_STATUSES = [
        'paid',
        'processing',
        'packed',
        'shipped',
        'out_for_delivery',
        'delivered',
        'cancelled',
    ];

    public function __construct(private readonly OrderRepository $orderRepository)
    {
    }

    public function listForAdmin(): array
    {
        return $this->orderRepository->listFulfillmentsForAdmin();
    }

    public function listForSeller(int $sellerId): array
    {
        return $this->orderRepository->listFulfillmentsForSeller($sellerId);
    }

    public function findForAdmin(int $fulfillmentId): ?array
    {
        return $this->orderRepository->findFulfillmentForAdmin($fulfillmentId);
    }

    public function findForSeller(int $fulfillmentId, int $sellerId): ?array
    {
        return $this->orderRepository->findFulfillmentForSeller($fulfillmentId, $sellerId);
    }

    public function adminStatusOptions(): array
    {
        return $this->buildStatusOptions(self::ADMIN_STATUSES);
    }

    public function sellerStatusOptions(): array
    {
        return $this->buildStatusOptions(self::SELLER_STATUSES);
    }

    public function summarize(array $fulfillments): array
    {
        $summary = [
            'total' => count($fulfillments),
            'awaiting_action' => 0,
            'in_transit' => 0,
            'delivered' => 0,
            'cancelled' => 0,
        ];

        foreach ($fulfillments as $fulfillment) {
            $status = (string) ($fulfillment['status'] ?? '');

            if (in_array($status, ['pending', 'paid', 'processing', 'packed'], true)) {
                $summary['awaiting_action']++;
            }

            if (in_array($status, ['shipped', 'out_for_delivery'], true)) {
                $summary['in_transit']++;
            }

            if ($status === 'delivered') {
                $summary['delivered']++;
            }

            if ($status === 'cancelled') {
                $summary['cancelled']++;
            }
        }

        return $summary;
    }

    public function updateForAdmin(int $fulfillmentId, array $input): array
    {
        $payload = $this->normalizeUpdatePayload($input, self::ADMIN_STATUSES);
        $updated = $this->orderRepository->updateFulfillment($fulfillmentId, $payload);

        if ($updated === null) {
            throw new RuntimeException('That delivery record could not be found.');
        }

        return $updated;
    }

    public function updateForSeller(int $fulfillmentId, int $sellerId, array $input): array
    {
        $existing = $this->orderRepository->findFulfillmentForSeller($fulfillmentId, $sellerId);

        if ($existing === null) {
            throw new RuntimeException('That delivery record could not be found in your store.');
        }

        $payload = $this->normalizeUpdatePayload($input, self::SELLER_STATUSES);
        $updated = $this->orderRepository->updateFulfillment($fulfillmentId, $payload);

        if ($updated === null) {
            throw new RuntimeException('That delivery record could not be updated.');
        }

        return $updated;
    }

    private function buildStatusOptions(array $statuses): array
    {
        return array_map(
            static fn (string $status): array => [
                'value' => $status,
                'label' => delivery_status_label($status),
            ],
            $statuses
        );
    }

    private function normalizeUpdatePayload(array $input, array $allowedStatuses): array
    {
        $status = trim((string) ($input['status'] ?? ''));

        if (!in_array($status, $allowedStatuses, true)) {
            throw new RuntimeException('Choose a valid delivery status.');
        }

        $courierName = trim((string) ($input['courier_name'] ?? ''));
        $trackingNumber = strtoupper(trim((string) ($input['tracking_number'] ?? '')));
        $estimatedDeliveryDate = trim((string) ($input['estimated_delivery_date'] ?? ''));
        $statusNote = trim((string) ($input['status_note'] ?? ''));

        if ($trackingNumber !== '' && !preg_match('/^[A-Z0-9\\-\\/]{4,120}$/', $trackingNumber)) {
            throw new RuntimeException('Tracking numbers can only contain letters, numbers, dashes, or slashes.');
        }

        if (in_array($status, ['shipped', 'out_for_delivery', 'delivered'], true) && $trackingNumber === '') {
            throw new RuntimeException('Add a tracking number before marking this delivery as shipped.');
        }

        if (strlen($statusNote) > 500) {
            throw new RuntimeException('Delivery notes must be 500 characters or fewer.');
        }

        if ($estimatedDeliveryDate !== '' && strtotime($estimatedDeliveryDate) === false) {
            throw new RuntimeException('Choose a valid estimated delivery date.');
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'status' => $status,
            'courier_name' => $courierName !== '' ? $courierName : null,
            'tracking_number' => $trackingNumber !== '' ? $trackingNumber : null,
            'status_note' => $statusNote !== '' ? $statusNote : null,
            'estimated_delivery_date' => $estimatedDeliveryDate !== '' ? $estimatedDeliveryDate : null,
            'shipped_at' => null,
            'out_for_delivery_at' => null,
            'delivered_at' => null,
        ];

        if (in_array($status, ['shipped', 'out_for_delivery', 'delivered'], true)) {
            $payload['shipped_at'] = $now;
        }

        if (in_array($status, ['out_for_delivery', 'delivered'], true)) {
            $payload['out_for_delivery_at'] = $now;
        }

        if ($status === 'delivered') {
            $payload['delivered_at'] = $now;
        }

        return $payload;
    }
}
