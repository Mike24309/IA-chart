<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canaux de diffusion
|--------------------------------------------------------------------------
|
| Ce fichier permettrait de declarer les canaux de diffusion si
| l application utilisait des evenements temps reel avec autorisation.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
