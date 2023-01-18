<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Helpers\ResponseGenerator;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isEmpty;

class UserController extends Controller
{
    public function register(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $user = new User();

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:20',
            'email' => 'required|email',
            'password' => 'required|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/',
            'password_confirm' => 'required|same:password',
        ]);

        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Something was wrong');
        }else{
            $user->name = $datos->name;
            $user->email = $datos->email;
            $user->password = $datos->password;

            try{
                $user->save();
                return ResponseGenerator::generateResponse(200, $user, 'ok');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, '', 'Failed to save');
            }
        }
    }
    public function addRestaurantToFavourite(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $user = User::find($datos->user_id);
        if($user){
            $restaurant = Restaurant::find($datos->restaurant_id);
            if($restaurant){
                try{
                    $user->restaurants()->attach($datos->restaurant_id);
                    return ResponseGenerator::generateResponse(200, $user, 'ok');
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse(400, '', 'Failed to save');
                }
            }else{
                return ResponseGenerator::generateResponse(400, '', 'Restaurant not found');
            }
        }else{
            return ResponseGenerator::generateResponse(400, '', 'User not found');
        }
    }   
    public function deleteRestaurantInFavourite(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $user = User::find($datos->user_id);
        if($user){
            $restaurant = Restaurant::find($datos->restaurant_id);
            if($restaurant){
                try{
                    $user->restaurants()->detach($datos->restaurant_id);
                    return ResponseGenerator::generateResponse(200, $user, 'ok');
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse(400, '', 'Failed to save');
                }
            }else{
                return ResponseGenerator::generateResponse(400, '', 'Restaurant not found');
            }
        }else{
            return ResponseGenerator::generateResponse(400, '', 'User not found');
        }
    }
    public function favouriteList($id){
        $user = User::with('restaurants')->find($id);
        if($user){
            return ResponseGenerator::generateResponse(200, $user->restaurants, 'ok');
        }else{
            return ResponseGenerator::generateResponse(200, '', 'Ninja not found');
        }
    }
    public function updateData(Request $request, $id){
        $json = $request->getContent();
        $datos = json_decode($json);

        
        $user = User::find($id);
        if($user){
            $validator = Validator::make($request->all(), [
                'name' => 'max:20',
                'email' => 'email',
                'password' => 'min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/',
            ]);
    
            if($validator->fails()){
                return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Something was wrong');
            }else{
                if(isset($datos->name)){
                    $user->name = $datos->name;
                }
                if(isset($datos->email)){
                    $user->email = $datos->email;
                }
                if(isset($datos->password)){
                    $user->password = $datos->password;
                }
                try{
                    $user->save();
                    return ResponseGenerator::generateResponse(200, $user, 'ok');
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse(400, '', 'Failed to save');
                }
            }            
        }else{
            return ResponseGenerator::generateResponse(400, '', 'User not found');
        }
        
    }
    public function login(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        try{
            $user = User::where('email', 'like', $datos->email)->firstOrFail();
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(400, '', 'Invalid email');
        }
        if($datos->password == $user->password){
            $token = $user->createToken('user');
            $fullUser = [$user, $token->plainTextToken];
            return ResponseGenerator::generateResponse(200, $fullUser, 'Login succesfully');
        }else{
            return ResponseGenerator::generateResponse(400, '', 'Invalid password');
        }
        
    }
    public function signOut($id){
        $user = User::find($id);

        if($user){
            $user->tokens()->delete();
            return ResponseGenerator::generateResponse(200, '', 'Sign Out succesfuly');
        }else{
            return ResponseGenerator::generateResponse(200, '', 'User not found');
        }
    }
}
