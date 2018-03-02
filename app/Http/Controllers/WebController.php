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

    public function home(Request $request)
    {
        $textQuery = $request->query('search');
        $urlQuery = $request->query('url');

        $textResults = null;
        $urlResults = null;
        if ($textQuery) {
            $textResults = $this->repo->searchText($textQuery);
        }
        else if ($urlQuery) {
            $urlResults = $this->repo->searchUrl($urlQuery);
        }

        return view('home', [
            'textResults' => $textResults,
            'urlResults' => $urlResults
        ]);
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
        if ($object) {
            if ($object->hasCurrentVersion()) {
                return $object->getCurrentVersion()->getBody();
            } else {
                throw new \Exception("Couldn't load data?!"); // TODO
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

    /**
     * Search for given text
     *
     * @return \Illuminate\Http\Response
     */
    public function searchText($query, $filters = [])
    {
        return $this->repo->searchText($query, $filters);
    }

    /**
     * Search for given URL
     *
     * @return \Illuminate\Http\Response
     */
    public function searchUrl($query, $filters = [])
    {
        return $this->repo->searchUrl($query, $filters);
    }

    private function prepareUrl($url)
    {
        if( $query = $this->request->getQueryString() ) {
            $url .= '?' . $query;
        }
        return $url;
    }
}
