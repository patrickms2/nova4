<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Rol Asignado - Taxilanz</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .title {
            color: #495057;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6c757d;
            font-size: 16px;
        }
        .role-change {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: center;
        }
        .role-item {
            margin: 15px 0;
        }
        .role-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .role-value {
            font-size: 18px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
        }
        .info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
        }
        .info h3 {
            color: #2563eb;
            margin-top: 0;
        }
        .button {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 20px 0;
            transition: background-color 0.3s;
        }
        .button:hover {
            background: #1d4ed8;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .department-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🚖 Taxilanz</div>
            <h1 class="title">🔄 Actualización de Rol</h1>
            <p class="subtitle">Tu rol en el sistema ha sido actualizado</p>
        </div>

        <p>Hola <strong>{{ $userName }}</strong>,</p>
        
        <p>Te informamos que tu rol en el sistema Taxilanz ha sido actualizado. Ahora tienes acceso como <strong>empleado</strong> del departamento <strong>{{ $departmentName }}</strong>.</p>

        <div class="role-change">
            <h2 style="margin-top: 0;">🎭 Cambio de Rol</h2>
            
            <div class="role-item">
                <div class="role-label">Rol Anterior</div>
                <div class="role-value">{{ $previousRole }}</div>
            </div>
            
            <div class="role-item">
                <div class="role-label">Nuevo Rol</div>
                <div class="role-value">{{ $newRole }}</div>
            </div>
        </div>

        <div class="department-info">
            <strong>📁 Departamento Asignado:</strong> {{ $departmentName }}
        </div>

        <div class="info">
            <h3>🎯 ¿Qué cambia con tu nuevo rol?</h3>
            <ul>
                <li>Acceso al panel de empleados con herramientas específicas</li>
                <li>Gestión de citas y turnos de tu departamento</li>
                <li>Acceso a documentos y recursos compartidos</li>
                <li>Capacidad para crear y gestionar tickets de soporte</li>
                <li>Reportes y métricas de tu departamento</li>
                <li>Colaboración con otros empleados del sistema</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="button">
                🚀 Acceder al Sistema
            </a>
        </div>

        <p>Si tienes alguna pregunta sobre tu nuevo rol o necesitas capacitación, no dudes en contactar al administrador del sistema.</p>

        <div class="footer">
            <p>Este es un correo automático generado por el sistema Taxilanz.<br>
            Si no reconoces este cambio, por favor contacta al administrador inmediatamente.</p>
            <p>© {{ date('Y') }} Taxilanz - Sistema de Gestión</p>
        </div>
    </div>
</body>
</html>
