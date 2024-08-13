<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\GuestEmail;
use App\Mail\MialNotification; 
use App\Models\Notification ;

use Illuminate\Support\Facades\Validator;
use App\Event\FrindRequestNotification;
use App\Models\GuestQuestion;

class GuestController extends Controller
{
    public function Question(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email'=>'required',
            'question'=>'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
       

    //    $question=GuestQuestion::with('user')->find($request->id)
       $question= new GuestQuestion;
       $question->name=$request->name;
       $question->email=$request->email;
       $question->question=$request->question;
       $question->save();
       
       

       try{
            Mail::to($request->email)->send(new GuestEmail($question));

       
            Mail::to(env('MAIL_FROM_ADDRESS'))->send(new GuestEmail($question));

        }catch(Exception $e){
        }
      
            return response()->json([
                'status' => 'sucess',
                'message send Successfully'
            ], 200);  
        }
    }
