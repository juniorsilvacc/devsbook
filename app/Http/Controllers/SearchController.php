<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SearchController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');

        $this->loggedUser = auth()->user();
    }

    public function search(Request $request)
    {
        $array = ['error' => '', 'user' => []];

        $txt = $request->input('txt');

        if ($txt) {

            //Busca de usuários
            $userList = User::where('name', 'like', '%' . $txt . '%')->get();
            foreach ($userList as $userItem) {
                $array['users'][] = [
                    'id' => $userItem['id'],
                    'name' => $userItem['name'],
                    'avatar' => $userItem['avatar']
                ];
            }

            //Buscar de posts

        } else {
            $array['error'] = 'Digite alguma informação.';
            return $array;
        }

        return $array;
    }
}
