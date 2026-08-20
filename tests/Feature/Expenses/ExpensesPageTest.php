<?php

namespace Tests\Feature\Expenses;

use App\Ai\Agents\GastoOcrAgent;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ExpensesPageTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_expenses_page_requires_authentication(): void
    {
        $this->get(route('facturacion.expenses'))
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_expenses_page(): void
    {
        $this->actingAsUser();

        $this->get(route('facturacion.expenses'))
            ->assertStatus(200);
    }

    public function test_page_lists_only_user_expenses(): void
    {
        $user = $this->actingAsUser();
        $otherUser = User::factory()->create();

        Transaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'expense',
            'description' => 'My expense',
            'amount' => 100,
        ]);

        Transaction::factory()->create([
            'user_id' => $otherUser->id,
            'type' => 'expense',
            'description' => 'Other expense',
            'amount' => 200,
        ]);

        $this->get(route('facturacion.expenses'))
            ->assertSee('My expense')
            ->assertDontSee('Other expense');
    }

    public function test_can_create_an_expense(): void
    {
        $this->actingAsUser();
        $category = Category::factory()->create(['type' => 'expense']);

        $component = Volt::test('expenses.expenses')
            ->set('description', 'Office rent')
            ->set('amount', '500.00')
            ->set('category_id', (string) $category->id)
            ->set('date', now()->format('Y-m-d'))
            ->call('save');

        $component->assertSet('showModal', false);

        $this->assertDatabaseHas('transactions', [
            'description' => 'Office rent',
            'amount' => 500.00,
            'type' => 'expense',
            'category_id' => $category->id,
        ]);
    }

    public function test_can_update_an_expense(): void
    {
        $user = $this->actingAsUser();
        $expense = Transaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'expense',
            'description' => 'Old',
            'amount' => 50,
        ]);

        $component = Volt::test('expenses.expenses')
            ->call('openEdit', $expense->id)
            ->set('description', 'Updated')
            ->set('amount', '75.00')
            ->call('update');

        $component->assertSet('showModal', false);

        $this->assertDatabaseHas('transactions', [
            'id' => $expense->id,
            'description' => 'Updated',
            'amount' => 75.00,
        ]);
    }

    public function test_can_delete_an_expense(): void
    {
        $user = $this->actingAsUser();
        $expense = Transaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'expense',
        ]);

        Volt::test('expenses.expenses')
            ->call('delete', $expense->id);

        $this->assertDatabaseMissing('transactions', ['id' => $expense->id]);
    }

    public function test_receipt_image_ocr_extracts_data_and_prefills_expense_form(): void
    {
        Storage::fake('public');

        $this->actingAsUser();

        GastoOcrAgent::fake([
            [
                'empresa' => 'GASOLINERA CEPSA',
                'fecha' => '2026-07-10',
                'base_imponible' => 46.73,
                'impuesto' => 3.27,
                'total' => 50.00,
                'concepto' => 'Combustible',
            ],
        ]);

        $file = UploadedFile::fake()->image('ticket.jpg');

        $component = Volt::test('expenses.expenses')
            ->set('receiptImage', $file)
            ->call('updatedReceiptImage');

        $component->assertSet('ocrEmpresa', 'GASOLINERA CEPSA');
        $component->assertSet('description', 'GASOLINERA CEPSA - Combustible');
        $component->assertSet('amount', '50');
        $component->assertSet('date', '2026-07-10');
    }
}
