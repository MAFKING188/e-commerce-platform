<?php

namespace Modules\CatalogDelivery\Database\Seeders;

class CatalogInventory
{
    /**
     * Product name => verified source image URL. One entry per product in
     * CATALOG; every URL curl-verified to return 200. No URL is shared across
     * categories (see test_no_image_is_shared_across_categories).
     *
     * The URLs are the DOWNLOAD SOURCE for the self-hosted copies in
     * storage/app/public/products/curated/<slug>.jpg (mirrored on every
     * environment — see docs + download script). imageFor() returns that LOCAL
     * path so seeded/live rows serve same-origin instead of hotlinking.
     */
    public const IMAGES = [
        // Electronics
        'Aether Pro Laptop' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',
        'Chronos Gold Watch' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
        'Zenith Studio Cam' => 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?auto=format&fit=crop&w=800&q=80',
        'Nova Mobile 12' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',
        'Vector Pods Max' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
        'Pulse Wireless Earbuds' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=800&q=80',
        'Orbit Desk Speaker' => 'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=800&q=80',
        'Vertex Mechanical Keyboard' => 'https://images.unsplash.com/photo-1541140532154-b024d705b90a?auto=format&fit=crop&w=800&q=80',
        'Prism 4K Monitor' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=800&q=80',
        'Lumen Smart Lamp' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80',
        'Drift Bluetooth Speaker' => 'https://images.unsplash.com/photo-1612170153139-6f881ff067e0?auto=format&fit=crop&w=800&q=80',
        'Atlas Quadcopter Drone' => 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?auto=format&fit=crop&w=800&q=80',
        'Volt GaN Charger' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=800&q=80',
        'Echo Smart Display' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=800&q=80',
        'Titan Gaming Console' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=800&q=80',
        'Slate E-Reader' => 'https://images.unsplash.com/photo-1592840496694-26d035b52b48?auto=format&fit=crop&w=800&q=80',
        'Nova Power Bank' => 'https://images.unsplash.com/photo-1601524909162-ae8725290836?auto=format&fit=crop&w=800&q=80',
        'Cascade Action Camera' => 'https://images.unsplash.com/photo-1520549233664-03f65c1d1327?auto=format&fit=crop&w=800&q=80',
        'Vertex Webcam Pro' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=800&q=80',
        'Prism LED Strip Kit' => 'https://images.unsplash.com/photo-1557672172-298e090bd0f1?auto=format&fit=crop&w=800&q=80',

        // Clothing
        'Imperial Silk Suit' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80',
        'Vanguard Leather Boots' => 'https://images.unsplash.com/photo-1511994298241-608e28f14fde?auto=format&fit=crop&w=800&q=80',
        'Elysian Evening Gown' => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=800&q=80',
        'Nomad Leather Carryall' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80',
        'Aura Linen Set' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=800&q=80',
        'Alpine Wool Coat' => 'https://images.unsplash.com/photo-1520006403909-838d6b92c22e?auto=format&fit=crop&w=800&q=80',
        'Mariner Canvas Jacket' => 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?auto=format&fit=crop&w=800&q=80',
        'Solstice Denim Jacket' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=800&q=80',
        'Atlas Trail Sneakers' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=800&q=80',
        'Horizon Wool Scarf' => 'https://images.unsplash.com/photo-1520975661595-6453be3f7070?auto=format&fit=crop&w=800&q=80',
        'Cascade Rain Shell' => 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=800&q=80',
        'Oasis Cotton Shirt' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=800&q=80',
        'Terra Hiking Pants' => 'https://images.unsplash.com/photo-1506629082955-511b1aa562c8?auto=format&fit=crop&w=800&q=80',
        'Ember Knit Sweater' => 'https://images.unsplash.com/photo-1602293589930-45aad59ba3ab?auto=format&fit=crop&w=800&q=80',
        'Drift Swim Trunks' => 'https://images.unsplash.com/photo-1530549387789-4c1017266635?auto=format&fit=crop&w=800&q=80',

        // Home & Kitchen
        'Nordic Pine Sofa' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',
        'Eclipse Sphere Lamp' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?auto=format&fit=crop&w=800&q=80',
        'Studio Oak Chair' => 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=800&q=80',
        'Minimalist Coffee Maker' => 'https://images.unsplash.com/photo-1517668808822-9ebb02f2a0e6?auto=format&fit=crop&w=800&q=80',
        'Ceramic Bloom Vase' => 'https://images.unsplash.com/photo-1493558103817-58b2924bce98?auto=format&fit=crop&w=800&q=80',
        'Linen Duvet Set' => 'https://images.unsplash.com/photo-1524578271613-d550eacf6090?auto=format&fit=crop&w=800&q=80',
        'Copper Tea Kettle' => 'https://images.unsplash.com/photo-1519682337058-a94d519337bc?auto=format&fit=crop&w=800&q=80',
        'Rustic Oak Dining Table' => 'https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=800&q=80',
        'Marble Cutting Board' => 'https://images.unsplash.com/photo-1499933374294-4584851497cc?auto=format&fit=crop&w=800&q=80',
        'Brass Table Clock' => 'https://images.unsplash.com/photo-1508057198894-247b23fe5ade?auto=format&fit=crop&w=800&q=80',
        'Woven Jute Rug' => 'https://images.unsplash.com/photo-1600166898405-da9535204843?auto=format&fit=crop&w=800&q=80',
        'Cast Iron Skillet' => 'https://images.unsplash.com/photo-1584302179602-e4c3d3fd629d?auto=format&fit=crop&w=800&q=80',
        'Glass Pitcher Set' => 'https://images.unsplash.com/photo-1523362628745-0c100150b504?auto=format&fit=crop&w=800&q=80',
        'Velvet Throw Pillow' => 'https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?auto=format&fit=crop&w=800&q=80',
        'Walnut Book Stand' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=800&q=80',
        'Porcelain Dinner Set' => 'https://images.unsplash.com/photo-1603574670812-d24560880210?auto=format&fit=crop&w=800&q=80',
        'Bamboo Plant Stand' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=800&q=80',
        'Aroma Oil Diffuser' => 'https://images.unsplash.com/photo-1526045478516-99145907023c?auto=format&fit=crop&w=800&q=80',
        'Frosted Mason Jar Set' => 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=crop&w=800&q=80',
        'Walnut Spice Rack' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=800&q=80',

        // Books
        'The Art of Minimalism' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',
        "Architectural Digest — Collector's Edition" => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=800&q=80',
        'Luxury Living Vol. 1' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=800&q=80',
        'The Craft of Design' => 'https://images.unsplash.com/photo-1532012197267-da84d127e765?auto=format&fit=crop&w=800&q=80',
        'Timeless Interiors' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?auto=format&fit=crop&w=800&q=80',
        'Modern Architecture in Focus' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=800&q=80',
        'The Art of Tea' => 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?auto=format&fit=crop&w=800&q=80',
        'Slow Living Journal' => 'https://images.unsplash.com/photo-1517842645767-c639042777db?auto=format&fit=crop&w=800&q=80',
        'The Coffee Atlas' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80',
        'Handmade Craftsmanship' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=800&q=80',
        'The Design of Everyday Things' => 'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?auto=format&fit=crop&w=800&q=80',
        'Color Theory for Creatives' => 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=800&q=80',
        'The Story of Ceramics' => 'https://images.unsplash.com/photo-1526243741027-444d633d7365?auto=format&fit=crop&w=800&q=80',
        'Urban Sketching Guide' => 'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?auto=format&fit=crop&w=800&q=80',
        'The Complete Photographer' => 'https://images.unsplash.com/photo-1589998059171-988d887df646?auto=format&fit=crop&w=800&q=80',

        // Beauty & Wellness
        'Rose Quartz Facial Roller' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=800&q=80',
        'Lavender Sleep Mist' => 'https://images.unsplash.com/photo-1598440947619-2c35fc9aa908?auto=format&fit=crop&w=800&q=80',
        'Argan Oil Serum' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?auto=format&fit=crop&w=800&q=80',
        'Dead Sea Salt Scrub' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80',
        'Bamboo Toothbrush Set' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=800&q=80',
        'Silk Sleep Mask' => 'https://images.unsplash.com/photo-1541781774459-bb2af2f05b55?auto=format&fit=crop&w=800&q=80',
        'Cedar Beard Oil' => 'https://images.unsplash.com/photo-1621605815971-fbc98d665033?auto=format&fit=crop&w=800&q=80',
        'Citrus Body Butter' => 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?auto=format&fit=crop&w=800&q=80',
        'Eucalyptus Bath Salts' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
        'Aloe Hydrating Gel' => 'https://images.unsplash.com/photo-1580870069867-74c57ee1bb07?auto=format&fit=crop&w=800&q=80',
        'Charcoal Face Mask' => 'https://images.unsplash.com/photo-1616394584738-fc6e612e71b9?auto=format&fit=crop&w=800&q=80',
        'Jasmine Hair Oil' => 'https://images.unsplash.com/photo-1526947425960-945c6e72858f?auto=format&fit=crop&w=800&q=80',
        'Chamomile Tea Blend' => 'https://images.unsplash.com/photo-1597481499750-3e6b22637e12?auto=format&fit=crop&w=800&q=80',
        'Peppermint Foot Cream' => 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?auto=format&fit=crop&w=800&q=80',
        'Vitamin C Brightening Drops' => 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=800&q=80',

        // Sports & Outdoors
        'Alpine Trekking Poles' => 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?auto=format&fit=crop&w=800&q=80',
        'Carbon Road Helmet' => 'https://images.unsplash.com/photo-1614889997399-ffaa0cdb2427?auto=format&fit=crop&w=800&q=80', // cyclist wearing helmet (verified 2026-08-24)
        'Yoga Mat Pro' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=800&q=80',
        'Insulated Water Bottle' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=800&q=80',
        'Trail Running Shoes' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80',
        'Camping Hammock' => 'https://images.unsplash.com/photo-1606117331085-5760e3b58520?auto=format&fit=crop&w=800&q=80',
        'All-Terrain Skateboard' => 'https://images.unsplash.com/photo-1572776685600-aca8c3456337?auto=format&fit=crop&w=800&q=80',
        'Adjustable Dumbbell Set' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80',
        'Resistance Band Kit' => 'https://images.unsplash.com/photo-1517164850305-99a3e65bb47e?auto=format&fit=crop&w=800&q=80',
        'LED Bike Lights' => 'https://images.unsplash.com/photo-1517420704952-d9f39e95b43e?auto=format&fit=crop&w=800&q=80',
        'Portable Camping Stove' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80',
        'Waterproof Backpack' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
        'Climbing Chalk Bag' => 'https://images.pexels.com/photos/7590964/pexels-photo-7590964.jpeg?auto=compress&cs=tinysrgb&w=800', // climber reaching into chalk pouch (verified 2026-08-24)
        'Folding Camp Chair' => 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=800&q=80',
        'Swim Training Fins' => 'https://images.unsplash.com/photo-1504851149312-7a075b496cc7?auto=format&fit=crop&w=800&q=80',

        // Toys & Games
        'Wooden Building Blocks' => 'https://images.unsplash.com/photo-1596460107916-430662021049?auto=format&fit=crop&w=800&q=80',
        'Chess Set Deluxe' => 'https://images.unsplash.com/photo-1558877385-81a1c7e67d72?auto=format&fit=crop&w=800&q=80',
        'Puzzle Adventure Box' => 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?auto=format&fit=crop&w=800&q=80',
        'Remote Control Drone' => 'https://images.unsplash.com/photo-1507582020474-9a35b7d455d9?auto=format&fit=crop&w=800&q=80',
        'Plush Moon Bear' => 'https://images.unsplash.com/photo-1500649297466-74794c70acfc?auto=format&fit=crop&w=800&q=80',
        'Card Game Night Pack' => 'https://images.unsplash.com/photo-1591370874773-6702e8f12fd8?auto=format&fit=crop&w=800&q=80',
        'Magnetic Building Tiles' => 'https://images.unsplash.com/photo-1594322436404-5a0526db4d13?auto=format&fit=crop&w=800&q=80',
        'Strategy Board Game' => 'https://images.unsplash.com/photo-1509228468518-180dd4864904?auto=format&fit=crop&w=800&q=80',
        'Watercolor Painting Kit' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80',
        'Mini Racing Car Set' => 'https://images.unsplash.com/photo-1594787318286-3d835c1d207f?auto=format&fit=crop&w=800&q=80',
        'Storybook Collection Vol. 2' => 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=800&q=80',
        'Building Brick Tower' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=800&q=80',
        'Jigsaw Map of the World' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=800&q=80',
        'Robot Coding Kit' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?auto=format&fit=crop&w=800&q=80',
        'Musical Keyboard Toy' => 'https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?auto=format&fit=crop&w=800&q=80',
    ];

