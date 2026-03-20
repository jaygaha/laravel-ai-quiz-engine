<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(channel: 'App.Models.User.{id}', callback: fn ($user, $id): bool => (int) $user->id === (int) $id);
