<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ReviewRepository;
use InvalidArgumentException;
use RuntimeException;

final class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly OrderRepository $orderRepository,
        private readonly ?ProductRepository $productRepository = null,
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

    public function replyAsSeller(int $reviewId, int $sellerId, string $reply): array
    {
        if ($this->productRepository === null) {
            throw new RuntimeException('Product repository is not available.');
        }

        $reply = trim($reply);

        if ($reply === '') {
            throw new InvalidArgumentException('Write a reply before submitting.');
        }

        if (mb_strlen($reply) > 1200) {
            throw new InvalidArgumentException('Seller reply must be 1200 characters or fewer.');
        }

        $review = $this->reviewRepository->findById($reviewId);

        if ($review === null) {
            throw new RuntimeException('Review not found.');
        }

        $product = $this->productRepository->findById((int) $review['product_id']);

        if ($product === null) {
            throw new RuntimeException('Product not found.');
        }

        if ((int) $product['seller_id'] !== $sellerId) {
            throw new RuntimeException('You can only reply to reviews on your own products.');
        }

        $this->reviewRepository->replyAsSeller($reviewId, $reply);

        return $this->forProduct((int) $review['product_id'], null);
    }

    public function flagAsAdmin(int $reviewId, int $adminId, string $reason): array
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Provide a reason for flagging this review.');
        }

        if (mb_strlen($reason) > 255) {
            throw new InvalidArgumentException('Flag reason must be 255 characters or fewer.');
        }

        $review = $this->reviewRepository->findById($reviewId);

        if ($review === null) {
            throw new RuntimeException('Review not found.');
        }

        $this->reviewRepository->flag($reviewId, $adminId, $reason);

        return $this->forProduct((int) $review['product_id'], null);
    }

    public function hideAsAdmin(int $reviewId, int $adminId, ?string $reason = null): array
    {
        $reason = $reason !== null ? trim($reason) : null;

        if ($reason !== null && mb_strlen($reason) > 255) {
            throw new InvalidArgumentException('Moderation reason must be 255 characters or fewer.');
        }

        $review = $this->reviewRepository->findById($reviewId);

        if ($review === null) {
            throw new RuntimeException('Review not found.');
        }

        $success = $this->reviewRepository->hide(
            $reviewId,
            $adminId,
            $reason !== '' ? $reason : null
        );

        if (!$success) {
            throw new RuntimeException('Unable to hide review.');
        }

        return $this->forProduct((int) $review['product_id'], null);
    }

    public function deleteAsAdmin(int $reviewId): array
    {
        $review = $this->reviewRepository->findById($reviewId);

        if ($review === null) {
            throw new RuntimeException('Review not found.');
        }

        $productId = (int) $review['product_id'];

        $deletedProductId = $this->reviewRepository->deleteById($reviewId);

        if ($deletedProductId === null) {
            throw new RuntimeException('Unable to delete review.');
        }

        return $this->forProduct($productId, null);
    }
}