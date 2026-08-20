# Mejoras para Flujo WhatsApp Natural y Fluido

## Análisis del Flujo Actual

### Problemas Identificados

1. **Flujo robótico y estructurado**
   - Pide datos uno por uno de forma rígida
   - No hay conversación natural
   - Respuestas muy formales

2. **Falta de contexto conversacional**
   - No recuerda detalles previos de forma natural
   - No detecta cambios de opinión suavemente
   - No adapta respuestas según historial

3. **Sin upselling cruzado**
   - Los 4 negocios (Lanzaloe, La Geria, Sirvo, Taxilanz) no se promocionan entre sí
   - Perdidas oportunidades de venta cruzada

4. **Sin sugerencias proactivas**
   - No recomienda opciones relacionadas
   - No anticipa necesidades del usuario

5. **Manejo pobre de ambigüedades**
   - No pregunta para aclarar
   - Asume demasiado

## Mejoras Propuestas

### 1. Contexto Conversacional Inteligente

**Objetivo:** Recordar y usar contexto previo de forma natural

**Implementación:**
```php
// NovaConversationContextService
class NovaConversationContextService
{
    public function getContext(string $phone): array
    {
        // Últimas 5 conversaciones
        // Preferencias detectadas
        // Negocios visitados
        // Patrones de comportamiento
    }

    public function suggestBasedOnContext(string $phone, string $intent): ?string
    {
        // "Vi que te gustó la bodega, ¿quieres volver?"
        // "¿Te interesa un taxi para llegar?"
    }
}
```

**Ejemplos de respuestas naturales:**
- ❌ "¿Para qué día quieres la reserva?"
- ✅ "¿Te sirve mañana por la tarde o prefieres otro día?"

- ❌ "¿Cuántas personas?"
- ✅ "¿Vendréis solos o sois varios?"

### 2. Detección de Cambios de Intención Suaves

**Objetivo:** Detectar cuando el usuario cambia de opinión sin ser explícito

**Implementación:**
```php
// NovaConversationDataExtractor
private function detectIntentChange(string $message, array $previousConversation): bool
{
    // "En realidad prefiero..." → cambio
    // "Mejor..." → cambio
    // "No, quiero..." → cambio
    // "¿Y si..." → cambio
}
```

**Ejemplos:**
- Usuario: "Quiero reservar mesa"
- Bot: "¿Para cuándo?"
- Usuario: "En realidad prefiero visitar la bodega"
- Bot: "¡Me parece genial! ¿Prefieres mañana por la mañana o tarde?"

### 3. Upselling Cruzado entre los 4 Negocios

**Objetivo:** Promocionar Lanzaloe, La Geria, Sirvo, Taxilanz entre sí

**Patrones de upselling:**

**La Geria → Lanzaloe:**
- "¿Te interesa probar los productos de aloe vera de Lanzaloe?"
- "¿Quieres visitar la finca de aloe después de la bodega?"

**La Geria → Taxilanz:**
- "¿Necesitas un taxi para llegar a la bodega?"
- "¿Te interesa una ruta turística en taxi?"

**Lanzaloe → La Geria:**
- "¿Te gustaría visitar la bodega de La Geria?"
- "¿Quieres probar los vinos con los que hacemos la vinoterapia?"

**Sirvo → La Geria:**
- "¿Te interesa una visita a la bodega después de cenar?"
- "¿Quieres probar los vinos de La Geria?"

**Taxilanz → Todos:**
- "¿Te interesa visitar la bodega de La Geria?"
- "¿Quieres cenar en Sirvo después del tour?"

**Implementación:**
```php
// NovaCrossSellingService
class NovaCrossSellingService
{
    public function suggestCrossSelling(string $currentBusiness, string $intent): array
    {
        return match ($currentBusiness) {
            'la-geria' => [
                'lanzaloe' => '¿Te interesa visitar la finca de aloe vera?',
                'taxilanz' => '¿Necesitas un taxi para llegar?',
            ],
            'lanzaloe' => [
                'la-geria' => '¿Te gustaría visitar la bodega?',
                'taxilanz' => '¿Quieres un taxi para la finca?',
            ],
            'sirvo' => [
                'la-geria' => '¿Visita bodega después de cenar?',
                'taxilanz' => '¿Taxi para volver al hotel?',
            ],
            'taxilanz' => [
                'la-geria' => '¿Visita bodega de La Geria?',
                'sirvo' => '¿Cenar en Sirvo?',
                'lanzaloe' => '¿Visitar finca de aloe?',
            ],
            default => [],
        };
    }
}
```

### 4. Sugerencias Proactivas

**Objetivo:** Anticipar necesidades y sugerir opciones

**Ejemplos:**
- Usuario: "Quiero una mesa para 2 mañana"
- Bot: "¡Perfecto! ¿Te sirve a las 14:00 o prefieres 20:00? También tenemos visita guiada a la bodega a las 11:00 si te interesa."

- Usuario: "Necesito taxi al aeropuerto"
- Bot: "¿A qué hora es tu vuelo? Puedo programar el taxi con tiempo suficiente. ¿Te interesa también un tour por la isla?"

### 5. Respuestas Más Conversacionales

**Objetivo:** Menos robótico, más humano

**Patrones de respuesta:**

