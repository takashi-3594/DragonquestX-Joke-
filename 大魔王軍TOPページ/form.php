<?php
declare(strict_types=1);

session_start();

mb_language('Japanese');
mb_internal_encoding('UTF-8');

header('Content-Type: application/json; charset=UTF-8');

/* =========================================================
基本設定（ここを編集）
========================================================= */
$admin_email             = 'darkness@makai.com';			// 管理者の受信先
$admin_subject           = '【大魔王城】お問い合わせを受信しました';
$auto_reply_enabled      = true;						// 自動返信を使う場合は true
$auto_reply_field        = 'email';						// 自動返信先に使う項目名（固定推奨）
$auto_reply_subject      = '【大魔王城】お問い合わせありがとうございます';
$privacy_consent_enabled = true;						// 個人情報同意チェックを使う場合は trueにし、少し下の、privacy_consent 内の required 도 true にする。※２箇所変更。使わないならfalse。
$attachment_enabled      = true;						// 添付ファイル欄を使う場合は true
$attachment_field        = 'attachment';				// 添付ファイル欄の name（最初は固定推奨）
$attachment_label        = '添付ファイル';
$attachment_max_size     = 5 * 1024 * 1024;				// 5MB
$use_envelope_sender     = true;						// Xserver等でエラーが出る場合は false に変更
$envelope_sender         = 'no-reply@example.com';		// Return-Path に使う送信元アドレス（自ドメイン推奨）
$from_email              = 'no-reply@example.com';		// From に使う固定アドレス（ユーザー入力は使わない）
$from_name               = '大魔王城';
$min_submit_interval     = 10;		// 連続送信の最小秒数。同一人物が10秒以内
$max_text_length         = 500;		// 1行テキスト系の最大文字数。約500文字超えたらエラー。
$max_textarea_length     = 8000;	// 複数行テキストエリアの最大文字数。約8000文字超えたらエラー。

/* 許可する添付ファイル形式（※ここに単に追加しても機能しません） */
$attachment_allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
$attachment_allowed_mime_map = [
    'pdf'  => ['application/pdf'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'webp' => ['image/webp'],
];

$auto_reply_intro = <<<TEXT
このたびはお問い合わせありがとうございます。
以下の内容で受け付けいたしました。
内容を確認のうえ、担当者よりご連絡いたします。
TEXT;

$auto_reply_signature = <<<TEXT
--------------------------------
大魔王城
https://example.com/
メール：darkness@makai.com
--------------------------------
TEXT;

/* =========================================================
入力項目設定（HTMLの name を変更したら、ここも合わせて変更）
type:
- text
- email
- postal      （郵便番号。ハイフンあり / なし両対応）
- select
- checkbox    （name="xxx[]" の複数チェック）
- radio
- textarea
- month_day   （name="xxx_month" / "xxx_day" の2つで扱う）
- date        （name="xxx_year" / "xxx_month" / "xxx_day" の3つで扱う）
- privacy     （個人情報同意用）
========================================================= */
$field_settings = [
    'name' => [
        'label'    => 'お名前',
        'type'     => 'text',
        'required' => true,
    ],
    'company' => [
        'label'    => '会社名',
        'type'     => 'text',
        'required' => false,
    ],
    'email' => [
        'label'    => 'メールアドレス',
        'type'     => 'email',
        'required' => true,
    ],
    'postal_code' => [
        'label'    => '郵便番号',
        'type'     => 'postal',
        'required' => false,
    ],
    'address_pref' => [
        'label'    => '都道府県',
        'type'     => 'text',
        'required' => false,
    ],
    'address_line' => [
        'label'    => '市区町村以下',
        'type'     => 'text',
        'required' => false,
    ],
    'category' => [
        'label'    => 'お問い合わせ種別',
        'type'     => 'select',
        'required' => false,
    ],
    'services' => [
        'label'    => 'ご希望内容',
        'type'     => 'checkbox',
        'required' => false,
    ],
    'contact_method' => [
        'label'    => 'ご希望の連絡方法',
        'type'     => 'radio',
        'required' => false,
    ],
    'visit_day' => [
        'label'    => 'ご希望月日',
        'type'     => 'month_day',
        'required' => false,
    ],
    'birthday' => [
        'label'      => '生年月日',
        'type'       => 'date',
        'required'   => false,
        'year_start' => 1900,
        'year_end'   => (int) date('Y'),
    ],
    'message' => [
        'label'    => 'お問い合わせ内容',
        'type'     => 'textarea',
        'required' => true,
    ],
    'privacy_consent' => [
        'label'    => 'プライバシーポリシーへの同意',
        'type'     => 'privacy',
        'required' => true,	// true → 表示して必須 ｜ false → 非表示＆判定しない（上の「基本設定」の「$privacy_consent_enabled」と合わせる。）
    ],
];

/* =========================================================
初期化（CSRFトークン取得用）
静的HTMLから使えるよう、JSが最初にここへGETします
========================================================= */
$action = isset($_GET['action']) ? (string) $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'init') {
    $token = generateCsrfToken();

    respondJson([
        'ok'                    => true,
        'csrfToken'             => $token,
        'privacyConsentEnabled' => $privacy_consent_enabled,
        'attachmentEnabled'     => $attachment_enabled,
    ]);
}

