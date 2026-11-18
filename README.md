# Thiscovery Feed

**Version 1.0.5**

Copyright (c) 2026 D Cube Consulting. All rights reserved.

A clean home feed for HumHub. Members see posts from the spaces they belong to, and profile posts the Dashboard would show. They can post once on their profile, the same way the Dashboard does, or to the spaces they choose.

## Features

- **Feed** — posts from spaces the member belongs to, plus profile posts the Dashboard would show, in three columns on a desktop screen
- **Composer** — the editor comes first. Nothing is selected until the member chooses **Everyone** or **Selected spaces**. Everyone creates one post on their profile. Selected spaces creates a post in each ticked space, shown once in the feed with those space names on one line. Submit stays off until a destination is chosen, and the choice clears after the post is sent
- **Shortcuts** — profile and the member's spaces
- **Announcements** — pinned posts from those spaces, in their original formatting. Large theme headings stay inside the column. Administrators set how many lines to show before Read more; the default is five. Each space name opens that space, and Read more opens the post
- **Live updates** — HumHub's live poll shows the same new-posts banner as a space or the Dashboard
- **Layout** — administrators set the default widgets. Each member can show, hide, reorder, or reset them
- **Home** — sit beside the Dashboard, or replace it so the logo and login open the feed
- **Top bar** — show Feed in HumHub’s top bar, or leave it off. The feed stays available at `/feed`
- **Banner** — administrators can upload an image and show it across the top of the feed

## Requirements

- HumHub **1.18** or later
- PHP **8.2** or later

Optional: **Thiscovery Navigation**. While the feed sits beside the Dashboard, Feed can be placed in that menu. When the feed replaces the Dashboard, the existing Dashboard item opens the feed.

## Installation

1. Copy this module to `protected/modules/thiscovery-feed`.
2. Enable it: **Administration → Modules → Thiscovery Feed**.
3. Open **Administration → Thiscovery Feed** (or `/thiscovery-feed/admin/index`).
4. Choose where the feed lives, how many announcements to show, how many lines of each announcement to preview, whether to show an uploaded banner, and which widgets are on by default.

Members open the feed at `/feed`.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).

Copyright (c) 2026 D Cube Consulting. All rights reserved.
