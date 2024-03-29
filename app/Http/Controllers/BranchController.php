<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchResource;
use App\Models\Bank;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BranchController extends Controller
{

    public function getNearestBranches(Request $request, $latitude, $longitude, $bankId = null): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return BranchResource::collection(Branch::getNearestBranches($latitude, $longitude, $bankId));
    }



    public function updateBranchData(): void
    {
        $banks = Bank::all();
        foreach ($banks as $bank) {
            $bank_branches = $this->callBranchApi($bank->slug, $bank->id);
            foreach ($bank_branches as $api_id => $bank_branch) {
                Branch::updateOrCreate(['api_id' => $api_id], $bank_branch);
            }
        }
    }

    private function callBranchApi(string $slug, int $bank_id): ?array
    {
        try {
            $response = Http::get(config('app_info.branch_endpoint') . $slug);
            $data = [];

            foreach ($response->json()['data'] as $bank_branch) {
                $data[$bank_branch['id']] = [
                    'name' => $bank_branch['data'][0]['branch_name'] ?? '',
                    'address' => $bank_branch['data'][0]['address'] ?? '',
                    'lat' => isset($bank_branch['data'][0]['lat']) ? floatval($bank_branch['data'][0]['lat']) : null,
                    'lng' => isset($bank_branch['data'][0]['lng']) ? floatval($bank_branch['data'][0]['lng']) : null,
                    'phone' => $bank_branch['data'][0]['phone'] ?? '',
                    'bank_id' => $bank_id,
                ];
            }
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }
}
