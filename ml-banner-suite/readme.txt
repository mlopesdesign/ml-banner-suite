=== ML Banner Suite ===
Contributors: mlopesdesign
Tags: banner, banners, advertising, adsense, shortcode, carousel, slider
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 3.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional banner management and isolated Google AdSense integration for WordPress, with responsive layouts, shortcodes, content placement, ads.txt, and GitHub updates.

== Description ==

ML Banner Suite is a professional WordPress banner management plugin foundation built for commercial evolution.

This base package includes:
- main plugin bootstrap
- top-level admin menu in the WordPress root
- banner custom post type
- group and category taxonomies
- statistics table creation
- dashboard, statistics, shortcodes, and settings pages
- GitHub-ready structure
- translation-ready textdomain
- isolated Google AdSense module with Auto Ads, manual units, content placement, and virtual ads.txt

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the WordPress Plugins screen.
3. Open **ML Banner Suite** in the admin sidebar.

== Changelog ==

= 3.0.1 =
* Atualizações automáticas passam a vir do repositório público de releases (ml-banner-suite-releases); o código-fonte passa a ser privado.
* Filtro de asset do atualizador mais estrito: aceita só ml-banner-suite.zip ou ml-banner-suite-v<versão>.zip.

= 3.0.0 =
* Adds an isolated Google AdSense module without changing the existing banner, statistics, licensing, or GitHub updater engines.
* Adds global Auto Ads code loading with strict Publisher ID validation.
* Adds reusable responsive manual ad units through the `[mlbs_adsense]` shortcode.
* Adds optional static insertion before content, after a selected paragraph, or after content.
* Adds a non-destructive virtual ads.txt endpoint that never overwrites a physical ads.txt file.
* Hides ads from connected administrators by default to reduce accidental impressions and clicks.
* Adds extension hooks for certified consent management integrations.

= 2.1.28 =
* Refactors the plugin details modal to match the clean panel design system already used in the admin area.
* Applies the official icon on a light container with correct contrast and transparent background assets.
* Exposes icon metadata to the WordPress update/details flow without changing the updater engine.

= 2.1.28 =
* Adds the official clean icon with transparent background for the update screen and plugin information modal.
* Keeps the premium modal layout aligned with the visual identity standard, without dark backgrounds.

= 2.1.25 =
* Corrige definitivamente o modal de informações do plugin com renderização local segura dentro do iframe do WordPress.
* Mantém o motor de atualização via GitHub Releases, slug original e atualização por cima sem criar plugin novo.

= 2.1.24 =
* Corrige o modal “Ver detalhes da versão” para sempre carregar informações locais do plugin, mesmo quando o GitHub falhar ou limitar a API.
* Mantém o motor de atualização via GitHub Releases sem instalar como plugin novo.

= 2.1.24 =
* Corrige definitivamente o modal “Ver detalhes da versão” usando metadata do último GitHub Release sem quebrar o updater via release asset ZIP.

= 2.1.20 =
* Remove o updater customizado antigo.
* Integra a biblioteca oficial Plugin Update Checker 5.6.
* Ativa atualização via GitHub Releases usando release assets ZIP.
* Exige release asset compatível com o slug ml-banner-suite, sem fallback para main.zip.


= 2.1.19 =
* Remove completamente o updater custom do GitHub para estabilizar a base antes da integração final do novo motor de atualização.

= 2.1.12 =
* Adiciona updater nativo via GitHub com detecção de release, pacote ZIP e informações de atualização dentro do WordPress.

= 2.1.3 =
* Corrige a listagem de banners para não ocultar posts publicados sem meta de ordenação.


= 1.1.0 =
* Initial professional foundation.
