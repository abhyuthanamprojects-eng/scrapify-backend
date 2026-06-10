<?php

namespace App\Enums;

enum RequestStatus: string
{
    // Initial state - assigned to warehouse
    case PENDING_WAREHOUSE = 'pending_warehouse';

    // Corporate booking specific
    case ESTIMATE_PENDING = 'estimate_pending';
    case ESTIMATE_SHARED = 'estimate_shared';
    case ESTIMATE_APPROVED = 'estimate_approved';
    case ESTIMATE_REJECTED = 'estimate_rejected';

    // Pickup assignment
    case PICKUP_BOY_ASSIGNED = 'pickup_boy_assigned';
    case PICKUP_STARTED = 'pickup_started';
    case PICKUP_COMPLETED = 'pickup_completed';

    // Warehouse verification
    case WAREHOUSE_RECEIVE_PENDING = 'warehouse_receive_pending';
    case WAREHOUSE_RECEIVED = 'warehouse_received';

    // Payment flow (only for scrap & corporate)
    case PAYMENT_PENDING = 'payment_pending';
    case PAYMENT_PROCESSING = 'payment_processing';
    case PAYMENT_COMPLETED = 'payment_completed';

    // Final states
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';

    /**
     * Get readable label for status
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING_WAREHOUSE => 'Awaiting Warehouse Assignment',
            self::ESTIMATE_PENDING => 'Estimate Pending',
            self::ESTIMATE_SHARED => 'Estimate Shared',
            self::ESTIMATE_APPROVED => 'Estimate Approved',
            self::ESTIMATE_REJECTED => 'Estimate Rejected',
            self::PICKUP_BOY_ASSIGNED => 'Assigned to Pickup Boy',
            self::PICKUP_STARTED => 'Pickup in Progress',
            self::PICKUP_COMPLETED => 'Pickup Completed',
            self::WAREHOUSE_RECEIVE_PENDING => 'Awaiting Warehouse Receipt',
            self::WAREHOUSE_RECEIVED => 'Warehouse Verified',
            self::PAYMENT_PENDING => 'Payment Pending',
            self::PAYMENT_PROCESSING => 'Processing Payment',
            self::PAYMENT_COMPLETED => 'Payment Completed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Check if status is a final state
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::REJECTED]);
    }

    /**
     * Check if status allows cancellation
     */
    public function isModifiable(): bool
    {
        return !in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::REJECTED,
            self::PAYMENT_COMPLETED,
            self::WAREHOUSE_RECEIVED,
        ]);
    }

    /**
     * Check if payment is required for this status
     */
    public function requiresPayment(): bool
    {
        return in_array($this, [
            self::PAYMENT_PENDING,
            self::PAYMENT_PROCESSING,
            self::PAYMENT_COMPLETED,
        ]);
    }

    /**
     * Get all allowed next statuses based on request type and current status
     */
    public static function getNextAllowedStatuses(RequestStatus $currentStatus, RequestType $requestType): array
    {
        return match ($currentStatus) {
            // Initial stage - all types go to pickup assignment
            self::PENDING_WAREHOUSE => match ($requestType) {
                RequestType::CORPORATE => [self::ESTIMATE_PENDING],
                RequestType::SCRAP, RequestType::DONATION => [self::PICKUP_BOY_ASSIGNED],
            },

            // Corporate booking estimate flow
            self::ESTIMATE_PENDING => [self::ESTIMATE_SHARED],
            self::ESTIMATE_SHARED => [self::ESTIMATE_APPROVED, self::ESTIMATE_REJECTED],
            self::ESTIMATE_REJECTED => [self::CANCELLED],
            self::ESTIMATE_APPROVED => [self::PICKUP_BOY_ASSIGNED],

            // Pickup flow - all types
            self::PICKUP_BOY_ASSIGNED => [self::PICKUP_STARTED],
            self::PICKUP_STARTED => [self::PICKUP_COMPLETED],
            self::PICKUP_COMPLETED => [self::WAREHOUSE_RECEIVE_PENDING],

            // Warehouse receipt
            self::WAREHOUSE_RECEIVE_PENDING => [self::WAREHOUSE_RECEIVED],
            self::WAREHOUSE_RECEIVED => match ($requestType) {
                RequestType::DONATION => [self::COMPLETED],
                RequestType::SCRAP, RequestType::CORPORATE => [self::PAYMENT_PENDING],
            },

            // Payment flow - only scrap & corporate
            self::PAYMENT_PENDING => [self::PAYMENT_PROCESSING, self::COMPLETED],
            self::PAYMENT_PROCESSING => [self::PAYMENT_COMPLETED, self::COMPLETED],
            self::PAYMENT_COMPLETED => [self::COMPLETED],

            // Final states
            self::COMPLETED, self::CANCELLED, self::REJECTED => [],

            default => [],
        };
    }

    /**
     * Get statuses visible on dashboard
     */
    public static function dashboardStatuses(): array
    {
        return [
            self::PENDING_WAREHOUSE,
            self::ESTIMATE_PENDING,
            self::PICKUP_BOY_ASSIGNED,
            self::PICKUP_STARTED,
            self::PICKUP_COMPLETED,
            self::WAREHOUSE_RECEIVE_PENDING,
            self::WAREHOUSE_RECEIVED,
            self::PAYMENT_PENDING,
            self::PAYMENT_PROCESSING,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }
}
