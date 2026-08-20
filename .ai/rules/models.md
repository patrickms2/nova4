---
paths:
  - 'app/Models/{Person,Property,RentalProperty,RentalGuest,RentalContact,RentalReservation,Credential,AccessGrant,Device,AccessPoint,DomoticsEvent}.php'
---

# Models

## Canonical identity and property boundaries
Person is the canonical real-world identity; User is authentication only. Property is the canonical physical property. RentalProperty, RentalGuest, and RentalContact remain compatibility/profile records and must link through property_id/person_id. Access and Domotics models must use Property; Credential is vendor-neutral and secrets remain encrypted/hidden.
