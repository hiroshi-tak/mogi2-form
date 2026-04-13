<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRequest;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function index()
    {
        $tab = request('tab', 'pending');

        $requests = AttendanceRequest::with(['attendance.user'])
            ->whereHas('attendance', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->where('status', $tab === 'approved' ? 'approved' : 'pending')
            ->latest()
            ->get();

        return view('request.index', compact('requests', 'tab'));
    }
}
