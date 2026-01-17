# Change: Update Authentication Entry Layout

## Why
The current welcome page is separate from authentication, which splits the entry experience and duplicates messaging. Unifying the entry point simplifies navigation and keeps the brand story adjacent to sign-in and registration.

## What Changes
- Remove the standalone welcome page and route in favor of the split auth layout.
- Add the existing welcome page messaging/content into the auth split panel.
- Keep authentication forms in the right panel, preserving current auth flows.

## Impact
- Affected specs: user-management
- Affected code: auth layout views, root route handling, welcome view content
