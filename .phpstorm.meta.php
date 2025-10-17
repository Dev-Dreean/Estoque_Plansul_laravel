<?php

namespace PHPSTORM_META {

    override(\Illuminate\Support\Facades\Auth::user(), map([
        '' => \App\Models\User::class,
    ]));

}
