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
                <h2 class="title is-3">Your 2018 Rewind</h2>
                <h3 class="subtitle is-6 has-text-grey">These are the top 20 songs you enjoy in 2018</h3>
              </div>
            </div>
          </div>

          <a class="button is-primary js-create-playlist" href="#">Create This Playlist</a>

          <a class="button is-primary is-invisible js-created-playlist" href="#" target="_blank">Play This Playlist</a>

          <div class="box">
            <div class="columns is-mobile">
              <div class="column is-1">Position</div>
              <div class="column is-narrow"></div>
              <div class="column is-narrow"></div>
              <div class="column"></div>
              <div class="column is-2 has-text-centered">PEAK</div>
              <div class="column is-2 has-text-centered">Weeks on Chart</div>
            </div>
          </div>

          @foreach ($chart as $index => $item)
            <?php $position = $index + 1; ?>
            <div class="box">
              <div class="columns is-mobile">
                <div class="column is-1 has-text-grey has-text-centered" style="min-width: 70px;">
                  <div class="is-size-5" style="color: #000">
                    <span>{{ $position }}</span>
                  </div>
                </div>
                <div class="column is-narrow">
                  <figure class="image is-48x48">
                    <img src="{{ json_decode($item->track_data)->album->images[2]->url }}" alt="{{ $item->track_name }}">
                  </figure>
                </div>
                <div class="column">
                  <div class="title is-5 has-text-weight-normal">
                    <a href="{{ json_decode($item->track_data)->external_urls->spotify }}" target="_blank">{{ $item->track_name }}</a>
                  </div>
                  <div class="subtitle is-6 has-text-grey">
                    {{ $item->track_artist }}
                  </div>
                </div>
                <div class="column is-2 has-text-centered">
                  {{-- <span class="is-size-7">PEAK</span> --}}
                  <div class="is-size-5">
                    <span>{{ $item->peak }}</span>
                  </div>
                  @if ($item->peak == 1)
                    <div class="is-size-7">
                      <span>({{ $item->weeks_on_no_1 }} Weeks on #1)</span>
                    </div>
                  @endif
                </div>
                <div class="column is-2 has-text-centered">
                  {{-- <span class="is-size-7">WEEKS ON CHART</span> --}}
                  <div class="is-size-5">
                    <span>{{ $item->total_periods_on_chart }}</span>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.min.js"></script>
  <script src="https://unpkg.com/sweetalert2@7.19.3/dist/sweetalert2.all.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/promise-polyfill@7.1.0/dist/promise.min.js"></script>

  <script>
    $(function() {
      $('.js-create-playlist').on('click', function(e) {
        e.preventDefault();

        createPlaylist();
      });

      var isCreatingPlaylist;

      function createPlaylist()
      {
        if (isCreatingPlaylist) {
          return false;
        }

        swal({
          title: 'Are you sure you want to create this playlist?',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, create this playlist!',
          showLoaderOnConfirm: true,
          preConfirm: function (title) {
            isCreatingPlaylist = true;

            axios.post("{{ route('rewind.create-playlist') }}")
            .then(function (response) {
              isCreatingPlaylist = false;
              $('.js-create-playlist').addClass('is-invisible');
              $('.js-created-playlist').attr('href', response.data.external_urls.spotify);
              $('.js-created-playlist').removeClass('is-invisible');
            })
            .catch(function(error) {
              swal.showValidationError(
                'Request failed: ' + error
              );
            })
          },
          allowOutsideClick: function () {
            return !swal.isLoading();
          }
        }).then(function(result) {
          if (result.value) {
            swal({
              title: 'Playlist created! Enjoy!'
            })
          }
        });
      }

    });
  </script>
@endsection