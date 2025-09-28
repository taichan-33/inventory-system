@extends('layouts.guest')

@section('content')
<div class="card shadow-lg auth-card">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold">新規登録</h3>
        </div>
        <form method="POST" action="{{ route('register') }}">
            @csrf
            
            <div class="form-floating mb-3">
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="山田 太郎">
                <label for="name">お名前</label>
                @error('name')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-floating mb-3">
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@example.com">
                <label for="email">メールアドレス</label>
                @error('email')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-floating mb-3">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Password">
                <label for="password">パスワード</label>
                @error('password')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <div class="form-floating mb-4">
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm Password">
                <label for="password-confirm">パスワード（確認用）</label>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">登録する</button>
            </div>

            <p class="text-center mt-4 small">
                すでにアカウントをお持ちですか？ <a href="{{ route('login') }}">ログイン</a>
            </p>
        </form>
    </div>
</div>
@endsection