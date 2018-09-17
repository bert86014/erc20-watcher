<?php

namespace Leonis\ERC20Watcher;

use phpseclib\Math\BigInteger;

/**
 * Class EventLog
 * @package Leonis\ERC20Watcher
 */
class Log
{
    /**
     * @var string
     */
    public $log;
    /**
     * @var string
     */
    public $contract;
    /**
     * @var string
     */
    public $from;
    /**
     * @var string
     */
    public $to;
    /**
     * @var string
     */
    public $value;
    /**
     * @var string
     */
    public $blockNumber;
    /**
     * @var string
     */
    public $hash;
    /**
     * @var string
     */
    public $txid;

    /**
     * EventLog constructor.
     * @param object $log
     */
    public function __construct($log)
    {
        $this->log = $log;
        $this->contract = $log->address;
        $this->from = $this->getAddressFromTopic($log->topics[1]);
        $this->to = $this->getAddressFromTopic($log->topics[2]);
        $this->value = $this->getNumberFromData($log->data);
        $this->blockNumber = $this->hex2num($log->blockNumber);
        $this->hash = $log->transactionHash;
        $this->txid = $log->transactionHash;
    }

    /**
     * @param string $topic
     * @return string
     */
    private function getAddressFromTopic($topic)
    {
        return implode('', explode('000000000000000000000000', $topic));
    }

    /**
     * @param string $data
     * @return string
     */
    private function getNumberFromData($data)
    {
        $data = new BigInteger(ltrim(substr($data, 2), '0'), 16);
        $decimals = new BigInteger(pow(10, config('coins.contract.decimals')), 10);
        /** @var BigInteger $quotient */
        /** @var BigInteger $residue */
        list($quotient, $residue) = $data->divide($decimals);
        $number = $quotient->toString() . '.' . str_pad($residue->toString(), config('coins.contract.decimals'), '0', STR_PAD_LEFT);

        return rtrim($number, '0');
    }

    /**
     * @param string $hex
     * @return string
     */
    private function hex2num($hex)
    {
        return (new BigInteger($hex, 16))->toString();
    }
}