<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
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
            'city' => 'nullable|string',
            'check_in' => 'nullable|date|required_with:check_out',
            'check_out' => 'nullable|date|after:check_in|required_with:check_in',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
        ]);

        $query = Hotel::query()
            ->where('status', 1);

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->filled('star_rating')) {
            $query->where('star_rating', $request->star_rating);
        }

        // Nếu có check_in/check_out thì lọc thêm theo tồn kho phòng 
        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);

            $startDate = $checkIn->toDateString();
            $endDate = $checkOut->copy()->subDay()->toDateString(); // check_out không tính là đêm ở
            $nights = $checkIn->diffInDays($checkOut);

            $minPrice = $request->min_price;
            $maxPrice = $request->max_price;

            $query->whereExists(function ($q) use ($startDate, $endDate, $nights, $minPrice, $maxPrice) {
                $q->select(DB::raw(1))
                    ->from('room_types as rt')
                    ->join('room_inventory as ri', 'ri.room_type_id', '=', 'rt.id')
                    ->whereColumn('rt.hotel_id', 'hotels.id')
                    ->whereBetween('ri.apply_date', [$startDate, $endDate])
                    ->groupBy('rt.id')
                    ->havingRaw('COUNT(*) = ?', [$nights])
                    ->havingRaw('MIN(ri.available_allotment) >= 1');

                // lọc giá theo MIN trong khoảng ngày
                if (!is_null($minPrice)) {
                    $q->havingRaw('MIN(ri.price) >= ?', [$minPrice]);
                }
                if (!is_null($maxPrice)) {
                    $q->havingRaw('MIN(ri.price) <= ?', [$maxPrice]);
                }
            });
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
