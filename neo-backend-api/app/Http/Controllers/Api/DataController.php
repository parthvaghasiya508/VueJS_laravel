<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Managers\PMSManager;
use RoomDB;
use App\Models\Currency;
use App\Models\Page;
use Illuminate\Http\Response;

class DataController extends Controller {

    /**
     * Get countries list
     *
     * @return Response
     */
    public function getCountries(PMSManager $manager) {
        $data = $manager->getCountriesOrStates();

        if ($data) {
            return response()->json(['data' => $data]);
        } else {
            return response()->json(['error' => 'Error getting the list of countries !']);
        }
    }

    /**
     * Get states list from
     * a given country_iso
     *
     * @param $country_iso
     * @return Response
     */
    public function getStates($country_iso, PMSManager $manager) {
        $data = $manager->getCountriesOrStates($country_iso);

        if ($data) {
            return response()->json(['data' => $data]);
        } else {
            return response()->json(['error' => 'Error getting the list of states !']);
        }
    }

    /**
     * Get currencies list
     *
     * @return Response
     */
    public function getCurrencies() {
        $data = RoomDB::getCurrencies();
        if ($data) {
            return response()->json(['data' => $data]);
        } else {
            return response()->json(['error' => 'Error getting the list of Currencies !']);
        }
    }

    public function getPages() {
        return Page::allPages();
    }

    /**
     * Get property types
     *
     * @return Response
     */
    public function getPropertyTypes() {
        $data = RoomDB::getPropertyTypes();
        if ($data) {
            return response()->json(['data' => $data]);
        } else {
            return response()->json(['error' => 'Error getting the list of property types !']);
        }
    }

    /**
     * Get property types
     *
     * @return Response
     */
    public function getLanguages() {
        $data = RoomDB::getLanguages();
        if ($data) {
            return response()->json(['data' => $data]);
        }
        return response()->json(['error' => 'Error getting the list of property types !']);
    }

    

}
