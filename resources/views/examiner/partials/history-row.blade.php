<article class="history-row">
    <div class="history-row-top">
        <strong>{{ $row['student'] }}</strong>
        <span class="decision {{ $row['decision'] }}">{{ $row['decision'] }}</span>
    </div>
    <span class="muted">{{ $row['matric_no'] }} · {{ $row['time'] }}</span>
    <span class="mono muted safe">{{ $row['token_ref'] }}</span>
    <a class="btn btn-ghost" href="{{ $row['detail_url'] }}">View</a>
</article>
