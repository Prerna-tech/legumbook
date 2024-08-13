<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator;
use App\Models\ReportUser;


class NotificationController extends Controller
{
    public function notification(){

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $notification=Notification::whereNull('readed_on')->where('owner_id',$user->id)->with('user')->orderBy('id', 'ASC')->paginate(15);

        return response()->json(['status' => 'success', 'data' =>$notification], 200);
    }


    public function readNotification(Request $request){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'id'=>'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
         ], 422);
        }
        Notification::find($request->id)->update(['readed_on'=>now()]);

        return response()->json([
            'status' => 'success',
        ], 200);
    }

    public function reportuser(Request $request){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'reported_user_id'=>'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
         ], 422);
        }

        $report = new ReportUser;
        $report->reported_user_id =$request->reported_user_id;
        $report->reporting_user_id=$user->id;
        $report->report_reason = $request->report_reason;
        $report->save();


       return response()->json(['message' => 'User reported successfully'], 200);
    }
}
