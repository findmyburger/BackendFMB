<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Helpers\ResponseGenerator;
use App\Mail\RecoverPassword;
use App\Models\Restaurant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\isEmpty;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Usuarios",
 *      description="Todas las funciones relacionadas con los usuarios.",
 * )
 */

class UserController extends Controller
{
    /**
     * @OA\Post(path="/api/users/register",
     *     tags={"user"},
     *     summary="Registrar un usuario",
     *     description="Función para registrar usuarios en la app.",
     *     operationId="registerUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Registrar un usuario",
     *         @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Registro",
     *                  summary="Este es un ejemplo de registro",
     *                  value = {
     *                      "name": "Paco",
     *                      "email": "paco@gmail.com",
     *                      "password": "Aa123456",
     *                      "password_confirm": "Aa123456",
     *                  }
     *              ),
     *          ),
     *     ),
     *     @OA\Response(response=200, description="Registro correcto"),
     *     @OA\Response(response=400, description="Datos erróneos")
     * )
     */

    public function register(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $user = new User();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'max:20', 'unique:users'],
            'email' => ['required','email', 'unique:users'],
            'password' => ['required', Password::min(8)->letters()->numbers()->mixedCase()],
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
            'password_confirm' => [
                'required' => 'La confirmación de contraseña es obligatoria',
                'same' => 'Las contraseñas no coinciden',
            ],
        ]);
        if($validator->fails()){
            $errors = [];
            foreach($validator->errors()->all() as $error){

                if($error == "The password must be at least 8 characters."){
                    array_push($errors, "La contraseña debe ser mínimo de 8 cifras." );
                }else if($error == "The password must contain at least one uppercase and one lowercase letter."){
                    array_push($errors, "La contraseña debe tener mínimo una letra minúscula y una mayúscula.");
                }else if($error == "The password must contain at least one letter."){
                    array_push($errors, "La contraseña debe tener mínimo una letra.");
                }else if($error == "The password must contain at least one number."){
                    array_push($errors, "La contraseña debe tener mínimo un número.");
                }else{
                    array_push($errors, $error);
                }
            }
            return ResponseGenerator::generateResponse(400, $errors, 'Fallos: ');
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
    /**
     * @OA\Post(path="/api/users/addRestaurantToFavourite",
     *     tags={"user"},
     *     summary="Añadir un restaurante a favoritos",
     *     description="Función para añadir un restaurante a favoritos.",
     *     operationId="addFavouriteUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Añadir un restaurante a favoritos",
     *         @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Añadir un restaurante a favoritos",
     *                  summary="Este es un ejemplo de añadir un restaurante a favoritos",
     *                  value = {
     *                      "restaurant_id": 1,
     *                  }
     *              ),
     *          ),
     *     ),
     *     @OA\Response(response=200, description="Añadido correctamente"),
     *     @OA\Response(response=400, description="Restaurante erróneo")
     * )
     */
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
    /**
     * @OA\Post(path="/api/users/deleteRestaurantInFavourite",
     *     tags={"user"},
     *     summary="Borrar un restaurante a favoritos",
     *     description="Función para borrar un restaurante a favoritos.",
     *     operationId="deleteRestaurantInFavourite",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Borrar un restaurante a favoritos",
     *         @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Borrar un restaurante a favoritos",
     *                  summary="Este es un ejemplo de borrar un restaurante a favoritos",
     *                  value = {
     *                      "restaurant_id": 1,
     *                  }
     *              ),
     *          ),
     *     ),
     *     @OA\Response(response=200, description="Añadido correctamente"),
     *     @OA\Response(response=400, description="Restaurante erróneo")
     * )
     */
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
    /**
     * @OA\Get(path="/api/users/favouriteList",
     *     tags={"user"},
     *     summary="Recibir la lista de favoritos",
     *     description="Función para recibir la lista de favoritos.",
     *     operationId="favouriteList",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Recibir la lista de favoritos",
     *     ),
     *     @OA\Response(response=200, description="Esta es la lista de favoritos: {Lista}"),
     *     @OA\Response(response=400, description="Restaurante erróneo")
     * )
     */
    public function favouriteList(){
        $user = User::with('restaurants')->find(Auth::user()->id);

        return ResponseGenerator::generateResponse(200, $user->restaurants, 'Estos son los restaurantes favoritos.');
    }
    /**
     * @OA\Post(path="/api/users/updateData",
     *     tags={"user"},
     *     summary="Cambiar los datos de un usuario",
     *     description="Función para cambiar los datos de los usuarios en la app.",
     *     operationId="updateUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Actualizar un usuario",
     *         @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Actualizar datos",
     *                  summary="Este es un ejemplo de actualizar datos",
     *                  value = {
     *                      "name": "Pedro",
     *                  }
     *              ),
     *          ),
     *     ),
     *     @OA\Response(response=200, description="Login correcto"),
     *     @OA\Response(response=400, description="Credenciales erroneas")
     * )
     */
    public function updateData(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);


        $validator = Validator::make($request->all(), [
            'name' => 'max:20',
            'password' => 'max:40',
            'newPassword' => Password::min(8)->letters()->numbers()->mixedCase(),
            'newPassword_confirmation' => 'same:newPassword',
            'image' => ['max:255'],
        ],
        [
            'name' => [
                'max' => 'El nombre es muy largo.',
            ],
            'password' => [
                'max' => 'La contraseña es muy larga',
            ],
            'newPassword_confirmation' => [
                'same' => 'Las contraseñas no coinciden'
            ],
            'image' => [
                'max' => 'La referencia es muy larga.',
            ],
        ]);

        if($validator->fails()){
            $errors = [];
            foreach($validator->errors()->all() as $error){
                if($error == "The password must be at least 8 characters."){
                    array_push($errors, "La contraseña debe ser mínimo de 8 cifras." );
                }else if($error == "The password must contain at least one uppercase and one lowercase letter."){
                    array_push($errors, "La contraseña debe tener mínimo una letra minúscula y una mayúscula.");
                }else if($error == "The password must contain at least one letter."){
                    array_push($errors, "La contraseña debe tener mínimo una letra.");
                }else if($error == "The password must contain at least one number."){
                    array_push($errors, "La contraseña debe tener mínimo un número.");
                }else{
                    array_push($errors, $error);
                }
            }
            return ResponseGenerator::generateResponse(400, $errors, 'Fallos: ');
        }else{
            $user = User::find(Auth::user()->id);

            if(isset($datos->name)){
                $user->name = $datos->name;
            }
            if(isset($datos->password) && isset($datos->newPassword) && isset($datos->newPassword_confirmation)){
                if (Hash::check($datos->password, Auth::user()->password)) {
                    $user->password = Hash::make($datos->newPassword);
                }else{
                    return ResponseGenerator::generateResponse(400, '', 'La contraseña es incorrecta.');
                }
            }
            if(isset($datos->image)){
                $imageData = $datos->image;

                $temporal_file = tempnam(sys_get_temp_dir(), 'img');
                file_put_contents($temporal_file, base64_decode($imageData));

                $file = new UploadedFile($temporal_file, base64_decode($imageData));
                $url = Storage::url(Auth::user()->name.'.jpg');
                $finalUrl = 'http://127.0.0.1:8000'.$url;

                $file->storeAs('public', Auth::user()->name.'jpg');
                $user->image = $finalUrl;

            }
            try{
                $user->save();
                return ResponseGenerator::generateResponse(200, '', 'Datos modificados correctamente.');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, '', 'Algo salió mal.');
            }
        }


    }
    /**
     * @OA\Post(path="/api/users/login",
     *     tags={"user"},
     *     summary="Hacer login de un usuario",
     *     description="Función para loguear usuarios en la app.",
     *     operationId="loginUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Loguear un usuario",
     *         @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Login",
     *                  summary="Este es un ejemplo de login",
     *                  value = {
     *                      "name": "Paco",
     *                      "password": "Aa123456",
     *                  }
     *              ),
     *          ),
     *     ),
     *     @OA\Response(response=200, description="Login correcto"),
     *     @OA\Response(response=400, description="Credenciales erroneas")
     * )
     */
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
    /**
     * @OA\Post(path="/api/users/signOut",
     *     tags={"user"},
     *     summary="Cerrar sesión de un usuario",
     *     description="Función para cerrar sesión de un usuario.",
     *     operationId="signOut",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Cerrar sesión de un usuario",
     *     ),
     *     @OA\Response(response=200, description="Se cerró sesión"),
     *     @OA\Response(response=400, description="Algo salió mal")
     * )
     */
    public function signOut(){
        $user = User::find(Auth::user()->id);

        try{
            $user->tokens()->delete();
            return ResponseGenerator::generateResponse(200, '', 'Se cerró sesión correctamente');
        }catch(\Exception $e){
            return ResponseGenerator::generateResponse(200, $e, 'Algo salió mal');
        }
    }
    /**
     * @OA\Post(path="/api/users/sendEmail",
     *     tags={"user"},
     *     summary="Mandar email a un usuario",
     *     description="Función para mandar email a un usuario.",
     *     operationId="sendEmail",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Mandar email a un usuario",
     *         @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Mandar email a un usuario",
     *                  summary="Este es un ejemplo de mandar email a un usuario",
     *                  value = {
     *                      "email": "paco@gmail.com",
     *                  }
     *              ),
     *          ),
     *     ),
     *     @OA\Response(response=200, description="Email mandado correctamente"),
     *     @OA\Response(response=400, description="Algo salió mal")
     * )
     */
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
                Mail::to($datos->email)->queue(new RecoverPassword($datos->email, $code));
                $data = array (
                    'code' => $code,
                    'email' => $datos->email
                );
                return ResponseGenerator::generateResponse(200, $data, 'El email se envió correctamente');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, '', 'Algo fue mal');
            }
        }
    }
    /**
     * @OA\Post(path="/api/users/recoverPass",
     *     tags={"user"},
     *     summary="Recuperar la contraseña de un usuario",
     *     description="Función para recuperar la contraseña de un usuario.",
     *     operationId="recoverPass",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Recuperar la contraseña de un usuario",
     *         @OA\JsonContent(
     *              @OA\Examples(
     *                  example="Recuperar la contraseña de un usuario",
     *                  summary="Este es un ejemplo de recuperar la contraseña de un usuario",
     *                  value = {
     *                      "password": "Aa123456",
     *                      "password_confirm": "Aa123456",
     *                      "email": "paco@gmail.com",
     *                  }
     *              ),
     *          ),
     *     ),
     *     @OA\Response(response=200, description="Email mandado correctamente"),
     *     @OA\Response(response=400, description="Algo salió mal")
     * )
     */
    public function recoverPass(Request $request){
        $json = $request->getContent();
        $datos = json_decode($json);

        $user = new User();

        $validator = Validator::make($request->all(), [
            'password' => ['required', Password::min(8)->letters()->numbers()->mixedCase()],
            'password_confirm' => ['required','same:password'],
            'email' => ['required'],
        ]);
        if($validator->fails()){
            $errors = [];
            foreach($validator->errors()->all() as $error){

                if($error == "The password must be at least 8 characters."){
                    array_push($errors, "La contraseña debe ser mínimo de 8 cifras." );
                }else if($error == "The password must contain at least one uppercase and one lowercase letter."){
                    array_push($errors, "La contraseña debe tener mínimo una letra minúscula y una mayúscula.");
                }else if($error == "The password must contain at least one letter."){
                    array_push($errors, "La contraseña debe tener mínimo una letra.");
                }else if($error == "The password must contain at least one number."){
                    array_push($errors, "La contraseña debe tener mínimo un número.");
                }else{
                    array_push($errors, $error);
                }
            }
            return ResponseGenerator::generateResponse(400, $errors, 'Fallos: ');
        }else{
            $user = User::where('email', '=', $datos->email)
                        ->select('users.*')
                        ->first();

            $user->password = Hash::make($datos->password);
            try{
                $user->save();
                return ResponseGenerator::generateResponse(200, '', 'Contraseña cambiada.');
            }catch(\Exception $e){
                return ResponseGenerator::generateResponse(400, '
                ', 'Fallo al guardar.');
            }
        }

    }
    /**
     * @OA\Get(path="/api/users/getData",
     *     tags={"user"},
     *     summary="Recibir los datos del usuario",
     *     description="Función para recibir los datos del usuario.",
     *     operationId="getData",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Recibir los datos del usuario",
     *     ),
     *     @OA\Response(response=200, description="Estos son los datos del usuario: {Datos}"),
     *     @OA\Response(response=400, description="Algo salió mal")
     * )
     */
    public function getData(){
        $userData = [
            "id" => Auth::user()->id,
            "name" => Auth::user()->name,
            "email" => Auth::user()->email,
            "image" => Auth::user()->image
        ];
        return ResponseGenerator::generateResponse(200, $userData, 'Estos son los datos del usuario');
    }
}
