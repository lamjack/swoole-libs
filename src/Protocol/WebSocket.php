<?php
/**
 * WebSocket.php
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

use Swoole\Request;
use Swoole\Response;

abstract class WebSocket extends Http
{
    const OPCODE_CONTINUATION_FRAME = 0x0;
    const OPCODE_TEXT_FRAME = 0x1;
    const OPCODE_BINARY_FRAME = 0x2;
    const OPCODE_CONNECTION_CLOSE = 0x8;
    const OPCODE_PING = 0x9;
    const OPCODE_PONG = 0xa;

    const CLOSE_NORMAL = 1000;
    const CLOSE_GOING_AWAY = 1001;
    const CLOSE_PROTOCOL_ERROR = 1002;
    const CLOSE_DATA_ERROR = 1003;
    const CLOSE_STATUS_ERROR = 1005;
    const CLOSE_ABNORMAL = 1006;
    const CLOSE_MESSAGE_ERROR = 1007;
    const CLOSE_POLICY_ERROR = 1008;
    const CLOSE_MESSAGE_TOO_BIG = 1009;
    const CLOSE_EXTENSION_MISSING = 1010;
    const CLOSE_SERVER_ERROR = 1011;
    const CLOSE_TLS = 1015;

    const WEBSOCKET_VERSION = 13;
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    public $maxConnect = 10000;
    public $maxFrameSize = 2097152; // 数据包最大长度
    public $heartTime = 600; // 600秒没有心跳就断开

    protected $frameList = [];
    protected $connections = [];

    protected $keepalive = true;

    /**
     * {@inheritdoc}
     */
    public function onConnect($server, $clientId, $fromId)
    {
        $this->cleanBuffer($clientId);
    }

    /**
     * {@inheritdoc}
     */
    public function onReceive($server, $clientId, $fromId, $data)
    {
        if (!isset($this->connections[$clientId])) {
            parent::onReceive($server, $clientId, $fromId, $data);
        }

        while (strlen($data) > 0 && isset($this->connections[$clientId])) {
            if (!isset($this->frameList[$clientId])) {
                $frame = $this->parseFrame($data);
                if (false === $frame) {
                    $this->error('错误数据帧');
                    $this->close($clientId);
                    break;
                }
                if (true === $frame['finish']) {
                    $this->opcodeSwitch($clientId, $frame);
                } else { // 加到缓存
                    $this->frameList[$clientId] = $frame;
                }
            } else {
                $frame = &$this->frameList[$clientId];
                $frame['data'] .= $data;

                if (strlen($frame['data']) >= $frame['length']) {
                    $frame['fin'] = 1;
                    $frame['finish'] = true;
                    $frame['data'] = substr($frame['data'], 0, $frame['length']);
                    $frame['message'] = $this->parseMessage($frame);
                    $this->opcodeSwitch($clientId, $frame);
                    $data = substr($frame['data'], $frame['length']);
                } else { // 数据不足,跳出循环,继续等待数据
                    break;
                }
            }
        }
    }

    /**
     * 新客户端连接
     *
     * @param int $clientId
     */
    abstract protected function onEnter($clientId);

    /**
     * 收到消息
     *
     * @param int $client_id
     * @param mixed $message
     */
    abstract function onMessage($client_id, $message);

    /**
     * {@inheritdoc}
     */
    protected function handleRequest(Request $request)
    {
        return $request->isWebSocket() ? $this->handleWebSocketRequest($request) : parent::handleRequest($request);
    }

    /**
     * 处理WebSocket请求
     *
     * @param Request $request
     * @return Response
     */
    protected function handleWebSocketRequest(Request $request)
    {
        $this->debug('WebSocket[处理WebSocket请求]');

        $response = new Response();
        $this->doHandshake($request, $response);
        return $response;
    }

    /**
     * 打开阶段握手
     *
     * @see https://tools.ietf.org/html/rfc6455
     * @see https://github.com/zhangkaitao/websocket-protocol
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    protected function doHandshake(Request $request, Response $response)
    {
        $this->debug('WebSocket[握手]');

        if (!isset($request->header['Sec-WebSocket-Key'])) {
            $this->error('请求必须带有Sec-WebSocket-Key头字段');
            return false;
        }
        $key = $request->header['Sec-WebSocket-Key'];
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $key) || 16 !== strlen(base64_decode($key))) {
            $this->error('请求头字段Sec-WebSocket-Key格式错误');
            return false;
        }

        $response->setStatusCode(101);
        $response->addHeaders([
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => base64_encode(sha1($key . self::GUID, true)),
            'Sec-WebSocket-Version' => self::WEBSOCKET_VERSION,
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function postResponse(Request $request, Response $response)
    {
        $this->debug('WebSocket[Response发送后回调]');
        if ($request->isWebSocket()) {
            $conn = [
                'header' => $request->header,
                'time' => time()
            ];
            $this->connections[$request->fd] = $conn;

            // @todo 检查WebSocket是否已经超过指定连接数

            $this->onWsConnect($request->fd);
        }

        parent::postResponse($request, $response);
    }

    /**
     * 客户端连接回调
     *
     * @param $clientId
     */
    protected function onWsConnect($clientId)
    {
        $this->debug(sprintf('WebSocket[客户端#%s已连接]', $clientId));
        $this->onEnter($clientId);
    }

    /**
     * 创建数据帧
     *
     * @param string $message
     * @param int $opcode
     * @param bool $end
     * @return string
     */
    protected function createFrame($message, $opcode = self::OPCODE_TEXT_FRAME, $end = true)
    {
        $fin = true === $end ? 0x1 : 0x0;
        $rsv1 = 0x0;
        $rsv2 = 0x0;
        $rsv3 = 0x0;
        $length = strlen($message);
        $out = chr(($fin << 7) | ($rsv1 << 6) | ($rsv2 << 5) | ($rsv3 << 4) | $opcode);

        if (0xffff < $length) {
            $out .= chr(0x7f) . pack('NN', 0, $length);
        } elseif (0x7d < $length) {
            $out .= chr(0x7e) . pack('n', $length);
        } else {
            $out .= chr($length);
        }
        $out .= $message;
        return $out;
    }

    /**
     * 解析数据帧
     * @see https://github.com/zhangkaitao/websocket-protocol/wiki/5.%E6%95%B0%E6%8D%AE%E5%B8%A7
     * @param $buffer
     * @return array|bool
     */
    protected function parseFrame(&$buffer)
    {
        $this->debug('WebSocket[解析数据帧]', ['bufferLength' => strlen($buffer)]);

        $ws = [];
        $ws['finish'] = false;

        $dataOffset = 0;

        // fin:1 rsv1:1 rsv2:1 rsv3:1 opcode:4
        $handle = ord($buffer[$dataOffset]);
        $ws['fin'] = ($handle >> 7) & 0x1;
        $ws['rsv1'] = ($handle >> 6) & 0x1;
        $ws['rsv2'] = ($handle >> 5) & 0x1;
        $ws['rsv3'] = ($handle >> 4) & 0x1;
        $ws['opcode'] = $handle & 0xf;
        $dataOffset++;

        // mask:1 length:7
        $handle = ord($buffer[$dataOffset]);
        $ws['mask'] = ($handle >> 7) & 0x1;
        // 0-125
        $ws['length'] = $handle & 0x7f;
        $length =  &$ws['length'];
        $dataOffset++;

        // 126 short
        if ($length == 0x7e) {
            // 2
            $handle = unpack('nl', substr($buffer, $dataOffset, 2));
            $dataOffset += 2;
            $length = $handle['l'];
        } // 127 int64
        elseif ($length > 0x7e) {
            // 8
            $handle = unpack('N*l', substr($buffer, $dataOffset, 8));
            $dataOffset += 8;
            $length = $handle['l'];

            // 超过最大允许的长度了
            if ($length > $this->maxFrameSize) {
                $this->error('数据包超过最大长度');
                return false;
            }
        }

        // mask-key: int32
        if (0x0 !== $ws['mask']) {
            $ws['mask'] = array_map('ord', str_split(substr($buffer, $dataOffset, 4)));
            $dataOffset += 4;
        }

        // 把头去掉
        $buffer = substr($buffer, $dataOffset);

        // 数据长度为0的帧
        if (0 === $length) {
            $ws['finish'] = true;
            $ws['message'] = '';
            return $ws;
        }

        // 完整的一个数据帧
        if (strlen($buffer) >= $length) {
            $ws['finish'] = true;
            $ws['data'] = substr($buffer, 0, $length);
            $ws['message'] = $this->parseMessage($ws);
            //截取数据
            $buffer = substr($buffer, $length);
            return $ws;
        } else { // 需要继续等待数据
            $ws['finish'] = false;
            $ws['data'] = $buffer;
            $buffer = '';
            return $ws;
        }
    }

    /**
     * 解析一个完整的数据帧
     *
     * @param array $ws
     * @return string
     */
    protected function parseMessage(array $ws)
    {
        $data = $ws['data'];
        if (0x0 !== $ws['mask']) { // 没有mask
            $maskC = 0;
            for ($j = 0, $_length = $ws['length']; $j < $_length; ++$j) {
                $data[$j] = chr(ord($data[$j]) ^ $ws['mask'][$maskC]);
                $maskC = ($maskC + 1) % 4;
            }
        }
        return $data;
    }

    /**
     * 发送报文
     *
     * @param int $clientId
     * @param string $message
     * @param int $opcode
     * @param bool $end
     * @return bool
     */
    protected function send($clientId, $message, $opcode = self::OPCODE_TEXT_FRAME, $end = true)
    {
        if ((self::OPCODE_TEXT_FRAME === $opcode or self::OPCODE_CONTINUATION_FRAME === $opcode) and false === (bool)preg_match('//u', $message)) {
            $this->error('发送内容非utf8编码');
            return false;
        } else {
            $out = $this->createFrame($message, $opcode, $end);
            return $this->server->send($clientId, $out);
        }
    }

    /**
     * 断开客户端连接
     *
     * @param int $clientId
     * @param int $code
     * @param string $reason
     * @return bool
     */
    protected function close($clientId, $code = self::CLOSE_NORMAL, $reason = '')
    {
        $this->send($clientId, pack('n', $code) . $reason, self::OPCODE_CONNECTION_CLOSE);
        $this->debug(sprintf('WebSocket[服务端关闭客户端#%s的链接,关闭码:%d,原因:%s]', $clientId, $code, $reason));
        return $this->server->close($clientId);
    }

    /**
     * @param int $clientId
     * @param array $ws
     */
    protected function opcodeSwitch($clientId, &$ws)
    {
        $this->debug("WebSocket[客户端#{$clientId},opcode{$ws['opcode']}]");

        switch ($ws['opcode']) {
            // 数据帧
            case self::OPCODE_BINARY_FRAME:
            case self::OPCODE_TEXT_FRAME:
                if (0x1 === $ws['fin']) {
                    $this->onMessage($clientId, $ws);
                } else {
                    // 帧不完整
                }
                break;

            // 心跳包
            case self::OPCODE_PING:
                $message = &$ws['message'];
                if (0x0 === $ws['fin'] or 0x7d < $ws['length']) {
                    $this->close($clientId, self::CLOSE_PROTOCOL_ERROR, "ping error");
                    break;
                }
                $this->connections[$clientId]['time'] = time();
                $this->send($clientId, $message, self::OPCODE_PONG, true);
                break;

            case self::OPCODE_PONG:
                if (0 === $ws['fin']) {
                    $this->close($clientId, self::CLOSE_PROTOCOL_ERROR, "pong? server cannot pong.");
                }
                break;

            // 连接关闭
            case self::OPCODE_CONNECTION_CLOSE:
                $length = &$ws['length'];
                if (1 === $length or 0x7d < $length) {
                    $this->close($clientId, self::CLOSE_PROTOCOL_ERROR, "client active close");
                    break;
                }

                if ($length > 0) {
                    $message = $ws['message'];
                    $_code = unpack('nc', substr($message, 0, 2));
                    $code = $_code['c'];

                    if ($length > 2) {
                        $reason = substr($message, 2);
                        if (false === (bool)preg_match('//u', $reason)) {
                            $this->close($clientId, self::CLOSE_MESSAGE_ERROR);
                            break;
                        }
                    }

                    if (1000 > $code || (1004 <= $code && $code <= 1006) || (1012 <= $code && $code <= 1016) || 5000 <= $code) {
                        $this->close($clientId, self::CLOSE_PROTOCOL_ERROR);
                        break;
                    } else {
                        $this->close($clientId, self::CLOSE_PROTOCOL_ERROR, "client active close, code={$code}");
                        break;
                    }
                }
                break;
            default:
                $this->close($clientId, self::CLOSE_PROTOCOL_ERROR, "unkown websocket opcode[{$ws['opcode']}]");
                break;
        }
        unset($this->frameList[$clientId]);
    }

    /**
     * 清理连接缓存
     *
     * @param int $clientId
     */
    protected function cleanBuffer($clientId)
    {
        unset($this->frameList[$clientId], $this->connections[$clientId]);
        parent::cleanBuffer($clientId);
    }
}