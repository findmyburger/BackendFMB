<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ResponseGenerator;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    public function getAllRestaurants(){
        $allRestaurants = Restaurant::all();
        try{
            return ResponseGenerator::generateResponse(200, $allRestaurants, 'Estos son todos los restaurantes.');
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(400, $e, 'Algo ha salido mal');
        }
    }
    public function getRecommended(){
        $recommendedNames = ["O’hara’s Irish Pub & Restaurant", "Burmet", "Toro Burger Lounge", "Frankie Burgers",
                        "El Rancho de Santa África", "La Bristoteca", "Freaks Burger", "Mad Grill", "Juancho’s BBQ",
                        "New York Burger", "Goiko", "SteakBurger", "Williamsburg Grill & Beer", "Nugu Burger"];

        $recomendRestaurants = [];


        foreach($recommendedNames as $restaurantName){
            $restaurant = Restaurant::where('restaurants.name', '=', $restaurantName)->first();
            array_push($recomendRestaurants, $restaurant);
        }

        try{
            return ResponseGenerator::generateResponse(200, $recomendRestaurants, 'Estos son todos los recomendados.');
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(400, $e, 'Algo ha salido mal');
        }
    }
    public function getRecentlyAdded(){
        $recentlyAddedNames = ["#PORNEAT", "VICIO", "Pink’s", "Basics by Goiko"];

        $recentlyAdded = [];


        foreach($recentlyAddedNames as $restaurantName){
            $restaurant = Restaurant::where('restaurants.name', '=', $restaurantName)->get();
            array_push($recentlyAdded, $restaurant);
        }

        try{
            return ResponseGenerator::generateResponse(200, $recentlyAdded, 'Estos son todos los recomendados.');
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(400, $e, 'Algo ha salido mal');
        }
    }
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
    public function filterRestaurants(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $validator = Validator::make($request->all(), [
            'name' => ['max:255'],
            'price' => ['numeric'],
            'burgerType' => [Rule::in(['pescado','cerdo','pollo','ternera','vegana','vegetariana','buey'])],
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
            $restaurants = Restaurant::where('restaurants.id','>',1);

            if($datos){
                if(isset($datos->latitude) && isset($datos->longitude) && isset($datos->radius)){
                    $restaurants = $this->filterByLocation($datos->latitude,$datos->longitude,$datos->radius);
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
                return ResponseGenerator::generateResponse(400,'', 'No hay filtros.');

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
    public function filterByLocation($latitude, $longitude, $radius){
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

