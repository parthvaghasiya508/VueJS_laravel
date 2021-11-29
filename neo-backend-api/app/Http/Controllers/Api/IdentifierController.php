<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RoomDB;

class IdentifierController extends Controller
{
    public function getIdentifierSources(): JsonResponse
    {
        $identifierSources = RoomDB::getIdentifierSources();
        return response()->json($identifierSources);
    }

    public function getPropertyIdentifiers(int $propertyId): JsonResponse
    {
        $propertyIdentifiers = RoomDB::getPropertyIdentifiers($propertyId);
        return response()->json($propertyIdentifiers);
    }

    public function updatePropertyIdentifiers(Request $request): JsonResponse
    {
        $data = $request->validate(
            [
                'propertyId' => 'int|required',
                'identifiers.*.identifier' => 'string|nullable',
                'identifiers.*.sourceId' => 'int|required',
            ]
        );

        $deleteIdentifiers = array_filter($data['identifiers'], fn($item) => is_null($item['identifier']));
        $updateIdentifiers = array_filter($data['identifiers'], fn($item) => !is_null($item['identifier']));

        foreach ($deleteIdentifiers as $identifier) {
            RoomDB::deletePropertyIdentifier($data['propertyId'], $identifier['sourceId']);
        }

        $data['identifiers'] = array_values($updateIdentifiers);
        $response = RoomDB::updatePropertyIdentifiers($data);

        return response()->json($response);
    }
}
