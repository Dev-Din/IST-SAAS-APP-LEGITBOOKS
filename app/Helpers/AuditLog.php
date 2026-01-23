<?php

namespace App\Helpers;

use App\Models\PlatformAuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLog
{
    /**
     * Record an audit log entry
     *
     * @param  Model|string  $actor  The admin/user who performed the action
     * @param  string  $action  The action performed (e.g., 'admin.invite.created', 'admin.invite.accepted')
     * @param  Model|string|null  $target  The target of the action (e.g., AdminInvitation, Admin)
     * @param  array  $meta  Additional metadata
     */
    public static function record($actor, string $action, $target = null, array $meta = []): PlatformAuditLog
    {
        $actorId = null;
        $actorType = null;

        if ($actor instanceof Model) {
            $actorId = $actor->id;
            $actorType = get_class($actor);
        } elseif (is_string($actor)) {
            $actorType = $actor;
        }

        $targetId = null;
        $targetType = null;

        if ($target instanceof Model) {
            $targetId = $target->id;
            $targetType = get_class($target);
        } elseif (is_string($target)) {
            $targetType = $target;
        }

        $details = array_merge($meta, [
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ]);

        return PlatformAuditLog::create([
            'admin_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
        ]);
    }
}
