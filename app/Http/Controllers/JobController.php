<?php

namespace App\Http\Controllers;

use App\Models\ApllidJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Jobs;
use App\Models\JobFeatures;
use Illuminate\Support\Facades\DB;
use App\Events\FriendRequestNotification;
use App\Mail\JobMialNotification;
use App\Mail\JobApplicantNotification;
use App\Mail\JobAdminNotification;
use App\Mail\MialNotification;
use App\Models\Notification;
use App\Models\JobReport;
use App\Mail\JobReport as ReportJob;
use Illuminate\Support\Facades\Mail;
use App\Models\Block;
use Exception;

class JobController extends Controller
{
    public function createJobs(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'role' => 'required',
            'titel' => 'required',
            'job_location' => 'required',
            'job_location_pin_code' => 'required',
            'job_description' => 'required',
            'company_name' => 'required',
            'longitude' => 'required',
            'latitude' =>'required'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // $newJob = Jobs::where('user_id','=',$user->id)->first();
        // if(!$newJob)
        // {
        //     $newJob = new Jobs();
        //    

        // }        

        // return $request->job_features;
        $newJob = new Jobs();
        $newJob->user_id = $user->id;
        $newJob->role = $request->role;
        $newJob->titel = $request->titel;
        $newJob->job_location = $request->job_location;
        $newJob->job_location_pin_code = $request->job_location_pin_code;
        $newJob->job_description = $request->job_description;
        $newJob->company_name = $request->company_name;
        $newJob->latitude = $request->latitude;
        $newJob->longitude = $request->longitude;
        $newJob->save();
        $newJobID = $newJob->id;

        if (isset($request->features)) {
            $JobFeatures = Jobs::find($newJobID);
            $JobFeatures->jobfeature()->attach($request->features);
            $JobFeatures->save();
        }
        $job=Jobs::where('id',$newJobID)->with('JobFeatures')->with('user')->with('work')->with('bookmark')->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Job Created Successfully',
            'data'=>$job
        ], 201);
    }

    public function Jobs()
    {

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $block_job =Block::where('blocked_by_id', $user->id)->pluck('block_userId');

        $jobs = Jobs::whereNotIn('user_id', $block_job)
        ->with('JobFeatures', 'applicant', 'user', 'work', 'bookmark')
        ->orderByDesc('id')
        ->paginate(10);        
        
        if ($jobs->isEmpty()) {
            return response()->json(['message' => 'Not Found'], 204);
        }
        return response()->json(['status' => 'success', 'data' =>  $jobs], 200);
    }

    public function myJob()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $jobs = Jobs::where('user_id', '=', $user->id)->with('JobFeatures')->withCount('applicant')->with("user")->with('user.UserProfile')->with('applicant.user')->with('work')->orderBy('id', 'DESC')->paginate(15);
        if ($jobs->isEmpty()) {
            return response()->json(['message' => 'Not Found'], 204);
        }
        return response()->json(['status' => 'success', 'data' =>  $jobs], 200);
    }


    public function Job($id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $jobs = Jobs::where('id', $id)->with('JobFeatures')->withCount('applicant')->with('user')->with('work')->with('applicant')->with('bookmark')->get();

        if ($jobs->isEmpty()) {
            return response()->json(['message' => 'Not Found'], 204);
        }
        return response()->json(['status' => 'success', 'data' =>  $jobs], 200);
    }

    public function JobsNearsYou()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $jobs = Jobs::with('JobFeatures')->withCount('applicant')->with('user')->with('work')->with('applicant')->paginate(15);

        foreach ($jobs as $job) {
            \Log::info('Job ID: ' . $job->id . ' Features: ' . $job->JobFeatures);
        }
        if ($jobs->isEmpty()) {
            return response()->json(['message' => 'Not Found'], 204);
        }
        return response()->json(['status' => 'success', 'data' =>  $jobs], 200);
    }

    public function apply_job(Request $request)
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
        $is_applied = ApllidJob::where('user_id', $user->id)->where('job_id', $request->job_id)->first();
        if ($is_applied) {
            return response()->json([
                'message' => 'already applied',
            ], 200);
        }
        $Applied = new ApllidJob();
        $Applied->user_id = $user->id;
        $Applied->job_id = $request->job_id;
        $Applied->save();

        $jobOwner=Jobs::with('user')->find($request->job_id);
        $notification=new Notification();
        $notification->post_type='Job';
        $notification->content='Job Application';
        $notification->owner_id=$jobOwner->user_id;
        $notification->user_id=$user->id;
        $notification->action_on= $jobOwner->id;
        $notification->save();
        $data= Notification::with('user')->find($notification->id);
        broadcast(new FriendRequestNotification($data,$user->id,$jobOwner->user_id))->toOthers();
          
        
            Mail::to("legumbook@gmail.com")->send(new JobAdminNotification($jobOwner));
            Mail::to($user->email)->send(new JobApplicantNotification($jobOwner));
           // Mail::to($user->email)->send(new JobMialNotification( "Thank you for your recent application for the ".$jobOwner->titel." position at ".$jobOwner->company_name.".We appreciate your interest in joining our team.Our hiring team is currently reviewing all applications, and we will be in touch with you soon regarding the next steps in the hiring process."));

            Mail::to($jobOwner->user->email)->send(new JobMialNotification($jobOwner));


        return response()->json([
            'status' => 'success',
            'message' => 'Job Applied Successfully',
        ], 201);
    }


    public function DeleteJob($id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $job = Jobs::findOrFail($id);
        if ($job->user_id !== $user->id) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $job->delete();
        return response()->json([
            $job
        ], 201);
    }
    public function updateJobs(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            // 'job_id ' => 'required',
            

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        // return $request->job_features;
        $fieldsToUpdate = $request->only([
            'titel',
            'job_location',
            'role',
            'job_location_pin_code',
            'job_description',
            'company_name',
        ]);
        
        Jobs::where('id',$request->job_id)->update($fieldsToUpdate);
        // return $request->remove_features[0]['id'];
            // delete job features 
        if (isset($request->remove_features)) {
          
            for ($i = 0; $i < count($request->remove_features); $i++) {

                $imageName =  $request->remove_features[$i]['id'];
                //  return $imageName;
                
                JobFeatures::where('id', $imageName)->delete(); 
            }
            // return $imageName;
        }
        // add in job_feature 
        if (isset($request->new_features)) {
            $imageData = [];
            for ($i = 0; $i < count($request->new_features); $i++) {

                $imageName = $request->new_features[$i];
                // return $imageName;
                
                $imageData[] = [
                    'job_id' => $request->job_id,
                    'features' => $imageName,
                ];
            }
            // return $imageData;
            JobFeatures::insert($imageData);
        }

        $updatedJob=Jobs::where('id',$request->job_id)->with('JobFeatures')->with("user")->first();


        return response()->json([
            'status' => 'success',
            'message' => 'Job Update Successfully',
            'job'=> $updatedJob,
        ], 201);
    }

    public function reportedJob(Request $request){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'reported_job_id'=>'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
         ], 422);
        }

        $report = new JobReport; 
        $report->reported_job_id =$request->reported_job_id;
        $report->reporting_user_id=$user->id;
        $report->job_report_reason = $request->report_reason;
        // $report->$request->{now()->toDateString('d-m-y')};
        $report->save();
        $reportData=JobReport::where("id",$report->id)->with('user')->with("job")->first();
        //  return $reportData->user;
        Mail::to("legumbook@gmail.com")->send(new ReportJob($reportData));

       return response()->json(['message' => 'Job reported successfully'], 200);
    }


    public function addJoblocation(Request $request)
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


        $loc = Jobs::where('user_id','=',$user->id)->first();

        if(!$loc)
        {
            $loc = new Jobs();
            $loc->user_id = $user->id;
        }
        
        $loc->latitude = $request->latitude;
        $loc->longitude = $request->longitude;
        $loc->save();

        return response()->json(['message' => 'Location added successfully'], 201);
    }

}
