<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;
use Validator;
use App\Models\User;
use App\Models\UserPin;
class AuthController extends Controller
{
  public function register(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'user_name' => 'required|string||min:4|max:20|alpha_dash|unique:users',
            'password' => 'required|string|min:8'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors());
        }

        $user = \DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'user_name' => $request->user_name,
                'user_role' => 'user',
                'password' => Hash::make($request->password)
             ]);

             $six_digit_random_number = random_int(100000, 999999);

             $userpin = UserPin::create([
                 'user_id' => $user->id,
                 'pin' => $six_digit_random_number,
              ]);

              // send pin to user email

               $to_name = $request->name;
               $to_email =  $request->email;
               $data = array("name"=>$request->name, "body" => "Thanks for Registering, Here is your 6 digit pin " . $six_digit_random_number);
               \Mail::send('emails.pin', $data, function($message) use ($to_name, $to_email) {

                     $message->to($to_email, $to_name)
                             ->subject('User Registration PIN');
                     $message->from('test@noreply.con','Test Mail');
               });

               return $user;


        });


         return response()
             ->json(['status' => 'success', 'message' => 'PIN has been sent to the registered email.','user' => $user ]);

    }

    public function login(Request $request)
    {


        $validator = Validator::make($request->all(),[
            'user_name' => 'required',
            'password' => 'required',

        ]);

        if($validator->fails()){
            return response()->json($validator->errors());
        }


        if (!Auth::attempt(['email' => $request->user_name, 'password' => $request->password, 'is_active' => 1])
        && !Auth::attempt(['user_name' => $request->user_name, 'password' => $request->password, 'is_active' => 1]))
        {
              return response()
                  ->json(['message' => 'Unauthorized'], 401);


         }

        $user = User::where('email', $request['user_name'])
          ->orWhere('user_name',$request['user_name'])->firstOrFail();


        if ($user->user_role == 'admin') {
            $token = $user->createToken('auth_token', ['send:registration-email'])->plainTextToken;
        }else{
            $token = $user->createToken('auth_token',[])->plainTextToken;
        }


        return response()
            ->json(['message' => 'Hi '.$user->name.', Login Successfully!','access_token' => $token, 'token_type' => 'Bearer', ]);
    }

    // method for user logout and delete token
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'You have successfully logged out and the token was successfully deleted'
        ];
    }


    public function pin(Request $request)
    {

        $validator = Validator::make($request->all(),[
            'email' => 'required',
            'pin' => 'required',

        ]);

        if($validator->fails()){
            return response()->json($validator->errors());
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $userpin = UserPin::where('user_id', $user->id)
                      ->where('pin', $request['pin'] )
                      ->where('is_active',true)
                      ->firstOrFail();


        $user->is_active = true;
        $userpin->is_active = false;

        $user->save();
        $userpin->save();

      return response()
          ->json(['status' => 'success', 'message' => 'User has been successfully created.', 'user'=> $user ]);
    }


    public function updateProfile(Request $request)
    {


      $validator = Validator::make($request->all(),[
          'name' => 'required|string|max:255',
          'avatar' => 'required|image|dimensions:max_width=256,max_height=256'

      ]);

      if($validator->fails()){
          return response()->json($validator->errors());
      }

      $avatarUrl = '';
      if ($image = $request->file('avatar')) {
        $destinationPath = 'image/';
        $profileImage = date('YmdHis') . "." . $image->getClientOriginalExtension();
        $image->move($destinationPath, $profileImage);
        $avatarUrl = $destinationPath . "$profileImage";

      }

       $user = auth()->user();

       $user->name = $request['name'];
       $user->avatar = $avatarUrl;
       $user->save();

       return response()
          ->json(['status' => 'success', 'message' => 'User has been successfully updated.', 'user' => $user]);

    }


    public function sendRegistrationEmail(Request $request)
      {


          $validator = Validator::make($request->all(),[
              'name' => 'required|string|max:255',
              'email' => 'required|string|email|max:255|unique:users',
          ]);

          if($validator->fails()){
              return response()->json($validator->errors());
          }

         if (auth()->user()->tokenCan('send:registration-email')) {
           $to_name = $request->name;
           $to_email =  $request->email;
           $data = array("name"=>$request->name, "body" => "Hi " . $request->name . ', Please register here <ENTER REGISTRATION LINK HERE>.');
           \Mail::send('emails.registerlink', $data, function($message) use ($to_name, $to_email) {

                 $message->to($to_email, $to_name)
                         ->subject('User Registration Link');
                 $message->from('test@noreply.con','Test Mail');
           });

          return response()
             ->json(['status' => 'success', 'message' => 'Email registration has been sent to ' . $request->email ]);
         }else{
           return response()
               ->json(['message' => 'Unauthorized'], 401);
         }



      }


}
