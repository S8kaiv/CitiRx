<script>
    (() => {
        let mode = 'auto';
        try { mode = localStorage.getItem('citiRxComfort') || 'auto'; } catch {}
        const dark = mode === 'dark' || (
            mode !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches
        );
        document.documentElement.dataset.comfort = dark ? 'dark' : 'light';
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        document.querySelector('meta[name="theme-color"]')?.setAttribute(
            'content', dark ? '#0F172A' : '#F8FAFC'
        );
    })();
</script>