/* =========================================================
POST送信処理
========================================================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson([
        'ok'        => false,
        'formError' => '無効なアクセスです。',
    ]);
}

if (isPostSizeExceeded()) {
    respondJson([
        'ok'        => false,
        'formError' => '送信データが大きすぎます。添付ファイルの容量をご確認ください。',
        'csrfToken' => generateCsrfToken(),
    ]);
}

$field_errors   = [];
$clean_values   = [];
$attachment_meta = [
    'originalName' => '',
    'storedPath'   => '',
    'mime'         => '',
    'size'         => 0,
];

/* -------------------------
セキュリティチェック
------------------------- */

// 1) CSRF対策
$posted_token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
if (!verifyCsrfToken($posted_token)) {
    respondJson([
        'ok'        => false,
        'formError' => 'ページの有効期限が切れました。そのままもう一度送信してください。',
        'csrfToken' => generateCsrfToken(),
    ]);
}

// 2) Honeypot
$honeypot = isset($_POST['website']) ? trim((string) $_POST['website']) : '';
if ($honeypot !== '') {
    respondJson([
        'ok'        => false,
        'formError' => '送信に失敗しました。',
        'csrfToken' => generateCsrfToken(),
    ]);
}

// 3) 連続送信制限
$now = time();
$last_submit = isset($_SESSION['form_last_submit_at']) ? (int) $_SESSION['form_last_submit_at'] : 0;

if ($last_submit > 0 && ($now - $last_submit) < $min_submit_interval) {
    respondJson([
        'ok'        => false,
        'formError' => '短時間に連続して送信できません。少し時間をおいてお試しください。',
        'csrfToken' => generateCsrfToken(),
    ]);
}

