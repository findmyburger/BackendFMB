<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ResponseGenerator;
use App\Models\Dish;
use Illuminate\Http\Request;

class DishController extends Controller
{
    public function show($id){
        if($id){
            $dish = Dish::with('ingredients')->find($id);
            if($dish){
                return ResponseGenerator::generateResponse(200, $dish, 'ok');
            }else{
                return ResponseGenerator::generateResponse(200, '', 'Dish not found');
            }
        }else{
            return ResponseGenerator::generateResponse(400, '', 'No id');
        }
    }
}
