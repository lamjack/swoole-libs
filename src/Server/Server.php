<?php
/**
 * Server.php
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

namespace Swoole\Server;

use Swoole;
use Swoole\Protocol\AbstractProtocol;

/**
 * Class Server
 * @package Swoole\Server
 */
class Server extends AbstractBaseServer
{
    static $swooleMode;
    static $useSwooleHttpServer = false;
    static $pidFile;

    protected $host;
    protected $port;
    /**
     * @var \swoole_server
     */
    protected $sw;
    protected $runtimeSetting = [];

    /**
     * @param string $host
     * @param int $port
     * @param bool $ssl
     */
    public function __construct($host, $port, $ssl = false)
    {
        $flag = $ssl ? (SWOOLE_SOCK_TCP | SWOOLE_SSL) : SWOOLE_SOCK_TCP;

        // 使用进程模式
        self::$swooleMode = SWOOLE_PROCESS;

        if (self::$useSwooleHttpServer)
            $this->sw = new \swoole_http_server($host, $port, self::$swooleMode, $flag);
        else
            $this->sw = new \swoole_server($host, $port, self::$swooleMode, $flag);

        $this->host = $host;
        $this->port = $port;
        $this->runtimeSetting = [
            'backlog' => 128
        ];
    }

    /**
     * @param string $host
     * @param int $port
     * @param bool $ssl
     * @return Server
     */
    static public function create($host, $port, $ssl = false)
    {
        return new self($host, $port, $ssl);
    }

    public function run($setting)
    {
        $this->runtimeSetting = array_merge($this->runtimeSetting, $setting);

        $this->sw->set($this->runtimeSetting);

        $this->sw->on('Start', [$this, 'onMasterStart']);
        $this->sw->on('Shutdown', [$this, 'onMasterStop']);
        $this->sw->on('ManagerStop', [$this, 'onManagerStop']);
        $this->sw->on('WorkerStart', [$this, 'onWorkerStart']);

        // 如果设置了处理协议
        $this->callProtocolMethod('setServer', [$this->sw]);

        $this->sw->on('Connect', function () {
            $this->callProtocolMethod('onConnect', func_get_args());
        });

        $this->sw->on('Close', function () {
            $this->callProtocolMethod('onClose', func_get_args());
        });

        if (self::$useSwooleHttpServer) {
            $this->sw->on('Request', function () {
                $this->callProtocolMethod('onRequest', func_get_args());
            });
        } else {
            $this->sw->on('Receive', function () {
                $this->callProtocolMethod('onReceive', func_get_args());
            });
        }

        $this->sw->on('Task', function () {
            $this->callProtocolMethod('onTask', func_get_args());
        });

        $this->sw->on('Finish', function () {
            $this->callProtocolMethod('onFinish', func_get_args());
        });

        $this->sw->start();
    }

    public function onMasterStart($serv)
    {
        Swoole\Console::setProcessName($this->getProcessName() . ': master -host=' . $this->host . ' -port=' . $this->port);
    }

    public function onMasterStop($serv)
    {

    }

    public function onManagerStop()
    {

    }

    public function onWorkerStart($serv, $workerId)
    {
        if ($workerId >= $serv->setting['worker_num']) {
            Swoole\Console::setProcessName($this->getProcessName() . ': task');
        } else {
            Swoole\Console::setProcessName($this->getProcessName() . ': worker');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send($clientId, $data)
    {
        return $this->sw->send($clientId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function close($clientId)
    {
        return $this->sw->shutdown();
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        return $this->sw->shutdown();
    }

    /**
     * 调用协议方法
     *
     * @param string $func
     * @param array $argv
     * @return mixed
     */
    protected function callProtocolMethod($func, $argv = [])
    {
        if (null !== $this->protocol && method_exists($this->protocol, $func)) {
            return call_user_func_array([$this->protocol, $func], $argv);
        }
        return null;
    }
}