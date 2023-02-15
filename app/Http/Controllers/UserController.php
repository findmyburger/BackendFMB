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
            'name' => ['required', 'max:20', 'unique:users'],
            'email' => ['required','email', 'unique:users'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'password_confirm' => ['required','same:password'],
        ],
        [
            'name' => [
                'required' => 'El nombre es obligatorio.',
                'max' => 'El nombre es muy largo.',
                'unique' => 'Ya existe un usuario con ese nombre.',
            ],
            'email' => [
                'required' => 'El email es obligatorio.',
                'email' => 'Formato de email inválido.',
                'unique' => 'Ya existe un usuario con ese email.',
            ],
            'password' => [
                'required' => 'La contraseña es obligatoria.',
                'min' => 'La contraseña debe ser mínimo de 8 cifras',
                'password.mixed' => 'La contraseña debe tener mínimo una letra minúscula y una mayúscula',
                'password.numbers' => 'La contraseña debe tener mínimo un número',
            ],
            'password_confirm' => [
                'required' => 'La confirmación de contraseña es obligatoria',
                'same' => 'Las contraseñas no coinciden',
            ],
        ]);
        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Fallos: ');
        }else{
            $user->name = $datos->name;
            $user->email = $datos->email;
            $user->password = Hash::make($datos->password);
            try{
                $user->save();
                return ResponseGenerator::generateResponse(200, '', 'Usuario gurdado correctamente');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, $e, 'Fallo al guardar');
            }
        }
    }
    public function addRestaurantToFavourite(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $validator = Validator::make($request->all(), [
            'restaurant_id' => ['required', 'exists:restaurants,id', 'numeric'],
        ],
        [
            'restaurant_id' => [
                'required' => 'La id es obligatoria.',
                'exists' => 'Restaurante no válido',
                'numeric' => 'La id tiene que ser un número',
            ],
        ]);
        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Fallo/s');
        }else{
            $user = User::find(Auth::user()->id);
            $restaurantAlreadyFav = Validator::make($request->all(),[
                'restaurant_id' => ['exists:restaurant_user,restaurant_id'],
            ]);
            if($restaurantAlreadyFav->fails()){
                try{
                    $user->restaurants()->attach($datos->restaurant_id);
                    return ResponseGenerator::generateResponse(200, '', 'El restaurante se añadió correctamente.');
                }catch(\Exception $e){
                    return ResponseGenerator::generateResponse(400, '', 'Algo ha salido mal.');
                }
            }else{
                return ResponseGenerator::generateResponse(400, '', 'El restaurante ya está añadido');
            }
        }
    }
    public function deleteRestaurantInFavourite(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $validator = Validator::make($request->all(), [
            'restaurant_id' => ['required', 'exists:restaurants,id', 'exists:restaurant_user,restaurant_id', 'numeric'],
        ],
        [
            'name' => [
                'required' => 'La id es obligatoria.',
                'exists' => 'Restaurante no válido',
                'numeric' => 'La id tiene que ser un número',
            ],
        ]);
        if($validator->fails()){
            return ResponseGenerator::generateResponse(400, $validator->errors()->all(), 'Fallo/s');
        }else{
            $user = User::find(Auth::user()->id);
            try{
                $user->restaurants()->detach($datos->restaurant_id);
                return ResponseGenerator::generateResponse(200, '', 'El restaurante se borró correctamente.');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, '', 'Algo ha salido mal.');
            }
        }
    }
    public function favouriteList(){
        $user = User::with('restaurants')->find(Auth::user()->id);

        return ResponseGenerator::generateResponse(200, $user->restaurants, 'Estos son los restaurantes favoritos.');
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
            $fullUser = array (
                'userName' => $user->name,
                'token' => $token->plainTextToken
            );
            return ResponseGenerator::generateResponse(200, $fullUser, 'Usuario válido');
        }else{
            return ResponseGenerator::generateResponse(400, '', 'La contraseña es incorrecta.');
        }

    }
    public function signOut($id){
        $user = User::find(Auth::user()->id);

        try{
            $user->tokens()->delete();
            return ResponseGenerator::generateResponse(200, '', 'Se cerró sesión correctamente');
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(200, $e, 'Algo salió mal');
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
