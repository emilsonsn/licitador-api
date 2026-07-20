<?php

namespace App\Http\Controllers;

use App\Services\Proposal\ProposalTrackingService;
use Illuminate\Http\Request;

class ProposalTrackingController extends Controller
{
    public function __construct(private ProposalTrackingService $proposalTrackingService) {}

    public function get($proposalId)
    {
        return $this->response($this->proposalTrackingService->get($proposalId));
    }

    public function update(Request $request, $proposalId)
    {
        $result = $this->proposalTrackingService->update($request, $proposalId);

        if ($result['status']) {
            $result['message'] = 'Acompanhamento atualizado com sucesso';
        }

        return $this->response($result);
    }

    public function applyDiscount(Request $request, $proposalId)
    {
        $result = $this->proposalTrackingService->applyDiscount($request, $proposalId);

        if ($result['status']) {
            $result['message'] = 'Desconto aplicado com sucesso';
        }

        return $this->response($result);
    }

    public function finish($proposalId)
    {
        $result = $this->proposalTrackingService->finish($proposalId);

        if ($result['status']) {
            $result['message'] = 'Acompanhamento finalizado com sucesso';
        }

        return $this->response($result);
    }

    public function reopen($proposalId)
    {
        $result = $this->proposalTrackingService->reopen($proposalId);

        if ($result['status']) {
            $result['message'] = 'Acompanhamento reaberto com sucesso';
        }

        return $this->response($result);
    }

    public function print($proposalId)
    {
        return $this->response($this->proposalTrackingService->printData($proposalId));
    }

    public function export($proposalId)
    {
        $result = $this->proposalTrackingService->export($proposalId);

        if (! $result['status']) {
            return $this->response($result);
        }

        return response($result['data']['content'], 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$result['data']['filename'].'"',
        ]);
    }

    private function response(array $result)
    {
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
        ], $result['statusCode'] ?? 200);
    }
}
