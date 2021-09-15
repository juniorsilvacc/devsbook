<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');

        $this->loggedUser = auth()->user();
    }

    public function update(Request $request)
    { //PUT api/user (name, email, password, birthdate, city, work, password, password_confirm)
        $error = ['error' => ''];

        $name = $request->input('name');
        $email = $request->input('email');
        $birthdate = $request->input('birthdate');
        $city = $request->input('city');
        $work = $request->input('work');
        $password = $request->input('password');
        $password_confirm = $request->input('password_confirm');

        $user = User::find($this->loggedUser['id']);

        //Name
        if ($name) {
            $user->name = $name;
        }

        //Email
        if ($email) {
            if ($email != $user->email) {
                $emailExists = User::where('email', $email)->count();

                if ($emailExists === 0) {
                    $user->email = $email;
                } else {
                    $array['error'] = 'E-mail já existe.';
                    return $array;
                }
            }
        }

        //Birthdate
        if ($birthdate) {

            if (strtotime($birthdate) === false) {
                $array['error'] = 'Data de nascimento inválido.';
                return $array;
            } else {
                $user->birthdate = $birthdate;
            }
        }

        //City
        if ($city) {
            $user->city = $city;
        }

        //Work
        if ($work) {
            $user->work = $work;
        }

        //Password
        if ($password && $password_confirm) {
            if ($password === $password_confirm) {

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $user->password = $hash;
            } else {
                $array['error'] = 'Você está digitando senhas diferente.';
                return $array;
            }
        }

        $user->save();


        return $error;
    }
}