/* -------------------------
通常項目のバリデーション
------------------------- */
foreach ($field_settings as $name => $setting) {
    $label    = isset($setting['label']) ? (string) $setting['label'] : $name;
    $type     = isset($setting['type']) ? (string) $setting['type'] : 'text';
    $required = !empty($setting['required']);

    if ($type === 'privacy' && !$privacy_consent_enabled) {
        continue;
    }

    switch ($type) {
        case 'checkbox':
            $value = isset($_POST[$name]) && is_array($_POST[$name]) ? $_POST[$name] : [];
            $value = array_values(array_filter(array_map('normalizeLineBreaks', array_map('sanitizeText', $value)), static function ($item) {
                return $item !== '';
            }));

            if ($required && count($value) === 0) {
                $field_errors[$name] = $label . 'を選択してください。';
            }

            $clean_values[$name] = $value;
            break;

        case 'privacy':
            $value = isset($_POST[$name]) ? (string) $_POST[$name] : '';

            if ($privacy_consent_enabled && $value !== '1') {
                $field_errors[$name] = 'プライバシーポリシーへの同意が必要です。';
            }

            $clean_values[$name] = $value === '1' ? '同意する' : '';
            break;

        case 'email':
            $value = isset($_POST[$name]) ? trim((string) $_POST[$name]) : '';
            $value = normalizeLineBreaks($value);

            if ($required && $value === '') {
                $field_errors[$name] = $label . 'を入力してください。';
            } elseif ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $field_errors[$name] = $label . 'の形式が正しくありません。';
            }

            $clean_values[$name] = $value;
            break;

        case 'postal':
            $value = isset($_POST[$name]) ? trim((string) $_POST[$name]) : '';
            $value = normalizeLineBreaks($value);
            $digits = preg_replace('/\D/u', '', $value);
            $is_blank = $digits === '';

            if ($required && $is_blank) {
                $field_errors[$name] = $label . 'を入力してください。';
            } elseif (!$is_blank && !preg_match('/^\d{7}$/', $digits)) {
                $field_errors[$name] = $label . 'は7桁で入力してください。';
            }

            if (!$is_blank && preg_match('/^\d{7}$/', $digits)) {
                $clean_values[$name] = substr($digits, 0, 3) . '-' . substr($digits, 3);
            } else {
                $clean_values[$name] = '';
            }
            break;

        case 'month_day':
            $month_key = $name . '_month';
            $day_key   = $name . '_day';
            $month_raw = isset($_POST[$month_key]) ? trim((string) $_POST[$month_key]) : '';
            $day_raw   = isset($_POST[$day_key]) ? trim((string) $_POST[$day_key]) : '';

            $all_blank  = ($month_raw === '' && $day_raw === '');
            $all_filled = ($month_raw !== '' && $day_raw !== '');

            if (($required && !$all_filled) || (!$required && !$all_blank && !$all_filled)) {
                $field_errors[$name] = $label . 'を正しく選択してください。';
                $clean_values[$name] = '';
                break;
            }

            if ($all_blank) {
                $clean_values[$name] = '';
                break;
            }

            $month = (int) $month_raw;
            $day   = (int) $day_raw;

            if ($month < 1 || $month > 12 || $day < 1 || $day > 31 || !checkdate($month, $day, (int) date('Y'))) {
                $field_errors[$name] = $label . 'の日付が正しくありません。';
                $clean_values[$name] = '';
                break;
            }

            $clean_values[$name] = sprintf('%02d月%02d日', $month, $day);
            break;

        case 'date':
            $year_key  = $name . '_year';
            $month_key = $name . '_month';
            $day_key   = $name . '_day';

            $year_raw  = isset($_POST[$year_key]) ? trim((string) $_POST[$year_key]) : '';
            $month_raw = isset($_POST[$month_key]) ? trim((string) $_POST[$month_key]) : '';
            $day_raw   = isset($_POST[$day_key]) ? trim((string) $_POST[$day_key]) : '';

            $all_blank  = ($year_raw === '' && $month_raw === '' && $day_raw === '');
            $all_filled = ($year_raw !== '' && $month_raw !== '' && $day_raw !== '');

            if (($required && !$all_filled) || (!$required && !$all_blank && !$all_filled)) {
                $field_errors[$name] = $label . 'を正しく選択してください。';
                $clean_values[$name] = '';
                break;
            }

            if ($all_blank) {
                $clean_values[$name] = '';
                break;
            }

            $year  = (int) $year_raw;
            $month = (int) $month_raw;
            $day   = (int) $day_raw;

            $year_start = isset($setting['year_start']) ? (int) $setting['year_start'] : 1900;
            $year_end   = isset($setting['year_end']) ? (int) $setting['year_end'] : (int) date('Y');

            if ($year < $year_start || $year > $year_end || $month < 1 || $month > 12 || $day < 1 || $day > 31 || !checkdate($month, $day, $year)) {
                $field_errors[$name] = $label . 'の日付が正しくありません。';
                $clean_values[$name] = '';
                break;
            }

            $clean_values[$name] = sprintf('%04d年%02d月%02d日', $year, $month, $day);
            break;

        case 'textarea':
            $value = isset($_POST[$name]) ? trim((string) $_POST[$name]) : '';
            $value = normalizeLineBreaks($value);

            if ($required && $value === '') {
                $field_errors[$name] = $label . 'を入力してください。';
            } elseif (mb_strlen($value) > $max_textarea_length) {
                $field_errors[$name] = $label . 'は' . $max_textarea_length . '文字以内で入力してください。';
            }

            $clean_values[$name] = $value;
            break;

        case 'radio':
        case 'select':
        case 'text':
        default:
            $value = isset($_POST[$name]) ? trim((string) $_POST[$name]) : '';
            $value = normalizeLineBreaks(sanitizeText($value));

            if ($required && $value === '') {
                $field_errors[$name] = $label . 'を入力してください。';
            } elseif (mb_strlen($value) > $max_text_length) {
                $field_errors[$name] = $label . 'は' . $max_text_length . '文字以内で入力してください。';
            }

            $clean_values[$name] = $value;
            break;
    }
}

