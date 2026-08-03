<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    use ChecksOrganizationAccess;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('bookings.view');
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->sameOrganization($user, $booking)
            && ($user->isSuperAdmin() || $user->can('bookings.view'));
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->can('bookings.create');
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->sameOrganization($user, $booking)
            && ($user->isSuperAdmin() || $user->can('bookings.update'));
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->sameOrganization($user, $booking)
            && ($user->isSuperAdmin() || $user->can('bookings.delete'));
    }
}
