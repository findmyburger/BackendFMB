<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ResponseGenerator;
use App\Models\Restaurant;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    public function show($id){
        if($id){
            $restaurant = Restaurant::with('dishes')->find($id);
            if($restaurant){
                return ResponseGenerator::generateResponse(200, $restaurant, 'ok');
            }else{
                return ResponseGenerator::generateResponse(200, '', 'Restaurant not found');
            }
        }else{
            return ResponseGenerator::generateResponse(400, '', 'No id');
        }
    }
    public function list(Request $request){

        $json = $request->getContent();
        $datos = json_decode($json);

        $validator = Validator::make($request->all(), [
            'name' => ['max:255'],
            'price' => ['numeric'],
            'burgerType' => [Rule::in(['pescado','cerdo','pollo','ternera','vegana','vegetal'])],
            'latitude' => ['between:-90,90', 'numeric'],
            'longitude' => ['between:-180,180', 'numeric'],
            'radius' => ['numeric'],
        ],
        [
            'name' => [
                'max' => 'El nombre es muy largo.',
            ],
            'price' => [
                'numeric' => 'Formato de precio inválido.',
            ],
            'burgerType' => 'Tipo de carne inválido',

            'latitude' => [
                'numeric' => 'Debe ser numérica',
                'between' => 'Formato inválido de la latitud',
            ],
            'longitude' => [
                'numeric' => 'Debe ser numérica',
                'between' => 'Formato inválido de la longitud',
            ],
            'radius' => [
                'numeric' => 'El radio debe ser un número',
            ],
        ]);

        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Fallo/s');
        }else{
            $recomendRestaurants = Restaurant::limit(15)->orderBy('rate','desc')->get();

            $restaurants = Restaurant::where('restaurants.id','>',1);

            if($datos){
                if(isset($datos->latitude) && isset($datos->longitude) && isset($datos->radius)){
                    $restaurants = Restaurant::select(DB::raw("restaurants.*,
                                ( 3959 * acos( cos( radians({$datos->latitude}) ) *
                                cos( radians( latitude ) )
                                * cos( radians( longitude ) - radians({$datos->longitude})
                                ) + sin( radians({$datos->latitude}) ) *
                                sin( radians( latitude ) ) )
                            ) AS distance"))
                        ->having('distance', '<', $datos->radius)
                        ->orderBy('distance');
                }
                if(isset($datos->price) || isset($datos->burgerType)){
                    $restaurants->join('dishes', 'dishes.restaurant_id', '=', 'restaurants.id');
                }
                if(isset($datos->name)){
                    $restaurants->where('restaurants.name', 'like', "%$datos->name%");
                }
                if(isset($datos->price)){
                    $restaurants->where('dishes.price', '<=', $datos->price);
                }
                if(isset($datos->burgerType)){
                    $restaurants->where('dishes.burgerType', 'like', "$datos->burgerType");
                }
                try{
                    return ResponseGenerator::generateResponse(200, $restaurants->get(), 'Estos son los restaurantes filtrados.');
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse(400, $e, 'Algo ha salido mal.');
                }
            }else{
                try{
                    return ResponseGenerator::generateResponse(200, $recomendRestaurants, 'Estos son los recomendados.');
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse(400, $e, 'Algo ha salido mal');
                }
            }
        }
    }
    public function register(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);


        foreach($datos as $item){

            $restaurant = new Restaurant();

            $restaurant->name = $item->name;
            $restaurant->image = $item->image;
            $restaurant->address = $item->address;
            $restaurant->latitude = $item->latitude;
            $restaurant->longitude = $item->longitude;
            $restaurant->rate = $item->rate;

            $restaurant->save();

        }
        return ResponseGenerator::generateResponse(200, $restaurant, 'ok');
    }
    public function filterByLocation($latitude, $longitude, $radius)
    {
        $restaurants = Restaurant::select(DB::raw("*,
                    ( 3959 * acos( cos( radians({$latitude}) ) *
                    cos( radians( latitude ) )
                    * cos( radians( longitude ) - radians({$longitude})
                    ) + sin( radians({$latitude}) ) *
                    sin( radians( latitude ) ) )
                ) AS distance"))
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->get();

        return $restaurants;
    }

}

