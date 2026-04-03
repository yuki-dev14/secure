<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public static function log(
        string $event,
        ?Model $model   = null,
        array  $old     = [],
        array  $new     = [],
        string $desc    = '',
        string $tag     = '',
    ): AuditLog {
        return AuditLog::record($event, $model, $old, $new, $desc ?: null, $tag ?: null);
    }

    public static function loginSuccess(string $role): void
    {
        static::log('login', null, [], [], "Successful login as {$role}", 'auth');
    }

    public static function loginFailed(string $identifier): void
    {
        AuditLog::create([
            'user_id'     => null,
            'user_type'   => 'guest',
            'event'       => 'login_failed',
            'description' => "Failed login attempt for: {$identifier}",
            'tags'        => 'auth,security',
            'url'         => request()->fullUrl(),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'created_at'  => now(),
        ]);
    }

    public static function created(Model $model, array $attributes = []): void
    {
        static::log('created', $model, [], $attributes ?: $model->toArray(), 'Record created', 'crud');
    }

    public static function updated(Model $model, array $original = [], array $changes = []): void
    {
        static::log('updated', $model, $original ?: $model->getOriginal(), $changes ?: $model->getChanges(), 'Record updated', 'crud');
    }

    public static function deleted(Model $model): void
    {
        static::log('deleted', $model, $model->toArray(), [], 'Record deleted', 'crud');
    }

    public static function qrScanned(int $beneficiaryId, string $scannedBy): void
    {
        static::log('qr_scanned', null, [], ['beneficiary_id' => $beneficiaryId, 'scanned_by' => $scannedBy], 'QR Code scanned', 'distribution,security');
    }

    public static function grantReleased(int $beneficiaryId, float $amount, string $transactionRef): void
    {
        static::log('grant_released', null, [], [
            'beneficiary_id'        => $beneficiaryId,
            'amount'                => $amount,
            'transaction_reference' => $transactionRef,
        ], "Cash grant of ₱{$amount} released", 'distribution');
    }

    public static function doubleClaim(int $beneficiaryId, string $distributionEventId): void
    {
        static::log('double_claim_attempt', null, [], [
            'beneficiary_id'        => $beneficiaryId,
            'distribution_event_id' => $distributionEventId,
        ], 'ALERT: Double claim attempt detected!', 'fraud,security');
    }
}
