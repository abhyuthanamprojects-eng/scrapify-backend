<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case WAREHOUSE = 'warehouse';
    case PICKUP_BOY = 'pickup_boy';
    case CUSTOMER = 'customer';
    case CHANNEL_PARTNER = 'channel_partner';
    case PAYMENT_ADMIN = 'payment_admin';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::WAREHOUSE => 'Warehouse Staff',
            self::PICKUP_BOY => 'Pickup Partner',
            self::CUSTOMER => 'Customer',
            self::CHANNEL_PARTNER => 'Channel Partner',
            self::PAYMENT_ADMIN => 'Payment Administrator',
        };
    }

    /**
     * Check if user can view all requests
     */
    public function canViewAllRequests(): bool
    {
        return self::ADMIN === $this;
    }

    /**
     * Check if user can assign pickup boy
     */
    public function canAssignPickupBoy(): bool
    {
        return in_array($this, [self::ADMIN, self::WAREHOUSE]);
    }

    /**
     * Check if user can process payments
     */
    public function canProcessPayments(): bool
    {
        return in_array($this, [self::ADMIN, self::PAYMENT_ADMIN]);
    }

    /**
     * Check if user can create estimates
     */
    public function canCreateEstimates(): bool
    {
        return in_array($this, [self::ADMIN, self::WAREHOUSE]);
    }

    /**
     * Check if user can confirm warehouse receipt
     */
    public function canConfirmWarehouseReceipt(): bool
    {
        return in_array($this, [self::ADMIN, self::WAREHOUSE]);
    }

    /**
     * Check if user can update pickup status
     */
    public function canUpdatePickupStatus(): bool
    {
        return in_array($this, [self::ADMIN, self::PICKUP_BOY]);
    }
}
