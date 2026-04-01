<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\OrderReturnRequestRepository;
use RuntimeException;

final class OrderReturnRequestService
{
    private const REQUEST_TYPES = [
        'refund' => 'Refund only',
        'return' => 'Return item',
        'return_refund' => 'Return and refund',
    ];

    private const REASON_CODES = [
        'damaged' => 'Item arrived damaged',
        'wrong_item' => 'Wrong item was delivered',
        'not_as_described' => 'Item does not match the listing',
        'missing_parts' => 'Item is missing parts or accessories',
        'late_delivery' => 'Package arrived too late',
        'changed_mind' => 'Changed my mind',
        'other' => 'Other reason',
    ];

    public function __construct(
        private readonly OrderReturnRequestRepository $requestRepository,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->requestRepository->isAvailable();
    }

    public function requestTypeOptions(): array
    {
        return $this->mapOptions(self::REQUEST_TYPES);
    }

    public function reasonOptions(): array
    {
        return $this->mapOptions(self::REASON_CODES);
    }

    public function listForCustomer(int $customerId): array
    {
        return array_map([$this, 'presentRequest'], $this->requestRepository->listForCustomer($customerId));
    }

    public function listForSeller(int $sellerId): array
    {
        return array_map([$this, 'presentRequest'], $this->requestRepository->listForSeller($sellerId));
    }

    public function listForAdmin(): array
    {
        return array_map([$this, 'presentRequest'], $this->requestRepository->listForAdmin());
    }

    public function requestsByPackageForCustomer(int $customerId): array
    {
        $mapped = [];

        foreach ($this->listForCustomer($customerId) as $request) {
            $key = $this->packageKey((int) $request['order_id'], (int) $request['seller_id']);

            if (!isset($mapped[$key])) {
                $mapped[$key] = $request;
            }
        }

        return $mapped;
    }

    public function findLatestForCustomerPackage(int $customerId, int $orderId, int $sellerId): ?array
    {
        $request = $this->requestRepository->findLatestForCustomerPackage($customerId, $orderId, $sellerId);

        return $request !== null ? $this->presentRequest($request) : null;
    }

    public function findForSeller(int $requestId, int $sellerId): ?array
    {
        $request = $this->requestRepository->findForSeller($requestId, $sellerId);

        return $request !== null ? $this->presentRequest($request) : null;
    }

    public function findForAdmin(int $requestId): ?array
    {
        $request = $this->requestRepository->findForAdmin($requestId);

        return $request !== null ? $this->presentRequest($request) : null;
    }

    public function createForCustomer(int $customerId, array $input): array
    {
        $this->ensureAvailable();

        $orderId = (int) ($input['order_id'] ?? 0);
        $sellerId = (int) ($input['seller_id'] ?? 0);
        $requestType = sanitize_single_line($input['request_type'] ?? '', 40);
        $reasonCode = sanitize_single_line($input['reason_code'] ?? '', 40);
        $reasonDetails = sanitize_multiline_text($input['reason_details'] ?? '', 2000);

        if ($orderId < 1 || $sellerId < 1) {
            throw new RuntimeException('Select a valid delivered package before submitting a request.');
        }

        if (!isset(self::REQUEST_TYPES[$requestType])) {
            throw new RuntimeException('Choose a valid return or refund request type.');
        }

        if (!isset(self::REASON_CODES[$reasonCode])) {
            throw new RuntimeException('Choose a valid reason for this request.');
        }

        $package = $this->orderRepository->findPackageForCustomer($orderId, $customerId, $sellerId);

        if ($package === null) {
            throw new RuntimeException('That order package could not be found in your account.');
        }

        if ((string) ($package['status'] ?? '') !== 'delivered') {
            throw new RuntimeException('Return and refund requests are available after the package has been delivered.');
        }

        if ($this->requestRepository->findActiveForCustomerPackage($customerId, $orderId, $sellerId) !== null) {
            throw new RuntimeException('You already have an active return or refund request for this package.');
        }

        return $this->presentRequest($this->requestRepository->create([
            'order_id' => $orderId,
            'seller_id' => $sellerId,
            'customer_id' => $customerId,
            'request_type' => $requestType,
            'status' => 'pending',
            'reason_code' => $reasonCode,
            'reason_details' => $reasonDetails !== '' ? $reasonDetails : null,
        ]));
    }

    public function sellerActionOptions(array $request): array
    {
        $status = (string) ($request['status'] ?? '');

        return match ($status) {
            'pending' => [
                ['value' => 'approved', 'label' => 'Approve request'],
                ['value' => 'rejected', 'label' => 'Reject request'],
            ],
            'approved' => [
                ['value' => 'received', 'label' => 'Mark return received'],
                ['value' => 'refunded', 'label' => 'Issue refund'],
            ],
            'received' => [
                ['value' => 'refunded', 'label' => 'Issue refund'],
            ],
            default => [],
        };
    }