/* -------------------------
添付ファイルのバリデーション
1ファイル / 5MB以下 / pdf・jpg・jpeg・png・webp
------------------------- */
if ($attachment_enabled) {
    $attachment_result = handleAttachmentUpload(
        $attachment_field,
        $attachment_label,
        $attachment_max_size,
        $attachment_allowed_extensions,
        $attachment_allowed_mime_map
    );

    if (!$attachment_result['ok']) {
        $field_errors[$attachment_field] = $attachment_result['error'];
    } else {
        $attachment_meta = $attachment_result['meta'];
    }
}

if (!empty($field_errors)) {
    cleanupAttachmentFile($attachment_meta['storedPath']);

    respondJson([
        'ok'          => false,
        'fieldErrors' => $field_errors,
        'csrfToken'   => generateCsrfToken(),
    ]);
}

/* =========================================================
メール本文作成
========================================================= */
$admin_body = [];
$admin_body[] = "お問い合わせがありました。";
$admin_body[] = "";
$admin_body[] = "【受信日時】";
$admin_body[] = date('Y-m-d H:i:s');
$admin_body[] = "";

foreach ($field_settings as $name => $setting) {
    $type = isset($setting['type']) ? (string) $setting['type'] : 'text';

    if ($type === 'privacy' && !$privacy_consent_enabled) {
        continue;
    }

    $label = isset($setting['label']) ? (string) $setting['label'] : $name;
    $value = $clean_values[$name] ?? '';

    $admin_body[] = "【{$label}】";

    if (is_array($value)) {
        $admin_body[] = count($value) > 0 ? implode(' / ', $value) : '未選択';
    } else {
        $admin_body[] = $value !== '' ? $value : '未入力';
    }

    $admin_body[] = "";
}

if ($attachment_enabled) {
    $admin_body[] = "【{$attachment_label}】";
    $admin_body[] = $attachment_meta['originalName'] !== '' ? $attachment_meta['originalName'] . '（' . formatBytes((int) $attachment_meta['size']) . '）' : 'なし';
    $admin_body[] = "";
}

$admin_message = implode("\n", $admin_body);

/* =========================================================
メール送信
========================================================= */
$reply_to_address = isset($clean_values[$auto_reply_field]) ? (string) $clean_values[$auto_reply_field] : '';

if ($attachment_enabled && $attachment_meta['storedPath'] !== '') {
    $mail_sent = sendMailWithAttachment(
        $admin_email,
        $admin_subject,
        $admin_message,
        $from_email,
        $from_name,
        $reply_to_address,
        $use_envelope_sender,
        $envelope_sender,
        $attachment_meta['storedPath'],
        $attachment_meta['originalName'],
        $attachment_meta['mime']
    );
} else {
    $headers = buildMailHeaders($from_email, $from_name, $reply_to_address);
    $mail_sent = sendMail($admin_email, $admin_subject, $admin_message, $headers, $use_envelope_sender, $envelope_sender);
}

if (!$mail_sent) {
    cleanupAttachmentFile($attachment_meta['storedPath']);

    respondJson([
        'ok'        => false,
        'formError' => 'メール送信に失敗しました。サーバー設定をご確認ください。',
        'csrfToken' => generateCsrfToken(),
    ]);
}

/* 自動返信（添付はしない。本文に受付済みだけ記載） */
if ($auto_reply_enabled) {
    $reply_to = isset($clean_values[$auto_reply_field]) ? (string) $clean_values[$auto_reply_field] : '';

    if ($reply_to !== '' && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
        $auto_body = [];
        $auto_body[] = trim($auto_reply_intro);
        $auto_body[] = "";

        foreach ($field_settings as $name => $setting) {
            $type = isset($setting['type']) ? (string) $setting['type'] : 'text';

            if ($type === 'privacy' && !$privacy_consent_enabled) {
                continue;
            }

            $label = isset($setting['label']) ? (string) $setting['label'] : $name;
            $value = $clean_values[$name] ?? '';

            $auto_body[] = "【{$label}】";

            if (is_array($value)) {
                $auto_body[] = count($value) > 0 ? implode(' / ', $value) : '未選択';
            } else {
                $auto_body[] = $value !== '' ? $value : '未入力';
            }

            $auto_body[] = "";
        }

        if ($attachment_enabled && $attachment_meta['originalName'] !== '') {
            $auto_body[] = "【{$attachment_label}】";
            $auto_body[] = '受け付けました（ファイル名：' . $attachment_meta['originalName'] . '）';
            $auto_body[] = "";
        }

        $auto_body[] = trim($auto_reply_signature);

        $auto_headers = buildMailHeaders($from_email, $from_name, '');
        sendMail($reply_to, $auto_reply_subject, implode("\n", $auto_body), $auto_headers, $use_envelope_sender, $envelope_sender);
    }
}

