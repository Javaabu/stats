<details class="stats-chart__details" data-slot="chart-table">
    <summary>{{ $strings['view_table'] }}</summary>
    <div class="stats-chart__table-wrapper" tabindex="0" role="region" aria-labelledby="{{ $id }}-title">
        <table class="stats-chart__table">
            <thead>
                <tr>
                    @foreach($table['headers'] as $i => $header)
                        <th scope="col" @if($i > 0) class="stats-chart__number" @endif>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($table['rows'] as $row)
                    <tr>
                        <th scope="row">{{ $row['label'] }}</th>
                        @foreach($row['cells'] as $cell)
                            <td class="stats-chart__number">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</details>
