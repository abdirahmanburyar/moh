<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Only authenticated users can access the inventory channel
Broadcast::channel('inventory', function ($user) {
    return auth()->check();
});

// Private channel for user-specific events
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for transfer events
Broadcast::channel('transfer.{id}', function ($user, $id) {
    // Allow access if user has permission to view transfers
    return $user->can('transfer.view');
});
