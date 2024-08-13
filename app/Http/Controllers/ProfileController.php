<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserProfile;
use Illuminate\Support\Facades\File;
// use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Education;
use App\Models\ExtraCurricular;
use App\Models\Certification;
use App\Models\Work;
use App\Models\Users;
use Illuminate\Support\Facades\Validator;


class ProfileController extends Controller
{

    public function profile()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $profile = Users::where('id', $user->id)->withCount('followerCount')->withCount('followingCount')->with('UserProfile')->with('education')->with('work')->get();
        if (!$profile) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        return response()->json(['status' => 'success', 'data' =>  $profile], 200);
    }
    public function addDesignation(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'designation' => 'required',
            'purpose_to_use_app' => 'required|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $userProfile=UserProfile::where('user_id', $user->id)->first();
        if (!$userProfile) {
            // return response()->json([
            //     'message' => 'Designation Already Exists'
            // ], 422);
            $userProfile  = new UserProfile();
        }
       
        $userProfile->user_id = $user->id;
        $userProfile->designation = $request->designation;
        $userProfile->purpose_to_use_app = $request->purpose_to_use_app;
        $userProfile->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Designation Add Successfully',
        ], 201);
    }

    public function addDob(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'dob' => 'required|date|date_format:Y-m-d|before:' . now()->subYears(10)->toDateString(),
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        UserProfile::where('user_id', $user->id)
            ->update([
                'dob' => $request->dob
            ]);
        return response()->json([
            'status' => 'success',
            'message' => 'DOB Add Successfully',
        ], 201);
    }

    public function addAddres(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'addres' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required|max:6',
            'country' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        UserProfile::where('user_id', $user->id)
            ->update([
                'addres1' => $request->addres,
                'addres2' => $request->addres2,
                'city' => $request->city,
                'state' => $request->state,
                'zip' => $request->zip,
                'country' => $request->country
            ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Address Add Successfully',
        ], 201);
    }

    public function addEducation(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'data' => 'required|array'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $Edu = [];
        for ($i = 0; $i < count($request->data); $i++) {
            $Edu[] = [
                'user_id' => $user->id,
                'institution' => $request->data[$i]['institution'],
                'degree' => $request->data[$i]['degree'],
                'start_date' => $request->data[$i]['start_date'],
                'end_date' => $request->data[$i]['end_date'],
                'education_description' => $request->data[$i]['education_description']
            ];
        }
        Education::insert($Edu);
        return response()->json([
            'status' => 'success',
            'message' => 'Education Add Successfully',
        ], 201);
    }

    public function addProfessionalDetails(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            // 'type' => 'nullable|string',
            // 'bar_council_no' => 'string',
            // 'specialization' => 'nullable|string',
            // 'bar_council_id' => 'nullable|file|mimes:pdf,doc,docx',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $work = [];

        foreach ($request->data as $workData) {
            // Handle file upload if present
            $barCouncilIdPath = null;
            if (isset($workData['bar_council_id']) && $workData['bar_council_id'] instanceof \Illuminate\Http\UploadedFile) {
                $barCouncilIdPath = $workData['bar_council_id']->store('public/file/'.'/' . $user->id . '/' . date('Y') . '/' . date('m'));
            }
            for ($i = 0; $i < count($request->data); $i++) {
            $work[] = [
                'user_id' => $user->id,
                'title' => $request->data[$i]['title'],
                'bar_council_no' =>$request->data[$i]['bar_council_no'],
                'bar_council_id' =>$barCouncilIdPath,
                'specialization' =>$request->data[$i]['specialization'],
                // 'type' => $request->data[$i]['type'],
                'company_name' => $request->data[$i]['company_name'],
                // 'location' => $request->data[$i]['location'],
                'employment_mode' => $request->data[$i]['employment_mode'],
                'start_date' => $request->data[$i]['start_date'],
                'current_working' => $request->data[$i]['current_working'],
                'end_date' => $request->data[$i]['end_date'],
                'work_description' => $request->data[$i]['work_description']
            ];

            // dd($work);
        }
        Work::insert($work);
        return response()->json([
            'status' => 'success',
            'message' => 'Work Added Successfully',
            'work' =>$work
        ], 201);
        }
    }
    
    public function updateProfessionalDetails(Request $request){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'title' => 'required',
            'bar_council_no' => 'required|string',
            'bar_council_id' => 'nullable|file|mimes:pdf,doc,docx',
            'specialization' => 'nullable|string',
            'type' => 'required',
            'company_name' => 'required',
            'location' => 'required',
            'employment_mode' => 'required',
            'start_date' => 'required',
            'work_description' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $work = Work::find($request->id);
        if(!$work ){
            return response()->json([
                'status' => 'error',
                'errors' => 'work not found'
            ], 404);
        }
        
        $work->user_id = $user->id;
        $work->title = $request->title;
        $work->type = $request->type;
        $work->company_name = $request->company_name;
        $work->location = $request->location;
        $work->employment_mode = $request->employment_mode;
        $work->start_date = $request->start_date;
        $work->current_working = $request->current_working;
        $work->end_date = $request->end_date;
        $work->work_description = $request->work_description;


        $work->bar_council_no = $request->input('bar_council_no');
        if ($request->hasFile('bar_council_id')) {
            $path = $request->file('bar_council_id')->store('bar_council_docs');
            $work->bar_council_id = $path;
        }

        $work->specialization = $request->input('specialization');
        $work->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Work Updated Successfully',
        ], 201);
    }




    public function addBio(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'bio' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userProfile=UserProfile::where('user_id', $user->id)->first();
        if(!$userProfile){
            $userProfile=new UserProfile();
        }
        $userProfile->bio= $request->bio;
        $userProfile->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Bio Add Successfully',
        ], 201);
    }


    public function viewEducation()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $edu = Education::where('user_id', $user->id)->get();
        if (!$edu) {
            return response()->json(['message' => 'No Network'], 204);
        }
        return response()->json(['data' =>  $edu], 200);
    }

    public function updateEducation(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'institution' => 'required',
            'degree' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'education_description' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $eduction = Education::find($request->id);
        $eduction->user_id = $user->id;
        $eduction->institution = $request->institution;
        $eduction->degree = $request->degree;
        $eduction->start_date = $request->start_date;
        $eduction->end_date = $request->end_date;
        $eduction->education_description = $request->description;
        $eduction->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Updated Successfully',
        ], 201);
    }


    public function deleteEducation($id){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $eduction = Education::findOrFail($id);
        if($eduction->user_id!==$user->id ){
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $eduction->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Education Deleted Successfully',
        ], 201);
    }



    public function deleteProfessionalDetails($id){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $work = Work::findOrFail($id);
        if($work->user_id!==$user->id ){
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $work->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Work Deleted Successfully',
        ], 201);
    }

    public function ShowData()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $details = UserProfile::where('user_id', $user->id)->first();
        if(!$details){
            return response()->json(['message'=>"not found"], 404);
        }

        return response()->json(['data' =>  $details], 200);
    }

   
    public function addCertification(Request $request)
    {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'unauthorized'], 401);
            }
            $validator = Validator::make($request->all(), [
                'data' => 'required|array'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $certify = [];
            $data = $request->input('data'); 
            if (is_array($data)) {
                foreach ($data as $item) {
                    try {

                        $issueDate = Carbon::createFromFormat('m-Y', $item['issue_date'])->startOfMonth()->format('m-Y');
                        $expirationDate = isset($item['expiration_date']) 
                            ? Carbon::createFromFormat('m-Y', $item['expiration_date'])->endOfMonth()->format('m-Y')
                            : null;
        
                        $certify[] = [
                            'user_id' => $user->id,
                            'coursename' => $item['coursename'],
                            'institute' => $item['institute'],
                            'course_id' => $item['course_id'],
                            'enrollment_no' => $item['enrollment_no'],
                            'issue_date' => $issueDate,
                            'expiration_date' => $expirationDate,
                            'course_description' => $item['course_description'],
                        ];
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid date format',
                        ], 422);
                    }
                }
        
            Certification::insert($certify);
            return response()->json([
                'status' => 'success',
                'message' => 'Certification Added Successfully',
            ], 201);
        }

    }


    public function updateCertification(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'coursename' => 'required',
            'id' => 'required',
            'institute' => 'required',
            'course_id' => 'required',
            'enrollment_no' => 'required',
            'issue_date' => 'required',
            'expiration_date' => 'nullable',
            'course_description' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $certification = Certification::find($request->id);
        $certification->user_id = $user->id;
        $certification->coursename = $request->coursename;
        $certification->institute = $request->institute;
        $certification->course_id = $request->course_id;
        $certification->enrollment_no = $request->enrollment_no;
        $certification->issue_date = $request->issue_date;
        $certification->expiration_date = $request->expiration_date;
        $certification->course_description = $request->course_description;
        $certification->save();

        return response()->json(['status' => 'success',
        'message' => 'Certification updated successfully'], 200);
    }

    public function deleteCertification($id){
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $certify = Certification::findOrFail($id);
        if($certify->user_id!==$user->id ){
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $certify->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Certification Deleted Successfully',
        ], 201);
    }

    public function viewCertification()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        // Fetch unique certifications for the user
        $certify = Certification::where('user_id', $user->id)->distinct('certification_id') ->get();

        if ($certify->isEmpty()) {
            return response()->json(['message' => 'No certifications found'], 204);
        }

        return response()->json(['data' => $certify], 200);
    }

    public function addExtraActivity(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'skills' => 'nullable|string|max:255',
            'achievements' => 'nullable|string|max:255',
            'awards' => 'nullable|string|max:255',
            'license' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $activity = new ExtraCurricular;

        $activity->user_id = $user->id;
        $activity->skills = $request->input('skills');
        $activity->achievements = $request->input('achievements');
        $activity->awards = $request->input('awards');
        $activity->license = $request->input('license');
        $activity->publisher = $request->input('publisher');
        $activity->save();
        

        return response()->json([
            'status' => 'success',
            'message' => 'ExtraActivity Added Successfully',
            'data' => $activity
        ], 201);
    }

    public function viewExtraActivity()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $certify = ExtraCurricular::where('user_id', $user->id)->get();
        if (!$certify) {
            return response()->json(['message' => 'No Network'], 204);
        }
        return response()->json(['data' =>  $certify], 200);
    }

}
