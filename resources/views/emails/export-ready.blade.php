<x-mail::message>
# Your PDF export is ready

Your download link is valid for **24 hours** and will expire on {{ $expiresAt->format('d M Y \a\t H:i') }}.

<x-mail::button :url="$downloadUrl">
Download PDF
</x-mail::button>

The file will be deleted automatically once downloaded or when the link expires.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
