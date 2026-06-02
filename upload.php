<?php
// تفعيل إظهار الأخطاء إذا وجدت لمساعدتك في التجربة
ini_set('display_errors', 1);
error_reporting(E_ALL);

// مسار المجلد النسبي لضمان الحفظ والعرض الصحيح
$uploadDir = 'storage/';

// إنشاء المجلد فوراً إذا لم يكن موجوداً مع إعطائه صلاحيات كاملة للرفع
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$action = $_GET['action'] ?? '';

// 1. قسم رفع الملفات
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $results = [];
    
    // التأكد من أن الملفات تم إرسالها بالفعل بالاسم الصحيح
    if (isset($_FILES['files'])) {
        foreach ($_FILES['files']['name'] as $i => $name) {
            $safeName = basename($name);
            $dest = $uploadDir . $safeName;
            
            // إذا كان اسم الملف مكرراً، يتم توليد اسم جديد فريد
            $c = 1;
            $info = pathinfo($safeName);
            while (file_exists($dest)) {
                $safeName = $info['filename'] . '_' . $c . '.' . ($info['extension'] ?? '');
                $dest = $uploadDir . $safeName;
                $c++;
            }
            
            // نقل الملف المرفوع إلى مجلد storage
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
                $results[] = ['ok' => true, 'name' => $safeName];
            } else {
                $results[] = ['ok' => false, 'name' => $name, 'error' => 'Failed to move file. Check folder permissions.'];
            }
        }
    } else {
        $results[] = ['ok' => false, 'error' => 'No files data received in $_FILES'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// 2. قسم حذف الملفات
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

// 3. قسم إعادة تسمية الملفات
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

// 4. قسم جلب واستعراض قائمة الملفات المرفوعة
if ($action === 'list') {
    $files = [];
    // التأكد من جلب الملفات حتى لو كان الاسم يحتوي على مسافات
    foreach (glob($uploadDir . '*') as $path) {
        if (is_file($path)) {
            // جلب نوع الـ MIME للملف بدقة ليظهر بالشكل المناسب في واجهة المستخدم
            $mime = mime_content_type($path) ?: 'application/octet-stream';
            $files[] = [
                'name' => basename($path),
                'size' => filesize($path),
                'mod'  => date('Y-m-d', filemtime($path)),
                'mime' => $mime
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($files);
    exit;
}
