<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;
use App\Models\Users;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;

class SendMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $sender_id;
    public $receiver_id;
public $deleted_for_every_one_at;
    /**
     * Create a new event instance.
     */
    public function __construct($message,$sender_id, $receiver_id,$deleted_for_every_one_at)
    {
        $this->message = $message;
        $this->sender_id=$sender_id; 
        $this->receiver_id = $receiver_id;
        $this->deleted_for_every_one_at=$deleted_for_every_one_at;
    }
    
    public function broadcastWith () {
        return [
            'sender_id' =>$this->sender_id,
            'receiver_id' => $this->receiver_id,
            'message'=> $this->message,
            'deleted_for_every_one_at'=>$this->deleted_for_every_one_at,
        ];
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return  new Channel('user.'.$this->sender_id.$this->receiver_id);
    }

  
}
