<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuthResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\Auth\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    protected $maxAttempts = 5;
    protected $decayMinutes = 15;
    
    public function __invoke(LoginRequest $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        
        /** @var \App\Models\User */
        $user = User::where(['email' => $request->validated('email')])->first();
        if ($user && Hash::check($request->validated('password'), $user->password)) {
            $this->clearLoginAttempts($request);
        
            $token = $user->createToken($request->userAgent())->plainTextToken;
            return (new AuthResource($user))->additional([
                'token' => $token,
            ]);
        }
        
        $this->incrementLoginAttempts($request);
        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }
    
    protected function hasTooManyLoginAttempts(Request $request)
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request), $this->maxAttempts
        );
    }
    
    protected function incrementLoginAttempts(Request $request)
    {
        $this->limiter()->hit(
            $this->throttleKey($request), $this->decayMinutes * 60
        );
    }
    
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );
        
        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])]
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }
    
    protected function clearLoginAttempts(Request $request)
    {
        $this->limiter()->clear($this->throttleKey($request));
    }
    
    protected function fireLockoutEvent(Request $request)
    {
        event(new Lockout($request));
    }
    
    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->email. '|' . $request->ip()));
    }
    
    /**
     * @return \Illuminate\Cache\RateLimiter
     */
    protected function limiter()
    {
        return app(RateLimiter::class);
    }
}
