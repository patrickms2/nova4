<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmployeeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $employee,
        public string $password
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenido al Sistema Taxilanz - Credenciales de Acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-employee',
            with: [
                'employeeName' => $this->employee->name,
                'employeeEmail' => $this->employee->email,
                'password' => $this->password,
                'departmentName' => $this->employee->bookingDepartment?->name ?? 'No asignado',
                'loginUrl' => config('app.url') . '/app',
            ]
        );
    }
}
