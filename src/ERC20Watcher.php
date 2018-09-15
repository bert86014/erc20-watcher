<?php

namespace Leonis\ERC20Watcher;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Console\Command;
use phpseclib\Math\BigInteger;
use Swoole\Timer;

class ERC20Watcher extends Command
{
    protected $signature = 'erc20:watcher';

    protected $description = 'Ethereum ERC20 Watcher';

    private $blockNumber = 6335179;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Timer::tick(1000, function () {
            $http = new Client();
            try {
                $response = $http->post(config('coins.rpc.ip') . ':' . config('coins.rpc.port'), [
                    'timeout' => config('coins.rpc.timeout'),
                    'json' => [
                        "jsonrpc" => "2.0",
                        "method" => "eth_blockNumber",
                        "params" => [],
                        "id" => 1,
                    ],
                ]);
            } catch (ConnectException $exception) {
                $this->error($exception->getMessage());
                return;
            }
            $response = json_decode($response->getBody()->getContents());
            $blockNumber = new BigInteger($response->result, 16);
            if ($blockNumber->toString() <= $this->blockNumber) {
                return;
            }

            $diff = $blockNumber->toString() - $this->blockNumber;
            if ($diff > 1000) {
                $to = $this->blockNumber + 1000;
            } elseif ($diff > 100) {
                $to = $this->blockNumber + 100;
            } elseif ($diff > 10) {
                $to = $this->blockNumber + 10;
            } else {
                $to = $this->blockNumber + 1;
            }

            try {
                $response = $http->post(config('coins.rpc.ip') . ':' . config('coins.rpc.port'), [
                    'timeout' => config('coins.rpc.timeout'),
                    'json' => [
                        "jsonrpc" => "2.0",
                        "method" => "eth_getLogs",
                        "params" => [
                            [
                                'fromBlock' => '0x' . ltrim((new BigInteger($this->blockNumber))->toHex(), 0),
                                'toBlock' => '0x' . ltrim((new BigInteger($to))->toHex(), 0),
                                'address' => config('coins.contract.address'),
                                'topics' => config('coins.topics'),
                            ],
                        ],
                        "id" => 1,
                    ],
                ]);
            } catch (ConnectException $exception) {
                $this->error($exception->getMessage());
                return;
            }

            $response = json_decode($response->getBody()->getContents());
            $logs = $response->result;
            if (count($logs)) {
                foreach ($logs as $log) {
                    if (
                        (new BigInteger($log->blockNumber, 16))
                            ->add(new BigInteger(config('coins.confirmations'), 10))
                            ->compare($blockNumber) == 1
                    ) {
                        event(new LogEvent($log));
                    }
                }
            }

            $this->blockNumber = $to;
            $this->info($this->blockNumber);
        });

        Timer::tick(60000, function () {
            $this->blockNumber = $this->blockNumber - 10;
        });

        Timer::tick(600000, function () {
            $this->blockNumber = $this->blockNumber - 100;
        });

        Timer::tick(3600000, function () {
            $this->blockNumber = $this->blockNumber - 1000;
        });

        Timer::tick(10800000, function () {
            $this->blockNumber = $this->blockNumber - 3000;
        });

        Timer::tick(86400000, function () {
            $this->blockNumber = $this->blockNumber - 10000;
        });
    }
}