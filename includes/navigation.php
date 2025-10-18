<?php
/**
 * Dynamic Navigation Menu Generator
 * Generates navigation menu from database categories and subcategories
 */

require_once 'config/database.php';

function generateNavigationMenu($page_type = 'html') {
    $pdo = getConnection();

    // Get categories with subcategories
    $stmt = $pdo->query("
        SELECT c.*,
               COUNT(DISTINCT s.id) as subcategory_count
        FROM categories c
        LEFT JOIN subcategories s ON c.id = s.category_id AND s.is_active = 1
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY c.sort_order, c.name
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get subcategories for each category
    foreach ($categories as &$category) {
        $stmt = $pdo->prepare("
            SELECT s.*
            FROM subcategories s
            WHERE s.category_id = ? AND s.is_active = 1
            ORDER BY s.sort_order, s.name
        ");
        $stmt->execute([$category['id']]);
        $category['subcategories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $extension = ($page_type === 'php') ? 'php' : 'html';

    $menu_html = '';

    foreach ($categories as $category) {
        if (empty($category['subcategories'])) {
            // Simple category without subcategories
            $menu_html .= '<li class="nav-item">
                <a href="products.' . $extension . '#" class="nav-link">' . htmlspecialchars($category['name']) . '</a>
            </li>';
        } else {
            // Category with subcategories
            $menu_html .= '<li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle">' . htmlspecialchars($category['name']) . ' <i class="fas fa-chevron-down"></i></a>
                <ul class="dropdown-menu">';

            foreach ($category['subcategories'] as $subcategory) {
                $menu_html .= '<li><a href="products.' . $extension . '#' . $subcategory['slug'] . '" class="dropdown-link">' . htmlspecialchars($subcategory['name']) . '</a></li>';
            }

            $menu_html .= '<li><a href="products.' . $extension . '" class="dropdown-link">View All ' . htmlspecialchars($category['name']) . '</a></li>
                </ul>
            </li>';
        }
    }

    return $menu_html;
}

// Function to get categories for forms
function getCategoriesForSelect() {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get subcategories for a category
function getSubcategoriesForCategory($category_id) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY sort_order, name");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
