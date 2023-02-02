<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Helpers\ResponseGenerator;
use App\Mail\RecoverPassword;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;


use function PHPUnit\Framework\isEmpty;

class UserController extends Controller
{
    public function register(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $user = new User();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'max:20'],
            'email' => ['required','email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'password_confirm' => ['required','same:password'],
            'image' => 'required',
        ],
        [
            'name' => [
                'required' => 'El nombre es obligatorio.',
                'max' => 'El nombre es muy largo.',
            ],
            'email' => [
                'required' => 'El email es obligatorio.',
                'email' => 'Formato de email inválido.',
            ],
            'password' => [
                'required' => 'La contraseña es obligatoria.',
                'min' => 'La contraseña debe ser mínimo de 8 cifras',
                'mixedCase' => 'La contraseña debe tener mínimo una letra minúscula y una mayúscula',
                'numbers' => 'La contraseña debe tener mínimo un número',
            ],
            'password_confirm' => [
                'required' => 'La confirmación de contraseña es obligatoria',
                'same:password' => 'Las contraseñas no coinciden',
            ],
            'image' => [
                'required' => 'La imagen es obligatoria',
            ],
        ]);

        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Fallo/s');
        }else{
            $user->name = $datos->name;
            $user->email = $datos->email;
            $user->password = Hash::make($datos->password);
            $user->image = $datos->image;

            $userResponse = [$user->id, $user->name];

            try{
                $user->save();
                return ResponseGenerator::generateResponse(200, $userResponse, 'Usuario gurdado correctamente');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, '', 'Fallo al guardar');
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
    public function updateData(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);


        $validator = Validator::make($request->all(), [
            'name' => 'max:20',
            'email' => 'email',
            'password' => ['min:8', Password::min(8)->mixedCase()->numbers()],
        ],
        [
            'name' => [
                'max' => 'El nombre es muy largo.',
            ],
            'email' => [
                'email' => 'Formato de email inválido.',
            ],
            'password' => [
                'min' => 'La contraseña debe ser mínimo de 8 cifras',
                'mixedCase' => 'La contraseña debe tener mínimo una letra minúscula y una mayúscula',
                'numbers' => 'La contraseña debe tener mínimo un número',
            ],
        ]);

        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Something was wrong');
        }else{
            $user = User::find(Auth::user()->id);
            if(isset($datos->name)){
                $user->name = $datos->name;
            }
            if(isset($datos->email)){
                $user->email = $datos->email;
            }
            if(isset($datos->password)){
                $user->password = Hash::make($datos->password);
            }
            try{
                $user->save();
                return ResponseGenerator::generateResponse(200, $user, 'Datos modificados correctamente.');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, '', 'Algo salió mal.');
            }
        }


    }
    public function login(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        try{
            $user = User::where('email', 'like', $datos->email)->firstOrFail();
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(400, '', 'No se ha encontrado ningún usuario con ese email.');
        }
        if(Hash::check($datos->password, $user->password)){
            $token = $user->createToken('user');
            $fullUser = [$user, $token->plainTextToken];
            return ResponseGenerator::generateResponse(200, $fullUser, 'Usuario válido');
        }else{
            return ResponseGenerator::generateResponse(400, '', 'La contraseña es incorrecta.');
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
    public function sendEmail(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

         $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'EL formato del email es inválido');
        }else{
            $code = mt_rand();
            try{
                Mail::to($datos->email)->send(new RecoverPassword($datos->email, $code));
                return ResponseGenerator::generateResponse(200, $code, 'El email se envió correctamente');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, '', 'Algo fue mal');
            }
        }
    }
}
