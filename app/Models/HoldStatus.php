<?php

namespace App\Models;

enum HoldStatus: int
{
    case Held = 0;
    case Confirmed = 1;
    case Cancelled = 2;
}
