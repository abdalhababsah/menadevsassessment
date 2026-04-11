<?php

namespace App\Queries\Dashboard;

use Spatie\Activitylog\Models\Activity;

class RecentActivityQuery
{
    public function execute(): array
    {
        return Activity::with(['causer', 'subject'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'subject_type' => $activity->subject_type,
                    'causer' => $activity->causer ? [
                        'name' => $activity->causer->name,
                        'email' => $activity->causer->email,
                    ] : null,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }
}
