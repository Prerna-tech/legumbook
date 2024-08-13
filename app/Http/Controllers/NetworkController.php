<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Network;
use App\Models\Users;
use App\Models\Work;
use App\Events\FriendRequestNotification;
use App\Mail\NetworkAcceptNotification;

use App\Mail\NetworkMialNotification;
use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\Mail;

class NetworkController extends Controller
{
    public function requestToNetwork(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        if (!Users::where('id', $request->receiver_id)->exists()) {
            return response()->json([
                'message' => 'User Not Exists'
            ], 422);
        }
        $is_friend=Network::where('receiver_id',$request->receiver_id)
       ->where('sender_id', "=", $user->id)->first();
       if(isset($is_friend)){
         $friend =Network::find($is_friend->id);    
         $friend->delete();
         return response()->json([
            'status' => 'success',
            'message' => 'unfollow',
        ], 200);
       }
        $newRequest = new Network();
        $newRequest->sender_id = $user->id;
        $newRequest->receiver_id = $request->receiver_id;
        $newRequest->requested_at = today();
        $newRequest->save();

       
        $notification=new Notification();
        $notification->post_type='FOLLOW';
        $notification->content='Follow Request';
        $notification->owner_id=$request->receiver_id;
        $notification->user_id=$user->id;
        $notification->action_on=$newRequest->id;
        $notification->save();
        $data= Notification::with('user')->find($notification->id);
        broadcast(new FriendRequestNotification($data,$user->id,$request->receiver_id))->toOthers();
           try{
            $receiver=Users::find($request->receiver_id);
            // Mail::to($receiver->email)->send(new NetworkMialNotification($user->name));
            Mail::to($receiver->email)->send(new NetworkMialNotification($receiver));
       }catch(Exception $e){
      }

        return response()->json([
            'status' => 'success',
            'message' => 'Request Send Successfully',
        ], 201);
    }


 
    public function acceptRequest(Request $request)
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
       $is_friend=Network::whereNotNull('accepted_at')->where('receiver_id', $user->id)
       ->where('sender_id', "=", $request->sender_id)->first();
       $is_frind2=Network::whereNotNull('accepted_at')->where('sender_id', $user->id)
       ->where('receiver_id', "=", $request->sender_id)->first();
       if(isset($is_friend)){
         $friend =Network::find($is_friend->id);    
         $friend->delete();
         return response()->json([
            'status' => 'success',
            'message' => 'unfollow',
        ], 200);
       }
       $is_frind2=Network::whereNotNull('accepted_at')->where('sender_id', $user->id)
       ->where('receiver_id', "=", $request->sender_id)->first();
       if(isset($is_frind2)){
         $friend =Network::find($is_frind2->id);    
         $friend->delete();
         return response()->json([
            'status' => 'success',
            'message' => 'unfollow',
        ], 200);
       }
        $accept = Network::where('receiver_id', $user->id)
            ->where('sender_id', "=", $request->sender_id)
            ->update([
                'accepted_at' => now()
            ]);
        if (!$accept) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
            ], 422);
        }
        try{
            $sender=Users::find($request->sender_id);
            Mail::to($sender->email)->send(new NetworkAcceptNotification($sender));
        }catch(Exception $e){
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Request Accepted Successfully',
        ], 201);
    }

    public function rejectRequest(Request $request)
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

        $is_friend = Network::where(function ($query) use ($user, $request) {
            $query->where('receiver_id', $user->id)
                  ->where('sender_id', $request->sender_id);
        })->orWhere(function ($query) use ($user, $request) {
            $query->where('receiver_id', $request->sender_id)
                  ->where('sender_id', $user->id);
        })->first();

    //    $is_friend=Network::where('receiver_id', $user->id)
    //    ->where('sender_id', "=", $request->sender_id)->first();


        if (!$is_friend) {
                    
            return response()->json([
                'status' => 'success',
                'message' => 'data not found',
            ], 404);
        }

       if(isset($is_friend)){
        $friend =Network::find($is_friend->id);    
        $friend->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'unfollow',
            ], 200);
       }

    }


    public function viewNetworkRequest()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $networkRequest = Network::where('receiver_id', $user->id)->where('accepted_at', null)->with('sender')->with('UserProfile')->get();
        if (!$networkRequest) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
            ], 204);
        }
        return response()->json([
            'status' => 'success',
            'data' => $networkRequest,
        ], 201);
    }


    public function viewMyNetwork()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        // $myNetwork = Network::whereNotNull('accepted_at')->where('receiver_id','=',$user->id)->orWhere('sender_id','=',$user->id)->with('sender')->with('user')->with('UserProfile')->with('user.work')->get();
        

        // $myNetwork = Network::where(function($query) use ($user) {
        //     $query->whereNotNull('accepted_at')
        //           ->where('receiver_id', $user->id)
        //           ->orWhere('sender_id', $user->id);
        // })
        // ->with('sender')
        // ->with(['user', 'UserProfile', 'user.work'])
        // ->paginate(15);
    

        $query = "
            SELECT 
                u.id, 
                u.name, 
                u.image,
                loc.longitude,
                loc.latitude,
                work.title,
                work.location,
                work.company_name,
                COUNT(f1.receiver_id) as mutual_friends
            FROM 
                users AS u
                LEFT JOIN network AS f1 ON u.id = f1.sender_id AND f1.receiver_id = $user->id AND f1.accepted_at IS NOT NULL
                LEFT JOIN network AS f2 ON u.id = f2.receiver_id AND f2.sender_id = $user->id AND f2.accepted_at IS NOT NULL
                LEFT JOIN work ON u.id = work.user_id
                LEFT JOIN user_profile AS loc ON u.id = loc.user_id
            WHERE 
                u.id != $user->id
                AND (
                    (f1.sender_id = $user->id AND f1.accepted_at IS NOT NULL)
                    OR (f2.receiver_id = $user->id AND f2.accepted_at IS NOT NULL)
                    OR u.id IN (
                        SELECT f3.sender_id FROM network AS f3 WHERE f3.receiver_id = $user->id AND f3.accepted_at IS NOT NULL
                    )
                    OR u.id IN (
                        SELECT f4.receiver_id FROM network AS f4 WHERE f4.sender_id = $user->id AND f4.accepted_at IS NOT NULL
                    )
                )
            GROUP BY 
                u.id, u.name, u.image, loc.longitude, loc.latitude, work.user_id, work.title, work.location, work.company_name
            ";


        $result = DB::select($query);

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'message' => 'something went wrong',
            ], 204);
        }
        return response()->json([
            'status' => 'success',
            'data' => $result,
            // 'data'=>Arr::flatten($result),
        ], 201);

    }
    
}
