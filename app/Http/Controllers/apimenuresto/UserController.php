<?php

namespace App\Http\Controllers\apimenuresto;

use Illuminate\Http\Request;

use App\Models\menuresto\Usermenu;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    function register(Request $req)
    {


        try {
            $validator = Validator::make($req->all(), [
                'email' => 'required|email|unique:user_customer,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors(),
                ], 422);
            }

            $user = new Usermenu;
            $user->nama = $req->input('nama');
            $user->email = $req->input('email');
            // $user->password = Hash::make($req->input('password'));
            $user->phone = $req->input('phone');
            $user->address = $req->input('address');
            $user->status = $req->input('status');
            $user->save();

            return response()->json($user);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Bad Request"], 400);
        }
    }
    // UserController.php

    public function uploadProfilePicture(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'image' => 'required|string'
        ]);

        try {
            $user = Usermenu::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Simpan base64 string langsung ke database
            $user->pictures = $request->image;
            $user->save();

            return response()->json([
                'success' => true,
                'image_url' => $user->pictures
            ]);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Bad Request"], 400);
        }
    }


    public function login(Request $req)
    {

        $validator = Validator::make($req->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Usermenu::where('email', $req->email)->first();

            if (!$user) {
                return response()->json(["message" => "Sorry, email doesn't match"], 400);
            }

            // Generate token
            // $token = $user->createToken('auth_token')->plainTextToken;
            $token = "Bearer 3462|kEhzRDFVp8difEqJcNwYnGctXFaNAu9YxKMYTCcq";

            return response()->json([
                'success' => true,
                'status' => $user->status,
                'token' => $token,
                'user' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Bad Request"], 400);
        }
    }

    public function deleteUser($id)
    {
        // Find the user by ID

        try {
            $user = Usermenu::find($id);

            // Check if the user exists
            if (!$user) {
                return response()->json(["message" => "User not found"], 404);
            }

            // Delete the user
            $user->delete();

            // Return success response
            return response()->json(["message" => "User deleted successfully"], 200);
        } catch (\Throwable $th) {
            return response()->json(["message" => "Bad Request"], 400);
        }
    }
    public function index()
    {
        // Fetch all users from the database
        $users = Usermenu::all();

        // Return users as JSON response
        return response()->json($users);
    }
    public function getoneuser($email)
    {
        $user = Usermenu::where('email', $email)->first();

        if (!$user) {
            return response()->json(["message" => "User not found"], 404);
        }

        return response()->json($user);
    }
    public function getpicturebyemail($email)
    {
        $user = Usermenu::where('email', $email)->first();

        if (!$user) {
            return response()->json(["message" => "User not found"], 404);
        }
        return response()->json([
            'picture' => $user->pictures
        ]);
    }
}
