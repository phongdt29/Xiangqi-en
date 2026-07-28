<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('room.{roomId}', function (User $user, int $roomId) {
    $room = Room::find($roomId);

    if (! $room) {
        return false;
    }

    return $user->id === $room->host_id || $user->id === $room->guest_id;
});
