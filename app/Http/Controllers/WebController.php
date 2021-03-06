<?php

namespace App\Http\Controllers;

use App\Exceptions\ResourceNotIndexedException;
use App\Helpers\Diff;
use App\Helpers\EpfHelpers;
use App\Helpers\Reply;
use App\Models\WebObjectRedirect;
use App\Repositories\DataRepository;
use App\Repositories\WebRepository;
use Illuminate\Support\Facades\Cache;
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
    protected $data;
    protected $request;

    public function __construct(WebRepository $repo, DataRepository $data, Request $request)
    {
        $this->repo = $repo;
        $this->data = $data;
        $this->request = $request;
    }

    public function home(Request $request)
    {
        $textQuery = $request->query('search');
        $inDeleted = (bool) $request->query('in_deleted', false);

        $textResults = [];
        if ($textQuery) {
            $textResults = $this->repo->searchText($textQuery, $inDeleted);

            foreach ($textResults as &$r) {
                if ($inDeleted) {
                    $r['link'] = EpfHelpers::route_slashed('diff', [
                        'url' => $r['url'],
                        'type' => 'html',
                        'timestamp_from' => $r['matching_last_seen']->format('YmdHis'),
                        'timestamp_to' => $r['not_matching_first_seen']->format('YmdHis')
                    ]);

                } else {
                    $r['link'] = EpfHelpers::route_slashed('view', [
                        'url' => $r['url'],
                        # Linking to when this version was seen last time
                        'timestamp' => $r['last_seen']->format('YmdHis')]);
                }
            }
        }

        return view('home', [
            'textResults' => $textResults,
            'textQuery' => $textQuery,
            'inDeleted' => $inDeleted
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
        // TODO handle resource is not scraped, implement https://github.com/epforgpl/gov_backup/issues/30

        if ($maybe_redirect = self::handleRedirect($object, $requestedTimestampString)) {
            return $maybe_redirect;
        }

        // Make sure it's original link; this should not be the issue, but better be safe
        if ($object->getWebUrl() != $url) {
            app('log')->warning("Matched and returned URL differ: $url <> " . $object->getWebUrl());
            $url = $object->getWebUrl();
        }

        $actualTimestamp = $requestedTimestamp;
        if ($requestedTimestamp != $object->getTimestamp()) {
            // we have another version at different moment that was requested
            // let's redirect for clarity

            $actualTimestamp = $object->getTimestamp();
            $actualTimestampString = self::stringifyTimestamp($actualTimestamp);
            \Log::debug("Redirecting $url from requested $requestedTimestampString to $actualTimestampString");

            // TODO we should inform user about changing the requested timestamp to the closest one we have
            return redirect(EpfHelpers::route_slashed('view', ['url' => $url, 'timestamp' => $actualTimestampString]));
        }

        if ($this->request->secure() && starts_with($url, 'http:')) {
            // Original content was not secure so we have to serve non-secure html
            //    to make sure it will load all external content
            return redirect()->to($this->request->getRequestUri(), 302, [], false);
        }

        // TODO actual revision timestamp can be different than requested; visualize it
        return view('web/view', [
            'object' => $object,
            'actualTimestamp' => $actualTimestamp,
            'get_url' => EpfHelpers::route_slashed('get', [
                'url' => $url,
                'timestamp' => self::stringifyTimestamp($actualTimestamp)]) // TODO check
            ]
        );
    }

    public function calendar($url) {
        $url = $this->prepareUrl($url);

        $revisions = $this->repo->getUrlRevisions($url);

        return view('calendar', [
            'revisions' => $revisions,
            'url' => $url
        ]);
    }

    /**
     * Detects streams and treat them accordingly, otherwise streams are converted to memory
     *
     * @param $content String or stream
     * @param $mediaType Media Type to set in a header
     * @return StreamedResponse
     */
    protected function maybeStreamResponse($content, $mediaType) {
        $headers = [];
        if ($mediaType) {
            $headers['Content-Type'] = $mediaType;
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
                return redirect(EpfHelpers::route_slashed('view', [
                    'url' => $redirection_url,
                    'timestamp' => $requestedTimestampString]));
            } else {
                // external redirect
                return redirect($redirection_url);
            }
        }
        return null;
    }

    public function get($timestamp_string, $url)
    {
        $url = $this->prepareUrl($url);
        try {
            $timestamp = \DateTime::createFromFormat('YmdHis', $timestamp_string);

            $object = $this->repo->get($url, $timestamp);
            $version = $object->getVersion();

            // redirect if originally it was a redirect
            if ($maybe_redirect = self::handleRedirect($object, $timestamp)) {
                return $maybe_redirect;
            }

            // redirect to save content from the cloud; no reason to be an intermediary if we are not changing content
            if ($version->getMediaType() != 'text/html' and $version->getMediaType() != 'text/css') {

                if ($publicUrl = $this->repo->getPublicUrl($version, 'basic')) {
                    return redirect($publicUrl);
                }
            }

            // Make sure it's original link; this should not be the issue, but better be safe
            if ($object->getWebUrl() != $url) {
                app('log')->warning("Matched and returned URL differ: $url <> " . $object->getWebUrl());
                $url = $object->getWebUrl();
            }

            $content = $this->repo->loadVersionContent($object->getVersion(), 'basic');

            // reply content
            $domains = collect([]);
            $rewrite = function(array $parsed_url, string $type) use($url, $timestamp_string, &$domains) {
                $archivedDomains = $this->repo->getArchivedDomains();

                // don't rewrite links to domains outside of our scope
                if (!in_array($parsed_url['host'], $archivedDomains)) {
                    return null;
                }

                $route = $type == 'get' ? 'get' : 'view';

                return EpfHelpers::route_slashed($route, [
                    'timestamp' => $timestamp_string,
                    'url' => Reply::unparse_url($parsed_url)],
                    true);
            };

            $version = $object->getVersion();
            if ($version->getMediaType() == 'text/html') {
                $content = Reply::replyHtml($content, $url, $rewrite);

            } else if ($version->getMediaType() == 'text/css') {
                $content = Reply::replyCss($content, $url, $rewrite);
            }
            // reply content end

            return $this->maybeStreamResponse($content, $version->getMediaType());

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
                return redirect()->away($url, 302, ['X-GovBackup' => 'NotScraped-RedirectingToOriginal']);
            }
        }
    }

    public function diff($fromTimestampString, $toTimestampString, $type, $url)
    {
        $url = $this->prepareUrl($url);
        $fromTimestamp = \DateTime::createFromFormat('YmdHis', $fromTimestampString);
        $toTimestamp = \DateTime::createFromFormat('YmdHis', $toTimestampString);

        if ($toTimestamp < $fromTimestamp) {
            // TODO are you user sure you want to diff it other way around?
            // TODO shouldn't we redirect to "right" order or at least switch timestamps?
        }

        $contentView = 'basic';
        if ($type == 'text') {
            $contentView = 'text';
        }

        $fromObject = $this->repo->get($url, $fromTimestamp);
        $toObject = $this->repo->get($url, $toTimestamp);

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
        // TODO handle above exceptions on the frontend as well as ContentViewNotFound

        // load body content
        $from = $this->repo->loadVersionContent($fromObject->getVersion(), $contentView);
        $to = $this->repo->loadVersionContent($toObject->getVersion(), $contentView);

        if (sha1($from) === sha1($to)) {
            throw new \Exception("Versions are identical");
        }

        if ($type == 'html-formatted' && $mediaType == 'text/html') {
            $indenter = new \Gajus\Dindent\Indenter();
            $from = $indenter->indent($from);
            $to = $indenter->indent($to);
        }

        $diff = Diff::renderChangesToHtml($from, $to);

        return view('diff', [
            'diff' => $diff,
            'diffType' => $type,
            'fromObject' => $fromObject,
            'toObject' => $toObject
        ]);
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

    public function deletedPages() {
        $pages = Cache::get('deletedPages');

        if ($pages == null) {
            $pages = $this->data->deletedPages();

            $pages = array_map(function ($r) {
                $r['last_seen_link'] = EpfHelpers::route_slashed('view', [
                    'url' => $r['url'],
                    'timestamp' => $r['ok_cts']->format('YmdHis')
                ]);
                return $r;
            }, $pages);

            Cache::set('deletedPages', $pages, \DateInterval::createFromDateString("15 minutes"));
        }

        return view('deletedPages', [
            'pages' => $pages
        ]);
    }

    private function prepareUrl($url)
    {
        if (ends_with($this->request->getPathInfo(), '/')) {
            // Laravel is trailing slashes so we need to re-add it
            $url .= '/';
        }

        if( $query = $this->request->getQueryString() ) {
            $url .= '?' . $query;
        }
        return $url;
    }
}
