@extends('layouts.auth')

@section('content')
<div class="card shadow-lg border-0 rounded-lg">
    <div class="card-header">
        <h3 class="text-center font-weight-light my-4">Login</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-floating mb-3">
                <input id="login" type="text" class="form-control @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" required autofocus>
                <label for="login">Student ID / Employee ID / Email</label>
                @error('login')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-floating mb-3">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                <label for="password">Password</label>
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    Remember Me
                </label>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                @if (Route::has('password.request'))
                    <a class="small" href="{{ route('password.request') }}">Forgot Password?</a>
                @endif
                <button type="submit" class="btn btn-primary">
                    {{ __('Login') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection 