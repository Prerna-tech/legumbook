<?php

namespace App\Http\Controllers;

use App\Events\DeleteMessage;
use App\Events\SendMessage;
use App\Events\UnreadMessage;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Network;
use App\Models\Users;
use Illuminate\Support\Facades\Validator;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MessagesController extends Controller
{
    public function index($sender_id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $message = Message::where('deleted_by','!=', $user->id)
        ->where(function($query) use ($user, $sender_id) {
            $query->where([
                    ['sender_id', '=', $sender_id],
                    ['receiver_id', '=', $user->id]
                ])->orWhere([
                    ['sender_id', '=', $user->id],
                    ['receiver_id', '=', $sender_id]
                ]);
        })
        ->orderBy('created_at', 'ASC')
        ->paginate(15);
        if (!$message) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        return response()->json(['status' => 'success', 'data' =>   $message], 200);
    }

    public function storeMessages(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'message' => 'required',
            'receiver_id' => 'required',
            'network_id'=>'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $message = $request->message;
        $sender_id = $user->id;
        $receiver_id = $request->receiver_id;
        $newMessage = new Message;
        $newMessage->sender_id = $sender_id;
        $newMessage->receiver_id = $receiver_id;
        $newMessage->message = $message;
        $newMessage->network_id = $request->network_id;
        $newMessage->save();
        $deleted_for_every_one_at='0';
        broadcast(new SendMessage($newMessage,$sender_id,$request->receiver_id,$deleted_for_every_one_at))->toOthers();
        $data=['sender_id'=>$sender_id,'message'=>$newMessage->message,'time'=>$newMessage->created_at];
        $unread=Message::whereNull('readed_on')->where('sender_id',$sender_id)->where('receiver_id',$receiver_id)->count();
        broadcast(new UnreadMessage($unread,$data,$sender_id,$request->receiver_id))->toOthers();
       return response()->json(['status' => 'Message sent','sentMessage'=>$newMessage]);
    }




    public function readMessage(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $lasmessage=Message::where([
            ['sender_id', '=', $request->sender_id],
            ['receiver_id', '=', $user->id]
        ])->orWhere([
            ['sender_id', '=', $user->id],
            ['receiver_id', '=', $request->sender_id]
        ])->orderBy('id','DESC')->first();
        
            Message::where('sender_id','=',$request->sender_id)->where('receiver_id','=',$user->id)->update(['readed_on'=>now()]);
        
        $data=['sender_id'=>$request->sender_id,'message'=>$lasmessage->message,'time'=>$lasmessage->created_at];
            $unread=Message::whereNull('readed_on')->where('receiver_id','=',$request->sender_id)->where('sender_id',$user->id)->count();
            broadcast(new UnreadMessage($unread,$data,$request->sender_id,$user->id))->toOthers();
       //return $unread;
    }





    public function messageList(){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
    $myNetwork = Network::whereNotNull('accepted_at')->where('receiver_id','=',$user->id)->orWhere('sender_id','=',$user->id)->with('user') 
       ->with('sender')->with('lastMessage')->withCount('unreadMessage')->paginate(15);
        if (!$myNetwork) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
            ], 204);
        }
       
        return response()->json([
            'status' => 'success',
            'data' =>$myNetwork,
        ], 201);
    }






    public function deleteMessage(Request $request){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        

        $validator = Validator::make($request->all(), [
            'msg_ids' => 'required',
            'is_deleted_everyone'=>'required',
            'receiver_id'=>'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
     //return $request->msg_ids;
     if($request->is_deleted_everyone){
        DB::beginTransaction();
        $msgs=Message::whereIn('id',$request->msg_ids)->where('sender_id',$user->id)->update(['message' => 'This message was deleted','deleted_for_every_one_at'=>Carbon::now()->timestamp]);
        DB::commit();
        $afterUpdate=Message::whereIn('id',$request->msg_ids)->get();
        //$myNetwork = Network::where('id',$msg->network_id)->first();
        broadcast(new  DeleteMessage($afterUpdate, $user->id, $request->receiver_id))->toOthers();
        return response()->json([
            'status' =>'success',
            'message' =>  $afterUpdate,
         ], 200);
     }else{
        $msgs=Message::whereIn('id',$request->msg_ids)->get();
        foreach($msgs as $msg) {
           if($msg->deleted_by==0){
            
               $msg->deleted_by = $user->id;
               $msg->save();
              
           }
           if($msg->deleted_by!=0){
            if($msg->deleted_by!= $user->id){
                $msg->delete();
            }
              
           } 
        }
      
       
     }
     
    }
}
