<?php

namespace App\Http\Controllers\Api;

use App\Models\RoomType;
use Carbon\Carbon;
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
        $hotel = Hotel::with('roomTypes')->find($id);

        if (!$hotel) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy khách sạn',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết khách sạn thành công',
            'data' => $hotel
        ]);
    }

    public function availability(Request $request, $id)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $hotel = Hotel::find($id);

        if (!$hotel) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy khách sạn',
                'data' => null
            ], 404);
        }

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        $roomTypes = RoomType::where('hotel_id', $id)->get();

        $result = [];

        foreach ($roomTypes as $roomType) {
            $inventories = $roomType->inventories()
                ->whereBetween('apply_date', [$checkIn->toDateString(), $checkOut->copy()->subDay()->toDateString()])
                ->orderBy('apply_date')
                ->get();

            $numberOfNights = $checkIn->diffInDays($checkOut);

            // if ($inventories->count() !== $numberOfNights) {
            //     continue;
            // }

            // $minAvailable = $inventories->min('available_allotment');
            // $totalPrice = $inventories->sum('price');

            // $result[] = [
            //     'room_type_id' => $roomType->id,
            //     'room_type_name' => $roomType->name,
            //     'available_rooms' => $minAvailable,
            //     'nights' => $numberOfNights,
            //     'total_price' => $totalPrice,
            // ];
            $result[] = [
                'room_type_id' => $roomType->id,
                'room_type_name' => $roomType->name,
                'inventory_count' => $inventories->count(),
                'nights' => $numberOfNights,
                'inventories' => $inventories,
            ];
            continue;
        }

        return response()->json([
            'success' => true,
            'message' => 'Kiểm tra phòng trống thành công',
            'data' => $result
        ]);
    }
}
