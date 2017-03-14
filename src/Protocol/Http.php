<?php
/**
 * Http.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    jack <linjue@wilead.com>
 * @copyright 2007-2017/3/13 WIZ TECHNOLOGY
 * @link      https://wizmacau.com
 * @link      https://lamjack.github.io
 * @link      https://github.com/lamjack
 * @version
 */

namespace Swoole\Protocol;

use Swoole\Http\Parser;
use Swoole\Kernel;
use Swoole\Request;
use Swoole\Response;

/**
 * Class Http
 * @package Swoole\Protocol
 */
abstract class Http extends Web
{
    const DATE_FORMAT_HTTP = 'D, d-M-Y H:i:s T';

    const HTTP_EOF = "\r\n\r\n";
    const HTTP_HEAD_MAXLEN = 8192; //http头最大长度不得超过2k

    const ST_FINISH = 1; //完成，进入处理流程
    const ST_WAIT = 2; //等待数据
    const ST_ERROR = 3; //错误，丢弃此包

    protected $bufferHeader = [];

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    public function onReceive($server, $clientId, $fromId, $data)
    {
        $this->debug('HTTP[收到请求报文]', ['clientId' => $clientId, 'data' => $data]);

        $ret = $this->checkHttpMessage($clientId, $data);
        switch ($ret) {
            case self::ST_ERROR:
                $this->server->close($clientId);
                return;
            case self::ST_WAIT:
                return;
            default:
                break;
        }

        // 请求结束，开始处理
        /** @var Request $request */
        $request = $this->requests[$clientId];
        $request->fd = $clientId;

        // Socket连接信息
        $info = $server->connection_info($clientId);
        $request->server['SWOOLE_CONNECTION_INFO'] = $info;
        $request->remoteIp = $info['remote_ip'];
        $request->remotePort = $info['remote_port'];

        // Server变量
        $request->server = array_merge($request->server, [
            'REQUEST_URI' => $request->meta['uri'],
            'REMOTE_ADDR' => $request->remoteIp,
            'REMOTE_PORT' => $request->remotePort,
            'REQUEST_METHOD' => $request->meta['method'],
            'REQUEST_TIME' => $request->time,
            'SERVER_PROTOCOL' => $request->meta['protocol']
        ]);
        $request->setGlobal();
        if (!empty($request->meta['query'])) {
            $_SERVER['QUERY_STRING'] = $request->meta['query'];
        }
        $this->parseRequest($request);

        // 产生Response
        $response = $this->handleRequest($request);
        if ($response and $response instanceof Response) {
            // 发送Response
            $this->response($request, $response);
        }
    }

    /**
     * 检查HTTP请求报文
     *
     * @param $clientId
     * @param $httpData
     * @return int
     */
    protected function checkHttpMessage($clientId, $httpData)
    {
        // 长连接用的,普通HTTP收到Response就断开
        if (isset($this->bufferHeader[$clientId])) {
            $httpData = $this->bufferHeader[$clientId] . $httpData;
        }

        // 创建Request
        $request = $this->createRequest($clientId, $httpData);

        // 错误的报文
        if (false === $request) {
            $this->bufferHeader[$clientId] = $httpData;

            if (strlen($httpData) > self::HTTP_HEAD_MAXLEN) {
                $this->error('HTTP报文长度超过限定');
                return self::ST_ERROR;
            } else {
                return self::ST_WAIT;
            }
        }

        if ($request->isMethod('post')) {

        } else {
            return self::ST_FINISH;
        }
    }

    /**
     * 检查HTTP报文首部,检查通过返回Request对象
     *
     * @param int $clientId
     * @param string $httpData
     * @return bool|Request
     */
    protected function createRequest($clientId, $httpData)
    {
        if (!isset($this->requests[$clientId])) {
            // 检查数据是否发送完毕
            if (strpos($httpData, self::HTTP_EOF) === false) {
                return false;
            } else {
                $this->bufferHeader[$clientId] = '';
                $request = new Request();
                // 这里的body没有内容,GET请求没有body
                list($header, $request->body) = explode(self::HTTP_EOF, $httpData, 2);
                $request->header = Parser::parseHeader($httpData);
                // HTTP协议头的数据放到request的meta中
                $request->meta = $request->header[0];
                unset($request->header[0]);
                // 保存请求
                $this->requests[$clientId] = $request;

                // 无法解析请求
                if (count($request->header) === 0)
                    return false;
            }
        } else { // POST请求分报文首和报文主体分两次请求的情况
            $request = $this->requests[$clientId];
            $request->body .= $httpData;
        }
        return $request;
    }

    /**
     * 检查POST数据
     *
     * @param Request $request
     * @return int
     */
    protected function checkPostData(Request $request)
    {
        if (isset($request->header['Content-Length'])) {
            $contentLength = intval($request->header['Content-Length']);
            $bodyLength = strlen($request->body);

            // 检查是否超过服务器限定长度

            // POST未传输完毕，等待数据
            if ($contentLength > $bodyLength) {
                return self::ST_WAIT;
            } else {
                return self::ST_FINISH;
            }
        }
        $this->error('HTTP报文无Content-Length字段，丢弃此请求。');
        return self::ST_ERROR;
    }

    /**
     * 解析请求
     *
     * @param Request $request
     */
    protected function parseRequest(Request $request)
    {
        $urlInfo = parse_url($request->meta['uri']);
        $request->time = time();
        $request->meta['path'] = $urlInfo['path'];

        if (isset($urlInfo['fragment'])) {
            $request->meta['fragment'] = $urlInfo['fragment'];
        }

        if (isset($urlInfo['query'])) {
            parse_str($urlInfo['query'], $request->get);
        }

        // 解析POST请求的报文主体
        if ($request->isMethod('post')) {
            Parser::parseBody($request);
        }

        // 解析Cookies
        if (!empty($request->header['Cookie'])) {
            Parser::parseCookie($request);
        }
    }

    /**
     * 处理请求
     *
     * @param Request $request
     * @return Response
     */
    protected function handleRequest(Request $request)
    {
        $response = new Response();
        Kernel::getInstance()->request = $request;
        Kernel::getInstance()->response = $response;

        return $response;
    }

    /**
     * 发送响应
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    protected function response(Request $request, Response $response)
    {
        $this->debug('Http[发送响应]');

        if (!isset($response->header['Date'])) {
            $response->header['Date'] = gmdate(self::DATE_FORMAT_HTTP);
        }

        if (!isset($response->header['Connection'])) {
            // 持久连接
            if ($this->keepalive && isset($request->header['Connection']) && strtolower($request->header['Connection']) == 'keep-alive') {
                $response->header['KeepAlive'] = 'on';
                $response->header['Connection'] = 'keep-alive';
            } else {
                $response->header['KeepAlive'] = 'off';
                $response->header['Connection'] = 'close';
            }
        }

        $out = $response->getHeader() . $response->body;
        $ret = $this->server->send($request->fd, $out);
        $this->postResponse($request, $response);
        return $ret;
    }

    /**
     * 发送响应后处理方法
     *
     * @param Request $request
     * @param Response $response
     */
    protected function postResponse(Request $request, Response $response)
    {
        if (!$this->keepalive || $response->header['Connection'] == 'close') {
            $this->server->close($request->fd);
        }

        $request->unsetGlobal();

        unset($this->requests[$request->fd]);
        unset($request);
        unset($response);
    }

    /**
     * @param int $clientId
     */
    protected function cleanBuffer($clientId)
    {
        unset($this->bufferHeader[$clientId]);
        parent::cleanBuffer($clientId);
    }
}