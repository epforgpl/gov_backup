<?php

namespace App\Http\Controllers;

use App\Repositories\WebRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as LaravelController;

class WebController extends LaravelController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $repo;
    protected $request;

    public function __construct(WebRepository $repo, Request $request)
    {
        $this->repo = $repo;
        $this->request = $request;
    }

    public function home()
    {
        return view('home');
    }

    public function view($url)
    {
        $url = $this->prepareUrl($url);
        $object = $this->repo->get($url);
        return view('web/view', [
            'object' => $object,
        ]);
    }

    public function get($url)
    {
        $url = $this->prepareUrl($url);
        $object = $this->repo->get($url, [
            'loadCurrentVersion' => true,
        ]);
        if( $object ) {
            if ($object->hasCurrentVersion()) {
                return $object->getCurrentVersion()->getBody();
            } else {
                dd('no version');
            }
        }

        abort(404);
    }

    public function thumb($id)
    {
        $object = $this->repo->getById($id, [
            'loadCurrentVersion' => true,
        ]);
        if( $object ) {
            if ($object->hasCurrentVersion()) {
                return $object->getCurrentVersion()->getBody();
            } else {
                dd('no version');
            }
        }
    }

    private function prepareUrl($url)
    {
        if( $query = $this->request->getQueryString() ) {
            $url .= '?' . $query;
        }
        return $url;
    }
}
