<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// The board is a presence channel purely so we can count who's watching the
// pass. Visitors are anonymous, so we authorize everyone via the "visitor"
// request guard, which identifies each one by their session id.
Broadcast::channel('board', fn (Authenticatable $user): array => [
    'id' => $user->getAuthIdentifier(),
], ['guards' => ['visitor']]);
