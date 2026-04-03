<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;

class HotelController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'city' => 'required|string',
            'star_rating' => 'nullable|integer|min:1|max:5',
        ]);

        $query = Hotel::query()
            ->where('status', 1)
            ->where('city', 'like', '%' . $request->city . '%');

        if ($request->filled('star_rating')) {
            $query->where('star_rating', $request->star_rating);
        }

        $hotels = $query->with('roomTypes')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách khách sạn thành công',
            'data' => $hotels
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Chưa code xong API chi tiết khách sạn',
            'data' => null
        ]);
    }

    public function availability(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Chưa code xong API kiểm tra phòng trống',
            'data' => null
        ]);
    }
}