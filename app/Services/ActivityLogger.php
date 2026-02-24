<?php

namespace App\Services;

use App\Models\ActivityLogModel;

class ActivityLogger
{
    /**
     * Record an admin action to the activity log.
     *
     * @param string   $action      Dot-namespaced action: 'post.created', 'theme.activated', etc.
     * @param string   $subjectType Entity type: 'post', 'page', 'user', 'theme', 'setting', 'marketplace'
     * @param int|null $subjectId   Primary key of the affected entity (null for non-entity actions)
     * @param string   $description Human-readable summary, e.g. "Published post: My Title"
     */
    public static function log(
        string $action,
        string $subjectType,
        ?int   $subjectId,
        string $description
    ): void {
        try {
            $user     = auth()->user();
            $userId   = $user ? $user->id : null;
            $username = $user ? ($user->username ?? '') : 'system';

            // Grab IP from the active request
            $request   = service('request');
            $ipAddress = $request ? $request->getIPAddress() : null;

            (new ActivityLogModel())->insert([
                'user_id'      => $userId,
                'username'     => $username,
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'description'  => mb_substr($description, 0, 255),
                'ip_address'   => $ipAddress,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Never let logging failures break the primary action
            log_message('error', 'ActivityLogger::log failed: ' . $e->getMessage());
        }
    }
}
