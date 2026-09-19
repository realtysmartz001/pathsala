<?php
// Returns data in the EXACT same array shape as inc/projects_data.php,
// so Project.php and project-details.php need no other changes.
require_once __DIR__ . '/../../includes/db_connect.php';

$out = [];
$res = $conn->query("SELECT * FROM projects WHERE is_published = 1 ORDER BY created_at DESC");
while ($p = $res->fetch_assoc()) {
    $cfgRes = $conn->query("SELECT config_size, typology, price_label FROM project_configurations WHERE project_id = " . (int) $p['id'] . " ORDER BY sort_order");
    $configs = [];
    $typologies = [];
    while ($c = $cfgRes->fetch_assoc()) {
        $configs[] = ['size' => $c['config_size'], 'typology' => $c['typology'], 'price' => $c['price_label']];
        if (!in_array($c['config_size'], $typologies))
            $typologies[] = $c['config_size'];
    }
    $out[] = [
        'title' => $p['title'],
        'sector_raw' => $p['sector_raw'],
        'location' => $p['location'],
        'lat' => $p['lat'] !== null ? (float) $p['lat'] : null,
        'lng' => $p['lng'] !== null ? (float) $p['lng'] : null,
        'google_maps_link' => $p['google_maps_link'] ?? null,
        'cover_image' => $p['cover_image'] ?? null,
        'land' => $p['land'],
        'land_num' => $p['land_num'] !== null ? (float) $p['land_num'] : null,
        'towers' => $p['towers'],
        'height' => $p['height'],
        'clubhouse' => $p['clubhouse'],
        'possession_raw' => $p['possession_raw'],
        'payment_plan' => $p['payment_plan'],
        'usp_raw' => $p['usp_raw'],
        'min_price' => $p['min_price'] !== null ? (float) $p['min_price'] : null,
        'max_price' => $p['max_price'] !== null ? (float) $p['max_price'] : null,
        'typologies' => $typologies,
        'configs' => $configs,
        'brochure_path' => $p['brochure_path'] ?? null,

    ];
}
return $out;