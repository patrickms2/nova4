<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Actions\DeleteGastoAction;
use App\Models\Gasto;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Elimina un gasto de NovaFact por su ID. Siempre pregunta antes de eliminar. Devuelve el ID y el código del gasto eliminado.')]
class DeleteExpenseTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:gastos,id',
            'confirmado' => 'nullable|boolean',
        ]);

        $gasto = Gasto::query()->findOrFail((int) $validated['id']);

        if (! ($validated['confirmado'] ?? false)) {
            return Response::json([
                'id' => $gasto->id,
                'codigo' => $gasto->codigo,
                'descripcion' => $gasto->descripcion,
                'total' => (float) $gasto->total,
                'requiere_confirmacion' => true,
                'mensaje' => 'Confirma la eliminación enviando confirmado: true.',
            ]);
        }

        $eliminado = [
            'id' => $gasto->id,
            'codigo' => $gasto->codigo,
            'descripcion' => $gasto->descripcion,
        ];

        app(DeleteGastoAction::class)->handle($gasto->id);

        return Response::json([
            'eliminado' => $eliminado,
            'mensaje' => 'Gasto eliminado correctamente.',
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID del gasto a eliminar (obligatorio).'),
            'confirmado' => $schema->boolean()->description('Debe ser true para confirmar la eliminación.'),
        ];
    }
}
