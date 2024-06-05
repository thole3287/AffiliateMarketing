<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coupons = Coupon::all();
        return response()->json($coupons);

        // if (!$coupons) {
        // }
        // return response()->json(["msg" => "Coupon code not found!"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|unique:coupons,coupon_code',
            'discount_amount' => 'required|numeric',
            'expiration_date' => 'required|date',
            'coupon_status' => 'nullable|string|in:active,inactive',
            // Add other validation rules as needed
        ]);

        $coupon = Coupon::create($request->all());
        return response()->json([
            "msg" => "Add coupon code sucessfully.",
            "data" => $coupon
        ], Response::HTTP_CREATED);
    //    try {

    //    } catch (\Exception $e) {
    //         $e->getMessage();
    //    }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $coupon = Coupon::findOrFail($id);
        return response()->json($coupon);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'coupon_code' => 'required|unique:coupons,coupon_code,' . $id,
            'discount_amount' => 'required|numeric',
            'expiration_date' => 'required|date',
            'coupon_status' => 'nullable|string|in:active,inactive',

            // Add other validation rules as needed
        ]);

        $coupon = Coupon::findOrFail($id);
        $coupon->update($request->all());
        return response()->json([
            "msg" => "Update coupon code sucessfully.",
            "data" => $coupon
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        return response()->json(["msg" => 'Delete coupon code sucessfully.'], Response::HTTP_NO_CONTENT);
    }
}
