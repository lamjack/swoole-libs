<?php
/**
 * Web.php
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

abstract class Web extends AbstractProtocol
{
    const SOFTWARE = 'swoole';

    /**
     * 请求信息，全部都是Request对象
     *
     * @var array
     */
    protected $requests = [];

    protected $keepalive = false;

    public function __construct($config = [])
    {
        define('SWOOLE_SERVER', true);
    }

    /**
     * @param int $clientId
     */
    protected function cleanBuffer($clientId)
    {
        unset($this->requests[$clientId]);
    }
}