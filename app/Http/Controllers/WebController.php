<?php

namespace App\Http\Controllers;

use App\Exceptions\ResourceNotIndexedException;
use App\Helpers\Diff;
use App\Helpers\EpfHelpers;
use App\Models\WebObjectRedirect;
use App\Models\WebObjectVersion;
use App\Repositories\WebRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as LaravelController;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public static function parseTimestamp(string $timestamp): \DateTime {
        return \DateTime::createFromFormat('YmdHis', $timestamp);
    }

    public static function stringifyTimestamp(\DateTime$timestamp): string {
        return $timestamp->format('YmdHis');
    }

    public function view($requestedTimestampString, $url)
    {
        $url = $this->prepareUrl($url);

        $requestedTimestamp = self::parseTimestamp($requestedTimestampString);
        $object = $this->repo->get($url, $requestedTimestamp);

        if ($maybe_redirect = self::handleRedirect($object, $requestedTimestampString)) {
            return $maybe_redirect;
        }

        $actualTimestamp = $requestedTimestamp;
        if ($requestedTimestamp != $object->getTimestamp()) {
            // we have another version at different moment that was requested
            // let's redirect for clarity

            $actualTimestamp = $object->getTimestamp();
            $actualTimestampString = self::stringifyTimestamp($actualTimestamp);
            \Log::debug("Redirecting $url from requested $requestedTimestampString to $actualTimestampString");

            // TODO we should inform user about changing the requested timestamp to the closest one we have
            return redirect(route('view', ['url' => $url, 'timestamp' => $actualTimestampString]));
        }

        // TODO actual revision timestamp can be different than requested; visualize it
        return view('web/view', [
            'object' => $object,
            'actualTimestamp' => $actualTimestamp,
            'get_url' => route('get', [
                'url' => $object->getWebUrl(),
                'timestamp' => self::stringifyTimestamp($actualTimestamp)]) // TODO check
            ]
        );
    }

    public function calendar($url) {
        $url = $this->prepareUrl($url);

        $revisions = $this->repo->getUrlRevisions($url);

        return view('calendar', [
            'revisions' => $revisions
        ]);
    }

    /**
     * Detects streams and treat them accordingly, otherwise streams are converted to memory
     *
     * @param $version
     * @return StreamedResponse
     */
    protected function maybeStreamResponse(WebObjectVersion $version, $contentType = null) {
        $content = $version->getBody();

        $headers = [];
        if ($version->getMediaType()) {
            $headers['Content-Type'] = $version->getMediaType();
        }

        if (! $content instanceof StreamInterface) {
            return response($content)->withHeaders($headers);
        }
        /**
         * @var $content StreamInterface
         */

        return response()->stream(function() use($content)
        {
            $out = fopen('php://output', 'w');
            while (!$content->eof()) fwrite($out, $content->read(8192));
            fclose($out);
        }, 200, $headers);
    }

    private static function handleRedirect($object, $requestedTimestampString) {
        if ($object instanceof WebObjectRedirect) {
            $redirection_url = $object->getRedirectLocation();

            if ($object->isRedirectionArchived()) {
                return redirect(route('view', [
                    'url' => $redirection_url,
                    'timestamp' => $requestedTimestampString]));
            } else {
                // external redirect
                return redirect($redirection_url);
            }
        }
        return null;
    }

    public function get($timestamp, $url)
    {
        $url = $this->prepareUrl($url);
        try {
            $timestamp = \DateTime::createFromFormat('YmdHis', $timestamp);

            $object = $this->repo->get($url, $timestamp, true);

            if ($maybe_redirect = self::handleRedirect($object, $timestamp)) {
                return $maybe_redirect;
            }

            return $this->maybeStreamResponse($object->getVersion());

        } catch (ResourceNotIndexedException $ex) {
            if (EpfHelpers::array_any(['.html', '.htm', '/'], function ($ends_with) use ($url) {
                return substr($url, -strlen($ends_with)) === $ends_with;
            })) {
                // may be page
                // TODO https://github.com/epforgpl/gov_backup/issues/30
                // show "Resource hasn't been scraped or resource not known" - see original link
                throw $ex;
            } else {
                // redirect temporary (till this resource will be scraped)
                // TODO Shouldn't assume http, but use original scheme
                return redirect()->away('http://' . $url, 302, ['X-GovBackup' => 'NotScraped-RedirectingToOriginal']);
            }
        }
    }

    public function diff($fromTimestampString, $toTimestampString, $type, $url)
    {
        $url = $this->prepareUrl($url);
        $fromTimestamp = \DateTime::createFromFormat('YmdHis', $fromTimestampString);
        $toTimestamp = \DateTime::createFromFormat('YmdHis', $toTimestampString);

        $fromObject = $this->repo->get($url, $fromTimestamp, 'non-transformed');
        $toObject = $this->repo->get($url, $toTimestamp, 'non-transformed');
        // TODO actual timestamps may be different, do we throw an Exception or inform user and redirect?

        if ($fromObject instanceof WebObjectRedirect or $toObject instanceof WebObjectRedirect) {
            throw new \Exception("Diff can't be computed on redirects");
        }

        if ($fromObject->getVersion()->getId() == $toObject->getVersion()->getId()) {
            throw new \Exception("Versions are identical");
        }

        if (!Diff::diffable($mediaType = $fromObject->getVersion()->getMediaType())) {
            throw new \Exception($fromObject->getVersion()->getMediaType() . " media type is not diffable.");
        }
        if (!Diff::diffable($toObject->getVersion()->getMediaType())) {
            throw new \Exception($fromObject->getVersion()->getMediaType() . " media type is not diffable.");
        }

        $from = $fromObject->getVersion()->getBody();
        $to = $toObject->getVersion()->getBody();

        if (sha1($from) === sha1($to)) {
            throw new \Exception("Versions are identical");
        }

        if ($type == 'html-formatted' && $mediaType == 'text/html') {
            $indenter = new \Gajus\Dindent\Indenter();
            $from = $indenter->indent($from);
            $to = $indenter->indent($to);
        }

        $html = Diff::renderChangesToHtml($from, $to);

        return view('diff', [
                'formatted_html' => $html
        ]);
    }

    public function thumb($id)
    {
        $object = $this->repo->getById($id, true);

        return $object->getVersion()->getBody();
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
