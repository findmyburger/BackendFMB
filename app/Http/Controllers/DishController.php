<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ResponseGenerator;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    /*public function show2(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'max_digists:11', 'exists:dishes,id'],
        ],
        [
            'id' => [
                'required' => 'La id es obligatoria.',
                'max_digits' => 'La id es muy larga.',
                'exits' => 'No se ha encontrado el plato'
            ],
        ]);
        try{
            $dish = Dish::with('ingredients')->find($datos->id);
            return ResponseGenerator::generateResponse(200, $dish, 'Este es el plato encontrado');
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(400, '', 'Fallo al buscar el plato');
        }
    }*/
}
