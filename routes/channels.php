<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

Broadcast::channel('video.{id}', function ($user, $id) {
    // allow only the user with that id to listen to their private channel
    return (int) $user->id === (int) $id;
});
