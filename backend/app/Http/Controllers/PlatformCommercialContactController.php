<?php

namespace App\Http\Controllers;

use App\Models\CommercialContact;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformCommercialContactController extends Controller
{
    public function index(Request $request): View
    {
        $query = CommercialContact::query()->latest();
        $status = trim((string) $request->query('status', ''));

        if ($status !== '') {
            $query->where('status', $status);
        }

        return view('platform.contacts.index', [
            'contacts' => $query->paginate(30)->withQueryString(),
            'status' => $status,
        ]);
    }
}
