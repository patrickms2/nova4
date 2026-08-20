<?php

namespace App\Actions;

use App\Models\Gasto;

class DeleteGastoAction
{
    public function handle(int $id): void
    {
        Gasto::query()->findOrFail($id)->delete();
    }
}
