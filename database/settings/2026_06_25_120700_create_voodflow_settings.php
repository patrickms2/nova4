<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('voodflow.timezone')) {
            $this->migrator->add('voodflow.timezone', config('app.timezone', 'UTC'));
        }

        if (! $this->migrator->exists('voodflow.date_format')) {
            $this->migrator->add('voodflow.date_format', 'd/m/Y H:i');
        }

        if (! $this->migrator->exists('voodflow.default_workflow_timeout')) {
            $this->migrator->add('voodflow.default_workflow_timeout', 300);
        }

        if (! $this->migrator->exists('voodflow.default_node_timeout')) {
            $this->migrator->add('voodflow.default_node_timeout', 30);
        }

        if (! $this->migrator->exists('voodflow.queue_connection')) {
            $this->migrator->add('voodflow.queue_connection', null);
        }

        if (! $this->migrator->exists('voodflow.queue_name')) {
            $this->migrator->add('voodflow.queue_name', null);
        }

        if (! $this->migrator->exists('voodflow.log_retention_days')) {
            $this->migrator->add('voodflow.log_retention_days', 30);
        }

        // Whitelabel / node access control
        if (! $this->migrator->exists('voodflow.node_blocking_enabled')) {
            $this->migrator->add('voodflow.node_blocking_enabled', true);
        }

        if (! $this->migrator->exists('voodflow.end_users_can_view_system_workflows')) {
            $this->migrator->add('voodflow.end_users_can_view_system_workflows', true);
        }

        if (! $this->migrator->exists('voodflow.end_users_can_edit_system_workflows')) {
            $this->migrator->add('voodflow.end_users_can_edit_system_workflows', false);
        }

        if (! $this->migrator->exists('voodflow.blocked_node_types')) {
            $this->migrator->add('voodflow.blocked_node_types', []);
        }

        if (! $this->migrator->exists('voodflow.readonly_node_types')) {
            $this->migrator->add('voodflow.readonly_node_types', ['php_code_node']);
        }

        if (! $this->migrator->exists('voodflow.non_deletable_node_types')) {
            $this->migrator->add('voodflow.non_deletable_node_types', ['php_code_node']);
        }

        if (! $this->migrator->exists('voodflow.non_executable_node_types')) {
            $this->migrator->add('voodflow.non_executable_node_types', ['php_code_node']);
        }
    }
};
