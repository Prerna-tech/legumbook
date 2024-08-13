<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use Dusterio\LinkPreview\Client;

use App\Models\Post;
use App\Models\LikePost;
use App\Models\PostComment;
use App\Models\HashTag;
use App\Models\Interested;
use App\Models\PostImage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Events\FriendRequestNotification;
use App\Mail\PostMialNotification;
use App\Models\Notification;
use App\Models\UserProfile;
use App\Models\Users;
use App\Notifications\PushNotification;
use App\Nova\event;
use App\Mail\PostReport as ReportPost;
use App\Models\PostReport;
use Illuminate\Support\Facades\Mail;
use App\Models\Block;
use Exception;
// use GuzzleHttp\Client;
use Wa72\HtmlPageDom\HtmlPageCrawler;


class PostController extends Controller
{

    public function Post()
    {
        // Check if the user is authenticated
        // $user = auth()->user();

        

        // $post = Post::with('user')->with('UserProfile')->with('work')->with('hashtags')->with('PostImage')->withCount('like')->with('comment.user')->with('likedBy')->withCount('interested')->with('is_interested')->orderBy('id', 'DESC')->paginate(10);

        $post = Post::with('user')->with('UserProfile')->with('user.work')->with('PostImage')->withCount('like')->with('comment.user')->with('likedBy')->withCount('interested')->with('is_interested')->orderBy('id', 'DESC')->paginate(15);
        // var_dump($post);
        if (!$post) {
            return response()->json(['message' => 'Not Found'], 204);
        }
        
        // if (!$user && $post->currentPage() > 1) {
        //     return response()->json(['message' => 'Please log in to view more posts.'], 401);
        // } 
        // Return the response with the posts and authentication status
        return response()->json([
            'status' => 'success',
            'data' => $post,
            // 'authenticated' => !is_null($user),
        ], 200);
    }

    public function PostById($id)
    {
        // $user = auth()->user();
        // if (!$user) {
        //     return response()->json(['message' => 'unauthorized'], 401);
        // }

        $post = Post::where('id', $id)->with('user')->with('UserProfile')->with('hashtags')->with('PostImage')->withCount('like')->with('comment.user')->with('likedBy')->withCount('interested')->with('is_interested')->orderBy('id', 'DESC')->paginate(15);

        if ($post->isEmpty()) {
            return response()->json(['message' => 'Not Found'], 204);
        }
        return response()->json(['status' => 'success', 'data' =>  $post], 200);
    }

