<?php

namespace App\Http\Controllers;

use App\Events\VideoSignal;
use Illuminate\Http\Request;

class CallController extends Controller
{
     public function signal(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'to' => 'required|integer',
            'data' => 'required',
        ]);

        $to = $request->to;
        $payload = [
            'type' => $request->type,
            'from' => auth()->id(),
            'data' => $request->data,
        ];

        // broadcast to private channel 'video.{to}'
        event(new VideoSignal($to, $payload));

        return response()->json(['ok' => true]);
    }
}
