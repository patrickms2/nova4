# Nova System Principles

This document is a **personal architectural mantra**.
It must be read before starting development work.

The purpose is to avoid building features without respecting
the architecture and interaction model of the system.

If something does not follow these principles,
stop and rethink the design before writing code.

---

## Nova Law #1 — All actors are Users

Every actor in the system is a `User`.

Possible profiles include:

- Employee
- Taxista
- Admin
- Client
- Hotel

Roles determine behaviour, not separate user tables.

---

## Nova Law #2 — Every entity belongs to a domain

Nothing exists outside the defined domains.

Valid domains are:

- Core
- Taxi
- HRM
- Central
- Support
- Config

If something does not clearly belong to one of these,
the architecture must be reconsidered.

---

## Nova Law #3 — Architecture precedes code

Never start by building views or resources.

The correct order is:

1. Define the domain
2. Define the entities
3. Define the interactions
4. Define the UI
5. Then implement code

If architecture is unclear, coding must stop.

---

## Nova Law #4 — UI reflects the domain

Every interface must represent a domain interaction.

Example:

Taxi domain → taxistas, taxis, services  
HRM domain → employees, shifts, attendance  
Central domain → departments, coordination

UI should never invent concepts that do not exist in the domain map.

---

## Nova Law #5 — Portal logic comes before resources

Before building a Filament Resource or page, define:

- who uses it
- why it exists
- what interaction it supports

Never create CRUDs just because data exists.

---

## Nova Law #6 — Employees and Taxistas share a common portal logic

Employees and Taxistas both interact with **Central**.

Shared capabilities include:

- request documents
- request appointments
- send tickets
- communicate with departments

These interactions must remain consistent in the portal.

---

## Nova Law #7 — Employees and Taxistas diverge in their operational domains

Employee specific capabilities:

- shifts
- department schedules
- attendance tracking
- time off
- statistics

Taxista specific capabilities:

- taxis
- real-time location
- drivers
- taxi services
- bookings (instant / scheduled)
- payments for transfers

---

## Nova Law #8 — Hotels are service requesters

Hotels are `Users` with role `hotel`.

They may create taxi requests for guests.

Requests generate:

TaxiBooking  
TaxiService

Booking types:

- instant
- scheduled

---

## Nova Law #9 — Central coordinates the ecosystem

Central departments manage:

- employees
- taxistas
- documents
- appointments
- tickets
- operational schedules

Central acts as the coordination layer of the system.

---

## Nova Law #10 — Simplicity beats cleverness

If something is difficult to explain,
the design is probably wrong.

Prefer clarity over complexity.

---

## Daily reminder

Before writing code ask:

- Which domain is this in?
- Who is the actor?
- What interaction does this represent?
- Does the UI reflect the domain?

If the answer is unclear,
return to the architecture first.
