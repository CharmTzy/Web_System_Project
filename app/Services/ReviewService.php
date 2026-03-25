<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\ReviewRepository;
use InvalidArgumentException;
use RuntimeException;

final class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    public function forProduct(int $productId, ?int $userId): array
    {
        $existingReview = $userId !== null
            ? $this->reviewRepository->findByProductAndUser($productId, $userId)
            : null;

        $canReview = $userId !== null
            ? $this->orderRepository->userHasPurchasedProduct($userId, $productId)
            : false;

        return [
            'reviews' => $this->reviewRepository->listVisibleByProduct($productId),
            'existing_review' => $existingReview,
            'can_review' => $canReview,
            'requires_sign_in' => $userId === null,
        ];
    }

    public function saveForUser(int $productId, int $userId, array $input): array
    {
        if (!$this->orderRepository->userHasPurchasedProduct($userId, $productId)) {
            throw new RuntimeException('You can review a product after purchasing it.');
        }

        $rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
        $title = trim((string) ($input['title'] ?? ''));
        $comment = trim((string) ($input['comment'] ?? ''));

        if ($rating === false || $rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Choose a rating from 1 to 5.');
        }

        if ($comment === '') {
            throw new InvalidArgumentException('Write a short review before submitting.');
        }

        if (mb_strlen($title) > 120) {
            throw new InvalidArgumentException('Review title must be 120 characters or fewer.');
        }

        if (mb_strlen($comment) > 1200) {
            throw new InvalidArgumentException('Review comments must be 1200 characters or fewer.');
        }

        $this->reviewRepository->save($productId, $userId, [
            'rating' => $rating,
            'title' => $title !== '' ? $title : null,
            'comment' => $comment,
        ]);

        return $this->forProduct($productId, $userId);
    }

    public function deleteForUser(int $productId, int $userId): array
    {
        $review = $this->reviewRepository->findByProductAndUser($productId, $userId);

        if ($review === null) {
            throw new RuntimeException('Review not found.');
        }

        $this->reviewRepository->delete($productId, $userId);

        return $this->forProduct($productId, $userId);
    }
}
