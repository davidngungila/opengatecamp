<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Open Gate Camp Mission Management System')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
@unless(file_exists(public_path('js/chart.umd.min.js')))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endunless
@include('partials.styles-core')
@include('partials.styles-components')
</head>
<body>

@php
    $isPortal = str_starts_with(request()->path(), 'portal');
@endphp

<div class="app-shell">
  <div class="sidebar-scrim" id="sidebarScrim" onclick="closeMobileSidebar()"></div>

  @if($isPortal)
    @include('partials.sidebar-portal')
  @else
    @include('partials.sidebar')
  @endif

  <div class="main-wrap" id="mainWrap">
    @if($isPortal)
      @include('partials.topbar-portal')
    @else
      @include('partials.topbar')
    @endif

    <main class="page-content" id="pageContent">
      @yield('content')
    </main>
  </div>
</div>

<div class="toast-stack" id="toastStack"></div>

@include('partials.scripts')
@stack('scripts')
</body>
</html>
