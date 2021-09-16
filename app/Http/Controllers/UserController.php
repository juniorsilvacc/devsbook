<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Intervention\Image\Facades\Image;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');

        $this->loggedUser = auth()->user();
    }

    public function update(Request $request)
    {
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

    public function updateAvatar(Request $request)
    {
        $array = ['error' => ''];

        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('avatar');

        if ($image) {

            if (in_array($image->getClientMimeType(), $allowedTypes)) {

                $filename = md5(time() . rand(0, 9999)) . '.jpg';

                $destPatch = public_path('/media/avatars');

                $img = Image::make($image)
                    ->fit(300, 300)
                    ->save($destPatch . '/' . $filename);

                $user = User::find($this->loggedUser['id']);
                $user->avatar = $filename;
                $user->save();

                $array['url'] = url('/media/avatars/' . $filename);
            } else {
                $array['error'] = 'Arquivo não suportado.';
                return $array;
            }
        } else {
            $array['error'] = 'Arquivo não enviado.';
            return $array;
        }


        return $array;
    }

    public function updateCover(Request $request)
    {
        $array = ['error' => ''];

        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('cover');

        if ($image) {

            if (in_array($image->getClientMimeType(), $allowedTypes)) {

                $filename = md5(time() . rand(0, 9999)) . '.jpg';

                $destPatch = public_path('/media/covers');

                $img = Image::make($image)
                    ->fit(850, 310)
                    ->save($destPatch . '/' . $filename);

                $user = User::find($this->loggedUser['id']);
                $user->cover = $filename;
                $user->save();

                $array['url'] = url('/media/covers/' . $filename);
            } else {
                $array['error'] = 'Arquivo não suportado.';
                return $array;
            }
        } else {
            $array['error'] = 'Arquivo não enviado.';
            return $array;
        }


        return $array;
    }
}
