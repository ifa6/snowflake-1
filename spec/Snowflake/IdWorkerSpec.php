<?php

namespace spec\Vscn\Snowflake;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class IdWorkerSpec extends ObjectBehavior
{
    protected $workerId;
    protected $datacenterId;

    function let()
    {
        $this->workerId = 1;
        $this->datacenterId = 1;
        $this->beConstructedWith($this->workerId, $this->datacenterId);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Vscn\Snowflake\IdWorker');
    }

    function it_genereate_an_id()
    {
        $this->nextId()->shouldNotBe(0);
    }

    function it_return_an_accurate_timestamp()
    {
        $t = floor(microtime(true) * 1000);

        $this->beAnInstanceOf('Vscn\Snowflake\EasyTimeWorker');
        $this->beConstructedWith(1, 1);
        $this->timestamp = $t;

        $this->getTimestamp()->shouldBe($t);
    }

    function it_return_the_correct_worker_id()
    {
        $this->getWorkerId()->shouldBe($this->workerId);
    }

    function it_return_the_correct_dc_id()
    {
        $this->getDataCenterId()->shouldBe($this->datacenterId);
    }

    function it_properly_mask_worker_id()
    {
        // setup
        $workerId     = 0x1F;
        $datacenterId = 1;
        $mask = 0x000000000001F000;

        $this->beConstructedWith($workerId, $datacenterId);

        // tests ...
        for ($i = 0; $i < 10; $i++) {
            $this->nextId()->shouldBeProperlyMasked($workerId, $mask, 12);
        }
    }

    function it_properly_mask_dc_id()
    {
        // setup
        $workerId     = 0;
        $datacenterId = 0x1F;
        $mask = 0x00000000003E0000;

        $this->beConstructedWith($workerId, $datacenterId);

        // tests ...
        for ($i = 0; $i < 10; $i++) {
            $this->nextId()->shouldBeProperlyMasked($datacenterId, $mask, 17);
        }
    }

    function it_properly_mask_timestamp()
    {
        // setup
        $workerId     = 31;
        $datacenterId = 31;
        $mask = 0xFFFFFFFFFFC00000;

        $this->beAnInstanceOf('Vscn\Snowflake\EasyTimeWorker');
        $this->beConstructedWith($workerId, $datacenterId);

        for ($i = 0; $i < 1; $i++) {
            $timestamp = floor(microtime(true) * 1000);
            $this->timestamp = $timestamp;
            $this->getTimestamp()->shouldBe($timestamp);
            $this->nextId()->shouldBeProperlyMasked(($timestamp - \Vscn\Snowflake\IdWorker::TWEPOC), $mask, 22);
        }
    }

    function it_roll_over_sequence_id()
    {
         // put a zero in the low bit so we can detect overflow from the sequence
        $workerId = 4;
        $datacenterId = 4;
        $startSequence = 0xFFFFFF-20;
        $endSequence = 0xFFFFFF+20;
        $mask = 0x000000000001F000;

        $this->beConstructedWith($workerId, $datacenterId, $startSequence);

        for ($i = $startSequence; $i < $endSequence; $i++) {
            $this->nextId()->shouldBeProperlyMasked($workerId, $mask, 12);
        }

    }

    public function getMatchers()
    {
        return [
            'beProperlyMasked' => function ($subject, $input, $mask, $shiftBits)
            {
                return (($subject & $mask) >> $shiftBits) == $input;
            },
        ];
    }
}

namespace Vscn\Snowflake;

class EasyTimeWorker extends IdWorker
{
    public $timestamp;
    public function getTimestamp() { return $this->timestamp; }
}