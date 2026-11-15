# Performance baseline: the app shell's cold start

NFR-6 asks for the Catalogue within about 5 seconds on the test phone: a low-end Android with 2 GB of
memory on slow 3G (400 kbps, 400 ms). Story 1.7 records where the empty shell starts, so every later epic
can see what it adds.

**Cold start** here means a first visit: no HTTP cache, no service worker, no saved data. The **first
usable screen** is the holding screen's "integrator" heading drawn, which is also its first paint (a service down page does not count).

## How it is measured (emulated)

`scripts/measure-cold-start.mjs` opens the built shell in Playwright's Chromium as a Galaxy S5 (360 by
640, touch), with the test phone's network (400 kbps down and up, 400 ms round trip, cache off) and the
CPU slowed 4 times. It takes the median of five runs.

```sh
npm run build
# with the app served, for example by `docker compose up app` on http://127.0.0.1:8000
node scripts/measure-cold-start.mjs http://127.0.0.1:8000 5
```

It reports the time to the first usable screen, the share of it **after the first byte** (the shell's
own cost: downloading and starting its assets, without the server's time to answer), the first paint,
the first byte and the bytes transferred.

## Readings

| Date | Where | Build | Usable | After first byte | First byte | Transferred |
| --- | --- | --- | --- | --- | --- | --- |
| 15 Nov 2026 | Emulated, local Docker server (Windows), 5 runs | Story 1.7 shell | 13.58 s | 8.07 s | 5.47 s | 330 KB |

Reading the first row:

- **The shell's own share is 8.1 s** and steady across runs (7.9 to 8.1 s). Almost all of it is the
  download: the local server sends the assets **uncompressed** (330 KB, of which the JavaScript is
  308 KB), and 330 KB at 400 kbps is about 6.8 s. Gzipped, the same assets are about 104 KB, about 2.1 s.
- **The first byte (5.5 s) is the local server**, not the shell: the development container on Windows
  takes 3 to 4 s to answer a request even without throttling, and it varies a lot between runs (3.2 to
  12 s). Production (Laravel Cloud) answers far faster; a staging reading will replace this column.
- A **second visit** is answered by the service worker from its precache (the shell's HTML and assets),
  so it does not wait for the network at all; only `/api/v1/health` goes out.

What to watch: the JavaScript bundle (308 KB raw, 99 KB gzipped), most of it React and TanStack Router.
The Catalogue (Epic 3 onwards) has to fit into the 5 seconds on top of this, so serve the assets
compressed and keep new screens in their own lazily loaded chunks.

## Adding a reading from a real phone

The owner's decision (15 November 2026) is to measure emulated now and on a real 2 GB phone later.
To add that reading:

1. Use an Android 9 or newer phone with 2 GB of memory and Chrome. Clear Chrome's site data for the
   app's address (Settings, Site settings, All sites), so the visit is cold, and do not open it from an
   installed icon.
2. Connect the phone by USB and open `chrome://inspect` on a computer. Inspect the phone's tab, and in
   the Network panel set throttling to a custom profile of 400 kbps down, 400 kbps up and 400 ms latency,
   with Disable cache ticked.
3. In the Performance panel, start recording with reload. Stop when the holding screen shows.
4. Read the time from the navigation start to the first contentful paint (the heading), and the first
   byte from the document request in the Network panel. Repeat five times and take the median.
5. Add a row to the table above: the date, "Real phone" with its model and the server (staging or
   production), the build (commit), and the readings.