**Saludo inicial:**
- ❌ "Hola, ¿en qué puedo ayudarte?"
- ✅ "¡Hola! ¿Qué te apetece hacer hoy? Puedo ayudarte con restaurantes, visitas bodega, taxis..."

**Confirmación de datos:**
- ❌ "Confirmo: mañana a las 14:00 para 2 personas"
- ✅ "¡Genial! Tengo apuntado mañana a las 14:00 para 2 personas. ¿Alguna preferencia especial?"

**Pregunta de datos faltantes:**
- ❌ "Falta la hora"
- ✅ "¿A qué hora te viene bien?"

**Error/ambigüedad:**
- ❌ "No entendí"
- ✅ "¿Te refieres a mañana o pasado mañana?"

### 6. Manejo de Ambigüedades con Preguntas de Aclaración

**Objetivo:** No asumir, preguntar de forma natural

**Ejemplos:**
- Usuario: "Quiero reservar"
- Bot: "¿Te refieres a mesa en restaurante o visita a la bodega?"

- Usuario: "Para 2"
- Bot: "¿Para 2 personas, correcto?"

- Usuario: "Mañana"
- Bot: "¿Mañana por la mañana o tarde?"

### 7. Detección de Emociones/Sentimiento

**Objetivo:** Adaptar tono según estado emocional del usuario

**Implementación:**
```php
// NovaSentimentService
class NovaSentimentService
{
    public function detectSentiment(string $message): string
    {
        // Positivo: "¡genial!", "perfecto", "me encanta"
        // Negativo: "no", "prefiero no", "no me gusta"
        // Urgente: "rápido", "ya", "ahora"
        // Indeciso: "quizás", "no sé", "a ver"
    }

    public function adaptTone(string $sentiment): string
    {
        return match ($sentiment) {
            'positive' => 'entusiasta',
            'negative' => 'empático',
            'urgent' => 'directo',
            'indeciso' => 'sugerente',
            default => 'neutral',
        };
    }
}
```

### 8. Recordatorio de Contexto Anterior Natural

**Objetivo:** Recordar sin sonar robótico

**Ejemplos:**
- Usuario: "Quiero reservar mesa"
- Bot: "¡Claro! Como la última vez reservaste para 4, ¿es el mismo número o sois más/menos esta vez?"

- Usuario: "¿Qué hay de bueno?"
- Bot: "Vi que te gustó la paella la última vez, ¿te apetece repetir o probar algo nuevo?"

## Plan de Implementación

### Fase 1: Contexto y Naturalidad (1-2 semanas)

1. **NovaConversationContextService**
   - Recuperar contexto de conversaciones previas
   - Detectar patrones de comportamiento
   - Sugerir basado en contexto

2. **Mejoras en NovaConversationDataExtractor**
   - Detección de cambios de intención suaves
   - Manejo de ambigüedades
   - Extracción más inteligente de datos

3. **NovaToneAdapter**
   - Adaptar tono según sentimiento
   - Respuestas más conversacionales

### Fase 2: Upselling Cruzado (1 semana)

4. **NovaCrossSellingService**
   - Lógica de upselling entre los 4 negocios
   - Sugerencias proactivas
   - Integración con flujo conversacional

### Fase 3: Pruebas y Ajustes (1 semana)

5. **Escenarios de prueba**
   - Conversaciones naturales
   - Cambios de intención
   - Upselling cruzado
   - Ambigüedades

6. **Ajustes basados en feedback**
   - Afinar respuestas
   - Mejorar detección de intención
   - Optimizar upselling

## Ejemplos de Conversaciones Mejoradas

### Ejemplo 1: Reserva Restaurante con Upselling

**Usuario:** "Quiero reservar mesa en Taberna La Cepa"

**Bot (actual):** "¿Para qué día?"

**Bot (mejorado):** "¡Perfecto elección! Taberna La Cepa tiene tapas increíbles. ¿Te sirve mañana por la noche o prefieres otro día? Por cierto, si te interesa, también tienen visita guiada a la bodega antes de cenar."

### Ejemplo 2: Taxi con Upselling

**Usuario:** "Necesito taxi al aeropuerto"

**Bot (actual):** "¿A qué hora?"

**Bot (mejorado):** "¿A qué hora es tu vuelo? Así calculo el tiempo suficiente. ¿Te interesa también hacer un tour por la isla antes de ir al aeropuerto? Tenemos rutas muy bonitas."

### Ejemplo 3: Cambio de Intención Natural

**Usuario:** "Quiero visitar la bodega"

**Bot:** "¿Te sirve mañana por la mañana?"

**Usuario:** "En realidad prefiero cenar en el restaurante"

**Bot (mejorado):** "¡Ah, perfecto! Taberna La Cepa es genial. ¿Te sirve mañana a las 20:00? ¿O prefieres otro horario?"

### Ejemplo 4: Contexto Previo

**Usuario:** "Quiero reservar"

**Bot (mejorado):** "¡Claro! La última vez reservaste mesa para 4 en La Cepa. ¿Es el mismo número o sois más/menos esta vez? ¿Te apetece repetir o probar algo nuevo?"

## Métricas de Éxito

- **Tasa de conversión:** % de conversaciones que terminan en reserva
- **Satisfacción:** Feedback de usuarios
- **Upselling:** % de ventas cruzadas exitosas
- **Tiempo de conversación:** Menos tiempo, más natural
- **Tasa de abandono:** % de usuarios que abandonan la conversación
