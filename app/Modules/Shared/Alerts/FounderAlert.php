<?php

namespace App\Modules\Shared\Alerts;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\HtmlString;

/**
 * One founder alert email. Deliberately not ShouldQueue: alerts are sent
 * synchronously, so they never depend on the queue they report on.
 */
final class FounderAlert extends Mailable
{
    /**
     * @param  list<string>  $lines  plain-text lines of the body, in order
     */
    public function __construct(public readonly string $title, public readonly array $lines) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'integrator alert: '.$this->title);
    }

    /**
     * An HTML part with a plain-text alternative, both built here (no view files).
     *
     * @return array{html: HtmlString, text: HtmlString}
     */
    protected function buildView(): array
    {
        $body = implode('', array_map(fn (string $line) => '<p>'.e($line).'</p>', $this->lines));

        return [
            'html' => new HtmlString('<h1>'.e($this->title).'</h1>'.$body),
            'text' => new HtmlString($this->plainText()),
        ];
    }

    /** The body as plain text, for logs and tests. */
    public function plainText(): string
    {
        return $this->title."\n\n".implode("\n", $this->lines);
    }
}
