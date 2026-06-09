<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | Admin Panel' : 'Kariyerlen Admin Panel'; ?></title>
    <link rel="stylesheet" href="css/admin.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
    <header class="admin-header">
        <div class="page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></div>
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
                <div style="width: 36px; height: 36px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    A
                </div>
                Admin
            </div>
        </div>
    </header>
    <main class="admin-content">
