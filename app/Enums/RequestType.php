<?php

namespace App\Enums;

enum RequestType: string
{
    case SCRAP = 'scrap';
    case CORPORATE = 'corporate';
    case DONATION = 'donation';

    public function label(): string
    {
        return match ($this) {
            self::SCRAP => 'Scrap Selling',
            self::CORPORATE => 'Corporate Booking',
            self::DONATION => 'Donation',
        };
    }

    /**
     * Does this request type require payment?
     */
    public function requiresPayment(): bool
    {
        return in_array($this, [self::SCRAP, self::CORPORATE]);
    }

    /**
     * Does this request type require estimate?
     */
    public function requiresEstimate(): bool
    {
        return self::CORPORATE === $this;
    }

    /**
     * Can this request skip warehouse verification?
     */
    public function skipsWarehouseVerification(): bool
    {
        return false; // All require warehouse verification now
    }
}
