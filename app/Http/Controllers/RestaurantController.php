<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ResponseGenerator;
use App\Models\Restaurant;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
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
            'price' => ['regex:/^\d*\.\d{0,2}$/'],
            'burgerType' => [Rule::in(['pescado','cerdo','pollo','ternera','vegana','vegetal'])],
            'latitude' => ['between:-90,90', 'numeric'],
            'longitude' => ['between:-180,180', 'numeric'],
        ],
        [
            'name' => [
                'max' => 'El nombre es muy largo.',
            ],
            'price' => [
                'regex' => 'Formato de precio inválido.',
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
        ]);

        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Fallo/s');
        }else{
            $recomendRestaurants = Restaurant::with('dishes')->limit(15)->orderBy('rate','desc')->get();
            if(!empty($datos)){
                $restaurants = Restaurant::all();

                if(isset($datos->name)){
                    $restaurantName = Restaurant::where('name', 'like', '%'.$datos->name.'%' )->get();
                    $restaurants = $restaurantName;
                }
                if(isset($datos->price)){
                    $dishPrice = Restaurant::join('dishes', 'dishes.restaurant_id', '=', 'restaurants.id')
                                            ->where('dishes.price', '<=', $datos->price)
                                            ->select('restaurants.*')
                                            ->get();
                    $restaurants = $dishPrice;
                }
                if(isset($datos->burgerType)){
                    $burgerType = Restaurant::join('dishes', 'dishes.restaurant_id', '=', 'restaurants.id')
                                            ->where('dishes.burgerType', 'like', $datos->burgerType)
                                            ->select('restaurants.*')
                                            ->get();
                    $restaurants = $burgerType;
                }

                try{
                    return ResponseGenerator::generateResponse(200, $restaurants, 'Estos son los restaurantes filtrados.');
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse(400, $e, 'Algo ha salido mal.');
                }
            }
            try{
                return ResponseGenerator::generateResponse(200, $recomendRestaurants, 'Estos son los recomendados.');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, $e, 'Something was wrong');
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
}
