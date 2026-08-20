namespace App\Services;

use GuzzleHttp\Client;

class AurigaService
{
    protected $client;

    public function __construct()
    {
        // Inicializamos el cliente HTTP con la base URL de AURIGA
        $this->client = new Client([
            'base_uri' => env('AURIGA_API_URL'),
            'timeout'  => 10.0,
        ]);
    }

    public function crearReserva($data)
    {
        try {
            $response = $this->client->post('/booking', [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . env('AURIGA_API_TOKEN'),
                    'Content-Type'  => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            return ['error' => 'Error al crear la reserva en AURIGA', 'message' => $e->getMessage()];
        }
    }

    public function actualizarReserva($id, $data)
    {
        try {
            $response = $this->client->put("/booking/{$id}", [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . env('AURIGA_API_TOKEN'),
                    'Content-Type'  => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            return ['error' => 'Error al actualizar la reserva en AURIGA', 'message' => $e->getMessage()];
        }
    }

    public function obtenerReserva($id)
    {
        try {
            $response = $this->client->get("/booking/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('AURIGA_API_TOKEN'),
                    'Content-Type'  => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            return ['error' => 'Error al obtener la reserva en AURIGA', 'message' => $e->getMessage()];
        }
    }

    public function eliminarReserva($id)
    {
        try {
            $response = $this->client->delete("/booking/{$id}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('AURIGA_API_TOKEN'),
                    'Content-Type'  => 'application/json',
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (\Exception $e) {
            return ['error' => 'Error al eliminar la reserva en AURIGA', 'message' => $e->getMessage()];
        }
    }
}
