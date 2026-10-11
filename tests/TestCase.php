<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Daftar berkas database tenant SQLite yang sudah ada sebelum test berjalan.
     *
     * @var array<int, string>
     */
    private array $preExistingTenantDatabases = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->preExistingTenantDatabases = $this->tenantDatabaseFiles();
    }

    /**
     * Bersihkan berkas database tenant yang dibuat SQLiteDatabaseManager selama test.
     *
     * Tanpa ini, setiap pemanggilan Tenant::create() meninggalkan berkas
     * database/tenant<uuid> karena event TenantDeleted tidak pernah terjadi di test.
     */
    protected function tearDown(): void
    {
        $leftovers = array_diff($this->tenantDatabaseFiles(), $this->preExistingTenantDatabases);

        foreach ($leftovers as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    /**
     * @return array<int, string>
     */
    private function tenantDatabaseFiles(): array
    {
        $prefix = (string) config('tenancy.database.prefix', 'tenant');

        if ($prefix === '') {
            return [];
        }

        return array_values(array_filter(
            (array) glob(database_path($prefix.'*')),
            static fn ($path): bool => is_string($path) && is_file($path),
        ));
    }
}
