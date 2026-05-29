# Browser for xOpat
PHP File Manager

Simple browser able to configure xOpat and provide its own REST API connectors (not enforced by the viewer).

Targets **xOpat v3**. Slide-protocol values sent in the session JSON are now string keys that must be registered on the viewer side in `env.json` (`core.client.<active>.slide_protocols`); see the xopat `INTEGRATION.md` §3.

The browser routes by file extension:

| File kind | Configured by | Default key |
|---|---|---|
| `.tif` / `.tiff` (IIP-served) | `FM_XOPAT_IIP_PROTOCOL` | `iipimage` |
| Other WSI (`.mrxs`, `.svs`, `.ndpi`, `.dcm`, …) | `FM_XOPAT_BACKGROUND_PROTOCOL` / `FM_XOPAT_VISUALIZATION_PROTOCOL` | `wsi_service` |
| Plain images (`.png`, `.jpg`, `.jpeg`) | `FM_XOPAT_PLAIN_IMAGE_PROTOCOL` | `plain_image` |

### :loudspeaker: Features 

[//]: # (<ul>)

[//]: # (<li>:cd: Open Source, light and extremely simple</li>)

[//]: # (<li>:information_source: Basic features likes Create, Delete, Modify, View, Download, Copy and Move files </li>)

[//]: # (<li>:arrow_double_up: Ability to upload multiple files and file extensions filter </li>)

[//]: # (<li>:file_folder: Ability to create folders and files</li>)

[//]: # (<li>:gift: Ability to compress, extract files</li>)

[//]: # (<li>:sunglasses: Support user permissions - based on session</li>)

[//]: # (<li>:floppy_disk: Copy direct file URL</li>)

[//]: # (<li>:pencil2: Edit text formats file using advanced editor</li>)

[//]: # (<li>:zap: Backup files</li>)

[//]: # (<li>:mag_right: Search - Advanced Ajax based seach</li>)

[//]: # (<li>:palm_tree: Tree file view</li>)

[//]: # (<li>:file_folder: Exclude folders from listing</li>)

[//]: # (<li>:bangbang: lots more...</li>)

[//]: # (</ul>)

