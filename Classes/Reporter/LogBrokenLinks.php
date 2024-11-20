<?php

namespace Unikka\LinkChecker\Reporter;

/*
 * This file is part of the Unikka LinkChecker package.
 *
 * (c) unikka
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\UriInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Class LogBrokenLinks
 * @package Unikka\LinkChecker\Reporter
 */
class LogBrokenLinks extends BaseReporter
{
    /**
     * Called when the crawl has ended.
     *
     * @return void
     */
    public function finishedCrawling(): void
    {
        $this->outputLine('');
        $this->outputLine('Summary:');
        $this->outputLine('--------');

        collect($this->resultItemsGroupedByStatusCode)
            ->each(function ($urls, $statusCode) {
                $count = \count($urls);
                if ($statusCode < 100) {
                    $this->outputLine("{$count} url(s) did have unresponsive host(s)");
                    return;
                }

                $this->outputLine("Crawled {$count} url(s) with status code {$statusCode}");
            });
    }

    /**
     * Called when the crawler had a problem crawling the given url.
     *
     *
     * @param UriInterface $url
     * @param RequestException $requestException
     * @param UriInterface|null $foundOnUrl
     * @param string|null $linkText
     * @return int
     */
    public function crawlFailed(
        UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null, ?string $linkText = null): void
    {
        parent::crawlFailed($url, $requestException, $foundOnUrl);
        $statusCode = $requestException->getCode();
        if ($this->isExcludedStatusCode($statusCode)) {
            exit();
        }

        $this->outputLine(
            $this->formatLogMessage($url, $requestException, $foundOnUrl)
        );
    }

    /**
     * Format the error message for crawling problems.
     *
     * @param UriInterface $url
     * @param RequestException $requestException
     * @param null|UriInterface $foundOnUrl
     * @return string
     */
    protected function formatLogMessage(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    ): string
    {
        $statusCode = $requestException->getCode();
        $reason = $requestException->getMessage();
        $logMessage = "{$statusCode} {$reason} - {$url}";

        if ($foundOnUrl) {
            $logMessage .= " (found on {$foundOnUrl}";
        }

        return $logMessage;
    }
}
