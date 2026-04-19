@extends('layouts.app')

@section('content')
<main class="login-page">
    <div class="login-background-shape shape-a"></div>
    <div class="login-background-shape shape-b"></div>

    <section class="login-card">
        <p class="login-kicker">Personalize Dashboard</p>
        <h1>ICQA Shared Login</h1>
        <p class="login-subtitle">Single account access for associate and manager chat collaboration.</p>

        @if ($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt', [], false) }}" class="login-form">
            @csrf
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>

            <label class="login-checkbox">
                <input type="checkbox" name="remember" value="1">
                <span>Remember this shared account on this browser</span>
            </label>

            <button type="submit" class="btn btn-primary btn-block">Login to Dashboard</button>
        </form>

    </section>
</main>
@endsection
