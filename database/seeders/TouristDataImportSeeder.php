<?php

namespace Database\Seeders;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TouristDataImportSeeder extends Seeder
{
    private string $source = 'tourist';

    private string $target;

    private array $userIdMap = [];

    public function run(): void
    {
        $this->target = (string) config('database.connections.mysql.database');

        $this->ensureCompatibleSchema();
        $this->buildUserIdMap();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $this->importUsers();

            $this->copyTable('countries');
            $this->copyTable('cities');
            $this->copyTable('locations', [
                'id' => '`id`',
                'name' => '`name`',
                'city_id' => '`city_id`',
                'address' => '`address`',
                'description' => '`description`',
                'latitude' => '`latitude`',
                'longitude' => '`longitude`',
                'is_popular' => '`is_popular`',
                'created_at' => '`created_at`',
                'updated_at' => '`updated_at`',
            ]);

            $this->copyTable('hotels', $this->commonWithUserMap('hotels', [
                'manager_id' => 'manager_id',
            ]));
            $this->copyTable('hotel_images');
            $this->copyTable('hotel_amenities');
            $this->copyTable('hotel_amenity_maps');
            $this->copyTable('room_types');

            $this->copyTable('taxi_services', $this->commonWithUserMap('taxi_services', [
                'manager_id' => 'manager_id',
            ]));
            $this->copyTable('vehicle_types');
            $this->copyTable('vehicles');
            $this->copyTable('drivers', [
                'id' => '`id`',
                'admin_id' => 'COALESCE(`admin_id`, 1)',
                'user_id' => $this->mapUserExpression('`user_id`'),
                'taxi_service_id' => '`taxi_service_id`',
                'license_number' => '`license_number`',
                'experience_years' => '`experience_years`',
                'rating' => '`rating`',
                'rating_count' => '`rating_count`',
                'rating_updated_at' => '`rating_updated_at`',
                'availability_status' => '`availability_status`',
                'last_seen_at' => '`last_seen_at`',
                'location_updated_at' => '`location_updated_at`',
                'is_active' => '`is_active`',
                'shift_start' => '`shift_start`',
                'shift_end' => '`shift_end`',
                'created_at' => '`created_at`',
                'updated_at' => '`updated_at`',
                'current_location' => 'ST_GeomFromText(CONCAT("POINT(", COALESCE(`longitude`, 0), " ", COALESCE(`latitude`, 0), ")"))',
                'latitude' => '`latitude`',
                'longitude' => '`longitude`',
            ]);
            $this->copyTable('trips', [
                'id' => '`id`',
                'driver_id' => '`driver_id`',
                'user_id' => $this->mapUserExpression('`user_id`'),
                'status' => '`status`',
                'requested_at' => '`requested_at`',
                'started_at' => '`started_at`',
                'completed_at' => '`completed_at`',
                'fare' => '`fare`',
                'distance_km' => '`distance_km`',
                'surge_multiplier' => '`surge_multiplier`',
                'trip_type' => '`trip_type`',
                'vehicle_id' => '`vehicle_id`',
                'created_at' => '`created_at`',
                'updated_at' => '`updated_at`',
                'pickup_location' => 'ST_GeomFromText("POINT(0 0)")',
                'dropoff_location' => 'ST_GeomFromText("POINT(0 0)")',
            ]);
            $this->copyTable('driver_vehicle_assignments');

            $this->copyTable('tour_categories', [
                'id' => '`id`',
                'category_name' => '`name`',
                'description' => '`description`',
                'parent_category_id' => '`parent_category_id`',
                'icon_url' => '`icon`',
                'display_order' => '`display_order`',
                'is_active' => '`is_active`',
            ]);
            $this->copyTable('tours', [
                'id' => '`id`',
                'tour_name' => '`name`',
                'description' => '`description`',
                'short_description' => '`short_description`',
                'location_id' => '`location_id`',
                'duration_hours' => '`duration_hours`',
                'duration_days' => '`duration_days`',
                'base_price' => '`base_price`',
                'discount_percentage' => '`discount_percentage`',
                'max_capacity' => '`max_capacity`',
                'min_participants' => '`min_participants`',
                'difficulty_level' => '`difficulty_level`',
                'average_rating' => '`average_rating`',
                'total_ratings' => '`total_ratings`',
                'main_image_url' => '`main_image_url`',
                'is_active' => '`is_active`',
                'is_featured' => '`is_featured`',
                'created_by' => $this->mapUserExpression('`created_by`'),
                'created_at' => '`created_at`',
                'updated_at' => '`updated_at`',
            ]);
            $this->copyTable('tour_category_mapping');
            $this->copyTable('tour_images');
            $this->copyTable('tour_schedules');

            $this->copyTable('restaurants', [
                'id' => '`id`',
                'restaurant_name' => '`name`',
                'description' => '`description`',
                'location_id' => '`location_id`',
                'cuisine' => '`cuisine`',
                'price_range' => '`price_range`',
                'opening_time' => '`opening_time`',
                'closing_time' => '`closing_time`',
                'average_rating' => '`average_rating`',
                'total_ratings' => '`total_ratings`',
                'main_image_url' => '`main_image_url`',
                'website' => '`website`',
                'phone' => '`phone`',
                'email' => '`email`',
                'has_reservation' => '`has_reservation`',
                'is_active' => '`is_active`',
                'is_featured' => '`is_featured`',
                'manager_id' => $this->mapUserExpression('`manager_id`'),
                'created_at' => '`created_at`',
                'updated_at' => '`updated_at`',
            ]);
            $this->copyTable('restaurant_images');
            $this->copyTable('restaurant_tables');
            $this->copyTable('menu_categories');
            $this->copyTable('menu_items');

            $this->copyTable('bookings', [
                'id' => '`id`',
                'booking_reference' => '`booking_reference`',
                'user_id' => $this->mapUserExpression('`user_id`'),
                'booking_type' => '`booking_type`',
                'booking_date' => '`booking_date`',
                'status' => '`status`',
                'total_price' => '`total_price`',
                'discount_amount' => '`discount_amount`',
                'payment_status' => '`payment_status`',
                'special_requests' => '`special_requests`',
                'cancellation_reason' => '`cancellation_reason`',
                'last_updated' => '`last_updated`',
            ]);
            $this->copyTable('tour_bookings', [
                'id' => '`id`',
                'booking_id' => '`booking_id`',
                'tour_id' => '`tour_id`',
                'schedule_id' => '`schedule_id`',
                'number_of_adults' => '`number_of_adults`',
                'number_of_children' => '`number_of_children`',
                'guide_id' => $this->mapUserExpression('`guide_id`'),
            ]);
            $this->copyTable('hotel_bookings');
            $this->copyTable('restaurant_bookings', [
                'id' => '`id`',
                'booking_id' => '`booking_id`',
                'restaurant_id' => '`restaurant_id`',
                'table_id' => '`table_id`',
                'reservation_date' => '`reservation_date`',
                'reservation_time' => '`reservation_time`',
                'number_of_guests' => '`number_of_guests`',
                'duration' => '`duration`',
            ]);
            $this->copyTable('taxi_bookings');
            $this->copyTable('package_bookings');
            $this->copyTable('public_booking_requests', [
                'id' => '`id`',
                'request_reference' => '`request_reference`',
                'type' => '`type`',
                'service_id' => '`service_id`',
                'service_name' => '`service_name`',
                'assigned_admin_id' => '`assigned_admin_id`',
                'assignment_source' => '`assignment_source`',
                'customer_name' => '`customer_name`',
                'customer_email' => '`customer_email`',
                'customer_phone' => '`customer_phone`',
                'status' => '`status`',
                'guests' => '`guests`',
                'rooms' => '`rooms`',
                'passengers' => '`passengers`',
                'participants' => '`participants`',
                'check_in_date' => '`check_in_date`',
                'check_out_date' => '`check_out_date`',
                'reservation_date' => '`reservation_date`',
                'reservation_time' => '`reservation_time`',
                'pickup_date_time' => '`pickup_date_time`',
                'tour_date' => '`tour_date`',
                'tour_schedule' => '`tour_schedule`',
                'pickup_address' => '`pickup_address`',
                'dropoff_address' => '`dropoff_address`',
                'notes' => '`notes`',
                'approved_at' => '`approved_at`',
                'cancelled_at' => '`cancelled_at`',
                'decided_by_admin_id' => '`decided_by_admin_id`',
                'decision_notes' => '`decision_notes`',
                'created_at' => '`created_at`',
                'updated_at' => '`updated_at`',
            ]);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function ensureCompatibleSchema(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('last_name');
            }
            if (! Schema::hasColumn('users', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->nullable()->after('country_id');
            }
            if (! Schema::hasColumn('users', 'registration_date')) {
                $table->dateTime('registration_date')->nullable()->after('user_type');
            }
            if (! Schema::hasColumn('users', 'last_login_date')) {
                $table->dateTime('last_login_date')->nullable()->after('registration_date');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->boolean('status')->default(true)->after('last_login_date');
            }
            if (! Schema::hasColumn('users', 'profile_image_url')) {
                $table->string('profile_image_url')->nullable()->after('status');
            }
            if (! Schema::hasColumn('users', 'preferred_language')) {
                $table->string('preferred_language')->nullable()->after('profile_image_url');
            }
            if (! Schema::hasColumn('users', 'is_email_verified')) {
                $table->boolean('is_email_verified')->default(false)->after('preferred_language');
            }
            if (! Schema::hasColumn('users', 'is_phone_verified')) {
                $table->boolean('is_phone_verified')->default(false)->after('is_email_verified');
            }
        });

