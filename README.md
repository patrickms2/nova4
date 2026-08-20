# NOVA HUB

> AI Native Operating System for Business Automation

---

# What is NOVA?

NOVA is not a chatbot.

NOVA is not an MCP server.

NOVA is not a Laravel application.

NOVA is an AI-native operating platform that orchestrates people, services, applications, providers and artificial intelligence through a modular workspace.

Every module is designed to work independently while sharing the same architecture, data model and AI orchestration layer.

---

# Vision

The objective of NOVA is to become a universal business operating system where every capability can be exposed as:

- Workspace
- Provider
- Service
- Tool
- MCP Server
- Agent
- API
- Widget
- Chat
- WhatsApp Assistant

Every capability can be combined with every other capability.

Nothing is developed as an isolated application.

Everything belongs to NOVA.

---

# Core Architecture

NOVA is composed of several architectural layers.

```
                    NOVA OS
                       │
               NOVA HUB Workspace
                       │
     ─────────────────────────────────────
     AI
     Agents
     MCP
     Providers
     Services
     APIs
     Connectors
     Knowledge
     Catalog
     Automation
     Chat
     WhatsApp
```

The Workspace is always the primary interface.

The rest of the platform exists to support the Workspace.

---

# Design Principles

The platform follows a few immutable principles.

- AI First
- Livewire First
- Server Driven UI
- Component First
- Provider Architecture
- Everything is Modular
- Everything is Reusable
- Everything is Observable
- Everything can become an MCP
- Business Logic never belongs in the UI

These principles are expanded inside:

```
.codex/NOVA_PRINCIPLES.md
```

---

# Repository Structure

```
app/
    Actions/
    Agents/
    AI/
    Domains/
    MCP/
    Providers/
    Services/
    Workspaces/

resources/
    css/
    js/
    views/

routes/

database/

.codex/
    README.md
    ARCHITECTURE.md
    NOVA_PRINCIPLES.md
    CODEX_RULES.md
    NEXT.md
    tasks/
```

The `.codex` directory defines the implementation workflow.

The application source code implements it.

---

# AI Development Workflow

Every implementation agent must start here.

```
AGENTS.md
```

The mandatory reading order is:

```
AGENTS.md

↓

.codex/README.md

↓

.codex/ARCHITECTURE.md

↓

.codex/NOVA_PRINCIPLES.md

↓

.codex/CODEX_RULES.md

↓

.codex/NEXT.md

↓

Current Mission
```

Agents must never skip this sequence.

---

# Current Development Model

Development is mission-driven.

Only the mission defined inside:

```
.codex/NEXT.md
```

may be implemented.

No additional features, refactors or experiments may be introduced unless explicitly required by the current mission.

---

# Major Platform Modules

Current modules include:

- Workspace
- AI Orchestrator
- Agents
- MCP Platform
- Provider System
- Catalog
- Reservations
- Commerce
- Knowledge
- Automation
- WhatsApp
- Connectors
- APIs

Every module follows the same architectural rules.

---

# Technology Stack

Core technologies:

- PHP 8+
- Laravel
- Livewire
- Volt
- Filament
- Alpine.js
- Tailwind CSS
- Vite

Supporting technologies depend on each module and provider.

---

# Documentation

Architecture documentation lives inside:

```
.codex/
```

Technical documentation lives inside:

```
docs/
```

Module documentation should remain close to the module that owns it.

---

# Legacy Modules

Historical prototypes, experiments and previous implementations remain available for reference but do not define the current architecture.

Examples include:

- MCP Studio
- Early MCP experiments
- Previous Workspace prototypes

These documents are preserved for historical context only.

They never override the NOVA Architecture.

---

# License

See LICENSE.
