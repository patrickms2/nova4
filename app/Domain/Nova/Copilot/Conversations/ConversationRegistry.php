<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Conversations;

final class ConversationRegistry
{
    /**
     * @var array<string, CapabilityConversation>
     */
    private array $conversations = [];

    public function __construct()
    {
        $this->register(new InvoiceConversation());
        $this->register(new GenericCrudConversation('expenses'));
        $this->register(new GenericCrudConversation('customers'));
        $this->register(new GenericCrudConversation('companies'));
        $this->register(new GenericCrudConversation('reservations'));
        $this->register(new GenericCrudConversation('documents'));
        $this->register(new GenericCrudConversation('products'));
        $this->register(new GenericCrudConversation('tours'));
        $this->register(new GenericCrudConversation('restaurant-menu'));
        $this->register(new GenericCrudConversation('hotels'));
        $this->register(new GenericCrudConversation('taxi'));
        $this->register(new GenericCrudConversation('bookings'));
        $this->register(new GenericCrudConversation('payments'));
        $this->register(new GenericCrudConversation('inventory'));
        $this->register(new GenericCrudConversation('issues'));
        $this->register(new GenericCrudConversation('tasks'));
        $this->register(new GenericCrudConversation('employees'));
        $this->register(new GenericCrudConversation('appointments'));
        $this->register(new GenericCrudConversation('winery-catalog'));
    }

    public function register(CapabilityConversation $conversation): void
    {
        $this->conversations[$conversation->capability()] = $conversation;
    }

    public function for(string $capability): CapabilityConversation
    {
        return $this->conversations[$capability] ?? new GenericCrudConversation($capability);
    }
}
