---
paths:
  - 'app/{Livewire,Support}/**/*Community*.php'
---

# Livewire Support

## Los departamentos Community nulos son globales
En Community, un empleado asignado a un CommunityDepartment con community_id NULL pertenece a un departamento global y puede operar sobre todas las comunidades. No interpretar esa asignación como ausencia de ámbito; usar CommunityPortalContext::employeeCommunityIds().
