<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function daily(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->input('date', now()))->format('Y-m-d');

        $orders = WorkOrder::with(['community', 'tasks' => function ($query) {
            $query->orderBy('sort')->with('comments');
        }])
            ->where('work_date', $date)
            ->orderBy('id')
            ->get();

        return response()->json($orders);
    }
}
