namespace App\Services;

class HotelesService
{
    public function crearReserva($data)
    {
        // Lógica para crear una reserva en el sistema de hoteles
        return response()->json(['message' => 'Reserva creada para el hotel']);
    }

    public function obtenerHotel($id)
    {
        // Lógica para obtener la información de un hotel
        return response()->json(['hotel' => 'Hotel Ejemplo', 'id' => $id]);
    }
}
