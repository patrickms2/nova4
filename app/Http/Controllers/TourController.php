<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class TourController extends Controller
{
    /**
     * Display a listing of tours.
     *
     * @return Response
     */
    public function index()
    {
        // This is just a test controller
        return view('pages.tours.index', [
            'tours' => [
                ['id' => 1, 'name' => 'City Tour', 'description' => 'Explore the city highlights', 'price' => 50],
                ['id' => 2, 'name' => 'Mountain Trek', 'description' => 'Adventure in the mountains', 'price' => 120],
                ['id' => 3, 'name' => 'Beach Day', 'description' => 'Relax at the beach', 'price' => 75],
            ],
        ]);
    }
}
