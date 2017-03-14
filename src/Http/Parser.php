<?php
/**
 * Parser.php
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
namespace Swoole\Http;

use Swoole\Request;

abstract class Parser
{
    const LINE_EOF = "\r\n";
    const HTTP_EOF = "\r\n\r\n";

    /**
     * 解析请求头部信息
     *
     * @param string $data
     * @return array|bool
     */
    static public function parseHeader($data)
    {
        // sample
        // PUT / HTTP/1.1\r\nHost: 127.0.0.1:9080\r\nUser-Agent: curl/7.51.0\r\nContent-Type:application/json\r\nAccept:application/json\r\nContent-Length: 34\r\n\r\n{\"boolean\" : false, \"foo\" : \"bar\"}
        $header = [[]];
        $meta = &$header[0];

        // parts[0] = HTTP头;
        // parts[1] = HTTP主体，GET请求没有body
        $parts = explode(self::HTTP_EOF, $data, 2);
        $headerLines = explode(self::LINE_EOF, $parts[0]);

        // HTTP协议头RFC-2616 5.1,方法 路径 协议
        list($meta['method'], $meta['uri'], $meta['protocol']) = explode(' ', $headerLines[0], 3);

        if (empty($meta['method']) or empty($meta['uri']) or empty($meta['protocol']))
            return false;

        unset($headerLines[0]);

        $header = array_merge($header, self::parseHeaderLine($headerLines));
        return $header;
    }

    /**
     * @param string|array $headerLines
     * @return array
     */
    static public function parseHeaderLine($headerLines)
    {
        if (is_string($headerLines))
            $headerLines = explode(self::LINE_EOF, $headerLines);

        $header = [];
        foreach ($headerLines as $v) {
            $v = trim($v);
            if (empty($v)) continue;
            $data = explode(':', $v, 2);

            // 头字段首字母大写,比如 user-agent => User-Agent
            $key = explode('-', $data[0]);
            $key = array_map('ucfirst', $key);
            $key = implode('-', $key);

            $value = isset($data[1]) ? $data[1] : '';
            $header[trim($key)] = trim($value);
        }
        return $header;
    }

    static public function parseBody(Request $request)
    {
        $content = strstr($request->header['Content-Type'], 'boundary');
    }

    /**
     * 解析Cookies
     *
     * @param Request $request
     */
    static public function parseCookie(Request $request)
    {
        $request->cookie = self::parseParams($request->header['Cookie']);
    }

    /**
     * @param string $str
     * @return array
     */
    static public function parseParams($str)
    {
        $params = [];
        $blocks = explode(';', $str);
        foreach ($blocks as $block) {
            $kv = explode('=', $block, 2);
            if (count($kv) === 2) {
                list($key, $value) = $kv;
                $params[trim($key)] = trim($value, "\r\n \t\"");
            } else {
                $params[trim($kv[0])] = '';
            }
        }
        return $params;
    }
}