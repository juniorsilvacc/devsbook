<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Post;
use App\Models\UserRelation;
use App\Models\User;
use App\Models\PostLike;
use App\Models\PostComment;

use Intervention\Image\Facades\Image;

class FeedController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');

        $this->loggedUser = auth()->user();
    }

    public function create(Request $request)
    {

        $array = ['error' => ''];

        $type = $request->input('type');
        $body = $request->input('body');
        $photo = $request->file('photo');

        if ($type) {

            switch ($type) {
                case 'text':
                    if (!$body) {
                        $array['error'] = 'Texto não enviado.';
                        return $array;
                    }
                    break;
                case 'photo':

                    if ($photo) {

                        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

                        if (in_array($photo->getClientMimeType(), $allowedTypes)) {

                            $filename = md5(time() . rand(0, 9999)) . '.jpg';

                            $destPatch = public_path('/media/uploads');

                            $img = Image::make($photo)
                                ->resize(800, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                })
                                ->save($destPatch . '/' . $filename);

                            $body = $filename;
                        } else {
                            $array['error'] = 'Arquivo não suportado.';
                            return $array;
                        }
                    } else {
                        $array['error'] = 'Arquivo não enviado.';
                        return $array;
                    }

                    break;
                default:
                    $array['error'] = 'Tipo de postagem inexistente.';
                    return $array;
                    break;
            }

            if ($body) {
                $newPost = new Post();
                $newPost->id_user = $this->loggedUser['id'];
                $newPost->type = $type;
                $newPost->created_at = date('Y-m-d H:i:s');
                $newPost->body = $body;
                $newPost->save();
            }
        } else {
            $array['error'] = 'Dados não enviados.';
            return $array;
        }

        return $array;
    }

    public function read(Request $request)
    {
        $array = ['error' => ''];

        $page = intval($request->input('page'));
        $perPage = 2;

        //1. Pegar a lista de usuário que EU sigo (incluindo eu mesmo)
        $users = [];
        $userList = UserRelation::where('user_from', $this->loggedUser['id'])->get();
        foreach ($userList as $userItem) {
            $users[] = $userItem['user_to'];
        }

        $users[] = $this->loggedUser['id'];

        //2. Pegar os postos das pessoas ORDENADO PELA DATA
        $postList = Post::whereIn('id_user', $users)
            ->orderBy('created_at', 'desc')
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

        $total = Post::whereIn('id_user', $users)->count();
        $pageCount = ceil($total / $perPage);



        //3. Preencher as informações adicionais
        $posts = $this->_postListToObject($postList, $this->loggedUser['id']);

        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }

    private function _postListToObject($postList, $loggedId)
    {

        //Verificar se o post é meu
        foreach ($postList as $postKey => $postItem) {

            if ($postItem['id_user'] == $loggedId) {
                $postList[$postKey]['mine'] = true;
            } else {
                $postList[$postKey]['mine'] = false;
            }

            //Preencher informações de usuário
            $userInfo = User::find($postItem['id_user']);
            $userInfo['avatar'] = url('media/avatars/' . $userInfo['avatar']);
            $userInfo['cover'] = url('media/covers/' . $userInfo['cover']);
            $postList[$postKey]['user'] = $userInfo;

            //Preencher informações de LIKE
            $likes = PostLike::where('id_post', $postItem['id'])->count();
            $postList[$postKey]['likeCount'] = $likes;

            $isLiked = PostLike::where('id_post', $postItem['id'])
                ->where('id_user', $loggedId)
                ->count();

            $postList[$postKey]['liked'] = ($isLiked > 0) ? true : false;

            //Preencher informações de COMMENTS
            $comments = PostComment::where('id_post', $postItem['id'])->get();
            foreach ($comments as $commentKey => $comment) {
                $user = User::find($comment['id_user']);
                $userInfo['avatar'] = url('media/avatars/' . $user['avatar']);
                $userInfo['cover'] = url('media/covers/' . $user['cover']);
                $comments[$commentKey]['user'] = $user;
            }
            $postList[$postKey]['comments'] = $comments;
        }

        return $postList;
    }
}
