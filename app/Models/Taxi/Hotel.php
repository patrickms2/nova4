<?php

namespace App\Models\Taxi;


use Cheesegrits\FilamentGoogleMaps\Concerns\InteractsWithMaps;
use App\Models\Taxi\UsuarioDireccion;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\UsuarioTipo;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Usuario
{
    use HasFactory, Notifiable, InteractsWithMaps;

    protected $table = 'usuarios_direcciones';

    const PROFILE = 'profile';
    const ADMIN = 1;

    const CLIENT = 2;

    protected $perPage = 20;
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'title',
        'lat',
        'lng',
        'street',
        'number',
        'city',
        'state',
        'zip',
        'country',
        'address',
        'formatted_address',
        'phone',
        'website',
        'place_id',
        'marker_type',
        'processed',
        'description',
        'location',
        'usuario_id',
    ];
    protected $appends = [
        'location', 'nombre', 'status', 'latlng',

    ];

    public function search(Builder|\App\Models\Taxi\Builder $query, ?string $search): void
    {
        $query->when($search, function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('zip', 'like', "%{$search}%")
                    ->orWhere('street', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

            });
        });
    }
    /*protected function casts(): array
    {
        return [
            'status' => UsuarioTipo::class
        ];
    }*/

    /*protected static function booted(): void
    {
        static::addGlobalScope('tipo_id', function (Builder $builder) {

        });
    }*/

    /*public function newQuery($excludeDeleted = true): Builder
    {
        return parent::newQuery($excludeDeleted)->where('tipo_id', 2);;
    }*/

    public function getLocationAttribute(): array
    {
        return [
            "lat" => (float)$this->lat,
            "lng" => (float)$this->lng,
        ];
    }

    /**
     * Takes a Google style Point array of 'lat' and 'lng' values and assigns them to the
     * 'lat' and 'lng' attributes on this model.
     *
     * Used by the Filament Google Maps package.
     *
     * Requires the 'location' attribute be included in this model's $fillable array.
     *
     * @param ?array $location
     * @return void
     */
    public function setLocationAttribute(?array $location): void
    {
        if (is_array($location)) {
            $this->attributes['lat'] = $location['lat'];
            $this->attributes['lng'] = $location['lng'];
            unset($this->attributes['location']);
        }
    }

    /**
     * Get the lat and lng attribute/field names used on this table
     *
     * Used by the Filament Google Maps package.
     *
     * @return string[]
     */
    public static function getLatlngAttribute(): array
    {
        return [
            'lat' => 'lat',
            'lng' => 'lng',
        ];
    }

    /**
     * Get the lat and lng attribute/field names used on this table
     *
     * Used by the Filament Google Maps package.
     *
     * @return string[]
     */
    public static function getLatlngAttributes(): array
    {
        return [
            'lat' => 'lat',
            'lng' => 'lng',
        ];
    }


    public function getStatusAttribute(): bool
    {
        return (bool)$this->usuario->estado_id ? true : false;
    }

    public function getNombreAttribute(): string
    {
        return $this->title;
    }

    public function getNameAttribute(): string
    {
        return $this->nombre;
    }

    public function getFilamentName(): string
    {
        return "{$this->nombre}";
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }


}
