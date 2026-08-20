<?php

namespace App\Models\Admin;

class TeamUser extends BaseAdminModel
{
    protected $table = "admin_team_users";

    protected string $adminLabelColumn = "name";

    protected $fillable = [
        "name",
        "email",
        "type",
        "status",
        "role",
        "notes",
    ];

    protected $casts = [];}
