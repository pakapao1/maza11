<?php
require_once __DIR__ . '/auth.php';

// Same ordering as the viewer: Original (*_R.xml), then Revised (*_R2020.xml) by year, then Current.
const VERSION_ORDER_SQL = "FIELD(v.version_kind, 'Original', 'Revised', 'Current'), v.version_year, v.file_name";

function version_kind(string $fileName): string
{
    $n = strtolower($fileName);
    if (str_ends_with($n, '_r.xml')) return 'Original';
    if (str_contains($n, '_r') && preg_match('/\d{4}/', $n)) return 'Revised';
    return 'Current';
}

function version_year(string $fileName, string $title): ?int
{
    if (preg_match('/R(\d{4})/i', $fileName, $m) || preg_match('/R(\d{4})/i', $title, $m)) return (int) $m[1];
    if (preg_match('/\d{4}/', $title, $m)) return (int) $m[0];
    return null;
}

function clean_text(?string $s): string
{
    return trim(preg_replace('/\s+/u', ' ', (string) $s));
}

/**
 * Validates one uploaded XML file and saves it as a version of its Act
 * (the Act is created on first upload). A file with the same name in the same Act is replaced.
 * Returns a message describing what happened; throws RuntimeException on a rejected file.
 */
function import_act_file(string $tmpPath, string $fileName, int $userId): string
{
    $fileName = basename($fileName);
    if (!preg_match('/\.xml$/i', $fileName)) throw new RuntimeException("$fileName: only .xml files are accepted.");
    $size = filesize($tmpPath);
    if ($size === false || $size === 0) throw new RuntimeException("$fileName: the file is empty.");
    if ($size > MAX_UPLOAD_BYTES) throw new RuntimeException("$fileName: larger than " . (MAX_UPLOAD_BYTES / 1048576) . ' MB.');

    // Parse without network access or entity expansion.
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $doc->loadXML(file_get_contents($tmpPath), LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
    $error = libxml_get_errors()[0] ?? null;
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) {
        $where = $error ? " (line {$error->line}: " . trim($error->message) . ')' : '';
        throw new RuntimeException("$fileName: not valid XML$where.");
    }

    $number = strtoupper(clean_text($doc->getElementsByTagName('NUMBER')->item(0)?->textContent));
    if ($number === '') throw new RuntimeException("$fileName: no <NUMBER> element, so the Act cannot be identified.");
    $title = clean_text($doc->getElementsByTagName('TITLE')->item(0)?->textContent) ?: $fileName;

    $english = $doc->getElementsByTagName('ENG_LANG')->item(0);
    $malay = $doc->getElementsByTagName('MALAY_LANG')->item(0);
    $root = $english ?? $malay ?? $doc->documentElement;
    $sections = $root->getElementsByTagName('SECTION')->length + $root->getElementsByTagName('SEKSYEN')->length;

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id FROM acts WHERE act_number = ?');
        $stmt->execute([$number]);
        $actId = $stmt->fetchColumn();
        if (!$actId) {
            $pdo->prepare('INSERT INTO acts (act_number, title, created_by) VALUES (?, ?, ?)')->execute([$number, mb_substr($title, 0, 255), $userId]);
            $actId = (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare('SELECT id, stored_name FROM act_versions WHERE act_id = ? AND file_name = ?');
        $stmt->execute([$actId, $fileName]);
        $existing = $stmt->fetch();
        $storedName = $existing['stored_name'] ?? bin2hex(random_bytes(12)) . '.xml';

        $dir = STORAGE_DIR . '/' . $actId;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new RuntimeException('Could not create the storage folder.');
        if (!copy($tmpPath, "$dir/$storedName")) throw new RuntimeException("$fileName: could not be saved to the storage folder.");

        $values = [version_kind($fileName), version_year($fileName, $title), mb_substr($title, 0, 255),
            $english ? 1 : 0, $malay ? 1 : 0, $sections, $size, $userId];
        if ($existing) {
            $pdo->prepare('UPDATE act_versions SET version_kind = ?, version_year = ?, title = ?, has_english = ?, has_malay = ?,
                section_count = ?, file_size = ?, uploaded_by = ?, uploaded_at = NOW() WHERE id = ?')
                ->execute([...$values, $existing['id']]);
        } else {
            $pdo->prepare('INSERT INTO act_versions (version_kind, version_year, title, has_english, has_malay, section_count,
                file_size, uploaded_by, act_id, file_name, stored_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([...$values, $actId, $fileName, $storedName]);
        }
        refresh_act_title((int) $actId);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }

    log_activity('upload', $fileName, (int) $actId);
    return ($existing ? 'Replaced ' : 'Added ') . "$fileName to $number.";
}

// The Act's title follows its latest version.
function refresh_act_title(int $actId): void
{
    $stmt = db()->prepare('SELECT v.title FROM act_versions v WHERE v.act_id = ? ORDER BY ' . VERSION_ORDER_SQL . ' DESC LIMIT 1');
    $stmt->execute([$actId]);
    $title = $stmt->fetchColumn();
    if ($title) db()->prepare('UPDATE acts SET title = ? WHERE id = ?')->execute([$title, $actId]);
}

function delete_version(int $versionId): ?array
{
    $stmt = db()->prepare('SELECT v.*, a.act_number FROM act_versions v JOIN acts a ON a.id = v.act_id WHERE v.id = ?');
    $stmt->execute([$versionId]);
    $version = $stmt->fetch();
    if (!$version) return null;

    db()->prepare('DELETE FROM act_versions WHERE id = ?')->execute([$versionId]);
    @unlink(STORAGE_DIR . '/' . $version['act_id'] . '/' . $version['stored_name']);
    log_activity('delete_version', $version['file_name'], (int) $version['act_id']);

    $left = db()->prepare('SELECT COUNT(*) FROM act_versions WHERE act_id = ?');
    $left->execute([$version['act_id']]);
    if ((int) $left->fetchColumn() === 0) delete_act((int) $version['act_id']);
    else refresh_act_title((int) $version['act_id']);
    return $version;
}

function delete_act(int $actId): ?array
{
    $stmt = db()->prepare('SELECT * FROM acts WHERE id = ?');
    $stmt->execute([$actId]);
    $act = $stmt->fetch();
    if (!$act) return null;

    $dir = STORAGE_DIR . '/' . $actId;
    foreach (glob("$dir/*.xml") ?: [] as $file) @unlink($file);
    @rmdir($dir);
    db()->prepare('DELETE FROM acts WHERE id = ?')->execute([$actId]); // versions cascade
    log_activity('delete_act', $act['act_number'] . ' ' . $act['title']);
    return $act;
}

// Normalises PHP's multi-file upload array into a list of [tmp_name, name, error].
function uploaded_files(string $field): array
{
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'])) return [];
    $list = [];
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $list[] = ['tmp' => $files['tmp_name'][$i], 'name' => $name, 'error' => $files['error'][$i]];
    }
    return $list;
}
