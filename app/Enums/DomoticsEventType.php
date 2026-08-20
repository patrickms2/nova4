<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DomoticsEventType: string implements HasColor, HasLabel
{
    case AccessGranted = 'access_granted';
    case AccessDenied = 'access_denied';
    case DeviceOnline = 'device_online';
    case DeviceOffline = 'device_offline';
    case AutomationTriggered = 'automation_triggered';
    case SensorReading = 'sensor_reading';
    case LightTurnedOn = 'light_turned_on';
    case LightTurnedOff = 'light_turned_off';
    case GateOpenRequested = 'gate_open_requested';
    case GateOpened = 'gate_opened';
    case GateFailed = 'gate_failed';
    case FinishRequested = 'finish_requested';
    case ReportSubmitted = 'report_submitted';
    case ReportRejected = 'report_rejected';
    case ExitGranted = 'exit_granted';
    case ExitDenied = 'exit_denied';
    case SessionFinished = 'session_finished';

    public function getLabel(): string
    {
        return match ($this) {
            self::AccessGranted => 'Acceso concedido',
            self::AccessDenied => 'Acceso denegado',
            self::DeviceOnline => 'Dispositivo online',
            self::DeviceOffline => 'Dispositivo offline',
            self::AutomationTriggered => 'Automatización ejecutada',
            self::SensorReading => 'Lectura de sensor',
            self::LightTurnedOn => 'Luz encendida',
            self::LightTurnedOff => 'Luz apagada',
            self::GateOpenRequested => 'Apertura solicitada',
            self::GateOpened => 'Portón abierto',
            self::GateFailed => 'Fallo del portón',
            self::FinishRequested => 'Fin de jornada solicitado',
            self::ReportSubmitted => 'Parte enviado',
            self::ReportRejected => 'Parte rechazado',
            self::ExitGranted => 'Salida concedida',
            self::ExitDenied => 'Salida denegada',
            self::SessionFinished => 'Jornada finalizada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AccessGranted => 'success',
            self::AccessDenied => 'danger',
            self::DeviceOnline => 'success',
            self::DeviceOffline => 'danger',
            self::AutomationTriggered => 'info',
            self::SensorReading => 'gray',
            self::LightTurnedOn => 'warning',
            self::LightTurnedOff => 'gray',
            self::GateOpenRequested => 'info',
            self::GateOpened => 'success',
            self::GateFailed => 'danger',
            self::FinishRequested => 'info',
            self::ReportSubmitted => 'success',
            self::ReportRejected => 'danger',
            self::ExitGranted => 'success',
            self::ExitDenied => 'danger',
            self::SessionFinished => 'gray',
        };
    }
}
