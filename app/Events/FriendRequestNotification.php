<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

     public $data;
     public $sender_id;
     public $receiver_id;


    public function __construct($data,$sender_id, $receiver_id)
    {
        $this->data = $data;
        $this->sender_id=$sender_id; 
        $this->receiver_id = $receiver_id;
    }


    public function broadcastWith () {
        return [
            'sender_id' =>$this->sender_id,
            'receiver_id' => $this->receiver_id,
            'message'=> $this->data,
        ];
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel('newRequest.'.$this->receiver_id);
    }
}
