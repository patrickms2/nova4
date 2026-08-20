<?php

namespace App\Http\Controllers\Api;

use AdultDate\FilamentWirechat\Models\Conversation;
use App\Enums\TaxistaExpenseStatus;
use App\Http\Controllers\Controller;
use App\Models\Taxista;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaDocument;
use App\Models\TaxistaExpense;
use App\Models\TaxistaExpensePayment;
use App\Models\TaxistaTaxi;
use App\Models\TaxistaTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PortalController extends Controller
{
    /**
     * Obtener datos del taxista autenticado
     */
    public function profile()
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $taxista = Taxista::find($user->id);

        if (! $taxista) {
            return response()->json(['error' => 'Taxista no encontrado'], 404);
        }

        return response()->json([
            'id' => $taxista->id,
            'name' => $taxista->name,
            'email' => $taxista->email,
            'phone' => $taxista->phone ?? null,
            'nif' => $taxista->nif ?? null,
            'role' => $taxista->role,
        ]);
    }

    /**
     * Obtener estadísticas del portal
     */
    public function stats()
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $taxistaId = $user->id;

        // Próxima cita
        $nextAppointment = TaxistaAppointment::query()
            ->where('taxista_user_id', $taxistaId)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        $stats = [
            'taxis' => $this->hasTable('taxista_taxis')
                ? TaxistaTaxi::query()->where('taxista_user_id', $taxistaId)->count()
                : 0,
            'appointments' => TaxistaAppointment::query()
                ->where('taxista_user_id', $taxistaId)
                ->count(),
            'documents' => TaxistaDocument::query()
                ->where('taxista_user_id', $taxistaId)
                ->count(),
            'tickets' => TaxistaTicket::query()
                ->where('user_id', $taxistaId)
                ->whereIn('status', ['abierto', 'en_progreso', 'en_proceso'])
                ->count(),
            'expenses' => $this->hasTable('taxista_expenses')
                ? TaxistaExpense::query()->where('taxista_user_id', $taxistaId)->count()
                : 0,
            'payments' => ($this->hasTable('taxista_expenses') && $this->hasTable('taxista_expense_payments'))
                ? TaxistaExpensePayment::query()
                    ->whereHas('expense', fn ($query) => $query->where('taxista_user_id', $taxistaId))
                    ->count()
                : 0,
            'chats' => /*class_exists('\AdultDate\FilamentWirechat\Models\Conversation')
                ? Conversation::query()
                    ->whereHas('participants', fn ($q) => $q->where('participant_id', $taxistaId))
                    ->count()
                :*/ 0,
            'nextAppointment' => $nextAppointment?->starts_at?->format('d/m/Y H:i'),
        ];

        return response()->json($stats);
    }

    /**
     * Obtener documentos del taxista
     */
    public function documents(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $taxistaId = $user->id;
        $type = $request->get('type');
        $year = $request->get('year');
        $month = $request->get('month');

        $query = TaxistaDocument::query()
            ->with('department')
            ->where('taxista_user_id', $taxistaId);

        if ($type) {
            $query->where('document_type', $type);
        }

        $documents = $query
            ->orderByDesc('is_favorite')
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name ?? $doc->file_name ?? 'Documento sin nombre',
                    'document_type' => $doc->document_type,
                    'is_favorite' => $doc->is_favorite,
                    'uploaded_at' => $doc->uploaded_at?->format('d/m/Y H:i') ?? $doc->created_at->format('d/m/Y H:i'),
                    'size' => $this->formatFileSize($doc->file_size ?? 0),
                    'status' => $doc->status ?? 'Revisado',
                    'status_tone' => $this->getStatusTone($doc->status ?? 'Revisado'),
                    'tag' => strtoupper($doc->document_type ?? 'DOCUMENTO'),
                    'file_path' => $doc->file_path,
                    'department' => $doc->department?->name,
                ];
            });

        // Agrupar por tipo, año y mes para estructura jerárquica
        $grouped = $this->groupDocumentsByTypeYearMonth($documents);

        return response()->json([
            'documents' => $documents,
            'grouped' => $grouped,
            'counts' => $this->getDocumentCounts($taxistaId),
        ]);
    }

    /**
     * Obtener citas del taxista
     */
    public function appointments(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $taxistaId = $user->id;
        $limit = $request->get('limit', 10);

        $appointments = TaxistaAppointment::query()
            ->with(['department', 'createdBy'])
            ->where('taxista_user_id', $taxistaId)
            ->orderBy('starts_at')
            ->limit($limit)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->title ?? 'Cita sin título',
                    'starts_at' => $appointment->starts_at->format('d/m/Y H:i'),
                    'ends_at' => $appointment->ends_at?->format('d/m/Y H:i'),
                    'status' => $appointment->status ?? 'Pendiente',
                    'department' => $appointment->department?->name,
                    'is_today' => $appointment->starts_at->isToday(),
                    'time_until' => $this->getTimeUntil($appointment->starts_at),
                ];
            });

        return response()->json($appointments);
    }

    /**
     * Obtener tickets del taxista
     */
    public function tickets(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $taxistaId = $user->id;
        $status = $request->get('status');
        $limit = $request->get('limit', 10);

        $query = TaxistaTicket::query()
            ->with(['department', 'createdBy', 'assignedTo'])
            ->where('user_id', $taxistaId);

        if ($status) {
            $query->where('status', $status);
        }

        $tickets = $query
            ->orderByDesc('opened_at')
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'priority' => $ticket->priority ?? 'media',
                    'status' => $ticket->status ?? 'abierto',
                    'opened_at' => $ticket->opened_at->format('d/m/Y H:i'),
                    'department' => $ticket->department?->name,
                    'priority_tone' => $this->getPriorityTone($ticket->priority ?? 'media'),
                    'status_tone' => $this->getStatusTone($ticket->status ?? 'abierto'),
                ];
            });

        return response()->json($tickets);
    }

    /**
     * Obtener gastos del taxista
     */
    public function expenses()
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if (! $this->hasTable('taxista_expenses')) {
            return response()->json(['total' => 0, 'paid' => 0, 'items' => []]);
        }

        $taxistaId = $user->id;
        $expenses = TaxistaExpense::query()
            ->with(['category', 'subcategory'])
            ->where('taxista_user_id', $taxistaId)
            ->orderByDesc('expense_date')
            ->get();

        $total = $expenses->sum('final_amount') ?: $expenses->sum('amount');
        $paid = $expenses->sum('paid_amount');

        $items = $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'title' => $expense->title,
                'amount' => (float) ($expense->final_amount ?: $expense->amount),
                'paid' => (float) $expense->paid_amount,
                'date' => $expense->expense_date->format('d/m/Y'),
                'category' => $expense->category?->name ?? 'General',
                'status' => ucfirst($expense->status->value ?? 'pendiente'),
                'statusTone' => $this->getStatusTone($expense->status->value ?? 'pendiente'),
            ];
        });

        return response()->json([
            'total' => (float) $total,
            'paid' => (float) $paid,
            'items' => $items,
        ]);
    }

    /**
     * Obtener feed de actividad del taxista
     */
    public function feed()
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $taxistaId = $user->id;
        $feed = [];

        // Próximas citas
        $nextAppointments = TaxistaAppointment::query()
            ->with('department')
            ->where('taxista_user_id', $taxistaId)
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addDays(7))
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        foreach ($nextAppointments as $appointment) {
            $feed[] = [
                'id' => 'appointment_'.$appointment->id,
                'module' => 'citas',
                'severity' => $appointment->starts_at->diffInHours(now()) <= 24 ? 'high' : 'medium',
                'actionable' => true,
                'ts' => $appointment->starts_at->timestamp,
                'title' => $appointment->title ?? 'Cita programada',
                'subtitle' => $appointment->starts_at->format('d/m/Y H:i').' · '.($appointment->department?->name ?? 'Sin departamento'),
                'badge' => $this->getTimeUntil($appointment->starts_at),
                'cta' => 'Ver cita',
                'route' => ['view' => 'feed'],
            ];
        }

        // Tickets abiertos
        $openTickets = TaxistaTicket::query()
            ->with('department')
            ->where('user_id', $taxistaId)
            ->whereIn('status', ['abierto', 'en_progreso', 'en_proceso'])
            ->orderByDesc('opened_at')
            ->limit(5)
            ->get();

        foreach ($openTickets as $ticket) {
            $feed[] = [
                'id' => 'ticket_'.$ticket->id,
                'module' => 'tickets',
                'severity' => $ticket->priority === 'critica' ? 'critical' : ($ticket->priority === 'alta' ? 'high' : 'medium'),
                'actionable' => true,
                'ts' => $ticket->opened_at->timestamp,
                'title' => $ticket->title,
                'subtitle' => 'Ticket · '.($ticket->department?->name ?? 'Sin departamento'),
                'badge' => ucfirst($ticket->status ?? 'abierto'),
                'cta' => 'Ver ticket',
                'route' => ['view' => 'feed'],
            ];
        }

        // Pagos pendientes (Gastos)
        if ($this->hasTable('taxista_expenses')) {
            $pendingExpenses = TaxistaExpense::query()
                ->where('taxista_user_id', $taxistaId)
                ->whereIn('status', [TaxistaExpenseStatus::Pending, TaxistaExpenseStatus::Partial])
                ->orderBy('due_date')
                ->limit(3)
                ->get();

            foreach ($pendingExpenses as $expense) {
                $feed[] = [
                    'id' => 'expense_'.$expense->id,
                    'module' => 'gastos',
                    'severity' => $expense->due_date && $expense->due_date->isPast() ? 'high' : 'medium',
                    'actionable' => true,
                    'ts' => $expense->due_date?->timestamp ?? $expense->created_at->timestamp,
                    'title' => $expense->title,
                    'subtitle' => 'Pago pendiente · '.number_format($expense->remaining, 2).'€',
                    'badge' => $expense->due_date ? 'Vence '.$expense->due_date->format('d/m') : 'Pendiente',
                    'cta' => 'Ver detalles',
                    'route' => ['view' => 'feed'],
                ];
            }
        }

        // Mensajes de chat no leídos (si es posible detectar)
        /*if (class_exists('\AdultDate\FilamentWirechat\Models\Conversation')) {
            $unreadConversations = Conversation::query()
                ->whereHas('participants', fn ($q) => $q->where('participant_id', $taxistaId))
                ->whereHas('messages', fn ($q) => $q->where('user_id', '!=', $taxistaId)->whereNull('read_at'))
                ->limit(3)
                ->get();

            foreach ($unreadConversations as $conv) {
                $feed[] = [
                    'id' => 'chat_'.$conv->id,
                    'module' => 'chat',
                    'severity' => 'medium',
                    'actionable' => true,
                    'ts' => $conv->updated_at->timestamp,
                    'title' => 'Mensaje nuevo',
                    'subtitle' => 'Chat · '.($conv->name ?? 'Departamento'),
                    'badge' => 'Nuevo',
                    'cta' => 'Ir al chat',
                    'route' => ['view' => 'chat'],
                ];
            }
        }*/

        // Documentos recientes
        $recentDocuments = TaxistaDocument::query()
            ->where('taxista_user_id', $taxistaId)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        foreach ($recentDocuments as $doc) {
            $feed[] = [
                'id' => 'document_'.$doc->id,
                'module' => $doc->document_type ?? 'documentos',
                'severity' => 'low',
                'actionable' => false,
                'ts' => $doc->created_at->timestamp,
                'title' => $doc->name ?? 'Nuevo documento',
                'subtitle' => 'Documento · '.ucfirst($doc->document_type ?? 'general'),
                'badge' => 'Nuevo',
                'cta' => 'Ver documento',
                'route' => ['view' => 'docs', 'type' => $doc->document_type],
            ];
        }

        // Ordenar por severidad y timestamp
        usort($feed, function ($a, $b) {
            $severityOrder = ['critical' => 3, 'high' => 2, 'medium' => 1, 'low' => 0];
            $severityDiff = ($severityOrder[$b['severity']] ?? 0) - ($severityOrder[$a['severity']] ?? 0);
            if ($severityDiff !== 0) {
                return $severityDiff;
            }

            return $b['ts'] - $a['ts'];
        });

        return response()->json(array_slice($feed, 0, 20));
    }

    // Métodos auxiliares
    private function hasTable(string $table): bool
    {
        static $cache = [];
        if (! isset($cache[$table])) {
            $cache[$table] = Schema::hasTable($table);
        }

        return $cache[$table];
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }

    private function getStatusTone(string $status): string
    {
        $status = strtolower($status);

        return match ($status) {
            'cerrado', 'resuelto' => 'emerald',
            'en_progreso', 'en_proceso' => 'blue',
            'abierto', 'pendiente' => 'amber',
            'pagado' => 'emerald',
            default => 'slate',
        };
    }

    private function getPriorityTone(string $priority): string
    {
        $priority = strtolower($priority);

        return match ($priority) {
            'critica', 'critical' => 'rose',
            'alta', 'high' => 'amber',
            'media', 'medium' => 'blue',
            'baja', 'low' => 'slate',
            default => 'slate',
        };
    }

    private function getTimeUntil($date): string
    {
        if (! $date) {
            return '';
        }

        $now = now();
        if ($date->isToday()) {
            if ($date->isFuture()) {
                return 'En '.$date->diffInHours($now).'h';
            }

            return 'Hoy';
        }

        if ($date->isTomorrow()) {
            return 'Mañana';
        }

        $days = $date->diffInDays($now);

        return 'En '.$days.' días';
    }

    private function groupDocumentsByTypeYearMonth($documents): array
    {
        $grouped = [];

        foreach ($documents as $doc) {
            $type = $doc['document_type'] ?? 'otros';
            $date = $doc['uploaded_at'];

            // Extraer año y mes de la fecha
            if (preg_match('/(\d{4})\/(\d{2})\//', $date, $matches)) {
                $year = (int) $matches[1];
                $month = (int) $matches[2];
            } else {
                $year = now()->year;
                $month = now()->month;
            }

            if (! isset($grouped[$type])) {
                $grouped[$type] = [
                    'label' => ucfirst($type),
                    'color' => $this->getDocumentTypeColor($type),
                    'icon' => $this->getDocumentTypeIcon($type),
                    'years' => [],
                ];
            }

            if (! isset($grouped[$type]['years'][$year])) {
                $grouped[$type]['years'][$year] = ['months' => []];
            }

            if (! isset($grouped[$type]['years'][$year]['months'][$month])) {
                $grouped[$type]['years'][$year]['months'][$month] = [];
            }

            $grouped[$type]['years'][$year]['months'][$month][] = $doc;
        }

        return $grouped;
    }

    private function getDocumentCounts(int $taxistaId): array
    {
        $counts = TaxistaDocument::query()
            ->where('taxista_user_id', $taxistaId)
            ->selectRaw('document_type, COUNT(*) as total')
            ->groupBy('document_type')
            ->pluck('total', 'document_type')
            ->toArray();

        return [
            'nomina' => $counts['nomina'] ?? 0,
            'impuesto' => $counts['impuesto'] ?? 0,
            'certificado' => $counts['certificado'] ?? 0,
            'seguro' => $counts['seguro'] ?? 0,
            'otros' => $counts['otros'] ?? 0,
        ];
    }

    private function getDocumentTypeColor(string $type): string
    {
        return match ($type) {
            'nomina' => 'violet',
            'impuesto', 'cuotas' => 'blue',
            'certificado', 'agencias' => 'amber',
            'seguro' => 'emerald',
            'repuestos' => 'orange',
            default => 'slate',
        };
    }

    private function getDocumentTypeIcon(string $type): string
    {
        return match ($type) {
            'nomina' => '🧾',
            'impuesto', 'cuotas' => '💳',
            'certificado', 'agencias' => '🏢',
            'seguro' => '🛡️',
            'repuestos' => '🔧',
            default => '📄',
        };
    }
}
