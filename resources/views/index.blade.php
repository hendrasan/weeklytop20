@extends('layout')

@section('content')
  @include('_partials.header')

  <div class="section">
    <div class="container">
      <div class="columns is-multiline">
        @foreach ($users as $user)
          <div class="column is-one-quarter">
            <a class="button is-primary is-block" href="{{ route('chart', $user->spotify_id) }}">{{ $user->name }}'s chart</a>
          </div>
        @endforeach
      </div>

      {{ $users->links() }}
    </div>
  </div>
@endsection
