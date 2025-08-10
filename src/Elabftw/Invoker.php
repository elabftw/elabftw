<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use RuntimeException;

final class Invoker
{
    private const string SOCKET_PATH = 'unix:///run/invoker/invoker.sock';

    /**
    * @var resource invoker socket
    */
    private $socket;

    public function __construct()
    {
        $socket = stream_socket_client(self::SOCKET_PATH, $errno, $errstr);
        if ($socket === false) {
            throw new RuntimeException("Failed to connect to the socket: $errstr ($errno)\n");
        }
        $this->socket = $socket;
    }

    public function __destruct()
    {
        fclose($this->socket);
    }

    public function write(string $message): void
    {
        fwrite($this->socket, sprintf('%s|%s', Env::asString('INVOKER_PSK'), $message));
    }
}
