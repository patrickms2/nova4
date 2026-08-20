<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class editExpenseController extends Controller
{
    public function editExpense($id, Request $request)
    {
        Transaction::find($id)->update([
            'expense' => $request->input('expense'),
            'total' => $request->input('total'),
            'date' => $request->input('date'),
        ]);

        return redirect('/')->with('success', 'Expense updated successfully!');
    }
}
