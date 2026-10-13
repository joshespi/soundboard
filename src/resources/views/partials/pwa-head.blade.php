<link rel="manifest" href="{{ $manifest ?? asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#4f46e5">
<link rel="apple-touch-icon" href="{{ $touchIcon ?? asset('icons/apple-touch-icon.png') }}">

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
