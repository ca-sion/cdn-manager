<?php

namespace App\Http\Controllers;

use App\Enums\EngagementStatusEnum;
use App\Models\ClientEngagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TrackingController extends Controller
{
    public function engagement(ClientEngagement $engagement)
    {
        if (is_null($engagement->viewed_at)) {
            $engagement->viewed_at = now();
            $engagement->status = EngagementStatusEnum::Idle;
            $engagement->save();
        }

        $pixel = base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICRAEAOw==');
        return Response::make($pixel, 200, ['Content-Type' => 'image/gif']);
    }
}