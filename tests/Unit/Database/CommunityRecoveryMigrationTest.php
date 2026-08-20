<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CommunityRecoveryMigrationTest extends TestCase
{
    #[DataProvider('recoveredTables')]
    public function test_recovery_migration_contains_each_required_table(string $table): void
    {
        $source = $this->migrationSource();

        $this->assertStringContainsString("Schema::hasTable('{$table}')", $source);
        $this->assertStringContainsString("Schema::create('{$table}'", $source);
    }

    public function test_recovery_migration_is_non_destructive(): void
    {
        $source = $this->migrationSource();
        $downMethod = substr($source, strpos($source, 'public function down(): void'));

        $this->assertStringNotContainsString('dropIfExists', $downMethod);
        $this->assertStringNotContainsString('dropColumn', $downMethod);
        $this->assertStringNotContainsString('dropConstrainedForeignId', $downMethod);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function recoveredTables(): array
    {
        $tables = [
            'communities',
            'people',
            'properties',
            'person_roles',
            'person_property',
            'community_person',
            'community_departments',
            'community_department_employee',
            'community_document_types',
            'community_owner_documents',
            'community_document_imports',
            'community_employee_documents',
            'community_tickets',
            'community_shifts',
            'community_attendance_community',
            'work_sessions',
            'work_reports',
        ];

        return array_combine($tables, array_map(fn (string $table): array => [$table], $tables));
    }

    private function migrationSource(): string
    {
        $path = dirname(__DIR__, 3).'/database/migrations/2026_08_15_062442_recover_community_identity_property_and_work_tables.php';
        $source = file_get_contents($path);

        $this->assertNotFalse($source);

        return $source;
    }
}
