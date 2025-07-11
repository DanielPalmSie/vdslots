                            @if (count($ladders) == 0)
                                <h4>No award ladder.</h4>
                            @else
                                <table class="table table-bordered">
                                    <tbody>
                                        @foreach ($ladders as $l)
                                            <tr>
                                                @if ($l->start_spot == $l->end_spot)
                                                    <td>{{ $ordinal($l->start_spot) }}</td>
                                                @else
                                                    <td>{{ $ordinal($l->start_spot) }} - {{ $ordinal($l->end_spot) }}</td>
                                                @endif
                                                <td><a href="{{ $app['url_generator']->generate('trophyawards.edit', ['trophyaward' => $l->award_id]) }}">{{ $l->description }}</a></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
