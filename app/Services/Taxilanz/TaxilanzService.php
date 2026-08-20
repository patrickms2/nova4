namespace App\Services;

class TaxilanzService
{
    public function solicitarTaxi($data)
    {
        // Lógica para solicitar un taxi
        return response()->json(['message' => 'Taxi solicitado']);
    }

    public function obtenerEstadoTaxi($id)
    {
        // Lógica para obtener el estado de un taxi
        return response()->json(['estado' => 'en camino', 'id' => $id]);
    }
}
