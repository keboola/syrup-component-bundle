<?php
/**
 * @package syrup-component-bundle
 * @copyright 2015 Keboola
 * @author Jakub Matejka <jakub@keboola.com>
 */

namespace Syrup\ComponentBundle\Monolog\Processor;

use Syrup\ComponentBundle\Aws\S3\Uploader;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestProcessor
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var Uploader
     */
    private $s3Uploader;

    public function __construct(RequestStack $requestStack, Uploader $s3Uploader)
    {
        $this->requestStack = $requestStack;
        $this->s3Uploader = $s3Uploader;
    }

    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        return $this->processRecord($record);
    }

    public function processRecord(array $record)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $record['http'] = [
                'url' => sprintf('[%s] [%s]', $request->getMethod(), $request->getRequestUri())
            ];
            if (count($request->query->all())) {
                $record['http']['get'] = $request->query->all();
            }
            if (count($request->request->all())) {
                $record['http']['post'] = $request->request->all();
            }
            $content = $request->getContent();
            if ($content) {
                if (strlen($content) < 1024) {
                    $record['http']['json'] = json_decode($content, true);
                } else {
                    $record['http']['json'] = $this->s3Uploader->uploadString('json-params', $content);
                }
            }
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $record['http']['ip'] = $_SERVER['REMOTE_ADDR'];
            }
            if (isset($_SERVER['HTTP_X_USER_AGENT'])) {
                $record['http']['userAgent'] = $_SERVER['HTTP_X_USER_AGENT'];
            } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
                $record['http']['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
            }
        }

        if (php_sapi_name() == 'cli') {
            if (!empty($_SERVER['argv'])) {
                $record['cliCommand'] = implode(' ', $_SERVER['argv']);
            }
        }

        return $record;
    }
}
