<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ParcelEventType;
use App\Enums\ParcelStatus;
use App\Exceptions\IllegalStatusTransitionException;
use App\Models\Parcel;
use App\Models\ParcelEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The ONE place that mutates parcel.status.
 *
 * Validates the transition against ParcelStatus::canTransitionTo() (ADR 0002).
 * On illegal transitions: writes an ILLEGAL_TRANSITION_ATTEMPT audit row and throws.
 * On success: updates parcel + writes a parcel_event row in the same transaction.
 */
class ScanService
{
    /**
     * @param  array{lat?: float, lng?: float}|null  $geo
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Parcel $parcel,
        ParcelEventType $eventType,
        ?User $actor = null,
        string $scanMode = 'qr',
        ?string $deviceId = null,
        ?array $geo = null,
        array $metadata = [],
        ?Carbon $occurredAt = null,
    ): ParcelEvent {
        $occurredAt = $occurredAt ?? Carbon::now();

        // Special case: an audit-only event type that doesn't transition the parcel.
        if ($eventType === ParcelEventType::ILLEGAL_TRANSITION_ATTEMPT) {
            return $this->writeEvent($parcel, $eventType, null, null, $actor, $scanMode, $deviceId, $geo, $metadata, $occurredAt);
        }

        $from = $parcel->status;
        $to = ParcelStatus::from($eventType->value);

        if (! $from->canTransitionTo($to)) {
            // Audit the attempt before throwing.
            $this->writeEvent(
                $parcel,
                ParcelEventType::ILLEGAL_TRANSITION_ATTEMPT,
                $from->value,
                $to->value,
                $actor,
                $scanMode,
                $deviceId,
                $geo,
                array_merge($metadata, ['attempted_event' => $eventType->value]),
                $occurredAt,
            );

            throw new IllegalStatusTransitionException($parcel->id, $from, $to);
        }

        return DB::transaction(function () use ($parcel, $eventType, $from, $to, $actor, $scanMode, $deviceId, $geo, $metadata, $occurredAt) {
            $parcel->status = $to;
            $parcel->status_changed_at = $occurredAt;
            $parcel->save();

            $event = $this->writeEvent($parcel, $eventType, $from->value, $to->value, $actor, $scanMode, $deviceId, $geo, $metadata, $occurredAt);
            
            \App\Events\ParcelStatusChanged::dispatch($parcel, $event);
            
            return $event;
        });
    }

    /**
     * @param  array{lat?: float, lng?: float}|null  $geo
     * @param  array<string, mixed>  $metadata
     */
    private function writeEvent(
        Parcel $parcel,
        ParcelEventType $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?User $actor,
        string $scanMode,
        ?string $deviceId,
        ?array $geo,
        array $metadata,
        Carbon $occurredAt,
    ): ParcelEvent {
        $event = new ParcelEvent([
            'parcel_id' => $parcel->id,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_user_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'trip_id' => $parcel->trip_id,
            'scan_mode' => $scanMode,
            'device_id' => $deviceId,
            'metadata' => $metadata,
            'occurred_at' => $occurredAt,
        ]);

        $event->save();

        if ($geo && isset($geo['lat'], $geo['lng'])) {
            $event->geo_lat = (float) $geo['lat'];
            $event->geo_lng = (float) $geo['lng'];
            $event->save();
        }

        return $event;
    }
}
