<?php

namespace App\Http\Controllers;

use App\Services\Webhook\WebhookService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    private $webhookService;

    public function __construct(WebhookService $webhookService) {
        $this->webhookService = $webhookService;
    }

    public function handle(Request $request){
        $result = $this->webhookService->handle($request);

        return response()->json(['status'=> true, 'data' => $result]);
    }
}
