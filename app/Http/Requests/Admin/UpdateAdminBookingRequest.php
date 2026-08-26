<?php

namespace App\Http\Requests\Admin;

class UpdateAdminBookingRequest extends StoreAdminBookingRequest
{
    /**
     * Only admin-created bookings are editable — customer bookings are the
     * tourist's own submission and stay untouched.
     */
    public function authorize(): bool
    {
        return $this->route('booking')?->isAdminBooking() === true;
    }
}
