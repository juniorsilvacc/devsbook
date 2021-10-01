<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserRelation;
use App\Models\Post;
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

    public function read($id = false)
    {
        $array = ['error' => ''];

        if ($id) {

            $info = User::find($id);
            if (!$info) {
                $array['error'] = 'Usuário inexistente.';
                return $array;
            }
        } else {

            $info = $this->loggedUser;
        }

        $info['avatar'] = url('media/avatars/' . $info['avatar']);
        $info['cover'] = url('media/covers/' . $info['cover']);

        if ($info['id'] == $this->loggedUser['id']) {
            $info['me'] = true;
        }

        $dateFrom = new \DateTime($info['birthdate']);
        $dateTo = new \DateTime($info['today']);
        $info['age'] = $dateFrom->diff($dateTo)->y;


        $info['followers'] = UserRelation::where('user_to', $info['id'])
            ->get()->count();

        $info['following'] = UserRelation::where('user_from', $info['id'])
            ->get()->count();

        $info['photoCount'] = Post::where('id_user', $info['id'])
            ->where('type', 'photo')
            ->count();

        $hasRelations = UserRelation::where('user_from', $this->loggedUser['id'])
            ->where('user_to', $info['id'])
            ->count();

        $info['isFollowing'] = ($hasRelations > 0) ? true : false;

        $array['data'] = $info;

        return $array;
    }

    public function follow($id)
    {
        $array = ['error' => ''];

        if ($id == $this->loggedUser['id']) {
            $array['error'] = 'Você não pode seguir você mesmo.';
            return $array;
        }

        $userExists = User::find($id);
        if ($userExists) {

            $relation = UserRelation::where('user_from', $this->loggedUser['id'])
                ->where('user_to', $id)
                ->first();

            if ($relation) {
                //Para de seguir
                $relation->delete();
            } else {
                //Seguir
                $newRelation = new UserRelation();
                $newRelation->user_from = $this->loggedUser['id'];
                $newRelation->user_to = $id;
                $newRelation->save();
            }
        } else {
            $array['error'] = 'Usuário inexistente.';
            return $array;
        }

        return $array;
    }

    public function followers($id)
    {
        $array = ['error' => ''];

        $userExists = User::find($id);
        if ($userExists) {

            $followers = UserRelation::where('user_to', $id)->get();
            $following = UserRelation::where('user_from', $id)->get();

            $array['followers'] = [];
            $array['following'] = [];

            foreach ($followers as $item) {
                $user = User::find($item['user_from']);
                $array['followers'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar' => url('media/avatars/' . $user['avatar'])
                ];
            }

            foreach ($following as $item) {
                $user = User::find($item['user_from']);
                $array['following'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'avatar' => url('media/avatars/' . $user['avatar'])
                ];
            }
        } else {
            $array['error'] = 'Usuário inexistente.';
            return $array;
        }

        return $array;
    }
}
