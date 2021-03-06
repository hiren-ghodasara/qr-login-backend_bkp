<?php

namespace App\Http\Controllers\Api;

use App\Events\MyEvent;
use App\Events\UniqueCodeDecode;
use App\Traits\PassportToken;
use App\UniqueCode;
use App\User;
use BaconQrCode\Encoder\QrCode;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Parser;

class AuthController extends Controller
{
    use PassportToken;

    public function register(Request $request)
    {
        //sleep(1);
        $validatedData = $request->validate([
            'name' => 'required|max:55',
            'email' => 'email|required|unique:users',
            'password' => 'required|confirmed'
        ]);
        //return $validator->errors()->all()->toJson();
        $validatedData['password'] = bcrypt($request->password);
        $user = User::create($validatedData);
        $accessToken = $user->createToken('authToken')->accessToken;
        return response()->json(['user' => $user, 'access_token' => $accessToken]);

    }

    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if (!auth()->attempt($loginData)) {
            return response(['message' => 'Invalid credentials']);
        }
        $accessToken = auth()->user()->createToken('authToken')->accessToken;
        return response(['user' => auth()->user(), 'access_token' => $accessToken, 'token_type' => "Bearer"]);
    }

    public function logout(Request $request)
    {
        //sleep(5);
        $value = $request->bearerToken();
        $id = (new Parser())->parse($value)->getHeader('jti');
        $token = $request->user()->tokens->find($id);
        $token->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function userList(Request $request)
    {
        $data = User::all();
        return response($data);
    }

    public function getQrCode(Request $request)
    {
        //sleep(1);
        //echo QrCode::generate('Make me into a QrCode!');die;
        //QrCode::generate('Make me into a QrCode!');
        $code = UniqueCode::create([
            'unique_code' => $this->getUniqueCode(70),
            'visitor' => $request->ip(),
            'channel_id' => uniqid(),
        ]);
        //var_dump($code);
        $data = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->margin(1)->size(400)->generate($code->unique_code));
        return response(['qr_code' => $data, 'channel_id' => $code->channel_id], 200);
        //<img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->size(100)->generate('Make me into an QrCode!')) !!} ">
    }

    function getUniqueCode($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString . '&&' . uniqid(rand());
    }

    function decodeQrCode(Request $request)
    {
        $validatedData = $request->validate([
            'text' => 'required',
        ]);

        $code = UniqueCode::where('unique_code', '=', $validatedData['text'])->first();
        if($code){
            //$code = UniqueCode::orderBy('created_at', 'desc')->first();
            $user = $request->user();
            $token = $this->getBearerTokenByUser($user, 3, false);
            $arr = ["code" => $code, 'token' => $token];
            event(new UniqueCodeDecode($arr));
            return response($arr);
        }else{
            return response(['message' => 'Invalid credentials']);
        }
    }
}
