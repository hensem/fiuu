<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\LogsChanges;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    use LogsChanges;

    /**
     * List all partners (for Partner Management)
     */
    public function index()
    {
        // 🔹 Return full partner info for table listing
        $partners = Partner::select(
            'id',
            'name',
            'code',
            'email',
            'contact_person',
            'phone',
            'address',
            'status'
        )
        ->orderBy('name')
        ->get()
        ->map(function ($partner) {
            $partner->status_text = $partner->status == 1 ? 'Active' : 'Inactive';
            return $partner;
        });

        return response()->json($partners);
    }

    /**
     * Generate a unique random partner code
     */
    private function generateUniqueCode($length = 12)
    {
        do {
            // Generate mixed-case alphanumeric string
            $code = Str::random($length);
        } while (Partner::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create a new partner (auto-generate unique code)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|unique:partners,name',
            'email'  => 'nullable|email',
            'contact_person' => 'nullable|string|max:255',
            'phone'  => 'nullable|string|max:50',
            'address'=> 'nullable|string|max:255',
            'status' => 'nullable|integer' // 1 active, 2 inactive
        ]);

        // 🔹 Generate secure, unique partner code
        $code = $this->generateUniqueCode(12);

        $partner = new Partner($request->only([
            'name', 'email', 'contact_person', 'phone', 'address', 'status'
        ]));

        $partner->code = $code;
        $partner->created_by = Auth::id();
        $partner->created_at = Carbon::now();

        $partner->save();

        $this->logModelDiff('PartnerController', 'store', null, $partner);

        return response()->json([
            'message' => 'Partner created successfully',
            'partner' => $partner
        ], 201);
    }

    /**
     * Update a partner
     */
    public function update(Request $request, $id)
    {
        $partner = Partner::findOrFail($id);
        $before = clone $partner;

        $partner->fill($request->only([
            'name', 'email', 'contact_person', 'phone', 'address', 'status'
        ]));

        $partner->updated_by = Auth::id();
        $partner->updated_at = Carbon::now();

        $partner->save();

        $this->logModelDiff('PartnerController', 'update', $before, $partner);

        return response()->json($partner);
    }

    /**
     * Show partner detail
     */
    public function show($id)
    {
        return response()->json(Partner::findOrFail($id));
    }
}
