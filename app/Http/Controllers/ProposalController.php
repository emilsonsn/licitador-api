<?php

namespace App\Http\Controllers;

use App\Services\Proposal\ProposalService;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function __construct(private ProposalService $proposalService)
    {
    }

    public function search(Request $request)
    {
        return $this->response($this->proposalService->search($request));
    }

    public function get($id)
    {
        return $this->response($this->proposalService->get($id));
    }

    public function fill(Request $request)
    {
        return $this->response($this->proposalService->fill($request));
    }

    public function create(Request $request)
    {
        $result = $this->proposalService->create($request);

        if ($result['status']) {
            $result['message'] = 'Proposta criada com sucesso';
        }

        return $this->response($result);
    }

    public function update(Request $request, $id)
    {
        $result = $this->proposalService->update($request, $id);

        if ($result['status']) {
            $result['message'] = 'Proposta atualizada com sucesso';
        }

        return $this->response($result);
    }

    public function delete($id)
    {
        $result = $this->proposalService->delete($id);

        if ($result['status']) {
            $result['message'] = 'Proposta removida com sucesso';
        }

        return $this->response($result);
    }

    public function view($id)
    {
        return $this->response($this->proposalService->view($id));
    }

    private function response($result)
    {
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
        ], $result['statusCode'] ?? 200);
    }
}
