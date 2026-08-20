<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Taxilanz</title>
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
        .credentials {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
            text-align: center;
        }
        .credential-item {
            margin: 15px 0;
        }
        .credential-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .credential-value {
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
        .security-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🚖 Taxilanz</div>
            <h1 class="title">¡Bienvenido al Equipo!</h1>
            <p class="subtitle">Tus credenciales de acceso al sistema</p>
        </div>

        <p>Hola <strong>{{ $employeeName }}</strong>,</p>
        
        <p>Te damos la bienvenida al sistema de gestión Taxilanz. Has sido registrado como empleado del departamento <strong>{{ $departmentName }}</strong>.</p>

        <div class="credentials">
            <h2 style="margin-top: 0;">🔐 Tus Credenciales de Acceso</h2>
            
            <div class="credential-item">
                <div class="credential-label">Correo Electrónico</div>
                <div class="credential-value">{{ $employeeEmail }}</div>
            </div>
            
            <div class="credential-item">
                <div class="credential-label">Contraseña Temporal</div>
                <div class="credential-value">{{ $password }}</div>
            </div>
        </div>

        <div class="security-note">
            <strong>⚠️ Importante:</strong> Por seguridad, te recomendamos cambiar tu contraseña en tu primer inicio de sesión.
        </div>

        <div class="info">
            <h3>📋 ¿Qué puedes hacer en el sistema?</h3>
            <ul>
                <li>Gestionar tus citas y turnos</li>
                <li>Ver y gestionar documentos</li>
                <li>Crear y seguir tickets de soporte</li>
                <li>Acceder a información de tu departamento</li>
                <li>Registrar gastos y reportes</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ $loginUrl }}" class="button">
                🚀 Acceder al Sistema
            </a>
        </div>

        <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactar al equipo de soporte.</p>

        <div class="footer">
            <p>Este es un correo automático generado por el sistema Taxilanz.<br>
            Si no solicitaste este registro, por favor contacta al administrador.</p>
            <p>© {{ date('Y') }} Taxilanz - Sistema de Gestión</p>
        </div>
    </div>
</body>
</html>
