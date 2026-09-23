<?php

namespace App\Http\Controllers\V1\EndPoint\System\Offre;

use App\Http\Controllers\Controller;
use App\Repository\V2\Student\Schedule\Training\Offre\FetchAllOffreRepo;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use App\Models\Roles\Admin\Offer\Offer;
use Illuminate\Http\Request;

class OffreController extends Controller
{
    
    public function getAllProducts(Request $request)
    {
      
            $zoneId = auth()->user()->zone_id;
    
            $filters = [];
           

            $filters['zone_id'] = $zoneId;
            $filters['is_cart'] = 1;
            $filters['boite_type'] = $request->boite_type;
            $filters['status']= true;
        
            // $offer=Offer::where('zone_id',$zoneId)->where('is_offer_cart',1)->where('is_auto',0)->get();
            // return $offer;

            

        return response()->json(FetchAllOffreRepo::run($filters));
    }

}
