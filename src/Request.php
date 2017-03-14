<?php
/**
 * Request.php
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

/**
 * Class Request
 * @package Swoole
 */
class Request
{
    public $server = [];
    public $header = [];
    public $meta = [];
    public $get = [];
    public $post = [];
    public $files = [];
    public $cookie = [];
    public $request;
    public $body;

    /**
     * TCP客户端连接的文件描述符
     *
     * @var int
     */
    public $fd;

    public $remoteIp;
    public $remotePort;
    public $time;

    /**
     * @param string $method
     * @return bool
     */
    public function isMethod($method)
    {
        $method = strtoupper($method);
        return strtoupper($this->meta['method']) === $method;
    }

    /**
     * 判断是否WebSocket请求
     *
     * @return bool
     */
    public function isWebSocket()
    {
        return isset($this->header['Upgrade']) && strtolower($this->header['Upgrade']) === 'websocket';
    }

    /**
     * 将原始请求信息转换到PHP超全局变量中
     */
    public function setGlobal()
    {
        foreach ($this->header as $k => $v) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $k));
            $this->server[$_key] = $v;
        }

        $_GET = $this->get;
        $_POST = $this->post;
        $_FILES = $this->files;
        $_COOKIE = $this->cookie;
        $_SERVER = $this->server;

        $this->request = $_REQUEST = array_merge($this->get, $this->post, $this->cookie);
    }

    /**
     * 清理PHP超全局变量
     */
    public function unsetGlobal()
    {
        $_REQUEST = $_SESSION = $_COOKIE = $_FILES = $_POST = $_SERVER = $_GET = [];
    }
}