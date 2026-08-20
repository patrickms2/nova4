<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class addExpenseController extends Controller
{
    public function store(Request $request)
    {
        // Create a new transaction record
        Transaction::create([
            'user' => session('username'),
            'expense' => $request->expense,
            'total' => $request->total,
            'date' => $request->date,
        ]);
        return redirect('/')->with('success', 'Expense added successfully!');
    }
}
