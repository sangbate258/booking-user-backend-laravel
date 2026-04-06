<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\RoomInventory;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'hotel_id' => 'required|integer|exists:hotels,id',
            'room_type_id' => 'required|integer|exists:room_types,id',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'rooms_count' => 'required|integer|min:1',
            'guest_name' => 'required|string|max:100',
            'guest_phone' => 'required|string|max:15',
        ]);

        $user = $request->user();

        $roomType = RoomType::where('id', $request->room_type_id)
            ->where('hotel_id', $request->hotel_id)
            ->first();

        if (!$roomType) {
            return response()->json([
                'success' => false,
                'message' => 'Loại phòng không thuộc khách sạn đã chọn',
            ], 400);
        }

        $inventories = RoomInventory::where('room_type_id', $request->room_type_id)
            ->whereBetween('apply_date', [$request->check_in_date, date('Y-m-d', strtotime($request->check_out_date . ' -1 day'))])
            ->orderBy('apply_date')
            ->get();

        $numberOfNights = (strtotime($request->check_out_date) - strtotime($request->check_in_date)) / 86400;

        if ($inventories->count() != $numberOfNights) {
            return response()->json([
                'success' => false,
                'message' => 'Không đủ dữ liệu tồn kho cho khoảng ngày đã chọn',
            ], 400);
        }

        foreach ($inventories as $inventory) {
            if ($inventory->available_allotment < $request->rooms_count) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không đủ số lượng phòng trống',
                ], 400);
            }
        }

        $totalAmount = $inventories->sum('price') * $request->rooms_count;

        DB::beginTransaction();

        try {
            $booking = Booking::create([
                'booking_code' => 'BK' . time(),
                'user_id' => $user->id,
                'hotel_id' => $request->hotel_id,
                'promotion_id' => null,
                'guest_name' => $request->guest_name,
                'guest_phone' => $request->guest_phone,
                'total_amount' => $totalAmount,
                'platform_fee' => 0,
                'status' => 0,
                'created_at' => now(),
            ]);

            BookingDetail::create([
                'booking_id' => $booking->id,
                'room_type_id' => $request->room_type_id,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'rooms_count' => $request->rooms_count,
                'subtotal' => $totalAmount,
            ]);

            foreach ($inventories as $inventory) {
                $inventory->available_allotment -= $request->rooms_count;
                $inventory->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đặt phòng thành công',
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'total_amount' => $booking->total_amount,
                    'status' => $booking->status,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Đặt phòng thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::with(['hotel', 'details.roomType'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy lịch sử đặt phòng thành công',
            'data' => $bookings
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();

        $booking = Booking::with(['hotel', 'details.roomType'])
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn đặt phòng',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy chi tiết đặt phòng thành công',
            'data' => $booking
        ]);
    }
}
