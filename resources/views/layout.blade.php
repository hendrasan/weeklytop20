<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>@yield('title', 'Weekly Top 20')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Bree+Serif&display:swap" rel="stylesheet" type="text/css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.0/css/bulma.min.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.12/css/all.css" integrity="sha384-G0fIWCsCzJIMAVNQPfjH08cyYaUtMwjJwqiRKxxE/rx96Uroj1BtIQ6MLJuheaO9" crossorigin="anonymous">

  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path() . '/css/app.css') }}">

  @yield('styles')
</head>
<body>
  @yield('content')

  @yield('before_scripts')

  {{-- <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
  <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path() . '/js/site.js') }}"></script> --}}

  @yield('scripts')
</body>
</html>