    /**
     * Category => [name, imageIdx][]. The image index is legacy and ignored —
     * images are looked up by exact product name via self::IMAGES / imageFor().
     * Reduced to 5-6 per category for testing cost optimization.
     */
    public const CATALOG = [
        'Electronics' => [
            ['Aether Pro Laptop', 0],
            ['Chronos Gold Watch', 1],
            ['Zenith Studio Cam', 2],
            ['Nova Mobile 12', 3],
            ['Vector Pods Max', 4],
            ['Prism 4K Monitor', 0],
        ],
        'Clothing' => [
            ['Imperial Silk Suit', 5],
            ['Vanguard Leather Boots', 6],
            ['Elysian Evening Gown', 7],
            ['Nomad Leather Carryall', 8],
            ['Aura Linen Set', 9],
            ['Atlas Trail Sneakers', 6],
        ],
        'Home & Kitchen' => [
            ['Nordic Pine Sofa', 10],
            ['Eclipse Sphere Lamp', 11],
            ['Studio Oak Chair', 12],
            ['Minimalist Coffee Maker', 13],
            ['Ceramic Bloom Vase', 14],
            ['Linen Duvet Set', 15],
        ],
        'Books' => [
            ['The Art of Minimalism', 15],
            ['Architectural Digest — Collector\'s Edition', 16],
            ['Luxury Living Vol. 1', 17],
            ['The Craft of Design', 15],
            ['Timeless Interiors', 16],
        ],
        'Beauty & Wellness' => [
            ['Rose Quartz Facial Roller', 15],
            ['Lavender Sleep Mist', 14],
            ['Argan Oil Serum', 14],
            ['Dead Sea Salt Scrub', 15],
            ['Bamboo Toothbrush Set', 15],
        ],
        'Sports & Outdoors' => [
            ['Alpine Trekking Poles', 12],
            ['Carbon Road Helmet', 6],
            ['Yoga Mat Pro', 9],
            ['Insulated Water Bottle', 13],
            ['Trail Running Shoes', 6],
            ['LED Bike Lights', 1],
        ],
        'Toys & Games' => [
            ['Wooden Building Blocks', 15],
            ['Chess Set Deluxe', 15],
            ['Puzzle Adventure Box', 16],
            ['Remote Control Drone', 2],
            ['Plush Moon Bear', 15],
        ],
    ];

