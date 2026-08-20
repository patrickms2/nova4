<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeRoleAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $previousRole
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo Rol Asignado - Sistema Taxilanz',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-role-assigned',
            with: [
                'userName' => $this->user->name,
                'previousRole' => $this->previousRole ?? 'sin rol',
                'newRole' => 'empleado',
                'departmentName' => $this->user->bookingDepartment?->name ?? 'No asignado',
                'loginUrl' => config('app.url') . '/app',
            ]
        );
    }
}
