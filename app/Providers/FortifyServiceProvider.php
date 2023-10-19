<?php

namespace App\Providers;

use App\Actions\Fortify\Admin\AttemptToAuthenticate;
use App\Actions\Fortify\Admin\RedirectIfTwoFactorAuthenticatable;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when([AuthenticatedSessionController::class, AttemptToAuthenticate::class, RedirectIfTwoFactorAuthenticatable::class])
            ->needs(StatefulGuard::class)
            ->give(function() {
                return Auth::guard('admin');
            });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(function () {
          return view('auth.login');
      });
      Fortify::registerView(function () {
        return view('auth.login');
    });
    Fortify::verifyEmailView(function(){
        return view(view:'auth.verify-email');
    });
    Fortify::requestPasswordResetLinkView(function(){
        return view(view:'auth.forgot-password');
    });
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(5)->by($email.$request->ip());
        });
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
        fortify::ResetPasswordView(function($request){
          return view('auth.reset-password',['request'=>$request]);
        });
    }
}
