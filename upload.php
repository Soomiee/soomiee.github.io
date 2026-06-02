<?php
$uploadDir = __DIR__ . '/storage/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$action = $_GET['action'] ?? '';

// رفع ملف
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $results = [];
    foreach ($_FILES['files']['name'] as $i => $name) {
        $safeName = basename($name);
        $dest = $uploadDir . $safeName;
        // إذا الاسم مكرر أضف رقم
        $c = 1;
        $info = pathinfo($safeName);
        while (file_exists($dest)) {
            $safeName = $info['filename'] . '_' . $c . '.' . ($info['extension'] ?? '');
            $dest = $uploadDir . $safeName;
            $c++;
        }
        if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
            $results[] = ['ok' => true, 'name' => $safeName];
        } else {
            $results[] = ['ok' => false, 'name' => $name];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// حذف ملف
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = basename($data['name'] ?? '');
    $path = $uploadDir . $name;
    if ($name && file_exists($path)) {
        unlink($path);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// إعادة تسمية
if ($action === 'rename' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $old = $uploadDir . basename($data['old'] ?? '');
    $new = $uploadDir . basename($data['new'] ?? '');
    if (file_exists($old) && !file_exists($new)) {
        rename($old, $new);
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// قائمة الملفات
if ($action === 'list') {
    $files = [];
    foreach (glob($uploadDir . '*') as $path) {
        if (is_file($path)) {
            $files[] = [
                'name' => basename($path),
                'size' => filesize($path),
                'mod'  => date('Y-m-d', filemtime($path)),
                'mime' => mime_content_type($path),
            ];
        }
    }
    usort($files, fn($a,$b) => strcmp($a['name'], $b['name']));
    header('Content-Type: application/json');
    echo json_encode($files);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown action']);
