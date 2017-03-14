<?php
/**
 * AbstractProtocol.php
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

namespace Swoole\Protocol;

/**
 * Class AbstractProtocol
 * @package Swoole\Protocol
 */
abstract class AbstractProtocol implements ProtocolInterface
{
    /**
     * @var \swoole_server
     */
    protected $server;

    /**
     * @param \swoole_server $server
     */
    public function setServer(\swoole_server $server)
    {
        $this->server = $server;
    }
}