
# Nova – Engineering Rules

Estas reglas evitan inconsistencias en el proyecto.

## Naming

Tablas:
snake_case

Modelos:
PascalCase

Resources:
PascalCase

Foreign keys:
singular_id

Ejemplo:

user_id  
department_id  
taxista_id

---

## Organización de dominios

El código debe seguir tres dominios principales.

Taxi  
HRM  
Central

---

## Filament Resources

Estructura recomendada:

App Panel

Clusters:

Taxistas  
Empleados  
Central

Admin Panel

Configuración global:

Tipos  
Estados  
Categorías

---

## Regla para agentes

Cualquier agente debe leer primero:

1. project vision
2. technical spec
3. ui system
4. engineering rules

Estas reglas definen el **Project Operating System de Nova**.

## Puerta documental antes de ejecutar

Antes de iniciar una tarea nueva, ejecutar una secuencia de comandos, modificar código o continuar un plan, el agente debe revisar:

1. `docs/01-project-vision.md`
2. `docs/02-technical-spec.md`
3. `docs/03-ui-system.md`
4. `docs/04-engineering-rules.md`
5. La spec relevante en `docs/superpowers/specs/`, si existe.
6. El plan relevante en `docs/superpowers/plans/`, si existe.

Si el trabajo continúa una funcionalidad ya diseñada, la spec y el plan más recientes prevalecen sobre la memoria de la conversación. Antes de tocar código, el agente debe decir qué documentos está usando como contexto.

## Puerta Filament Blueprint

Cuando una tarea cree una propuesta, spec, plan o implementación nueva para Filament, el agente debe empezar leyendo y aplicando:

1. `docs/filament-blueprint/SKILL.md`
2. `vendor/filament/blueprint/resources/boost/guidelines/core.blade.php`
3. `vendor/filament/blueprint/resources/markdown/planning/overview.md`
4. Los documentos de Blueprint relevantes para el tipo de trabajo:
   - `resources.md` para Resources
   - `forms.md` y `schema-layouts.md` para formularios
   - `tables.md` para tablas
   - `actions.md` y `bulk-actions.md` para acciones
   - `relationships.md` y `pivot-tables.md` para relaciones
   - `widgets.md`, `custom-pages.md`, `infolists.md`, `imports.md`, `exports.md`, `wizards.md`, `authorization.md`, `testing.md` cuando apliquen

Esta puerta aplica especialmente cuando el usuario pida:

- crear una propuesta o blueprint de una funcionalidad Filament
- crear o modificar Resources, Pages, RelationManagers, Widgets, Tables, Forms, Actions, Infolists, Imports, Exports o Wizards
- diseñar una nueva experiencia de admin antes de implementarla

Antes de escribir el plan o tocar código, el agente debe indicar explícitamente: "Using Filament Blueprint", listar los archivos de Blueprint leídos y explicar qué decisiones quedan cerradas por el blueprint. Si falta información crítica, debe preguntar antes de planificar.

## Puerta UX para formularios Filament

Cuando una tarea cree, edite o revise formularios Filament, resources, pages, tables, widgets o flujos de entrada de datos del panel admin, el agente debe leer y aplicar:

`docs/filament-forms-ux-audit/SKILL.md`

Si la tarea también crea una propuesta, un plan o una implementación nueva de Filament, esta auditoría se aplica después de la Puerta Filament Blueprint para validar estructura, claridad y carga cognitiva.

Esta regla aplica especialmente si se toca:

- `form()`
- `schema()`
- `TextInput`
- `Select`
- `DatePicker`
- `Toggle`
- `Textarea`
- `Section`
- `Grid`
- `Tabs`
- validaciones
- relationship selects

Antes de implementar cambios de formularios, el agente debe indicar que está usando esta auditoría y qué criterios aplicará: estructura, agrupación, tipo de input, ayudas, validación, comportamiento condicional, consistencia y carga cognitiva.

---
