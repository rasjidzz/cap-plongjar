<?php

namespace App\Http\Controllers;

use App\Models\KelompokKeahlian;
use Illuminate\Http\Request;

class KelompokKeahlianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kelompokKeahlian = KelompokKeahlian::all();
        return response()->json([
            'status' => 'success',
            'data' => $kelompokKeahlian
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(KelompokKeahlian $kelompokKeahlian)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KelompokKeahlian $kelompokKeahlian)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KelompokKeahlian $kelompokKeahlian)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KelompokKeahlian $kelompokKeahlian)
    {
        //
    }
}
