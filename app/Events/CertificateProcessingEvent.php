<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CertificateProcessingEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $status;
    public $message;
    public $type;

    /**
     * Имя очереди, в которую нужно поместить задание трансляции.
     *
     * @var string
     */
    public $queue = 'notify';


    public function __construct($status, $type, $message)
    {
        $this->status = $status;
        $this->message = $message;
        $this->type = $type;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('certificate.processing')
        ];
    }
}
