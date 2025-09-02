<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 40px; background: #f7fafc; color: #1a202c; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); }
        .title { font-size: 24px; margin-bottom: 8px; }
        .status { display: inline-block; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .status.success { background: #def7ec; color: #03543f; }
        .status.pending { background: #e1effe; color: #1e429f; }
        .status.failed { background: #fde8e8; color: #9b1c1c; }
        .muted { color: #4a5568; }
        .meta { margin-top: 16px; font-size: 14px; }
        .meta div { margin: 4px 0; }
        .footer { margin-top: 24px; font-size: 12px; color: #718096; }
    </style>
    <script>
        // Optional: auto-refresh once to let webhook catch up
        document.addEventListener('DOMContentLoaded', function() {
            var success = {{ isset($success) && $success ? 'true' : 'false' }};
            if (!success && !sessionStorage.getItem('refreshedOnce')) {
                sessionStorage.setItem('refreshedOnce', '1');
                setTimeout(function(){ location.reload(); }, 3000);
            }
        });
    </script>
    </head>
<body>
    <div class="card">
        <div class="title">Payment Status</div>
        @if(isset($success) && $success)
            <div class="status success">PAID</div>
        @else
            <div class="status pending">PENDING</div>
        @endif
        <p class="muted">{{ $message ?? '' }}</p>

        @if(isset($payment))
            <div class="meta">
                <div><strong>Amount:</strong> {{ number_format($payment->amount, 2) }} {{ strtoupper($payment->currency) }}</div>
                <div><strong>Reference:</strong> {{ $payment->reference }}</div>
                <div><strong>Provider:</strong> {{ strtoupper($payment->provider) }}</div>
                <div><strong>Status:</strong> {{ strtoupper($payment->status) }}</div>
            </div>
        @endif

        <div class="footer">You may close this tab. If your payment was just completed, this page may update shortly after the webhook processes.</div>
    </div>
</body>
</html>