/* 送信成功 */
cleanupAttachmentFile($attachment_meta['storedPath']);
$_SESSION['form_last_submit_at'] = $now;

respondJson([
    'ok' => true,
]);

/* =========================================================
関数
========================================================= */
function respondJson(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function generateCsrfToken(): string
{
    $token = bin2hex(random_bytes(32));

    if (!isset($_SESSION['form_csrf_tokens']) || !is_array($_SESSION['form_csrf_tokens'])) {
        $_SESSION['form_csrf_tokens'] = [];
    }

    $_SESSION['form_csrf_tokens'][$token] = time();

    foreach ($_SESSION['form_csrf_tokens'] as $storedToken => $createdAt) {
        if (!is_int($createdAt) || (time() - $createdAt) > 3600) {
            unset($_SESSION['form_csrf_tokens'][$storedToken]);
        }
    }

    return $token;
}

function verifyCsrfToken(string $token): bool
{
    if ($token === '') {
        return false;
    }

    if (empty($_SESSION['form_csrf_tokens']) || !is_array($_SESSION['form_csrf_tokens'])) {
        return false;
    }

    if (!isset($_SESSION['form_csrf_tokens'][$token])) {
        return false;
    }

    unset($_SESSION['form_csrf_tokens'][$token]);

    return true;
}

function sanitizeText(string $value): string
{
    $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
    return trim($value);
}

function normalizeLineBreaks(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    return trim($value);
}

function isPostSizeExceeded(): bool
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }

    $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    if ($contentLength <= 0) {
        return false;
    }

    $postMaxSize = iniSizeToBytes((string) ini_get('post_max_size'));
    if ($postMaxSize <= 0) {
        return false;
    }

    if ($contentLength <= $postMaxSize) {
        return false;
    }

    return empty($_POST) && empty($_FILES);
}

function iniSizeToBytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;

    switch ($unit) {
        case 'g':
            $number *= 1024;
            // no break
        case 'm':
            $number *= 1024;
            // no break
        case 'k':
            $number *= 1024;
            break;
    }

    return (int) $number;
}

function handleAttachmentUpload(
    string $fieldName,
    string $label,
    int $maxBytes,
    array $allowedExtensions,
    array $allowedMimeMap
): array {
    $emptyMeta = [
        'originalName' => '',
        'storedPath'   => '',
        'mime'         => '',
        'size'         => 0,
    ];

    if (!isset($_FILES[$fieldName])) {
        return ['ok' => true, 'meta' => $emptyMeta, 'error' => ''];
    }

    $file = $_FILES[$fieldName];

    if (!is_array($file) || is_array($file['name'] ?? null)) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'は1ファイルのみ添付できます。'];
    }

    $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'meta' => $emptyMeta, 'error' => ''];
    }

    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => uploadErrorMessage($label, $error)];
    }

    $tmpName = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'の受け取りに失敗しました。'];
    }

    $originalName = sanitizeFileName((string) ($file['name'] ?? ''));
    if ($originalName === '') {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'のファイル名が取得できませんでした。'];
    }

    $size = isset($file['size']) ? (int) $file['size'] : 0;
    if ($size <= 0) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'が空のファイルです。'];
    }

    if ($size > $maxBytes) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'は' . formatBytes($maxBytes) . '以下で添付してください。'];
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'の形式が許可されていません。'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpName);
    $allowedMimeList = $allowedMimeMap[$extension] ?? [];

    if ($mime === '' || !in_array($mime, $allowedMimeList, true)) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'の形式が正しくありません。'];
    }

    $tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'form_upload_mail_temp';
    if (!is_dir($tempDir) && !mkdir($tempDir, 0700, true) && !is_dir($tempDir)) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'の一時保存先を作成できませんでした。'];
    }

    $storedPath = $tempDir . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.' . $extension;

    if (!move_uploaded_file($tmpName, $storedPath)) {
        return ['ok' => false, 'meta' => $emptyMeta, 'error' => $label . 'の一時保存に失敗しました。'];
    }

    return [
        'ok' => true,
        'meta' => [
            'originalName' => $originalName,
            'storedPath'   => $storedPath,
            'mime'         => $mime,
            'size'         => $size,
        ],
        'error' => '',
    ];
}

