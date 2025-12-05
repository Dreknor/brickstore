<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'bricklink_store_name',
        'is_active',
        'bl_consumer_key',
        'bl_consumer_secret',
        'bl_token',
        'bl_token_secret',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
        'nextcloud_url',
        'nextcloud_username',
        'nextcloud_password',
        'nextcloud_invoice_path',
        'invoice_number_format',
        'invoice_number_counter',
        'is_small_business',
        'is_setup_complete',
        'company_name',
        'owner_name',
        'street',
        'postal_code',
        'city',
        'country',
        'tax_number',
        'vat_id',
        'phone',
        'email',
        'bank_name',
        'bank_account_holder',
        'iban',
        'bic',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_small_business' => 'boolean',
            'is_setup_complete' => 'boolean',
            'invoice_number_counter' => 'integer',
            'bl_consumer_key' => 'encrypted',
            'bl_consumer_secret' => 'encrypted',
            'bl_token' => 'encrypted',
            'bl_token_secret' => 'encrypted',
            'smtp_password' => 'encrypted',
            'nextcloud_password' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function hasBrickLinkCredentials(): bool
    {
        return ! empty($this->bl_consumer_key)
            && ! empty($this->bl_consumer_secret)
            && ! empty($this->bl_token)
            && ! empty($this->bl_token_secret);
    }

    public function hasSmtpCredentials(): bool
    {

        Log::debug('Checking SMTP credentials', [
            'host' => $this->smtp_host,
            'port' => $this->smtp_port,
            'username' => $this->smtp_username,
            'password' => ! empty($this->smtp_password) ? '***' : null,
            // Do not log password for security reasons
        ]);

        // Host and port are required
        // Username and password are optional (e.g. MailHog)
        return ! empty($this->smtp_host)
            && ! empty($this->smtp_port);
    }

    public function hasNextcloudCredentials(): bool
    {
        return ! empty($this->nextcloud_url)
            && ! empty($this->nextcloud_username)
            && ! empty($this->nextcloud_password);
    }
}
