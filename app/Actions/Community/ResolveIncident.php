<?php

namespace App\Actions\Community;

use App\Models\Incident;

class ResolveIncident
{
    public function handle(Incident $incident, string $status, ?int $actorId, ?string $note = null): void
    {
        $incident->update([
            'status' => $status,
            'resolved_by' => in_array($status, ['resolved', 'closed'], true) ? $actorId : null,
            'resolved_at' => in_array($status, ['resolved', 'closed'], true) ? now() : null,
            'resolution_note' => $note,
            'updated_by' => $actorId,
        ]);
    }
}
