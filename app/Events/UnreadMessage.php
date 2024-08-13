<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnreadMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $count;
    public $message;
    public $sender_id;
    public $receiver_id;




    public function __construct($count,$message,$sender_id,$receiver_id)
    {
        $this->count=$count;
        $this->message = $message;
        $this->sender_id=$sender_id;
        $this->receiver_id = $receiver_id;
    }


    public function broadcastWith () {
        return [
            'sender_id' =>$this->sender_id,
            'receiver_id' => $this->receiver_id,
            'message'=> $this->message,
            'count'=>  $this->count,
        ];
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('unread.'.$this->receiver_id);
    }
}
