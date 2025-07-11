<?php

declare(strict_types=1);

namespace Videoslots\Mts;

use GuzzleHttp\MessageFormatterInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Message;

final class MessageFormatter implements MessageFormatterInterface
{
    private string $short;
    private string $long;
    private array $codes;

    public function __construct(
        string $short,
        string $long,
        int ...$codes
    ) {
        $this->short = $short;
        $this->long = $long;
        $this->codes = $codes;
    }

    public function format(RequestInterface $request, ResponseInterface $response = null, \Throwable $error = null): string
    {
        $template = $this->short;

        if (in_array($response->getStatusCode(), $this->codes)) {
            $template = $this->long;
        }

        //@see https://github.com/guzzle/guzzle/blob/2779e868a00289e1b1fd854af030c43a06f4bcb4/src/MessageFormatter.php#L76
        return \preg_replace_callback(
            '/{\s*([A-Za-z_\-\.0-9]+)\s*}/',
            function (array $matches) use ($request, $response, $error, &$cache) {
                if (isset($cache[$matches[1]])) {
                    return $cache[$matches[1]];
                }

                $result = '';
                switch ($matches[1]) {
                    case 'request':
                        $result = Message::toString($request);
                        break;
                    case 'response':
                        $result = $response ? Message::toString($response) : '';
                        break;
                    case 'req_headers':
                        $result = \trim($request->getMethod()
                                . ' ' . $request->getRequestTarget())
                            . ' HTTP/' . $request->getProtocolVersion() . "\r\n"
                            . $this->headers($request);
                        break;
                    case 'res_headers':
                        $result = $response ?
                            \sprintf(
                                'HTTP/%s %d %s',
                                $response->getProtocolVersion(),
                                $response->getStatusCode(),
                                $response->getReasonPhrase()
                            ) . "\r\n" . $this->headers($response)
                            : 'NULL';
                        break;
                    case 'req_body':
                        $result = $request->getBody()->__toString();
                        break;
                    case 'res_body':
                        if (!$response instanceof ResponseInterface) {
                            $result = 'NULL';
                            break;
                        }

                        $body = $response->getBody();

                        if (!$body->isSeekable()) {
                            $result = 'RESPONSE_NOT_LOGGEABLE';
                            break;
                        }

                        $result = $response->getBody()->__toString();
                        break;
                    case 'ts':
                    case 'date_iso_8601':
                        $result = \gmdate('c');
                        break;
                    case 'date_common_log':
                        $result = \date('d/M/Y:H:i:s O');
                        break;
                    case 'method':
                        $result = $request->getMethod();
                        break;
                    case 'req_version':
                    case 'version':
                        $result = $request->getProtocolVersion();
                        break;
                    case 'uri':
                    case 'url':
                        $result = $request->getUri()->__toString();
                        break;
                    case 'target':
                        $result = $request->getRequestTarget();
                        break;
                    case 'res_version':
                        $result = $response
                            ? $response->getProtocolVersion()
                            : 'NULL';
                        break;
                    case 'host':
                        $result = $request->getHeaderLine('Host');
                        break;
                    case 'hostname':
                        $result = \gethostname();
                        break;
                    case 'code':
                        $result = $response ? $response->getStatusCode() : 'NULL';
                        break;
                    case 'phrase':
                        $result = $response ? $response->getReasonPhrase() : 'NULL';
                        break;
                    case 'error':
                        $result = $error ? $error->getMessage() : 'NULL';
                        break;
                    default:
                        // handle prefixed dynamic headers
                        if (\strpos($matches[1], 'req_header_') === 0) {
                            $result = $request->getHeaderLine(\substr($matches[1], 11));
                        } elseif (\strpos($matches[1], 'res_header_') === 0) {
                            $result = $response
                                ? $response->getHeaderLine(\substr($matches[1], 11))
                                : 'NULL';
                        }
                }

                $cache[$matches[1]] = $result;

                return $result;
            },
            $template
        );
    }

    private function headers(MessageInterface $message): string
    {
        $result = '';
        foreach ($message->getHeaders() as $name => $values) {
            $result .= $name . ': ' . \implode(', ', $values) . "\r\n";
        }

        return \trim($result);
    }
}