function sanitizeFileName(string $fileName): string
{
    $fileName = basename($fileName);
    $fileName = str_replace(["\0", "\r", "\n"], '', $fileName);
    return trim($fileName);
}

function uploadErrorMessage(string $label, int $errorCode): string
{
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return $label . 'はアップロード上限を超えています。';
        case UPLOAD_ERR_PARTIAL:
            return $label . 'のアップロードが途中で中断されました。';
        case UPLOAD_ERR_NO_TMP_DIR:
            return $label . 'の一時保存先が見つかりません。';
        case UPLOAD_ERR_CANT_WRITE:
            return $label . 'の保存に失敗しました。';
        case UPLOAD_ERR_EXTENSION:
            return $label . 'のアップロードがサーバー設定により中断されました。';
        default:
            return $label . 'のアップロードに失敗しました。';
    }
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1) . 'MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . 'KB';
    }

    return $bytes . 'B';
}

function cleanupAttachmentFile(string $filePath): void
{
    if ($filePath !== '' && is_file($filePath)) {
        @unlink($filePath);
    }
}

function buildBaseHeaders(string $fromEmail, string $fromName, string $replyTo = ''): array
{
    $safeFromEmail = filter_var($fromEmail, FILTER_VALIDATE_EMAIL) ? $fromEmail : 'no-reply@example.com';
    $safeFromName = mb_encode_mimeheader(trim(str_replace(["\r", "\n"], '', $fromName)), 'UTF-8');
    $headers = [];

    $headers[] = 'From: ' . $safeFromName . ' <' . $safeFromEmail . '>';

    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    $headers[] = 'MIME-Version: 1.0';

    return $headers;
}

function buildMailHeaders(string $fromEmail, string $fromName, string $replyTo = ''): string
{
    $headers = buildBaseHeaders($fromEmail, $fromName, $replyTo);
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';

    return implode("\n", $headers);
}

function buildMultipartHeaders(string $fromEmail, string $fromName, string $replyTo, string $boundary): string
{
    $headers = buildBaseHeaders($fromEmail, $fromName, $replyTo);
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    return implode("\n", $headers);
}

function sendMail(
    string $to,
    string $subject,
    string $message,
    string $headers,
    bool $useEnvelopeSender,
    string $envelopeSender
): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if ($useEnvelopeSender && filter_var($envelopeSender, FILTER_VALIDATE_EMAIL)) {
        return mb_send_mail($to, $subject, $message, $headers, '-f' . $envelopeSender);
    }

    return mb_send_mail($to, $subject, $message, $headers);
}

function sendMailWithAttachment(
    string $to,
    string $subject,
    string $message,
    string $fromEmail,
    string $fromName,
    string $replyTo,
    bool $useEnvelopeSender,
    string $envelopeSender,
    string $attachmentPath,
    string $attachmentName,
    string $attachmentMime
): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if ($attachmentPath === '' || !is_file($attachmentPath) || !is_readable($attachmentPath)) {
        return false;
    }

    $binary = file_get_contents($attachmentPath);
    if ($binary === false) {
        return false;
    }

    $boundary = '----=_Part_' . bin2hex(random_bytes(12));
    $headers = buildMultipartHeaders($fromEmail, $fromName, $replyTo, $boundary);

    $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', $attachmentName);
    if ($fallbackName === null || $fallbackName === '') {
        $fallbackName = 'attachment';
    }
    $encodedName = rawurlencode($attachmentName);

    $body = '';
    $body .= '--' . $boundary . "\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\n";
    $body .= "Content-Transfer-Encoding: base64\n\n";
    $body .= chunk_split(base64_encode($message)) . "\n";

    $body .= '--' . $boundary . "\n";
    $body .= 'Content-Type: ' . $attachmentMime . '; name="' . $fallbackName . "\"\n";
    $body .= "Content-Transfer-Encoding: base64\n";
    $body .= 'Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . $encodedName . "\n\n";
    $body .= chunk_split(base64_encode($binary)) . "\n";
    $body .= '--' . $boundary . "--\n";

    return sendMail($to, $subject, $body, $headers, $useEnvelopeSender, $envelopeSender);
}
