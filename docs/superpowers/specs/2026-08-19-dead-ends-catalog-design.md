# SmartShop — Dead-End Fix + Coherent Marketplace Catalog (2026-08-19)

## Context

Three dead ends on the live site:

1. `/collection` returns 404 while the nav labels a link "Collection" (it points at `/shop`).
2. Footer "New Arrivals" and "Featured" are `href="#"` — dead anchors.
3. Product catalog is placeholder-grade: numbered duplicates ("Aether Pro Laptop 1"…"15") and 8 faker junk categories (`laboriosam ea`, `eos harum`, …) created by an older seeder.

## Decisions (owner-approved)

- The platform is a **multi-vendor marketplace** — "anyone can sell anything." Categories stay generic and broad, NOT themed.
- Keep the 4 real categories (Electronics, Clothing, Home & Kitchen, Books) untouched in name.
- Delete the 8 faker categories (1 orphan product moves to Home & Kitchen).
- Add 3 standard marketplace categories: **Beauty & Wellness**, **Sports & Outdoors**, **Toys & Games** — 15 products each.
- Every product gets a **unique, coherent marketplace name** (no numbering).
- `/collection` becomes a real editorial page (hero + New Arrivals + Featured).
- Footer dead anchors resolve to the collection page sections.

## 1. /collection editorial page

- `GET /collection` → `ViewController@collection` → `catalogdelivery::collection` view.
- Layout: brand hero (LUWI Collection), **New Arrivals** section (8 latest via `CatalogQueryService`), **Featured** section (6 in-stock latest).
- Nav "Collection" link → `route('collection')` (`resources/views/components/app-layout.blade.php:45`).

## 2. Footer

- "New Arrivals" → `route('collection')#new-arrivals`
- "Featured" → `route('collection')#featured`
- No `href="#"` remains anywhere in blades.

## 3. Catalog data

Name pools (unique; 15–20 per category; Unsplash ids reused from the verified 18-id set).

| Category | Names (count) |
|---|---|
| Electronics | 20 (laptop, watch, camera, phone, earbuds, speaker, keyboard, monitor, lamp, bluetooth speaker, drone, charger, display, console, e-reader, power bank, action cam, webcam, LED kit, headset) |
| Clothing | 15 (suit, boots, gown, carryall, linen set, wool coat, canvas jacket, denim jacket, sneakers, scarf, rain shell, shirt, hiking pants, sweater, swim trunks) |
| Home & Kitchen | 20 (sofa, lamp, chair, coffee maker, vase, duvet, kettle, dining table, cutting board, clock, rug, skillet, pitcher set, throw pillow, book stand, dinner set, plant stand, diffuser, jar set, spice rack) |
| Books | 15 (art/design/interior titles) |
| Beauty & Wellness | 15 (roller, mist, serum, scrub, toothbrush set, sleep mask, beard oil, body butter, bath salts, gel, mask, hair oil, tea blend, foot cream, drops) |
| Sports & Outdoors | 15 (poles, helmet, mat, bottle, shoes, hammock, skateboard, dumbbells, bands, bike lights, stove, backpack, chalk bag, camp chair, fins) |
| Toys & Games | 15 (blocks, chess, puzzle, drone, plush, card game, tiles, board game, painting kit, car set, books, brick tower, jigsaw, robot kit, keyboard toy) |

Total inventory: 115 names (20+15+20+15+15+15+15); seeder creates 105 products (15 per category × 7).

### Live DB migration `2026_08_19_170001_coherent_catalog`

- Delete junk categories (move the single orphan product into Home & Kitchen first).
- Rename existing products deterministically: per category, ordered by `id`, assign pool names (pools are ≥ category size; extra rows keep cycling — live has 18 Electronics / 16 Home & Kitchen rows, pools are 20 each).
- Create the 3 new categories + 15 products each (deterministic: pool order × Unsplash id cycle, `price` 300–3500, `stock` 10–50, standard LUWI description).
- No-op on fresh installs (runs before seeding on empty tables).

### Seeder rewrite

`ProductSeeder` creates the same 7 categories × 15 products from the same inventory. Fresh installs and live converge on coherent data.

## 4. Tests

- `CollectionPageTest`: GET `/collection` 200 + renders New Arrivals/Featured headings; nav/footer HTML contains no `href="#"`.
- `CatalogCoherenceTest` (fresh-seed, RefreshDatabase): 7 categories, no faker names; 105 products; zero product names matching `/\s\d+$/`; each category has ≥ 15 products.
- Existing suite must stay green (category names unchanged; product renames are data-only).

## Out of scope (YAGNI)

- No "featured" boolean column (Featured = in-stock latest, same as home page).
- No description rewrite (already reads "LUWI craftsmanship").
- No image replacement (Unsplash placeholder ids stay).
- No vendor-facing category management.