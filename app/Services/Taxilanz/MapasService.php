namespace App\Services;

class MapasService
{
    public function obtenerUbicacion($id)
    {
        // Lógica para obtener la ubicación de un servicio o usuario en el mapa
        return response()->json(['lat' => 40.4168, 'lng' => -3.7038, 'id' => $id]);
    }

    public function agregarMarcador($data)
    {
        // Lógica para agregar un nuevo marcador en el mapa
        return response()->json(['message' => 'Marcador agregado', 'data' => $data]);
    }
}
