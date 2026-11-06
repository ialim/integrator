{{--
    A console page the signed-in member's role cannot use: "shown, and who can".
    It keeps the console shell and the page's own title, says who can and names
    an active person who holds the capability; the server sends it as 403.
    Never "Access denied", never a lock.
--}}
<x-filament-panels::page>
    <section class="console-panel console-refused" aria-labelledby="console-refused-heading">
        <h2 id="console-refused-heading" class="console-panel-heading">Not part of your role</h2>
        <p>{{ $this->refusal }}</p>
    </section>
</x-filament-panels::page>
