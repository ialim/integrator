{{--
    The 12-hour session: a warning 5 minutes before the end with "Stay signed in",
    which asks for the authenticator code again, and the offer to put back values
    that were unsaved when the last session ended. resources/console/session.ts drives it.
--}}
<div
    id="console-session"
    data-member="{{ $memberId }}"
    data-ends-at="{{ $endsAtIso }}"
    data-server-time="{{ $nowIso }}"
    data-warning-seconds="{{ $warningSeconds }}"
    data-extend-url="{{ $extendUrl }}"
>
    <div class="console-banner console-banner-needs" role="alert" data-session-warning @unless ($inWarning) hidden @endunless>
        <p>
            Your session ends in <span data-session-minutes>{{ $minutesLeft }} {{ $minutesLeft === 1 ? 'minute' : 'minutes' }}</span>,
            at <span data-session-ends-wat>{{ $endsAtWat }}</span>. Unsaved values are kept as a draft if it ends.
        </p>
        <button type="button" class="console-button" data-session-stay>Stay signed in</button>
    </div>

    <div class="console-banner" role="status" data-draft-banner hidden>
        <p>You have unsaved values on this page from before you were signed out.</p>
        <button type="button" class="console-button" data-draft-restore>Put them back</button>
        <button type="button" class="console-button-secondary" data-draft-discard>Discard them</button>
    </div>

    <dialog class="console-dialog" aria-labelledby="console-stay-heading" data-session-dialog>
        <form method="dialog" data-session-form novalidate>
            <h2 id="console-stay-heading" class="console-dialog-heading">Stay signed in for another 12 hours?</h2>
            <p>Enter the 6-digit code your authenticator app shows now. A fresh 12 hours starts and you stay on this page.</p>
            <label for="console-stay-code" class="console-label">Code from your authenticator app</label>
            <input
                id="console-stay-code"
                name="code"
                class="console-input"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]{6}"
                maxlength="6"
                required
                aria-describedby="console-stay-error"
                data-no-draft
            />
            <p id="console-stay-error" class="console-field-error" data-session-error aria-live="assertive"></p>
            <div class="console-dialog-actions">
                <button type="submit" class="console-button" data-session-submit>Start a fresh 12 hours</button>
                <button type="button" class="console-button-secondary" data-session-cancel>Not now</button>
            </div>
        </form>
    </dialog>
</div>
