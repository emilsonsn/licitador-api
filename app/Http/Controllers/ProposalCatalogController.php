<?php

namespace App\Http\Controllers;

use App\Services\Proposal\ProposalCatalogService;
use Illuminate\Http\Request;

class ProposalCatalogController extends Controller
{
    public function __construct(private ProposalCatalogService $proposalCatalogService) {}

    public function get($proposalId)
    {
        return $this->response($this->proposalCatalogService->get($proposalId));
    }

    public function update(Request $request, $proposalId)
    {
        $result = $this->proposalCatalogService->update($request, $proposalId);

        if ($result['status']) {
            $result['message'] = 'Catálogo atualizado com sucesso';
        }

        return $this->response($result);
    }

    public function uploadImage(Request $request, $proposalId, $itemId)
    {
        $result = $this->proposalCatalogService->uploadImage($request, $proposalId, $itemId);

        if ($result['status']) {
            $result['message'] = 'Imagem atualizada com sucesso';
        }

        return $this->response($result);
    }

    public function deleteImage($proposalId, $itemId)
    {
        $result = $this->proposalCatalogService->deleteImage($proposalId, $itemId);

        if ($result['status']) {
            $result['message'] = 'Imagem removida com sucesso';
        }

        return $this->response($result);
    }

    public function generate($proposalId)
    {
        $result = $this->proposalCatalogService->generate($proposalId);

        if ($result['status']) {
            $result['message'] = 'Catálogo gerado com sucesso';
        }

        return $this->response($result);
    }

    public function view($catalogId)
    {
        return $this->response($this->proposalCatalogService->view($catalogId));
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
