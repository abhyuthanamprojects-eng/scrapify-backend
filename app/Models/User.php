<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, \App\Traits\BelongsToPartner, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'referral_code',
        'otp',
        'otp_expires_at',
        'wallet_balance',
        'status',
        'profile_photo_path',
        'bank_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'city_id',
        'latitude',
        'longitude',
        'pincode',
        'vehicle_number',
        'employee_id',
        'is_online',
        'is_manual_offline',
        'is_available',
        'daily_capacity',
        'location_updated_at',
        'last_active_at',
        'warehouse_id',
        'channel_partner_id',
        'language',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_active_at' => 'datetime',
            'location_updated_at' => 'datetime',
            'is_online' => 'boolean',
            'is_manual_offline' => 'boolean',
            'is_available' => 'boolean',
            'daily_capacity' => 'integer',
        ];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getAccessibleWarehouseIds(): array
    {
        $ids = \App\Models\Warehouse::where('manager_id', $this->id)->pluck('id')->toArray();
        if ($this->warehouse_id && !in_array($this->warehouse_id, $ids)) {
            $ids[] = $this->warehouse_id;
        }
        return $ids;
    }

    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'pickup_boy_warehouse', 'pickup_boy_id', 'warehouse_id')
            ->withPivot(['status', 'created_by'])
            ->withTimestamps();
    }

    public function activeWarehouses()
    {
        return $this->warehouses()->wherePivot('status', 'active');
    }

    public function pickupRequests()
    {
        return $this->hasMany(PickupRequest::class, 'customer_id');
    }

    public function todayAssignments()
    {
        return $this->assignments()->whereDate('assigned_at', now()->toDateString());
    }

    /**
     * Logic for dynamic "online" status.
     * Online if:
     * 1. Not manually marked offline.
     * 2. Between 08:00 AM and 07:00 PM (19:00).
     */
    public function getIsOnlineAttribute()
    {
        if ($this->is_manual_offline) {
            return false;
        }

        $now = now()->timezone('Asia/Kolkata');
        $startTime = $now->copy()->setTime(8, 0);
        $endTime = $now->copy()->setTime(19, 0);

        return $now->between($startTime, $endTime);
    }

    /**
     * Get count of active assignments today.
     */
    public function getTodayAssignmentsCountAttribute()
    {
        return $this->todayAssignments()
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->count();
    }

    /**
     * Check if driver has reached daily capacity.
     */
    public function getIsCapacityFullAttribute()
    {
        return $this->today_assignments_count >= $this->daily_capacity;
    }

    /**
     * Scope for drivers who are "Online" based on time and manual toggle.
     */
    public function scopeOnline($query)
    {
        $now = now()->timezone('Asia/Kolkata');
        $startTime = $now->copy()->setTime(8, 0)->format('H:i:s');
        $endTime = $now->copy()->setTime(19, 0)->format('H:i:s');

        return $query->where('is_manual_offline', false)
            ->whereRaw("TIME(CONVERT_TZ(NOW(), 'UTC', 'Asia/Kolkata')) BETWEEN ? AND ?", [$startTime, $endTime]);
    }

    public function referralsGiven()
    {
        return $this->hasMany(Referral::class, 'referrer_user_id');
    }

    public function referralReceived()
    {
        return $this->hasOne(Referral::class, 'referred_user_id');
    }

    public function referralCoupons()
    {
        return $this->hasMany(ReferralCoupon::class, 'user_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'pickup_boy_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function kycDocument()
    {
        return $this->hasOne(KycDocument::class);
    }

    public function managedWarehouses()
    {
        return $this->hasMany(Warehouse::class, 'manager_id');
    }

    public function settlements()
    {
        return $this->hasMany(Settlement::class, 'partner_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function paymentDetails()
    {
        return $this->hasMany(PaymentDetail::class);
    }

    public function locations()
    {
        return $this->hasMany(PickupBoyLocation::class, 'pickup_boy_id');
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string|array|null
     */
    public function routeNotificationForFcm($notification)
    {
        return $this->fcm_token;
    }

    /**
     * Get the URL for the user's profile photo.
     *
     * @return string|null
     */
    public function getProfilePhotoUrlAttribute()
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)) {
            return $this->profile_photo_path;
        }

        if (
            str_starts_with($this->profile_photo_path, 'profile_photos/')
            || Storage::disk('public')->exists($this->profile_photo_path)
        ) {
            return Storage::disk('public')->url($this->profile_photo_path);
        }

        return asset($this->profile_photo_path);
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            if (in_array(SoftDeletes::class, class_uses_recursive(static::class)) && !$user->isForceDeleting()) {
                $timestamp = time();
                $updates = [];
                if ($user->phone && !str_contains($user->phone, '_del_')) {
                    $user->phone = $user->phone . '_del_' . $timestamp;
                    $updates['phone'] = $user->phone;
                }
                if ($user->email && !str_contains($user->email, '_del_')) {
                    $user->email = $user->email . '_del_' . $timestamp;
                    $updates['email'] = $user->email;
                }
                if ($user->referral_code) {
                    $user->referral_code = null;
                    $updates['referral_code'] = null;
                }
                if ($user->employee_id && !str_contains($user->employee_id, '_del_')) {
                    $user->employee_id = $user->employee_id . '_del_' . $timestamp;
                    $updates['employee_id'] = $user->employee_id;
                }
                if (!empty($updates)) {
                    $user->saveQuietly();
                }
            }
        });
    }

    public function ensureEmployeeId(string $prefix = 'PB'): string
    {
        if (!empty($this->employee_id)) {
            return $this->employee_id;
        }

        $employeeId = sprintf('%s-%05d', strtoupper($prefix), $this->id);

        $this->forceFill([
            'employee_id' => $employeeId,
        ])->saveQuietly();

        return $employeeId;
    }

    protected $appends = [
        'profile_photo_url',
    ];
}