    public function adminActionOptions(array $request): array
    {
        $status = (string) ($request['status'] ?? '');

        return match ($status) {
            'pending' => [
                ['value' => 'approved', 'label' => 'Approve request'],
                ['value' => 'rejected', 'label' => 'Reject request'],
                ['value' => 'cancelled', 'label' => 'Cancel request'],
            ],
            'approved' => [
                ['value' => 'received', 'label' => 'Mark return received'],
                ['value' => 'refunded', 'label' => 'Issue refund'],
                ['value' => 'cancelled', 'label' => 'Cancel request'],
            ],
            'received' => [
                ['value' => 'refunded', 'label' => 'Issue refund'],
                ['value' => 'cancelled', 'label' => 'Cancel request'],
            ],
            default => [],
        };
    }

    public function updateForSeller(int $requestId, int $sellerId, array $input): array
    {
        $this->ensureAvailable();

        $request = $this->requestRepository->findForSeller($requestId, $sellerId);

        if ($request === null) {
            throw new RuntimeException('That return request could not be found in your store.');
        }

        $allowedStatuses = array_column($this->sellerActionOptions($this->presentRequest($request)), 'value');

        return $this->applyDecision($request, $input, $allowedStatuses);
    }

    public function updateForAdmin(int $requestId, array $input): array
    {
        $this->ensureAvailable();

        $request = $this->requestRepository->findForAdmin($requestId);

        if ($request === null) {
            throw new RuntimeException('That return request could not be found.');
        }

        $allowedStatuses = array_column($this->adminActionOptions($this->presentRequest($request)), 'value');

        return $this->applyDecision($request, $input, $allowedStatuses);
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'approved', 'received', 'refunded' => 'request-status-badge request-status-badge--success',
            'rejected', 'cancelled' => 'request-status-badge request-status-badge--danger',
            default => 'request-status-badge request-status-badge--neutral',
        };
    }

    private function ensureAvailable(): void
    {
        if (!$this->requestRepository->isAvailable()) {
            throw new RuntimeException('Return and refund requests will be available after the latest database migration is applied.');
        }
    }

    private function applyDecision(array $request, array $input, array $allowedStatuses): array
    {
        $status = sanitize_single_line($input['status'] ?? '', 40);
        $sellerResponse = sanitize_multiline_text($input['seller_response'] ?? '', 2000);

        if (!in_array($status, $allowedStatuses, true)) {
            throw new RuntimeException('Choose a valid request action.');
        }

        if ($status === 'rejected' && $sellerResponse === '') {
            throw new RuntimeException('Add a brief reason before rejecting this request.');
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'status' => $status,
            'seller_response' => $sellerResponse !== '' ? $sellerResponse : null,
        ];

        if (in_array($status, ['approved', 'rejected', 'received', 'refunded', 'cancelled'], true)
            && empty($request['reviewed_at'])) {
            $payload['reviewed_at'] = $now;
        }

        if (in_array($status, ['rejected', 'refunded', 'cancelled'], true)) {
            $payload['resolved_at'] = $now;
        }

        $updated = $this->requestRepository->update((int) $request['id'], $payload);

        if ($updated === null) {
            throw new RuntimeException('That return request could not be updated right now.');
        }

        return $this->presentRequest($updated);
    }

    private function presentRequest(array $request): array
    {
        $status = (string) ($request['status'] ?? 'pending');
        $requestType = (string) ($request['request_type'] ?? 'refund');
        $reasonCode = (string) ($request['reason_code'] ?? 'other');

        $request['request_type_label'] = self::REQUEST_TYPES[$requestType] ?? ucfirst(str_replace('_', ' ', $requestType));
        $request['reason_label'] = self::REASON_CODES[$reasonCode] ?? ucfirst(str_replace('_', ' ', $reasonCode));
        $request['status_label'] = $this->statusLabel($status);
        $request['status_badge_class'] = $this->statusBadgeClass($status);
        $request['created_at_formatted'] = !empty($request['created_at']) ? date('d M Y, g:i A', strtotime((string) $request['created_at'])) : null;
        $request['reviewed_at_formatted'] = !empty($request['reviewed_at']) ? date('d M Y, g:i A', strtotime((string) $request['reviewed_at'])) : null;
        $request['resolved_at_formatted'] = !empty($request['resolved_at']) ? date('d M Y, g:i A', strtotime((string) $request['resolved_at'])) : null;

        return $request;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'received' => 'Return received',
            'refunded' => 'Refund completed',
            'cancelled' => 'Cancelled',
            default => 'Pending review',
        };
    }

    private function mapOptions(array $options): array
    {
        $mapped = [];

        foreach ($options as $value => $label) {
            $mapped[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $mapped;
    }

    private function packageKey(int $orderId, int $sellerId): string
    {
        return $orderId . ':' . $sellerId;
    }
}