        Schema::table('locations', function (Blueprint $table) {
            if (! Schema::hasColumn('locations', 'address')) {
                $table->text('address')->nullable()->after('city_id');
            }
        });

        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('admin_id');
            }
            if (! Schema::hasColumn('drivers', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('current_location');
            }
            if (! Schema::hasColumn('drivers', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        Schema::table('taxi_services', function (Blueprint $table) {
            if (! Schema::hasColumn('taxi_services', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            }
        });

        DB::statement('UPDATE `users` SET `first_name` = COALESCE(`first_name`, `name`), `last_name` = COALESCE(`last_name`, "")');
    }

    private function buildUserIdMap(): void
    {
        $sourceUsers = collect(DB::select('SELECT `id`, `email` FROM '.$this->qualified($this->source, 'users')));
        $targetUsers = DB::table('users')->get(['id', 'email'])->keyBy('id');

        foreach ($sourceUsers as $user) {
            $target = $targetUsers->get($user->id);
            $this->userIdMap[(int) $user->id] = $target && $target->email !== $user->email
                ? 100000 + (int) $user->id
                : (int) $user->id;
        }
    }

    private function importUsers(): void
    {
        $idExpression = $this->mapUserExpression('`id`');

        $this->insertSelect('users', [
            'id' => $idExpression,
            'name' => '`name`',
            'first_name' => '`first_name`',
            'last_name' => '`last_name`',
            'email' => '`email`',
            'password' => '`password`',
            'phone' => '`phone`',
            'country_id' => '`country_id`',
            'user_type' => '`user_type`',
            'registration_date' => '`registration_date`',
            'last_login_date' => '`last_login_date`',
            'status' => '`status`',
            'profile_image_url' => '`profile_image_url`',
            'preferred_language' => '`preferred_language`',
            'is_email_verified' => '`is_email_verified`',
            'is_phone_verified' => '`is_phone_verified`',
            'email_verified_at' => 'CASE WHEN `is_email_verified` = 1 THEN COALESCE(`updated_at`, `created_at`, NOW()) ELSE NULL END',
            'remember_token' => '`remember_token`',
            'created_at' => '`created_at`',
            'updated_at' => '`updated_at`',
        ]);
    }

    private function commonWithUserMap(string $table, array $columns): array
    {
        $mapped = $this->commonColumnMap($table);

        foreach ($columns as $target => $source) {
            $mapped[$target] = $this->mapUserExpression('`'.$source.'`');
        }

        return $mapped;
    }

    private function copyTable(string $table, ?array $explicitColumns = null): void
    {
        if (! Schema::hasTable($table) || ! $this->sourceTableExists($table)) {
            return;
        }

        $columns = $explicitColumns ?? $this->commonColumnMap($table);

        if ($columns === []) {
            return;
        }

        $this->insertSelect($table, $columns);
    }

    private function commonColumnMap(string $table): array
    {
        $sourceColumns = collect(DB::select(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
            [$this->source, $table],
        ))->pluck('column_name')->all();

        $targetColumns = collect(DB::select(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
            [$this->target, $table],
        ))->pluck('column_name')->all();

        return collect($targetColumns)
            ->intersect($sourceColumns)
            ->mapWithKeys(fn (string $column): array => [$column => '`'.$column.'`'])
            ->all();
    }

    private function insertSelect(string $table, array $columns): void
    {
        $targetColumns = collect(DB::select(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?',
            [$this->target, $table],
        ))->pluck('column_name')->flip();

        $columns = collect($columns)
            ->filter(fn (string $expression, string $column): bool => $targetColumns->has($column))
            ->all();

        if ($columns === []) {
            return;
        }

        $insertColumns = collect(array_keys($columns))
            ->map(fn (string $column): string => '`'.$column.'`')
            ->implode(', ');

        $selectColumns = collect($columns)
            ->map(fn (string $expression, string $column): string => $expression.' AS `'.$column.'`')
            ->implode(', ');

        $updateColumns = collect(array_keys($columns))
            ->reject(fn (string $column): bool => $column === 'id')
            ->map(fn (string $column): string => '`'.$column.'` = VALUES(`'.$column.'`)')
            ->implode(', ');

        $sql = 'INSERT INTO '.$this->qualified($this->target, $table).' ('.$insertColumns.') '.
            'SELECT '.$selectColumns.' FROM '.$this->qualified($this->source, $table);

        if ($updateColumns !== '') {
            $sql .= ' ON DUPLICATE KEY UPDATE '.$updateColumns;
        }

        DB::statement($sql);
    }

    private function mapUserExpression(string $sourceColumn): string
    {
        if ($this->userIdMap === []) {
            return $sourceColumn;
        }

        $cases = collect($this->userIdMap)
            ->filter(fn (int $targetId, int $sourceId): bool => $targetId !== $sourceId)
            ->map(fn (int $targetId, int $sourceId): string => 'WHEN '.$sourceColumn.' = '.$sourceId.' THEN '.$targetId)
            ->implode(' ');

        if ($cases === '') {
            return $sourceColumn;
        }

        return 'CASE WHEN '.$sourceColumn.' IS NULL THEN NULL '.$cases.' ELSE '.$sourceColumn.' END';
    }

    private function sourceTableExists(string $table): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $this->source)
            ->where('table_name', $table)
            ->exists();
    }

    private function qualified(string $database, string $table): string
    {
        return '`'.str_replace('`', '``', $database).'`.`'.str_replace('`', '``', $table).'`';
    }
}
