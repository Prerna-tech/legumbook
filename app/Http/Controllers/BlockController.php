<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Block; 
use Illuminate\Support\Facades\Validator;

class BlockController extends Controller
{
    //
    public function blockUser(Request $request){

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'block_userId'=>'required'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
         ], 422);
        }

        $block= new Block;
        $block->block_userId =$request->block_userId;
        $block->blocked_by_id = $user->id;
        $block->save();
      
        return response()->json(['message' => 'User Blocked successfully'], 200);
        
    }

    public function showBlockUser(Request $request){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        
        
        $block_details = Block::where('blocked_by_id', $user->id)->get();
        
        // Check if the block_details is empty
        if(!$block_details){
            return response()->json(['message'=>"No users in block list"], 404);
        }

        return response()->json(['data' =>  $block_details], 200);
    }
}
