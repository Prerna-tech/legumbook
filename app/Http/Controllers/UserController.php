<?php

namespace App\Http\Controllers;

use App\Models\bookmark;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Users;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Network;
use App\Models\ForgetPassword;
use App\Models\Jobs;
use App\Models\Otp;
use App\Models\UserProfile;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; 
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Craftsys\Msg91\Facade\Msg91;

class UserController extends Controller
{

    public function sendOTP(Request $request){
        $validator = Validator::make($request->all(), [
            "phone" => "required_without:email",
            "email" => "required_without:phone|email",
            "type" => "required",
        
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        if($request->phone){
          $sender="91".$request->phone;  
          if($request->type=="signup"){
            $is_user=User::where('phone',$sender)->first();
            if($is_user){
            return response()->json(['errors'=>'Phone already exists'],422);
            }
          }
         
        }
        if($request->email){
            $sender=$request->email; 
            if($request->type=="signup"){
                $is_user=User::where('email',$sender)->first();
                if($is_user){
                return response()->json(['errors'=>'Email already exists'],422);
                }
            }
        }
        $findOtp=Otp::where('phone_or_email', $sender)->first();

        if(!$findOtp){
            $otp = rand(1000, 9999);
            $addOtp=new Otp;
            $addOtp->otp=$otp;
            $addOtp->phone_or_email= $sender;
            $addOtp->save();
        }elseif($findOtp->created_at< now()->subMinutes(2)){
            $findOtp->delete();
            $otp = rand(1000, 9999);
            $addOtp=new Otp;
            $addOtp->otp=$otp;
            $addOtp->phone_or_email=$sender;
            $addOtp->save();
        }else{
            $otp= $findOtp->otp;
            $findOtp->created_at=now();
            $findOtp->save();
        }
        
       if($request->phone){
        Msg91::sms()->to('91'.$request->phone)->flow('65f973b8d6fc053e301080f3')->variable('OTP', $otp)->send();
       }else{
         Mail::raw("Greetings from LnL Network,

         Your Legumbook email verification OTP is: $otp", function($message) use ($request) {
            $message->to($request->email)
                    ->subject('Your One Time Password');
        });
       }
       
      return response()->json(['msg'=>'OTP sent successfully'], 201);
    }

    public function verifyOtp($sender, $otp){
       $hasOtp= Otp::where('phone_or_email',$sender)->where('otp',$otp)->first();
       if($hasOtp){
          $is_Ex=$hasOtp->created_at>now()->subMinutes(2);
        if($is_Ex){
            return $hasOtp;
        }
            
       }
    }

    public function register(Request $request)
    {

       // return $request->phone;
        if($request->phone){
            $validator = Validator::make($request->all(), [
                'phone' => 'required|unique:users|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' =>$validator->errors()
                ], 422);
            }
        }
        if($request->email){
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users|max:255',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' =>$validator->errors()->first('email')
                ], 422);
            }
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255', 
            'password'  => 'required|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' =>$validator->errors()
            ], 422);
        }
        
        $userPhone=null;
        if($request->phone){
            $userPhone='91'.$request->phone;
            $isVerifyed=$this->verifyOtp($userPhone,$request->otp);
            if(!$isVerifyed){
                return response()->json(['errors'=>'please enter valid OTP'], 422);
            }
            Otp::destroy($isVerifyed->id);
            $credentials = $request->only('phone', 'password');
        }
        if($request->email){
            $isVerifyed=$this->verifyOtp($request->email,$request->otp);
            if(!$isVerifyed){
                return response()->json(['errors'=>'please enter valid OTP'], 422);
            }
            Otp::destroy($isVerifyed->id);
            $credentials = $request->only('email', 'password');
        }
        $user  = new Users();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone= $userPhone;
        $user->password = bcrypt($request->password);
        $user->save();
       
        
        Auth::attempt($credentials);
        $token = $user->createToken($user->name)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Registered Successfully',
            'user' => $user,
            'token' => $token
        ], 201);
        // return response()->json(['status' => 'success', 'message' => 'Registered Successfully'], 201);
    }

    /**
     * login the user
     */
    public function login(Request $request)
    {

       
        $validator = Validator::make($request->all(), [
            'email' => 'required_if:phone,null|exists:users',
            'phone' => 'required_if:email,null|numeric',
            'password' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => "Invalid Credentials"
            ], 422);
        }
       
        // if($request->phone){
        //     $isVerifyed=$this->verifyOtp($request->phone,$request->otp);
        //     if(!$isVerifyed){
        //         return response()->json(['please enter valide OTP'], 404);
        //     }
        //     Otp::destroy($isVerifyed);
        // }
        // if($request->email){
        //     $isVerifyed=$this->verifyOtp($request->email,$request->otp);
        //     if(!$isVerifyed){
        //         return response()->json(['please enter valide OTP'], 404);
        //     }
        //     Otp::destroy($isVerifyed);
        // }


      
      if($request->email){
        $user = Users::where('email', $request->email)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'errors' => 'Invalid Credentials'
            ], 422);
        }
        $credentials = $request->only('email', 'password');
      }else{
       // $credentials = $request->only('phone', 'password');
       if(strlen($request->phone)<= 10){
        $phone="91".$request->phone;
        $user = Users::where('phone', $phone)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'errors' => 'Invalid Credentials'
            ], 422);
        }
       }else{
        $phone=$request->phone;
        $user = Users::where('phone', $phone)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'errors' => 'Invalid Credentials'
            ], 422);
        }
        
       }
       $credentials =['phone'=>$phone,'password'=>$request->password];
      
      }

        Auth::attempt($credentials);
        $user->tokens()->delete();
        $token = $user->createToken($user->name)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Logged In Successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    
    public function logout(Request $request)
    {
        $request->user()->tokens->each(function ($token, $key) {
            $token->delete();
        });
        //request()->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => 'success',
            'msg' => 'Logged out Successfully.'
        ], 200);
    }



    public function getUser()
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }


        // Constructing the query using Laravel's query builder
        $query = User::select(
                'u.id',
                'u.name',
                'u.image',
                'loc.longitude',
                'loc.latitude',
                'work.user_id',
                'work.title',
                'work.location',
                'work.company_name',
                DB::raw('COUNT(f1.receiver_id) as mutual_friends')
            )
            ->from('users as u')
            ->leftJoin('network as f1', 'u.id', '=', 'f1.sender_id')
            ->leftJoin('network as f2', 'u.id', '=', 'f2.receiver_id')
            ->leftJoin('work', 'u.id', '=', 'work.user_id')
            ->leftJoin('user_profile as loc', 'u.id', '=', 'loc.user_id')
            ->where('u.id', '!=', $user->id)
            ->groupBy('u.id', 'u.name', 'u.image', 'work.user_id', 'loc.longitude', 'loc.latitude', 'work.title', 'work.location', 'work.company_name');

        // Paginate the query results with 15 items per page
        $result = $query->paginate(15);

        if ($result->isEmpty()) {
            return response()->json(['message' => 'No Network'], 204);
        }

        return response()->json(['people_you_may_know' => $result], 200);
    }

    // public function updateUserDetails(Request $request, $id)
    // {
    //     // Find the user by ID
    //     $user = Users::find($id);
        
    //     if (!$user) {
    //         return response()->json(['error' => 'User not found'], 404);
    //     }

       
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'sometimes|string|max:255',
    //         'gender' => 'sometimes|integer|in:0,1,2',
    //         'dob' => 'sometimes|date',

    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 422);
    //     }

        
    //     $user->update($request->only(['name', 'gender']));

    //     // Update user profile details
    //     if ($request->has('dob')) {
    //         $user->UserProfile()->update(['dob' => $request->dob]);
    //     }

    //     return response()->json(['status' => 'success',
    //     'user'=>$user,
    //     'message' =>'UserDetails Updated Successfully',
    //     201]);
    // }

    public function updateUserDetails(Request $request, $id)
    {
        $user = Users::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|integer|in:0,1,2',
            'dob' => 'sometimes|date',
            'email' => 'sometimes|email',
            'phone' => 'sometimes|numeric|digits:10',
            'otp' => 'required_if:email,null|required_if:mobile,null',
            'twitter' => 'nullable|url',
            'facebook' => 'nullable|url',
            'instagram' => 'nullable|url',
            'linkedin' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Handle OTP verification
        $otp = $request->input('otp');
        if ($otp) {
            $this->forgetOtpVerify($request);
        }

        $user->update($request->only(['name', 'gender']));

        if ($request->has('dob','twitter','facebook','instagram','linkedin')) {
            $user->UserProfile()->update(['twitter' => $request->twitter]);
            $user->UserProfile()->update(['linkedin' => $request->linkedin]);
            $user->UserProfile()->update(['facebook' => $request->facebook]);
            $user->UserProfile()->update(['instagram' => $request->instagram]);
        }

        if ($request->has('email') && $this->forgetOtpVerify($request, $user, 'email')) {
            $user->update(['email' => $request->email]);
        }

        if ($request->has('phone') && $this->forgetOtpVerify($request, $user, 'phone')) {
            $user->update(['phone' => $request->phone]);
        }

        $user->load('userProfile');

        return response()->json([
            'status' => 'success',
            'user' => $user,
            // 'user_profile' => $user->UserProfile,
            'message' => 'User details updated successfully'
        ], 200);
    }





    public function updateUserImage(Request $request)
    {

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'image' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        File::delete($user->image);
        $path1 = storage_path('app/public/image');
        if (!File::exists($path1)) {
            File::makeDirectory($path1);
        }
        $path2 = storage_path('app/public/image/profile');
        if (!File::exists($path2)) {
            File::makeDirectory($path2);
        }
        $path = storage_path('app/public/image/profile/' . $user->id);
        if (!File::exists($path)) {
            File::makeDirectory($path);
        }
        $imageName = $user->id . time() . '.' . $request->image->extension();
        $fullUrl = 'storage/image/profile/' . $user->id . '/' . $imageName;;
        $request->image->move($path, $imageName);
        Users::where('id', $user->id)
            ->update([
                'image' => $fullUrl
            ]);
        return response()->json([
            'status' => 'success',
            'image'=>$fullUrl,
            'message' =>'Image Update Successfully',
        ], 201);
    }



    public function viewUser($userID){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        if(!isset($userID)){
            return response()->json(['message' => 'unauthorized'], 204);
        }
        $userWithDetail=Users::where('id',$userID)->withCount('postCount')->withCount('followerCount')->withCount('followingCount')->with('UserProfile')->with('education')->with('work')->with('certifications')->with('ExtraActivity')->first();
        $friend1=Network::where('sender_id','=',$userID)->where('receiver_id','=',$user->id)->first();
        $friend2=Network::where('receiver_id','=',$userID)->where('sender_id','=',$user->id)->first();
        if(isset($friend2)){
            $frinds=$friend2;
        }elseif(isset($friend1)){
            $frinds=$friend1;
        }else{
            $frinds=null;
        }

        return response()->json([
            'status' => 'success',
            'data' =>$userWithDetail,
            'is_friend'=> $frinds,
        ], 200);
    }

    


    public function createbookmark(Request $request )
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            
            'job_id' => 'required',
            
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        // return $request->job_features;
        $newbookmark=bookmark::where('user_id','=',$user->id)->where('job_id','=',$request->job_id)->exists();
        
        
        if($newbookmark)
        {
            bookmark::where('user_id','=',$user->id)->where('job_id','=',$request->job_id)->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Bookmark Deleted  Successfully',
            ], 201);

        }
        $newJob = new bookmark();
        $newJob->user_id = $user->id;
        $newJob->job_id = $request->job_id;
        
        $newJob->save();
        
       


        return response()->json([
            'status' => 'success',
            'message' => 'Bookmark Added Successfully',
        ], 201);
    }
    public function bookmark()
    {
        
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

       
        $bookmark = bookmark::where('user_id','=',$user->id)->with('job')->with('job.JobFeatures')->with('job.user')->orderBy('id', 'DESC')->paginate(15);
        if ($bookmark->isEmpty()) {
            return response()->json(['message' => 'Not Found'], 204);
        }
        return response()->json(['status' => 'success', 'data' =>  $bookmark], 200);
    }




    // forgetPassword 
    public function existingUserOpt(Request $request )
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_if:phone,null|email|max:255',
            'phone' => 'required_if:email,null|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        if($request->phone){
          $sender="91".$request->phone; 
          $is_user=User::where('phone',$sender)->first();
          
        }
        if($request->email){
            $sender=$request->email; 
            $is_user=User::where('email',$request->email)->first();
           
        }
        if(!$is_user){
           return response()->json(['msg'=>'User not found'], 404);
        }
        $findOtp=Otp::where('phone_or_email', $sender)->first();

        if(!$findOtp){
            $otp = rand(1000, 9999);
            $addOtp=new Otp;
            $addOtp->otp=$otp;
            $addOtp->phone_or_email= $sender;
            $addOtp->save();
        }elseif($findOtp->created_at< now()->subMinutes(2)){
            $findOtp->delete();
            $otp = rand(1000, 9999);
            $addOtp=new Otp;
            $addOtp->otp=$otp;
            $addOtp->phone_or_email=$sender;
            $addOtp->save();
        }else{
            $otp= $findOtp->otp;
            $findOtp->created_at=now();
            $findOtp->save();
        }
        
       if($request->phone){
       Msg91::sms()->to('91'.$request->phone)->flow('65f973b8d6fc053e301080f3')->variable('OTP', $otp)->send();
        //Msg91::sms()->to('91'.$request->phone)->flow('660a76cfd6fc0558d4363782')->variable('OTP', $otp)->send();
       }else{
         Mail::raw("Greetings from LnL Network,

         Your Legumbook email verification OTP is: $otp", function($message) use ($request) {
            $message->to($request->email)
                    ->subject('Your One Time Password');
        });
       }
       
      return response()->json(['msg'=>'OTP sent successfully'], 201);
    }
    public function forgetOtpVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            
            'email' => 'required_if:phone,null|email|max:255',
            'phone' => 'required_if:email,null|numeric',
            'otp'  => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $sender=null;
        if($request->email){
            $sender=$request->email;
        }
        if($request->phone){
            $sender='91'.$request->phone;
        }
        $hasOtp= Otp::where('phone_or_email',$sender)->where('otp',$request->otp)->first();
        if($hasOtp){
           $hasOtp->is_verify=1;
           $hasOtp->save();
           $is_Ex=$hasOtp->created_at>now()->subMinutes(2);
         if($is_Ex){
            return response()->json([
                'msg' => "OTP Verified "
            ], 200);
         }
             
        }
        return response()->json([
            'msg' => "Enter valid OTP"
        ], 404);
        
    }
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:phone|email|max:255',
            'phone' => 'required_without:email|numeric',
            'password' => 'required|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'msg' => $validator->errors()
            ], 422);
        }
       if($request->password!=$request->confirmPassword){
        return response()->json([
            'msg' => "Password not Match"
        ], 422);
       }
        if($request->email){
            
            $isVerifyed= Otp::where('phone_or_email',$request->email)->where('is_verify',1)->first();
            if(!$isVerifyed){
                return response()->json([
                    'status' => 'error',
                    'msg' => "Enter valid OTP"
                ], 404);
            }
            $chngeUser=Users::where('email', $request->email)->first();
           
                
        }
        
 
        if($request->phone){
            
            $isVerifyed= Otp::where('phone_or_email','91'.$request->phone)->where('is_verify',1)->first();
            
            if(!$isVerifyed){
                return response()->json([
                    'status' => 'error',
                    'msg' => "Enter valid OTP"
                ], 404);
            }
            $chngeUser= Users::where('phone', '91'.$request->phone)->first();
            
        }
        if(!$chngeUser){
            return response()->json([
                'status' => 'error',
                'msg' => "User not found"
            ], 404);
        }
        $chngeUser->password=bcrypt($request->password);
        $chngeUser->save();
        Otp::destroy($isVerifyed->id);
        return response()->json([
            'status' => 'success',
            'msg' => 'Password Changed  Successfully',
           
        ], 200);
        
    }



    public function googleLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'google_token' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Users::where('email',$request->email)->first();
        if(!$user){
            $user  = new Users();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->google_token);
            $user->google_token=$request->google_token;
            $user->image= $request->image;
            $user->save();
        }
        Auth::login($user);
        $token = $user->createToken($user->name)->plainTextToken;
        return response()->json([
            'status' => 'success',
            'message' => 'Logged In Successfully',
            'user' => $user,
            'token' => $token
        ], 200);
    }


    public function search($search){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $users = Users::where('name', 'like', '%' . $search . '%')->get();
        $jobs=Jobs::where('role', 'like', '%' . $search . '%')->orWhere('titel', 'like', '%' . $search . '%')->
        orWhere('job_description', 'like', '%' . $search . '%')->orWhere('company_name', 'like', '%' . $search . '%')->get();
        $data=['user'=>$users,'job'=>$jobs];
         return response( $data );
    }
    public function search_jobs($search){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        // $users = Users::where('name', 'like', '%' . $search . '%')->get();
        $jobs=Jobs::where('role', 'like', '%' . $search . '%')->orWhere('titel', 'like', '%' . $search . '%')->
        orWhere('job_description', 'like', '%' . $search . '%')->orWhere('company_name', 'like', '%' . $search . '%')
        ->with('JobFeatures')->withCount('applicant')->with('user')->with('work')->with('bookmark')->get();
        $data=['job'=>$jobs];
         return response( $data );
    }

    public function addlocation(Request $request)
    {

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        
        $validator = Validator::make($request->all(), [
            
            'longitude' => 'required',
            'latitude' =>'required'
            
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }


        $loc = UserProfile::where('user_id','=',$user->id)->first();

        if(!$loc)
        {
            $loc = new UserProfile();
            $loc->user_id = $user->id;
        }
        
        $loc->latitude = $request->latitude;
        $loc->longitude = $request->longitude;
        $loc->save();

        return response()->json(['message' => 'Location added successfully'], 201);
    }
}
