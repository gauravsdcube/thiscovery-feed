# Thiscovery Feed

**Version 1.0.1**

Copyright (c) 2026 D Cube Consulting. All rights reserved.

A clean home feed for HumHub. Members see new posts from the spaces they belong to, and can post to every space they can use or to the spaces they choose.

## Features

- **Feed** — posts from spaces the member belongs to, in three columns on a desktop screen
- **Composer** — post to everyone the member can post in, or to selected spaces. A post sent to several spaces appears once, with those space names on one line
- **Shortcuts** — profile and the member's spaces
- **Announcements** — pinned posts from those spaces
- **Layout** — administrators set the default widgets. Each member can show, hide, reorder, or reset them
- **Home** — sit beside the Dashboard, or replace it so the logo and login open the feed
- **Top bar** — show Feed in HumHub’s top bar, or leave it off. The feed stays available at `/feed`

## Requirements

- HumHub **1.18** or later
- PHP **8.2** or later

Optional: **Thiscovery Navigation**. While the feed sits beside the Dashboard, Feed can be placed in that menu. When the feed replaces the Dashboard, the existing Dashboard item opens the feed.

## Installation

1. Copy this module to `protected/modules/thiscovery-feed`.
2. Enable it: **Administration → Modules → Thiscovery Feed**.
3. Open **Administration → Thiscovery Feed** (or `/thiscovery-feed/admin/index`).
4. Choose where the feed lives, how many announcements to show, and which widgets are on by default.

Members open the feed at `/feed`.

## License

AGPL-3.0-or-later. See [LICENSE](LICENSE).

Copyright (c) 2026 D Cube Consulting. All rights reserved.
