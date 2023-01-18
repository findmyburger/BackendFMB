<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ResponseGenerator;
use App\Models\Restaurant;
use Illuminate\Http\Request;

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
        $restaurants = Restaurant::with('dishes')->orderBy('rate','desc')->get();

        if($datos){
            if(isset($datos->name)){
                $restaurantName = $restaurants->where('name', 'like', $datos->name );
                $restaurants = $restaurantName;
            }else{
                if(isset($datos->price)){
                    $dishPrice = Restaurant::join('dishes', 'restaurant_id', '=', 'restaurants.id')
                                            ->where('price', '<=', $datos->price)
                                            ->get();
                    $restaurants = $dishPrice;
                }
                if(isset($datos->burgerType)){
                    $burgerType = Restaurant::join('dishes', 'restaurant_id', '=', 'restaurants.id')
                                            ->join('dish_ingredient', 'dishes.id', '=', 'dish_ingredient.dish_id')
                                            ->join('ingredients', 'dish_ingredient.ingredient_id', '=', 'ingredients.id')
                                            ->where('name', 'like', $datos->burgerType)
                                            ->get();
                    $restaurants = $burgerType;
                }
            }
        }
        try{
            return ResponseGenerator::generateResponse(200, $restaurants, 'ok');
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(400, '', 'Something was wrong');
        }
    }
}
