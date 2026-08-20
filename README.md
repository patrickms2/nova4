# NOVA Filament Hotfix v2.0.1

Corrige el error:

Class "App\Models\Nova\NovaRepresentation" not found

Instalación:

```bash
./install-hotfix-v201.sh /Volumes/BACKUP/novahub
```

Después:

```bash
php artisan nova:discover-filament-structure --resource=TaxistaResource
```
