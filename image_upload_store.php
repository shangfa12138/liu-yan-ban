<?php
/**
 * 校验并保存上传图片到 upload/，返回相对路径或错误信息。
 *
 * @return array{ok:bool,path?:string,error?:string}
 */
function store_validated_profile_image(array $file, int $maxBytes = 5242880, int $maxSide = 3000): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => '请选择图片文件'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => '上传失败'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $type = finfo_file($finfo, $file['tmp_name']);
    $info = @getimagesize($file['tmp_name']);
    $raw = @file_get_contents($file['tmp_name']);
    if ($raw === false) {
        return ['ok' => false, 'error' => '无法读取文件'];
    }
    $errors = [];
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        $errors[] = '仅支持 jpg、png';
    }
    if (!in_array($type, ['image/jpeg', 'image/png'], true)) {
        $errors[] = 'MIME 类型不允许';
    }
    if ($info === false) {
        $errors[] = '不是有效图片';
    }
    if ($info && ($info[0] > $maxSide || $info[1] > $maxSide)) {
        $errors[] = "图片边长不能超过 {$maxSide} 像素";
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        $errors[] = '文件过大';
    }
    if (strpos($raw, '<?php') !== false || strpos($raw, '<?=') !== false) {
        $errors[] = '疑似恶意文件';
    }
    if (!empty($errors)) {
        return ['ok' => false, 'error' => implode('；', $errors)];
    }
    $uploadDir = __DIR__ . '/upload/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $newname = bin2hex(random_bytes(16)) . '.' . ($ext === 'png' ? 'png' : 'jpg');
    $target = $uploadDir . $newname;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => '保存失败'];
    }
    return ['ok' => true, 'path' => 'upload/' . $newname];
}
