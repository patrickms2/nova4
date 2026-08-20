<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (DB::table('users')->orderBy('id')->get() as $user) {
                $personId = $this->findOrCreatePerson((array) $user, 'user', $user->id);
                DB::table('people')->where('id', $personId)->whereNull('user_id')->update(['user_id' => $user->id]);
                $this->assignRole($personId, 'application_user');
            }

            foreach (DB::table('rental_guests')->orderBy('id')->get() as $guest) {
                $personId = $this->findOrCreatePerson((array) $guest, 'guest');
                DB::table('rental_guests')->where('id', $guest->id)->update(['person_id' => $personId]);
                DB::table('rental_reservations')->where('guest_id', $guest->id)->update(['person_id' => $personId]);
                $this->assignRole($personId, 'guest');
            }

            foreach (DB::table('rental_contacts')->orderBy('id')->get() as $contact) {
                $personId = $this->findOrCreatePerson((array) $contact, 'contact');
                DB::table('rental_contacts')->where('id', $contact->id)->update(['person_id' => $personId]);
                $propertyId = DB::table('rental_properties')->where('id', $contact->rental_property_id)->value('property_id');
                if ($propertyId !== null) {
                    $this->assignPropertyRole($personId, (int) $propertyId, $contact->category ?: 'contact');
                }
                $this->assignRole($personId, $contact->category ?: 'contact');
            }

            DB::table('rental_reservations')->whereNull('property_id')->update([
                'property_id' => DB::raw('(SELECT property_id FROM rental_properties WHERE rental_properties.id = rental_reservations.rental_property_id)'),
            ]);

            foreach (DB::table('property_user')->get() as $membership) {
                $personId = DB::table('people')->where('user_id', $membership->user_id)->value('id');
                if ($personId !== null) {
                    $this->assignPropertyRole((int) $personId, (int) $membership->property_id, $membership->role ?: 'member');
                }
            }

            foreach (DB::table('properties')->whereNotNull('owner_id')->get() as $property) {
                $personId = DB::table('people')->where('user_id', $property->owner_id)->value('id');
                if ($personId !== null) {
                    $this->assignRole((int) $personId, 'owner');
                    $this->assignPropertyRole((int) $personId, (int) $property->id, 'owner');
                }
            }

            foreach (DB::table('access_grants')->whereNotNull('pin')->orderBy('id')->get() as $grant) {
                $personId = $grant->person_id ?? DB::table('people')->where('user_id', $grant->user_id)->value('id');
                if ($personId !== null && $grant->person_id === null) {
                    DB::table('access_grants')->where('id', $grant->id)->update(['person_id' => $personId]);
                }
                $credentialId = DB::table('credentials')->insertGetId([
                    'person_id' => $personId,
                    'type' => 'pin',
                    'name' => $grant->name.' PIN',
                    'identifier' => 'legacy-pin-'.hash('sha256', $grant->property_id.'|'.$grant->pin),
                    'secret' => Crypt::encryptString($grant->pin),
                    'status' => $grant->is_active ? 'active' : 'inactive',
                    'valid_from' => $grant->valid_from,
                    'valid_until' => $grant->valid_until,
                    'metadata' => json_encode(['migrated_from' => 'access_grants.pin']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('access_grant_credential')->insert(['access_grant_id' => $grant->id, 'credential_id' => $credentialId, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Compatibility backfill is intentionally forward-only; schema rollback removes its data.
    }

    private function findOrCreatePerson(array $identity, string $source, ?int $userId = null): int
    {
        $email = isset($identity['email']) && filled($identity['email']) ? Str::lower(trim($identity['email'])) : null;
        $existing = $email === null ? null : DB::table('people')->whereRaw('LOWER(email) = ?', [$email])->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        $firstName = trim((string) ($identity['first_name'] ?? $identity['name'] ?? 'Unknown'));
        $lastName = trim((string) ($identity['last_name'] ?? '')) ?: null;

        return DB::table('people')->insertGetId([
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => trim($firstName.' '.($lastName ?? '')),
            'email' => $email,
            'phone' => $identity['phone'] ?? null,
            'document_number' => $identity['document_number'] ?? null,
            'metadata' => json_encode(['source' => $source]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function assignRole(int $personId, string $role): void
    {
        DB::table('person_roles')->insertOrIgnore(['person_id' => $personId, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function assignPropertyRole(int $personId, int $propertyId, string $role): void
    {
        DB::table('person_property')->insertOrIgnore(['person_id' => $personId, 'property_id' => $propertyId, 'role' => $role, 'created_at' => now(), 'updated_at' => now()]);
    }
};
