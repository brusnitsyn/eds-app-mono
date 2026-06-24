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
    public $stage;
    public $data;

    /**
     * Имя очереди, в которую нужно поместить задание трансляции.
     *
     * @var string
     */
    public $queue = 'notify';


    /**
     * @param string $status 'started'|'processed'|'success'|'failed' (батч) или
     *                        'running'|'done'|'warning'|'failed' (этап, см. $stage)
     * @param string|null $stage 'container'|'chain'|'crl'|'save' — для пакетных событий не задаётся
     * @param array $data Доп. данные этапа, например распознанные ФИО/СНИЛС для stage='save'
     */
    public function __construct($status, $type, $message, $stage = null, $data = [])
    {
        $this->status = $status;
        $this->message = $message;
        $this->type = $type;
        $this->stage = $stage;
        $this->data = $data;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('certificate.processing')
        ];
    }
}
