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

                  @php
                    $runsRaw = json_decode($item->chart_runs, true);
                    $runsWithGaps = annotateRuns($runsRaw, $current_period);
                  @endphp

                  @if (!empty($runsWithGaps))
                    <div class="chart__runs" aria-label="Chart history">
                      <div class="flex flex-wrap items-end gap-4 mt-3">
                        @foreach ($runsWithGaps as $run)
                          <div class="flex flex-col items-center text-center">
                            <div class="h-3 mb-1">
                              @if (!isset($run['is_gap']) && !is_null($run['trend']))
                                @if ($run['trend'] === 'up')
                                  <svg class="w-3 h-3 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="12" y1="19" x2="12" y2="5" />
                                    <polyline points="5 12 12 5 19 12" />
                                  </svg>
                                @elseif ($run['trend'] === 'down')
                                  <svg class="w-3 h-3 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <polyline points="19 12 12 19 5 12" />
                                  </svg>
                                @else
                                  <svg class="w-3 h-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                  </svg>
                                @endif
                              @endif
                            </div>

                            <div
                              class="w-9 h-9 rounded-full flex items-center justify-center font-semibold text-sm leading-none
                                @if (isset($run['is_gap']))
                                  bg-gray-300 text-gray-500
                                @else
                                  {{ $run['is_current'] ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700' }}
                                  {{ $run['is_peak'] ? 'ring-2 ring-rose-400' : '' }}
                                @endif">
                              {{ $run['display'] }}
                            </div>
                          </div>
                          @if (isset($run['is_gap']))
                            <!-- 👇 Force line break -->
                            <div class="basis-full"></div>
                          @endif
                        @endforeach
                      </div>

                      <div class="flex items-center gap-4 mt-3">
                        <div class="flex items-center gap-2">
                          <div class="w-4 h-4 rounded-full bg-green-500"></div>
                          <span class="text-sm text-gray-600">This week</span>
                        </div>

                        <div class="flex items-center gap-2">
                          <div class="w-4 h-4 rounded-full bg-rose-400"></div>
                          <span class="text-sm text-gray-600">Peak position</span>
                        </div>
                      </div>
                    </div>
                  @endif
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