    public static function namesFor(string $category): array
    {
        return array_map(fn ($item) => $item[0], self::CATALOG[$category]);
    }

    /**
     * Realistic per-category price bands (USD). Deterministic per product name
     * so seeder, migrations, and re-runs all agree on the same price.
     */
    public const PRICE_BANDS = [
        'Electronics' => [29, 899],
        'Clothing' => [19, 179],
        'Home & Kitchen' => [24, 229],
        'Books' => [9, 45],
        'Beauty & Wellness' => [12, 79],
        'Sports & Outdoors' => [19, 189],
        'Toys & Games' => [14, 89],
    ];

    public static function priceFor(string $category, string $name): float
    {
        [$min, $max] = self::PRICE_BANDS[$category] ?? [19, 149];

        // Stable pseudo-random position inside the band.
        $seed = hexdec(substr(md5($name), 0, 6));
        $price = $min + ($seed % 1000 / 1000) * ($max - $min);

        // Charming retail endings (.99 / .49 / .00).
        $base = floor($price);
        $ending = [0.00, 0.49, 0.99][$seed % 3];

        return round($base + $ending, 2);
    }

    /**
     * Local storage path (relative to the public disk) of the self-hosted
     * copy of the curated image for $name. Null when the name is unknown.
     */
    public static function imageFor(string $name): ?string
    {
        return isset(self::IMAGES[$name])
            ? 'products/curated/' . \Illuminate\Support\Str::slug($name) . '.jpg'
            : null;
    }

    /** Absolute source URL of the curated image for $name (download origin). */
    public static function sourceUrlFor(string $name): ?string
    {
        return self::IMAGES[$name] ?? null;
    }
}