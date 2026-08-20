<div class="flex items-center gap-x-4 ml-4 hidden lg:flex">
    <a href="{{ \App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource::getUrl('index') }}"
       class="hidden text-gray-500 sm:inline hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
       title="Departamentos">
        <x-heroicon-o-user-group class="w-6 h-6"/>
    </a>

    <a href="{{ \App\Filament\App\Resources\Taxistas\TaxistaResource::getUrl('index') }}"
       class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
       title="Taxistas">
        <x-phosphor-taxi-fill class="w-6 h-6"/>
    </a>

    <a href="{{ \App\Filament\App\Resources\Employees\EmployeeResource::getUrl('index') }}"
       class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
       title="Empleados">
        <x-heroicon-empleado class="w-6 h-6"/>
    </a>

    <a href="{{ \App\Filament\App\Resources\Hoteles\HotelesResource::getUrl('index') }}"
       class="hidden text-gray-500 sm:inline md:block lg:block hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
       title="Hoteles">
        <x-heroicon-map class="w-6 h-6"/>
    </a>

    <a href="{{ \App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource::getUrl('index') }}"
       class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
       title="Citas">
        <x-heroicon-o-calendar-days class="w-6 h-6"/>
    </a>
    <a href="{{ \App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource::getUrl('index') }}"
       class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
       title="Documentos">
        <x-heroicon-pdf class="w-6 h-6"/>
    </a>
    <a href="{{ \App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource::getUrl('index') }}"
       class="hidden text-gray-500 sm:inline hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
       title="Tickets">
        <x-heroicon-ticket class="w-6 h-6"/>
    </a>
</div>
