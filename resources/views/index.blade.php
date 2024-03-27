@extends('layout')

@section('content')
  @include('_partials.header')

  <div class="mx-auto max-w-7xl px-4 my-10">
    <div class="flex flex-wrap -mx-4">
      @foreach ($users as $user)
        <div class="w-full sm:w-1/2 lg:w-1/4 p-2">
          <div class="border rounded h-full overflow-hidden">
            {{-- <a class="button is-primary is-block" href="{{ route('chart', $user->spotify_id) }}">{{ $user->name }}'s chart</a> --}}

            <div class="bg-black text-white text-lg font-black text-center py-2 px-4">{{ $user->name }}'s Top 20</div>

            <div class="p-4 space-y-2">
              <p class="text-gray-500 italic text-sm">Chart date: {{ $user->latest_chart->created_at->format('d M Y') }}</p>

              <div class="divide-y space-y-2">
                @foreach ($user->latest_chart_top_tracks as $index => $c)
                  <div class="flex items-center gap-4 py-2">
                    <div class="flex flex-col items-center self-start">
                      <span class="font-black text-3xl">{{ $c->position }}</span>
                      @if (!empty($c->last_position) && $c->position != $c->last_position)
                        <div class="{{ $c->position < $c->last_position ? 'text-green-500' : 'text-red-500' }}">
                          @if ($c->position < $c->last_position)
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor"
                              stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <line x1="12" y1="19" x2="12" y2="5" />
                              <polyline points="5 12 12 5 19 12" />
                            </svg>
                          @else
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor"
                              stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <line x1="12" y1="5" x2="12" y2="19" />
                              <polyline points="19 12 12 19 5 12" />
                            </svg>
                          @endif
                        </div>
                      @endif
                    </div>

                    <div class="min-w-0">
                      <p class="font-bold text-lg truncate">{{ $c->track_name }}</p>
                      <p>{{ $c->track_artist }}</p>
                    </div>
                  </div>
                @endforeach
              </div>

              <a href="{{ route('chart', $user->spotify_id) }}" class="text-center bg-green-600 hover:text-white hover:bg-green-500 text-white block rounded px-4 py-2 font-bold text-lg uppercase">View full chart</a>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    {{ $users->links() }}
  </div>
@endsection
