<?php
$user = current_user();
$sellerId = (int) $user['id'];
$store = seller_store($sellerId);
$name = input('store_name', 60);
$tagline = input('tagline', 100);
$description = input('description', 3000);
require_fields(['Shop name' => $name]);

$logoFiles = uploaded_files('logo');
$newLogo = $logoFiles ? store_image_upload($logoFiles[0], 'stores') : null;
$oldLogo = $store['logo_path'] ?? null;
$logo = $newLogo ?? (!empty($_POST['remove_logo']) ? null : $oldLogo);

try {
    if ($store) {
        db()->prepare('UPDATE stores SET name = ?, tagline = ?, description = ?, logo_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$name, $tagline ?: null, $description ?: null, $logo, $store['id']]);
    } else {
        db()->prepare('INSERT INTO stores (user_id, name, slug, tagline, description, logo_path) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$sellerId, $name, unique_slug('stores', $name), $tagline ?: null, $description ?: null, $logo]);
    }
} catch (Throwable $e) {
    delete_upload($newLogo);
    throw $e;
}
if ($oldLogo && $oldLogo !== $logo) {
    delete_upload($oldLogo);
}
flash('success', 'Your store settings have been saved.');
