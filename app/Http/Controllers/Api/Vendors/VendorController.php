<?php

namespace App\Http\Controllers\Api\Vendors;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends ApiController
{
    public function index(Request $request, VendorService $vendors): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->paginated($vendors->paginate($filters), VendorResource::class);
    }

    public function store(StoreVendorRequest $request, VendorService $vendors): JsonResponse
    {
        return $this->success(new VendorResource($vendors->create($request->validated(), $request->user())), 'Vendor created', 201);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        return $this->success(new VendorResource($vendor), 'Vendor loaded');
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor, VendorService $vendors): JsonResponse
    {
        return $this->success(new VendorResource($vendors->update($vendor, $request->validated(), $request->user())), 'Vendor updated');
    }

    public function destroy(Request $request, Vendor $vendor, VendorService $vendors): JsonResponse
    {
        $result = $vendors->delete($vendor, $request->user());

        return $this->success(null, $result === 'deactivated' ? 'Vendor deactivated' : 'Vendor deleted');
    }
}
