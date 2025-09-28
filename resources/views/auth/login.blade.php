@extends('layouts.guest')

@section('content')
<div class="card shadow-lg auth-card">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold">ログイン</h3>
        </div>

        @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-floating mb-3">
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="name@example.com">
                <label for="email">メールアドレス</label>
                @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-floating mb-3">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Password">
                <label for="password">パスワード</label>
                @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">ログイン情報を記憶する</label>
                </div>
                @if (Route::has('password.request'))
                <a class="small" href="{{ route('password.request') }}">パスワードをお忘れですか?</a>
                @endif
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">ログイン</button>
            </div>

            <p class="text-center mt-4 small">
                アカウントをお持ちでないですか？ <a href="{{ route('register') }}">新規登録</a>
            </p>
        </form>
    </div>
</div>
@endsection