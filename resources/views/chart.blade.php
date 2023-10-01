@extends('layout')

@section('content')
  @include('_partials.header')

  <div class="section">
    <div class="container">
      <div class="columns is-variable is-6">
        <div class="column">
          <div class="level">
            <div class="level-left">
              <div>
                <h2 class="title is-3">{{ $user->name }}'s Top 20</h2>
                <h3 class="subtitle is-6 has-text-grey">Chart date: {{ $chart[0]->created_at->format('d M Y') }}</h3>
                {{-- <h3 class="subtitle is-6 has-text-grey">(Last update at {{ $chart[0]->created_at->format('d M Y H:i:s') }})</h3> --}}
                @if ($current_period > 1)
                  <a href="{{ route('chart', [$user->spotify_id, $current_period - 1] ) }}">< Prev Week</a>
                @endif
                @if ($current_period < $latest_period)
                  <a href="{{ route('chart', [$user->spotify_id, $current_period + 1] ) }}">Next Week ></a>
                @endif
              </div>
            </div>
          </div>

          <div class="chart">
            @foreach ($chart as $index => $item)
              <?php $position = $index + 1; ?>
              <div class="chart__card">
                <div class="chart__top-section">
                  <div class="chart__position">
                    <span class="chart__number">{{ $position }}</span>
                    @if (!empty($item->last_position) && $position != $item->last_position)
                      <div class="chart__icon {{ $position < $item->last_position ? 'has-text-success' : 'has-text-danger' }}">
                        @if ($position < $item->last_position)
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="19" x2="12" y2="5" />
                            <polyline points="5 12 12 5 19 12" />
                          </svg>
                        @else
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="icon" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <polyline points="19 12 12 19 5 12" />
                          </svg>
                        @endif
                        <span class="chart__icon__number">{{ abs($position - $item->last_position) }}</span>
                      </div>
                    @endif
                  </div>
                  <div class="chart__image">
                    <img src="{{ json_decode($item->track_data)->album->images[1]->url }}" alt="{{ $item->track_name }}">
                  </div>
                </div>
                <div class="chart__track">
                  <p class="chart__meta">
                    {{ $item->periods_on_chart }} week{{ $item->periods_on_chart > 1 ? 's' : '' }} on chart
                  </p>

                  <p class="chart__title"><a href="{{ json_decode($item->track_data)->external_urls->spotify }}" target="_blank">{{ $item->track_name }}</a></p>
                  <p class="chart__artist">by {{ $item->track_artist }}</p>

                  <p class="chart__additional">
                    @if (empty($item->last_position))
                      New entry
                    @else
                      @if ($position > 1)
                        Peaked at number {{ $item->peak_position }}
                      @endif
                    @endif
                  </p>

                  {{-- <p>Chart Runs:</p>
                  <ul>
                    {{ implode(', ', $item->chart_runs) }}
                  </ul> --}}
                </div>
                <div class="chart__actions">
                  <a href="{{ json_decode($item->track_data)->external_urls->spotify }}" target="_blank" class="btn-play">
                    <i class="icon fas fa-play"></i>
                  </a>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
