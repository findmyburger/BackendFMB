<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ResponseGenerator;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DishController extends Controller
{
    public function show($id){
        if($id){
            $dish = Dish::with('ingredients')->find($id);
            if($dish){
                return ResponseGenerator::generateResponse(200, $dish, 'Este es el plato encontrado');
            }else{
                return ResponseGenerator::generateResponse(200, '', 'No se ha encontrado ningún plato');
            }
        }else{
            return ResponseGenerator::generateResponse(400, '', 'No id');
        }
    }
    public function restaurantFilter(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $validator = Validator::make($request->all(), [
            'restaurant_id' => ['integer','exists:restaurants,id'],
            'burgerType' => [Rule::in(['pescado','cerdo','pollo','ternera','vegana','vegetariana','buey'])],
        ],
        [
            'burgerType' => 'Tipo de carne inválido',
            'restaurant_id' => [
                'integer' => 'La id debe ser un número',
                'exists' => 'Restaurante inválido',
            ]
        ]);
        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Fallo/s');
        }else{

            $dishes = Dish::where('dishes.burgerType', 'like', "$datos->burgerType")
                            ->where('dishes.restaurant_id','=', $datos->restaurant_id);
            try{
                return ResponseGenerator::generateResponse(200, $dishes->get(), 'Estos son los platos filtrados.');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, $e, 'Algo ha salido mal.');
            }
        }
    }
}