    public function myPost()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $post = Post::where('user_id', $user->id)->with('user')->with('UserProfile')->with('hashtags')->withCount('like')->with('PostImage')->with('comment.user')->with('likedBy')->withCount('interested')->with('interested')->orderBy('id', 'DESC')->paginate(15);
        if (!$post) {
            return response()->json(['message' => 'Not Found'], 204);
        }
        return response()->json(['status' => 'success', 'data' =>  $post], 200);
    }
    
    

    public function createPost(Request $request)
    {

        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            // 'image' => 'required',
            'post_description' => 'nullable'

    
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $storePath = storage_path('app/public/image/');
        if (!File::exists($storePath)) {
            File::makeDirectory($storePath);
        }
        $path = storage_path('app/public/image/' . $user->id . date('Y') . date('m'));
        if (!File::exists($path)) {
            File::makeDirectory($path);
        }
        // $request->description
        // return $request->data->getClientOriginalName();
    
        $newPost = new Post();
        $newPost->user_id = $user->id;
        $newPost->image = 'null';
        $newPost->event_title = $request->event_title;
        if($request->link){
            $dis='.';
        }else{
            $dis=$request->post_description;
        }
        
        $newPost->post_description = $dis;
        $newPost->event_start_at = $request->event_start_at;
        $newPost->event_end_at = $request->event_end_at;
        // $newPost->library_title = $request->library_title;
        $newPost->library_link = $request->library_link;
        $newPost->link = $request->link;
        $newPost->save();
        $newPostID = $newPost->id;


        if (isset($request->data)) {
            $imageData = [];
            for ($i = 0; $i < count($request->data); $i++) {

                $imageName = $user->id . $i . time() . '.' . $request->data[$i]->extension();
                 $ext=$request->data[$i]->extension();
                 $orgName=$request->file('data.'.$i)->getClientOriginalName();
                $fullUrl = 'storage/image/' . $user->id . date('Y') . date('m') . '/' . $imageName;
                $request->data[$i]->move($path, $imageName);
                $imageData[] = [
                    'post_id' => $newPostID,
                    'image' => $fullUrl,
                    'name'=> $orgName,
                    'extension'=>$ext
                ];
            }
            // return $imageData;
            PostImage::insert($imageData);
        }

        // if (isset($request->tag)) {
        //     $hashTag = Post::find($newPostID);
        //     $hashTag->hashtag()->attach($request->tag);
        //     $hashTag->save();
        // }

        if (isset($request->tag) && is_array($request->tag)) {
            foreach ($request->tag as $tag) {
                $tag = ltrim($tag, '#');
                $existingTag = Hashtag::where('tag', $tag)->first();
                if (!$existingTag) {
                    $newTag = Hashtag::create(['tag' => $tag]);
                    $newPost->hashtags()->attach($newTag->id);
                } else {
                    $newPost->hashtags()->attach($existingTag->id);
                }
            }
        }

        $postDtl=Post::where('id',$newPost->id)->with('user')->with('UserProfile')->with('hashtags')->with('PostImage')->withCount('like')->with('comment.user')->with('likedBy')->withCount('interested')->with('is_interested')->first();
        return response()->json([
            'status' => 'success',
            'message' => 'Post Created Successfully',
            'data'=>$postDtl
        ], 201);
    }


    public function likePost(Request $request)
    {
        $user = auth()->user();
       // $user->notify(new PushNotification());
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        if (LikePost::where('user_id', $user->id)->where('post_id', $request->post_id)->exists()) {
            LikePost::where('user_id', $user->id)->where('post_id', $request->post_id)->delete();
            $postOwner=Post::with('user')->find( $request->post_id);
           
            $notification=new Notification;
            $notification->post_type='POST';
            $notification->content='Remove Like Your Post';
            $notification->owner_id=$postOwner->user_id;
            $notification->user_id=$user->id;
            $notification->action_on=$postOwner->id;
            $notification->save();
            $data= Notification::with('user')->find($notification->id);
            event(new FriendRequestNotification($data,$user->id,$postOwner->user_id));
            return response()->json([
                'status' => 'success',
                'message' => 'Remove Like Successfully',
            ], 201);
        }
        $Like = new LikePost();
        $Like->user_id = $user->id;
        $Like->post_id = $request->post_id;
        $Like->save();
        $postOwner=Post::find( $request->post_id);
       
        $notification=new Notification;
        $notification->post_type='POST';
        $notification->content='Like Your Post';
        $notification->owner_id=$postOwner->user_id;
        $notification->user_id=$user->id;
        $notification->action_on=$postOwner->id;
        $notification->save();
        $data= Notification::with('user')->find($notification->id);
        broadcast(new FriendRequestNotification($data,$user->id,$postOwner->user_id))->toOthers();
        try{
        //    Mail::to($postOwner->user->email)->send(new  PostMialNotification($user->name. ' like your post  on Legumbook, a professional networking platform for the legal community.'));
        }catch(Exception $e){
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Like Successfully',
        ], 201);
    } 

   
    public function PostComment(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
            'comment' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        if (!Post::where('id', $request->post_id)->exists()) {
            return response()->json([
                'message' => 'Post Does Not Exists'
            ], 422);
        }
        $Comment = new PostComment();
        $Comment->post_id = $request->post_id;
        $Comment->user_id = $user->id;
        $Comment->comment = $request->comment;
        $Comment->save();
        $postOwner=Post::with('user')->find( $request->post_id);
       
        $notification=new Notification;
        $notification->post_type='POST';
        $notification->content='Comment On Your Post';
        $notification->owner_id=$postOwner->user_id;
        $notification->user_id=$user->id;
        $notification->action_on=$postOwner->id;
        $notification->save();
       
        $data= Notification::with('user')->find($notification->id);
        broadcast(new FriendRequestNotification($data,$user->id,$postOwner->user_id))->toOthers();

        try{
            Mail::to($postOwner->user->email)->send(new  PostMialNotification($postOwner->user->name. ' commenton your post  on Legumbook, a professional networking platform for the legal community.'));
        }catch(Exception $e){
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Comment Successfully',
        ], 201);
    }



    public function editComment(Request $request)
    {
      $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'id'  =>'required',
            'comment' => 'required|string|max:255',
        ]);
        // dd($comment);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $comment = PostComment::find($request->id);
        if(! $comment){
          return response()->json(['message' => 'comment not found',], 404);
        }
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json(['message' => 'Comment updated successfully', 'comment' => $comment], 200);
    }

    public function deleteComment($id)
    {
        $user = auth()->user();
        
        $comment = PostComment::find($id);
        // dd($comment);
        if (! $comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        
        if ($comment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully'], 200);
        
    }

    public function commentOnComment(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
            'comment' => 'required',
            'parent_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $newComment = new PostComment();
        $newComment->post_id = $request->post_id;
        $newComment->user_id = $user->id;
        $newComment->comment = $request->comment;
        $newComment->parent_id = $request->parent_id; 
        $newComment->save();

        $parentComment = PostComment::with('user')->find($request->parent_id);
        $notification = new Notification();
        $notification->post_type = 'COMMENT';
        $notification->content = 'Replied to your comment';
        $notification->owner_id = $parentComment->user_id;
        $notification->user_id = $user->id;
        $notification->action_on = $parentComment->id;
        $notification->save();

        $data = Notification::with('user')->find($notification->id);
        broadcast(new FriendRequestNotification($data, $user->id, $parentComment->user_id))->toOthers();

        try {
            // Optionally send an email notification
            // Mail::to($parentComment->user->email)->send(new PostMailNotification($user->name . ' replied to your comment.'));
        } catch (Exception $e) {
            // Handle exception if needed
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Comment successfully posted',
            'data' => $newComment
        ], 201);
    } 



    public function editPost(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'post_description' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $path = storage_path('app/public/image/' . $user->id . date('Y') . date('m'));
        if (!File::exists($path)) {
            File::makeDirectory($path);
        }
        

        //$post = Post::find($request->id);
       // return $post;
       $updateData = $request->only([
        'post_description',
        'event_title',
        'event_start_at',
        'event_end_at',
        'library_title',
        'library_link',
    ]);

      
    
    Post::where('id', $request->id)->update($updateData);
    // return $updateData;
    
        // $post->update($updateData);
        // $post->save();

        if (isset($request->old_image)) {
          
            for ($i = 0; $i < count($request->old_image); $i++) {
                
                $id=  $request->old_image[$i];
                $postimg= PostImage::where('id', $id)->first();
                if (file_exists($postimg)) {
                    File::delete($postimg);
                 
                }
                $postimg->delete(); 
                // PostImage::where('image', $postimg)->delete(); 
            }
            
        }
       

        if (isset($request->data)) {
            $imageData = [];
            for ($i = 0; $i < count($request->data); $i++) {

                $imageName = $user->id . $i . time() . '.' . $request->data[$i]->extension();
                $ext=$request->data[$i]->extension();
                $orgName=$request->file('data.'.$i)->getClientOriginalName();
                $fullUrl = 'storage/image/' . $user->id . date('Y') . date('m') . '/' . $imageName;
                $request->data[$i]->move($path, $imageName);
                $imageData[] = [
                    'post_id' => $request->id,
                    'image' => $fullUrl,
                    'name'=>$orgName,
                    'extension'=>$ext
                ];
            }
            
            PostImage::insert($imageData);
        }


        $updatedPost=Post::where('id',$request->id)->with('PostImage')->with("user")->first();

        return response()->json([
            'status' => 'success',
            'message' => 'post updated successfully',
            'data' =>$updatedPost,
        ], 201);
    }

    public function StoreImage(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'post_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }


        $newPost = new PostImage();
        $newPostID = $newPost->id;
        $newPost->image = 'null';
        $newPost->save();

        return $newPost;


        $path = storage_path('app/public/image/' . $user->id . date('Y') . date('m'));
        if (!File::exists($path)) {
            File::makeDirectory($path);
        }

        // return  $request->data;
        if (isset($request->data)) {
            $imageData = [];
            for ($i = 0; $i < count($request->data); $i++) {

                $ext=$request->data[$i]->extension();
                $orgName=$request->file('data.'.$i)->getClientOriginalName();
                $fullUrl = 'storage/image/' . $user->id . date('Y') . date('m') . '/' . $imageName;
                $request->data[$i]->move($path, $imageName);
                $imageData[] = [
                    'post_id' => $request->id,
                    'image' => $fullUrl,
                    'name'=>$orgName,
                    'extension'=>$ext

                ];
            }

            PostImage::insert($imageData);
        }

        // return response()->json([
        //     'status' => 'success',
        //     'data' => $newPost,
        // ], 201);
    }

    public function DeletePostimages(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        //    return $request->old_image;
        if (isset($request->old_image)) {
            //$imageData = [];
            // return  $request->old_image;
            // return count($request->old_image);
            
            for ($i = 0; $i < count($request->old_image); $i++) {

                $imageName =  $request->old_image[$i];
                //  return $imageName;
                if (file_exists($imageName)) {
                    File::delete($imageName);
                    // delete from database too
                   
                 }
                PostImage::where('image', $imageName)->delete(); 
               
            }
            return response()->json([
                'status' => 'success',
                'message' => ' Image deleted successfully',
            ], 201);
           
        }



        return response()->json([
            'status' => 'success',
            'message' => 'Image deleted successfully',
        ], 201);
    }

    public function DeletePost($id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $post = Post::findOrFail($id);
        if ($post->user_id !== $user->id) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $post->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'post deleted',
        ], 201);
    }



    public function interested(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $is_added = Interested::where('user_id', '=', $user->id)->where('post_id', '=', $request->post_id)->exists();
        if ($is_added) {
            Interested::where('user_id', '=', $user->id)->where('post_id', '=', $request->post_id)->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Already Interested',
            ], 200);
        }

        $interested = new Interested();
        $interested->user_id = $user->id;
        $interested->post_id = $request->post_id;
        $interested->save();

        $postOwner=Post::with('user')->find( $request->post_id);

        $notification=new Notification();
        $notification->post_type='Post';
        $notification->content='Interested your post';
        $notification->owner_id= $postOwner->user_id;
        $notification->user_id=$user->id;
        $notification->action_on=$interested->id;
        $notification->save();
        $data= Notification::with('user')->find($notification->id);
        broadcast(new FriendRequestNotification($data,$user->id,$request->receiver_id))->toOthers();

        try{
            // Mail::to($postOwner->user->email)->send(new  PostMialNotification($postOwner->user->name. ' is interested in your post  on Legumbook, a professional networking platform for the legal community.'));
        }catch(Exception $e){
        }
       
        return response()->json([
            'status' => 'success',
            'message' => 'Add Interest Successfully',
        ], 201);
    }




    public function getLinkPreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'link' => "required",
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $url = $request->input('link');
        return $this->linkToData($url);
    }



    public function reportedPost(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'reported_post_id'=>'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        $report = new PostReport;
        $report->reported_post_id =$request->reported_post_id;
        $report->reporting_user_id=$user->id;
        $report->post_report_reason = $request->report_reason;
        $report->save();

        $reportData=PostReport::where("id",$report->id)->with('user')->with("post")->first();

    
        Mail::to("legumbook@gmail.com")->send(new ReportPost($reportData));
    
       return response()->json(['message' => 'Post reported successfully'], 200);
       
       
    }

    public function linkToData($url){
        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            return response()->json(['error' => 'Invalid URL'], 400);
        }

        // $client = new Client();
        // $response = $client->get($url);

        // if ($response->getStatusCode() !== 200) {
        //     return response()->json(['error' => 'Unable to fetch URL'], 500);
        // }

        // $htmlContent = (string) $response->getBody();
        // $crawler = HtmlPageCrawler::create($htmlContent);
        
        // if ($crawler->filter('title')->count() > 0) {
            
        //     $title = $crawler->filter('title')->text();
        // }else{
        //         $title = '';
        //     }
            
        // if ($crawler->filter('meta[name="description"]')->count() > 0) {
        //     $description = $crawler->filter('meta[name="description"]')->attr('content');
        //     } else {
        //         $description = '';
        //     }

        // if ($crawler->filter('meta[property="og:image"]')->count() > 0) {
        //     $image = $crawler->filter('meta[property="og:image"]')->attr('content');
        // } else{
        //         $image = '';
        //     }

        $previewClient = new Client($url);

        // Get previews from all available parsers
        $previews = $previewClient->getPreviews();

        // Get a preview from specific parser
        $preview = $previewClient->getPreview('general');

        // Convert output to array
        $preview = $preview->toArray();


        // dd($preview);
            
        // $title = $crawler->filter('title')->text();
        // $description = $crawler->filter('meta[name="description"]')->attr('content');
        // $image = $crawler->filter('meta[property="og:image"]')->attr('content');

        return response()->json([
            'title' => $preview['title'] ?? '',
            'description' => $preview['description'] ?? '',
            'image' => $preview['cover'] ?? '',
            'url' => $url,
        ]);
    }


    



    public function blockPost(Request $request)
    {


        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'unauthorized'], 401);
        }
        $block_ids = Block::whereNotIn('blocked_by_id', $user->id)->pluck('block_userId')->toArray();
        return $block_ids;
         $posts = Post::whereNotIn('user_id', $block_ids)->with('hashtags')->with('PostImage')->withCount('like')->with('comment.user')->with('likedBy')->withCount('interested')->with('is_interested')->orderBy('id', 'DESC')->get();

        return response()->json(['status' => 'success', 'data' =>  $posts], 200);
    }
    



    public function getPostsByHashtag($tag)
    {
        $hashtag = Hashtag::where("tag",$tag)->first();
        if (!$hashtag) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        }
        //$posts = $hashtag->posts()->get();
        //dd($posts);
        
        $posts = $hashtag->posts()->distinct()
        ->with('user')->with('UserProfile')->with('hashtags')->withCount('like')->with('PostImage')->with('comment.user')
        ->with('likedBy')->with('interested')->orderBy('id', 'DESC')->paginate(15);

        if ($posts->isEmpty()) {
            return response()->json(['message' => 'No posts found for this hashtag'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $posts], 200);
        dd($hashtag->posts()->toSql());

    }

}
