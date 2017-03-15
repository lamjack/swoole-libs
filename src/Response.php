<?php
/**
 * Response.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    jack <linjue@wilead.com>
 * @copyright 2007-2017/3/14 WIZ TECHNOLOGY
 * @link      https://wizmacau.com
 * @link      https://lamjack.github.io
 * @link      https://github.com/lamjack
 * @version
 */

namespace Swoole;

use Swoole\Protocol;

/**
 * Class Response
 * @package Swoole
 */
class Response
{
    public $httpProtocol = 'HTTP/1.1';
    public $httpStatus = 200;

    public $cookie = [];
    public $header = [];
    public $body = '';

    static $HTTP_HEADERS = [
        100 => "100 Continue",
        101 => "101 Switching Protocols",
        200 => "200 OK",
        201 => "201 Created",
        204 => "204 No Content",
        206 => "206 Partial Content",
        300 => "300 Multiple Choices",
        301 => "301 Moved Permanently",
        302 => "302 Found",
        303 => "303 See Other",
        304 => "304 Not Modified",
        307 => "307 Temporary Redirect",
        400 => "400 Bad Request",
        401 => "401 Unauthorized",
        403 => "403 Forbidden",
        404 => "404 Not Found",
        405 => "405 Method Not Allowed",
        406 => "406 Not Acceptable",
        408 => "408 Request Timeout",
        410 => "410 Gone",
        413 => "413 Request Entity Too Large",
        414 => "414 Request URI Too Long",
        415 => "415 Unsupported Media Type",
        416 => "416 Requested Range Not Satisfiable",
        417 => "417 Expectation Failed",
        500 => "500 Internal Server Error",
        501 => "501 Method Not Implemented",
        503 => "503 Service Unavailable",
        506 => "506 Variant Also Negotiates"
    ];

    /**
     * @param bool $fastcgi
     * @return string
     */
    public function getHeader($fastcgi = false)
    {
        $out = '';

        if ($fastcgi) {

        } else {
            if (isset($this->header[0])) {
                $out .= $this->header[0] . "\r\n";
                unset($this->header[0]);
            } else {
                $out .= "HTTP/1.1 200 OK\r\n";
            }
        }

        if (!isset($this->header['Server'])) {
            $this->header['Server'] = Protocol\Web::SOFTWARE;
        }

        if (!isset($this->header['Content-Type'])) {
            $this->header['Content-Type'] = 'text/html; charset=' . Kernel::$charset;
        }

        if (!isset($this->header['Content-Length'])) {
            $this->header['Content-Length'] = strlen($this->body);
        }

        // Headers
        foreach ($this->header as $k => $v) {
            $out .= $k . ': ' . $v . "\r\n";
        }

        // Cookies
        if (!empty($this->cookie) and is_array($this->cookie)) {
            foreach ($this->cookie as $v) {
                $out .= "Set-Cookie: $v\r\n";
            }
        }

        $out .= "\r\n";
        return $out;
    }

    /**
     * 设置Http状态码
     *
     * @param int $code
     */
    public function setStatusCode($code)
    {
        $this->header[0] = $this->httpProtocol . ' ' . self::$HTTP_HEADERS[$code];
        $this->httpStatus = $code;
    }

    /**
     * @param array $headers
     */
    public function addHeaders($headers = [])
    {
        $this->header = array_merge($this->header, $headers);
    }
}